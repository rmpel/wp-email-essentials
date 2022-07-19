<?php
/**
 * Handles mail log.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

class History {
	public static function getInstance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}

	public function __construct() {
		self::init();
	}

	private static function last_insert( $set = null ) {
		static $id;
		if ( $set ) {
			$id = $set;
		}

		return $id;
	}

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

			add_action( 'phpmailer_init', array( History::class, 'phpmailer_init' ), 10000000000 );
			add_filter( 'wp_mail', array( History::class, 'wp_mail' ), 10000000000 );
			add_action( 'wp_mail_failed', array( History::class, 'wp_mail_failed' ), 10000000000 );

			add_action( 'shutdown', array( History::class, 'shutdown' ) );

			add_action( 'admin_menu', array( History::class, 'admin_menu' ) );
		} else {
			if ( get_option( 'wpes_hist_rev', 0 ) ) {
				$wpdb->query( "DROP TABLE `{$wpdb->prefix}wpes_hist`;" );
				delete_option( 'wpes_hist_rev' );
			}
		}

		add_action(
			'init',
			function () {
				global $wpdb;
				if ( current_user_can( 'manage_options' ) && isset( $_GET['download_eml'] ) ) {
					$eml = $wpdb->get_var( $wpdb->prepare( "SELECT eml FROM {$wpdb->prefix}wpes_hist WHERE id = %d LIMIT 1", $_GET['download_eml'] ) );
					if ( $eml ) {
						header( 'Content-Type: message/rfc822' );
						header( 'Content-Disposition: inline; filename=message.eml' );
						header( 'Content-Length: ' . strlen( $eml ) );
						print $eml;
						exit;
					}
				}
			}
		);
	}

	public static function shutdown() {
		global $wpdb;
		$wpdb->query( "DELETE FROM `{$wpdb->prefix}wpes_hist` WHERE thedatetime <  NOW() - INTERVAL 1 MONTH" );
	}

	public static function admin_menu() {
		add_submenu_page(
			'wp-email-essentials',
			'WP-Email-Essentials - Email History',
			'Email History',
			'manage_options',
			'wpes-emails',
			array(
				History::class,
				'admin_interface',
			)
		);
	}

	public static function admin_interface() {
		include __DIR__ . '/../admin-emails.php';
	}

	public static function get_to_addresses( $phpmailer ) {
		if ( method_exists( $phpmailer, 'getToAddresses' ) ) {
			return $phpmailer->getToAddresses();
		}

		// this version of PHPMailer does not have getToAddresses and To is protected. Use a dump to get the data we need.
		$mailer_data = self::object_data( $phpmailer );

		return $mailer_data->to;
	}

	private static function object_data( $object ) {
		ob_start();
		$class = get_class( $object );
		print_r( $object );
		$object = ob_get_clean();
		$object = str_replace( $class . ' Object', 'Array', $object );
		$object = str_replace( ':protected]', ']', $object );
		$object = self::print_r_reverse( $object );
		$object = json_decode( json_encode( $object ) );

		return $object;
	}

	private static function print_r_reverse( $in ) {
		$lines = explode( "\n", trim( $in ) );
		if ( trim( $lines[0] ) != 'Array' ) {
			// bottomed out to something that isn't an array
			return $in;
		} else {
			// this is an array, lets parse it
			if ( preg_match( '/(\s{5,})\(/', $lines[1], $match ) ) {
				// this is a tested array/recursive call to this function
				// take a set of spaces off the beginning
				$spaces        = $match[1];
				$spaces_length = strlen( $spaces );
				$lines_total   = count( $lines );
				for ( $i = 0; $i < $lines_total; $i ++ ) {
					if ( substr( $lines[ $i ], 0, $spaces_length ) == $spaces ) {
						$lines[ $i ] = substr( $lines[ $i ], $spaces_length );
					}
				}
			}
			array_shift( $lines ); // Array
			array_shift( $lines ); // (
			array_pop( $lines ); // )
			$in = implode( "\n", $lines );
			// make sure we only match stuff with 4 preceding spaces (stuff for this array and not a nested one)
			preg_match_all( '/^\s{4}\[(.+?)\] \=\> /m', $in, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER );
			$pos          = array();
			$previous_key = '';
			$in_length    = strlen( $in );
			// store the following in $pos:
			// array with key = key of the parsed array's item
			// value = array(start position in $in, $end position in $in)
			foreach ( $matches as $match ) {
				$key         = $match[1][0];
				$start       = $match[0][1] + strlen( $match[0][0] );
				$pos[ $key ] = array( $start, $in_length );
				if ( $previous_key != '' ) {
					$pos[ $previous_key ][1] = $match[0][1] - 1;
				}
				$previous_key = $key;
			}
			$ret = array();
			foreach ( $pos as $key => $where ) {
				// recursively see if the parsed out value is an array too
				$ret[ $key ] = self::print_r_reverse( substr( $in, $where[0], $where[1] - $where[0] ) );
			}

			return $ret;
		}
	}


	public static function phpmailer_init( $phpmailer ) {
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
		$data = json_encode( $data, JSON_PRETTY_PRINT );

		$phpmailer->PreSend();
		$eml = $phpmailer->GetSentMIMEMessage();

		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = 1, sender = %s, alt_body = %s, debug = %s, eml = %s WHERE ID = %d AND subject = %s LIMIT 1", $sender, $phpmailer->AltBody, $data, $eml, self::last_insert(), $phpmailer->Subject ) );
	}


	public static function wp_mail( $data ) {
		global $wpdb;
		// fallback values
		$to      = $subject = $message = $from = '';
		$headers = $attachments = array();

		/** @var String $to the addressee */
		/** @var String $subject the subject */
		/** @var String $message the message */
		/** @var array|String $headers the headers */
		/** @var array $attachments the attachments */
		extract( $data );

		if ( ! is_array( $headers ) ) {
			$headers = explode( "\n", $headers );
		}

		$headers = array_map( 'trim', $headers );

		foreach ( $headers as $header ) {
			if ( preg_match( '/^[Ff][Rr][Oo][Mm]:(.+)$/', $header, $m ) ) {
				$from = trim( $m[1] );
			}
		}
		$_headers = trim( implode( "\n", $headers ) );

		$ip = Queue::server_remote_addr();
		$wpdb->query( $wpdb->prepare( "INSERT INTO `{$wpdb->prefix}wpes_hist` (status, sender, recipient, subject, headers, body, thedatetime, ip) VALUES (0, %s, %s, %s, %s, %s, %s, %s);", $from, is_array( $to ) ? implode( ',', $to ) : $to, $subject, $_headers, $message, date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ), $ip ) );
		self::last_insert( $wpdb->insert_id );

		return $data;
	}

	public static function wp_mail_failed( $error ) {
		global $wpdb;
		$data     = $error->get_error_data();
		$errormsg = $error->get_error_message();
		if ( ! $data ) {
			$errormsg = 'Unknown error';
		}
		// 'to', 'subject', 'message', 'headers', 'attachments'
		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = 2, errinfo = CONCAT(%s, errinfo) WHERE ID = %d LIMIT 1", $errormsg . "\n", self::last_insert() ) );
	}


}
