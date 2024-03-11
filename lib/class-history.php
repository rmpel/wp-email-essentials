<?php
/**
 * Handles mail log.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

/**
 * The mail history / log class.
 */
class History {
	const MAIL_NEW    = 0;
	const MAIL_SENT   = 1;
	const MAIL_FAILED = 2;
	const MAIL_OPENED = 3;

	/**
	 * Get the singleton instance.
	 *
	 * @return History
	 */
	public static function instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * Memory cell: remember the last inserted ID.
	 *
	 * @param null|int $set The content for the cell.
	 *
	 * @return null|int
	 */
	private static function last_insert( $set = null ) {
		static $id;
		if ( $set ) {
			$id = $set;
		}

		return $id;
	}

	/**
	 * Main code.
	 */
	private static function init() {
		global $wpdb;
		$enabled = Plugin::get_config();
		$enabled = $enabled['enable_history'];

		if ( $enabled ) {
			$schema = "CREATE TABLE `{$wpdb->prefix}wpes_hist` (
			  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `sender` varchar(256) NOT NULL DEFAULT '',
			  `ip` varchar(128) NOT NULL DEFAULT '',
			  `recipient` varchar(256) NOT NULL DEFAULT '',
			  `subject` varchar(256) NOT NULL DEFAULT '',
			  `headers` text NOT NULL,
			  `body` text NOT NULL,
			  `alt_body` text NOT NULL,
			  `eml` LONGTEXT NOT NULL,
			  `thedatetime` datetime NOT NULL,
			  `status` int(11) NOT NULL,
			  `errinfo` text NOT NULL,
			  `debug` text NOT NULL,
			  PRIMARY KEY (`ID`),
			  KEY `sender` (`sender`(255)),
			  KEY `recipient` (`recipient`(255)),
			  KEY `subject` (`subject`(255)),
			  KEY `thedatetime` (`thedatetime`),
			  KEY `status` (`status`)
			) DEFAULT CHARSET=utf8mb4";
			$hash   = md5( $schema );
			if ( get_option( 'wpes_hist_rev' ) !== $hash ) {
				require_once ABSPATH . '/wp-admin/includes/upgrade.php';
				dbDelta( $schema );

				update_option( 'wpes_hist_rev', $hash );
			}
			add_action( 'phpmailer_init', [ self::class, 'phpmailer_init' ], 10000000000 );
			add_filter( 'wp_mail', [ self::class, 'wp_mail' ], 10000000000 );
			add_action( 'wp_mail_failed', [ self::class, 'wp_mail_failed' ], 10000000000 );
			add_action( 'pre_handle_404', [ self::class, 'handle_tracker' ], ~PHP_INT_MAX );
			add_action( 'shutdown', [ self::class, 'shutdown' ] );
			add_action( 'admin_menu', [ self::class, 'admin_menu' ] );
		} elseif ( get_option( 'wpes_hist_rev', 0 ) ) {
			$wpdb->query( "DROP TABLE `{$wpdb->prefix}wpes_hist`;" );
			delete_option( 'wpes_hist_rev' );
		}

		add_action(
			'init',
			function () {
				global $wpdb;
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- not a form!.
				if ( current_user_can( 'manage_options' ) && isset( $_GET['download_eml'] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- still not a form!.
					$data = $wpdb->get_row( $wpdb->prepare( "SELECT ID, eml, subject, recipient, thedatetime FROM {$wpdb->prefix}wpes_hist WHERE id = %d LIMIT 1", $_GET['download_eml'] ), ARRAY_A );
					if ( $data['eml'] ?? false ) {
						header( 'Content-Type: message/rfc822' );
						$uniq = sprintf(
							'%1$s-%2$d-%3$s-%4$s',
							sanitize_title( $data['thedatetime'] ),
							(int) $data['ID'],
							sanitize_title(
								strtr(
									$data['recipient'],
									[
										'.' => '-dot-',
										'@' => '-at-',
									]
								)
							),
							sanitize_title( $data['subject'] )
						);
						header( 'Content-Disposition: inline; filename=' . $uniq . '.eml' );
						header( 'Content-Length: ' . strlen( $data['eml'] ) );
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- How do you escape an email? we're DOWNLOADING it.
						print $data['eml'];
						exit;
					}
				}
			}
		);
	}

	/**
	 * Callback for shutdown action. Purge the history older than 1 month.
	 */
	public static function shutdown() {
		global $wpdb;
		$wpdb->query( "DELETE FROM `{$wpdb->prefix}wpes_hist` WHERE thedatetime <  NOW() - INTERVAL 1 MONTH" );
	}

