<?php
/**
 * Handles mail queueing.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

/**
 * The Queue class.
 */
class Queue {
	const FRESH   = 0;
	const SENDING = 1;
	const SENT    = 2;
	const STALE   = 3;
	const BLOCK   = 9;

	/**
	 * Get singleton instance.
	 *
	 * @return Queue
	 */
	public static function instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$schema = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wpes_queue (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `dt` datetime NOT NULL,
  `ip` varchar(256) NOT NULL DEFAULT '',
  `to` mediumtext NOT NULL,
  `subject` varchar(256) NOT NULL DEFAULT '',
  `message` mediumtext NOT NULL,
  `headers` mediumtext NOT NULL,
  `attachments` longtext NOT NULL,
  `status` INT(2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `dt` (`dt`),
  KEY `ip` (`ip`(255))
) DEFAULT CHARSET=utf8mb4;";
		$hash   = md5( $schema );
		if ( get_option( 'wpes_queue_rev' ) !== $hash ) {
			require_once ABSPATH . '/wp-admin/includes/upgrade.php';
			dbDelta( $schema );

			update_option( 'wpes_queue_rev', $hash );
		}

		$enabled = Plugin::get_config();
		$enabled = $enabled['enable_queue'];

		// Only queue more if enabled.

		if ( $enabled ) {
			// queue handler.
			add_filter( 'wp_mail', [ self::class, 'wp_mail' ], PHP_INT_MAX - 2000 );

			// queue display.
			add_action( 'admin_menu', [ self::class, 'admin_menu' ] );
		}

		// But always try to send a batch, in case the Queue was deactivated recently.

		// maybe send a batch.
		add_action( 'wp_footer', [ self::class, 'maybe_send_batch' ] );
		add_action( 'admin_footer', [ self::class, 'maybe_send_batch' ] );
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			add_action( 'init', [ self::class, 'maybe_send_batch' ] );
		}
	}

	/**
	 * Implementation of filter wp_mail .
	 *
	 * @param array $mail_data The mail data.
	 *
	 * @return array
	 */
	public static function wp_mail( $mail_data ) {
		/**
		 * Array elements in $mail_data: .
		 *
		 * @var array $mail_data Has elements 'to', 'subject', 'message', 'headers', 'attachments'.
		 */
		if ( [] === $mail_data ) {
			return $mail_data;
		}
		if ( ! $mail_data['to'] ) {
			return $mail_data;
		}
		if ( ! $mail_data['subject'] ) {
			return $mail_data;
		}
		if ( ! $mail_data['message'] ) {
			return $mail_data;
		}

		$me = self::instance();

		global $wpdb;

		$priority = self::get_mail_priority( $mail_data );

		$skip_queue = $me->set_skip_queue();

		if ( defined( 'WP_EMAIL_ESSENTIALS_QUEUE_BYPASS' ) && true === WP_EMAIL_ESSENTIALS_QUEUE_BYPASS ) {
			$skip_queue = true;
		}
		if ( 1 === (int) $priority ) {
			$skip_queue = true;
		}
		$throttle = false;

		if ( self::throttle() ) {
			$skip_queue = false;
			$throttle   = true;
		}

		if ( ! $skip_queue ) {
			$queue_item                = $mail_data;
			$queue_item['attachments'] = self::instance()->get_attachment_data( $queue_item );

			$queue_item = array_map( 'serialize', $queue_item );

			$queue_item = array_merge(
				[
					'dt'     => gmdate( 'Y-m-d H:i:s' ),
					'ip'     => self::server_remote_addr(),
					'status' => $throttle ? self::BLOCK : self::FRESH,
				],
				$queue_item
			);

			$wpdb->insert(
				"{$wpdb->prefix}wpes_queue",
				$queue_item,
				[
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
					'%s',
				]
			);

			// do not send mail, but to prevent errors, keep the array in same.
			add_action( 'phpmailer_init', [ self::class, 'stop_mail' ], PHP_INT_MIN );
		}

		return $mail_data;
	}

	/**
	 * Memory cell: skip-queue.
	 *
	 * @param bool $_state Set the state to ...
	 *
	 * @return mixed
	 */
	private function set_skip_queue( $_state = null ) {
		static $state;
		if ( null !== $_state ) {
			$state = $_state;
		}

		return $state;
	}

	/**
	 * Are we throttling? .
	 *
	 * @return bool
	 */
	private static function throttle() {
		$me = self::instance();
		global $wpdb;
		$ip = $me->server_remote_addr();

		$q                   = $wpdb->prepare( "SELECT count(id) FROM {$wpdb->prefix}wpes_queue WHERE ip = %s AND dt >= %s", $ip, gmdate( 'Y-m-d H:i:s', time() - self::get_time_window() ) );
		$mails_recently_sent = $wpdb->get_var( $q ); // @phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( $mails_recently_sent > self::get_max_count_per_time_window() ) {
			return apply_filters( 'wpes_mail_is_throttled', true, $ip, $mails_recently_sent );
		}

		return false;
	}

	/**
	 * Get the time window to count the number of sent e-mails in. A larger window makes a stricter system.
	 *
	 * @return mixed|null
	 */
	public static function get_time_window() {
		$window = (int) apply_filters( 'wpes_mail_throttle_time_window', 5 );

		return $window > 0 ? $window : 5;
	}

	/**
	 * Get the max amount of e-mails allowed to be sent within the time window (from the same IP). A smaller amount makes a stricter system.
	 *
	 * @return int
	 */
	public static function get_max_count_per_time_window() {
		$max = (int) apply_filters( 'wpes_mail_throttle_max_count_per_time_window', 10 );

		return $max > 0 ? $max : 10;
	}

	/**
	 * Get the amount of e-mails sent in a single batch.
	 *
	 * @return int
	 */
	public static function get_batch_size() {
		$max = (int) apply_filters( 'wpes_mail_throttle_batch_size', 25 );

		return $max > 0 ? $max : 25;
	}

	/**
	 * Get the mail priority.
	 *
	 * @param array $mail_array A wp_mail data array.
	 *
	 * @return int
	 */
	public static function get_mail_priority( $mail_array ) {
		$headers = self::processed_mail_headers( $mail_array['headers'] );

		$prio_fields = [ 'x-priority', 'x-msmail-priority', 'importance' ];
		foreach ( $prio_fields as $field ) {
			if ( isset( $headers[ $field ] ) ) {
				$value = strtolower( $headers[ $field ] );
				$prio  = strtr(
					$value,
					[
						'high'   => 1,
						'normal' => 3,
						'low'    => 5,
					]
				);
				$prio  = (int) $prio;
				if ( 0 !== $prio ) {
					return $prio;
				}
			}
		}

		return 3;
	}

	/**
	 * Get the mail headers.
	 *
	 * @param array|string $headers Raw headers.
	 *
	 * @return array Processed headers.
	 */
	public static function processed_mail_headers( $headers ) {
		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", str_replace( "\r", "\n", $headers ) );
			$headers = array_filter( $headers );
		}
		$headers_assoc = [];
		foreach ( $headers as $_key => $value ) {
			if ( is_numeric( $_key ) ) {
				[ $key, $value ] = explode( ':', $value, 2 );
				if ( '' === $value ) {
					$headers_assoc[] = $key;
				} else {
					$headers_assoc[ $key ] = $value;
				}
			} else {
				$headers_assoc[ $_key ] = $value;
			}
		}

		return array_combine( array_map( 'strtolower', array_keys( $headers_assoc ) ), array_values( $headers_assoc ) );
	}

	/**
	 * Get the real remote address.
	 *
	 * @param bool $return_htaccess_variable Return the variable used (true) or the value thereof (false).
	 *
	 * @return string
	 */
	public static function server_remote_addr( $return_htaccess_variable = false ) {
		$possibilities = [
			'HTTP_CF_CONNECTING_IP' => 'HTTP:CF-CONNECTING-IP',
			'HTTP_X_FORWARDED_FOR'  => 'HTTP:X-FORWARDED-FOR',
			'REMOTE_ADDR'           => false,
		];
		foreach ( $possibilities as $option => $htaccess_variable ) {
			if ( isset( $_SERVER[ $option ] ) && trim( $_SERVER[ $option ] ) ) {
				$ip = explode( ',', $_SERVER[ $option ] );

				return $return_htaccess_variable ? $htaccess_variable : end( $ip );
			}
		}

		return $_SERVER['REMOTE_ADDR'];
	}

	/**
	 * Get database-ready attachments.
	 *
	 * @param array $mail_data A wp_mail data array.
	 */
	public function get_attachment_data( $mail_data ) {
		if ( ! $mail_data['attachments'] ) {
			$mail_data['attachments'] = [];
		}
		if ( ! is_array( $mail_data['attachments'] ) ) {
			$mail_data['attachments'] = [ $mail_data['attachments'] ];
		}
		$mail_data['attachments'] = array_combine( array_map( 'basename', $mail_data['attachments'] ), $mail_data['attachments'] );
		foreach ( $mail_data['attachments'] as $filename => $path ) {
			// @phpcs:disable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			// @phpcs:disable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$mail_data['attachments'][ $filename ] = base64_encode( file_get_contents( $path ) );
			// @phpcs:enable WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			// @phpcs:enable WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}

		return $mail_data['attachments'];
	}

	/**
	 * Restore attachment data for sending with wp_mail.
	 *
	 * @param array $mail_data A wp_mail data array.
	 */
	public function restore_attachment_data( $mail_data ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		$tmp = wp_upload_dir();
		$tmp = $tmp['basedir'];
		$tmp = "$tmp/mail_queue_atts";
		if ( ! is_dir( $tmp ) ) {
			mkdir( $tmp );
			if ( ! is_writable( ABSPATH . '/wp-load.php' ) ) {
				chmod( $tmp, 0777 );
			}
		}
		$tmp .= '/' . $this->mail_token();
		if ( ! is_dir( $tmp ) ) {
			mkdir( $tmp );
			if ( ! is_writable( ABSPATH . '/wp-load.php' ) ) {
				chmod( $tmp, 0777 );
			}
		}
		foreach ( $mail_data['attachments'] as $filename => $data ) {
			// @phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			$data = base64_decode( $data );
			if ( ! is_file( $data ) ) {
				// @phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
				file_put_contents( "$tmp/$filename", $data );
				$data = "$tmp/$filename";
			}
			$mail_data['attachments'][ $filename ] = $data;
		}
		$mail_data['attachments'] = array_values( $mail_data['attachments'] );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return $mail_data['attachments'];
	}

	/**
	 * Generate a mail token (and keep using it until call-end).
	 *
	 * @return string
	 */
	private function mail_token() {
		static $token;
		if ( ! $token ) {
			$token = md5( microtime( true ) . $_SERVER['REMOTE_ADDR'] . wp_rand( 0, PHP_INT_MAX ) );
		}

		return $token;
	}

	/**
	 * Scheduled task.
	 */
	public static function scheduled_task() {
		self::send_batch();
	}

	/**
	 * Send a single queued email.
	 */
	public static function send_one_email() {
		global $wpdb;
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpes_queue WHERE status = %d ORDER BY dt ASC", self::FRESH ) );
		self::send_now( $id );
	}

	/**
	 * Implementation of wp action wp_footer .
	 */
	public static function maybe_send_batch() {
		$last = get_option( 'last_batch_sent', '0' );
		$now  = gmdate( 'YmdHi' );
		if ( $last < $now ) {
			update_option( 'last_batch_sent', $now );
			self::send_batch();
		}
	}

	/**
	 * Send a batch of emails.
	 */
	public static function send_batch() {
		$me = self::instance();

		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}wpes_queue WHERE status = %d ORDER BY dt ASC LIMIT %d", self::FRESH, self::get_batch_size() ) );
		foreach ( $ids as $id ) {
			self::send_now( $id );
		}
	}

	/**
	 * Send a specific email now.
	 *
	 * @param int $id The id of the item to send.
	 */
	public static function send_now( $id ) {
		global $wpdb;
		$mail_data = $wpdb->get_row( $wpdb->prepare( "SELECT `to`, `subject`, `message`, `headers`, `attachments` FROM {$wpdb->prefix}wpes_queue WHERE id = %d", $id ), ARRAY_A );
		self::instance()->set_skip_queue( true );
		$mail_data = array_map( 'unserialize', $mail_data );
		self::set_status( $id, self::SENDING );

		$result = wp_mail( $mail_data['to'], $mail_data['subject'], $mail_data['message'], $mail_data['headers'], self::instance()->restore_attachment_data( $mail_data ) );
		self::instance()->set_skip_queue( false );
		if ( $result ) {
			self::set_status( $id, self::SENT );
		} else {
			self::set_status( $id, self::STALE );
		}
	}

	/**
	 * Set the status of a queue item.
	 *
	 * @param int $mail_id The queued item ID.
	 */
	public static function get_status( $mail_id ) {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}wpes_queue", [ 'status' => $status ], [ 'id' => $mail_id ] );
	}

	/**
	 * Set the status of a queue item.
	 *
	 * @param int $mail_id The queued item ID.
	 * @param int $status  The status to compare to.
	 */
	public static function is_status( $mail_id, $status ) {
		return self::get_status( $mail_id ) === $status;
	}

	/**
	 * Set the status of a queue item.
	 *
	 * @param int $mail_id The queued item ID.
	 * @param int $status  The new status.
	 */
	private static function set_status( $mail_id, $status ) {
		global $wpdb;
		$wpdb->update( "{$wpdb->prefix}wpes_queue", [ 'status' => $status ], [ 'id' => $mail_id ] );
	}

	/**
	 * When needed, this callback overwrites the passed phpMailer object with a non-sending one.
	 *
	 * @param WPES_PHPMailer $phpmailer The mailer object.
	 */
	public static function stop_mail( &$phpmailer ) {
		remove_action( 'phpmailer_init', [ self::class, 'stop_mail' ], PHP_INT_MIN );
		$phpmailer = new Fake_Sender();
	}

	/**
	 * Callback for admin_menu action.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'wp-email-essentials',
			Plugin::plugin_data()['Name'] . ' - ' . __( 'E-mail Throttling', 'wpes' ),
			__( 'E-mail Throttling', 'wpes' ),
			'manage_options',
			'wpes-queue',
			[ self::class, 'admin_interface' ]
		);
	}

	/**
	 * The admin interface.
	 */
	public static function admin_interface() {
		Plugin::view( 'admin-queue' );
	}
}