	/**
	 * Callback for admin_menu action.
	 */
	public static function admin_menu() {
		add_submenu_page(
			'wp-email-essentials',
			Plugin::plugin_data()['Name'] . ' - ' . __( 'E-mail History', 'wpes' ),
			__( 'E-mail History', 'wpes' ),
			'manage_options',
			'wpes-emails',
			[ self::class, 'admin_interface' ]
		);
	}

	/**
	 * The admin interface.
	 */
	public static function admin_interface() {
		Plugin::view( 'admin-emails' );
	}

	/**
	 * Retrieve the recipients from the Mailer object.
	 *
	 * @param WPES_PHPMailer $phpmailer The mailer object.
	 *
	 * @return array
	 */
	public static function get_to_addresses( $phpmailer ) {
		if ( method_exists( $phpmailer, 'getToAddresses' ) ) {
			return $phpmailer->getToAddresses();
		}

		// this version of PHPMailer does not have getToAddresses and To is protected. Use a dump to get the data we need.
		$mailer_data = self::object_data( $phpmailer );

		return $mailer_data->to;
	}

	/**
	 * Use print_r to dump the object and extract the data we need.
	 *
	 * @param mixed $the_object Object to inspect.
	 *
	 * @return mixed
	 */
	private static function object_data( $the_object ) {
		ob_start();
		$class = get_class( $the_object );
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- How else are we supposed to get the info we need?.
		print_r( $the_object );
		$the_object = ob_get_clean();
		$the_object = str_replace( $class . ' Object', 'Array', $the_object );
		$the_object = str_replace( ':protected]', ']', $the_object );
		$the_object = self::print_r_reverse( $the_object );

		return json_decode( wp_json_encode( $the_object ) );
	}

	/**
	 * The reverse of print_r; make an object of a dump.
	 *
	 * @param string $in A print_r dump.
	 *
	 * @return mixed
	 */
	private static function print_r_reverse( $in ) {
		$lines = explode( "\n", trim( $in ) );
		if ( 'Array' !== trim( $lines[0] ) ) {
			// bottomed out to something that isn't an array.
			return $in;
		} else {
			// this is an array, lets parse it.
			if ( preg_match( '/(\s{5,})\(/', $lines[1], $match ) ) {
				// this is a tested array/recursive call to this function.
				// take a set of spaces off the beginning.
				$spaces        = $match[1];
				$spaces_length = strlen( $spaces );
				$lines_total   = count( $lines );
				for ( $i = 0; $i < $lines_total; $i++ ) {
					if ( substr( $lines[ $i ], 0, $spaces_length ) === $spaces ) {
						$lines[ $i ] = substr( $lines[ $i ], $spaces_length );
					}
				}
			}
			array_shift( $lines ); // Array.
			array_shift( $lines ); // ( .
			array_pop( $lines ); // ) .
			$in = implode( "\n", $lines );
			// make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one).
			preg_match_all( '/^\s{4}\[(.+?)\] \=\> /m', $in, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );
			$pos          = [];
			$previous_key = '';
			$in_length    = strlen( $in );
			// store the following in $pos:.
			// array with key = key of the parsed array's item.
			// value = array(start position in $in, $end position in $in).
			foreach ( $matches as $match ) {
				$key         = $match[1][0];
				$start       = $match[0][1] + strlen( $match[0][0] );
				$pos[ $key ] = [ $start, $in_length ];
				if ( '' !== $previous_key ) {
					$pos[ $previous_key ][1] = $match[0][1] - 1;
				}
				$previous_key = $key;
			}
			$ret = [];
			foreach ( $pos as $key => $where ) {
				// recursively see if the parsed out value is an array too.
				$ret[ $key ] = self::print_r_reverse( substr( $in, $where[0], $where[1] - $where[0] ) );
			}

			return $ret;
		}
	}

	/**
	 * Callback to action phpmailer_init: Grab the object for debug purposes.
	 *
	 * @param WPES_PHPMailer $phpmailer The PHPMailer object.
	 */
	public static function phpmailer_init( &$phpmailer ) {
		// @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer, folks...
		global $wpdb;
		$data                  = self::object_data( $phpmailer );
		$data->Password        = '********';
		$data->DKIM_passphrase = '********';
		$sender                = $data->From === $data->FromName || ! $data->FromName ? $data->From : sprintf( '%s <%s>', $data->FromName, $data->From );
		$reply_to              = $data->ReplyTo ?? $sender;
		if ( $sender !== $reply_to ) {
			$reply_to       = (array) $data->ReplyTo;
			$reply_to       = reset( $reply_to );
			$reply_to_name  = trim( $reply_to[1] ?? '' );
			$reply_to_email = trim( $reply_to[0] ?? '' );
			$reply_to       = $reply_to_name && $reply_to_name !== $reply_to_email ? sprintf( '%s <%s>', $reply_to_name, $reply_to_email ) : $reply_to_email;

			$sender = $reply_to . ' *';
		}
		$data = wp_json_encode( $data, JSON_PRETTY_PRINT );

		self::add_tracker( $phpmailer->Body, self::last_insert() );

		$phpmailer->PreSend();
		$eml = $phpmailer->GetSentMIMEMessage();

		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = %d, sender = %s, alt_body = %s, debug = %s, eml = %s WHERE ID = %d AND subject = %s LIMIT 1", self::MAIL_SENT, $sender, $phpmailer->AltBody, $data, $eml, self::last_insert(), $phpmailer->Subject ) );
		// @phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- PHPMailer, folks...
	}

	/**
	 * Callback on action wp_mail: Record a mail into the history.
	 *
	 * @param array $data WP Mail data with keys 'to', 'subject', 'message', 'headers' and 'attachments'.
	 *
	 * @return mixed
	 */
	public static function wp_mail( $data ) {
		global $wpdb;
		// fallback values.
		$to          = '';
		$subject     = '';
		$message     = '';
		$from        = '';
		$headers     = [];
		$attachments = [];

		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Deal with it.

		if ( ! is_array( $headers ) ) {
			/**
			 * Headers might be a string...
			 *
			 * @var string|array $headers
			 */
			$headers = explode( "\n", $headers );
		}

		$headers = array_map( 'trim', $headers );

		foreach ( $headers as $header ) {
			if ( preg_match( '/^From:(.+)$/i', $header, $m ) ) {
				$from = trim( $m[1] );
			}
		}
		$_headers = trim( implode( "\n", $headers ) );

		$ip = Queue::server_remote_addr();
		$wpdb->query( $wpdb->prepare( "INSERT INTO `{$wpdb->prefix}wpes_hist` (status, sender, recipient, subject, headers, body, thedatetime, ip) VALUES (%d, %s, %s, %s, %s, %s, %s, %s);", self::MAIL_NEW, $from, is_array( $to ) ? implode( ',', $to ) : $to, $subject, $_headers, $message, gmdate( 'Y-m-d H:i:s', time() ), $ip ) );
		self::last_insert( $wpdb->insert_id );

		return $data;
	}

	/**
	 * Callback on action wp_mail_failed: register the error.
	 *
	 * @param \WP_Error $error The error.
	 */
	public static function wp_mail_failed( $error ) {
		global $wpdb;
		$data     = $error->get_error_data();
		$errormsg = $error->get_error_message();
		if ( ! $data ) {
			$errormsg = 'Unknown error';
		}
		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = %d, errinfo = CONCAT(%s, errinfo) WHERE ID = %d LIMIT 1", self::MAIL_FAILED, $errormsg . "\n", self::last_insert() ) );
	}

	/**
	 * Add a tracker to the outgoing email. This only happens when debugging is enabled, which is not GDPR compliant anyway.
	 *
	 * @param string $message The email.
	 * @param int    $mail_id The mail id.
	 */
	private static function add_tracker( &$message, $mail_id ) {
		$tracker_url = trailingslashit( home_url() ) . 'email-image-' . $mail_id . '.png';

		$tracker = '<img src="' . esc_attr( $tracker_url ) . '" alt="" />';

		$message = false !== strpos( $message, '</body>' ) ? str_replace( '</body>', $tracker . '</body>', $message ) : $message . $tracker;
	}

	/**
	 * Callback for action pre_handle_404: act on the calling of the tracker URL.
	 */
	public static function handle_tracker() {
		global $wpdb;
		if ( preg_match( '/\/email-image-(\d+).png/', $_SERVER['REQUEST_URI'], $match ) ) {
			$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = %s WHERE ID = %d;", self::MAIL_OPENED, $match[1] ) );

			header( 'Content-Type: image/png' );
			header( 'Content-Length: 0' );
			header( 'HTTP/1.1 404 Not Found' );
			exit;
		}

		return false;
	}
}
