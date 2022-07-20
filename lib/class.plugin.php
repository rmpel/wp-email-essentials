<?php
/**
 * The main class.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

/**
 * The main plugin class.
 */
class Plugin {
	/**
	 * Holds a message to show in the admin panel.
	 *
	 * @var string
	 */
	public static $message;

	/**
	 * Holds an error-message to show in the admin panel.
	 *
	 * @var string
	 */
	public static $error;

	/**
	 * Holds debug information.
	 *
	 * @var mixed
	 */
	public static $debug;

	/**
	 * List of supported encodings.
	 */
	const ENCODINGS = 'utf-8,utf-16,utf-32,latin-1,iso-8859-1';

	/**
	 * Constructor.
	 */
	public function __construct() {
		self::init();
	}

	/**
	 * The main initialisation.
	 */
	public static function init() {
		add_action(
			'init',
			function () {
				load_plugin_textdomain( 'wpes', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
			}
		);

		$config = self::get_config();
		add_action( 'phpmailer_init', [ self::class, 'action_phpmailer_init' ] );
		if ( $config['is_html'] ) {
			add_filter(
				'wp_mail_content_type',
				function () {
					return 'text/html';
				}
			);
			add_filter(
				'wp_mail_charset',
				function () {
					return 'UTF-8';
				}
			);
			add_filter(
				'wpcf7_mail_html_header',
				[
					self::class,
					'wpcf7_mail_html_header',
				],
				~PHP_INT_MAX
			);
			add_filter(
				'wpcf7_mail_html_footer',
				[
					self::class,
					'wpcf7_mail_html_footer',
				],
				~PHP_INT_MAX
			);
		}

		// set default from email and from name.
		if ( $config['from_email'] ) {
			self::log( 'Config FromMail: ' . $config['from_email'] );
			add_filter( 'wp_mail_from', [ self::class, 'filter_wp_mail_from' ], 9999 );
		}
		if ( $config['from_name'] ) {
			self::log( 'Config FromName: ' . $config['from_name'] );
			add_filter( 'wp_mail_from_name', [ self::class, 'filter_wp_mail_from_name' ], 9999 );
		}

		add_filter( 'wp_mail', [ self::class, 'action_wp_mail' ], PHP_INT_MAX - 1000 );
		add_action( 'admin_menu', [ self::class, 'admin_menu' ], 10 );

		add_action( 'admin_menu', [ self::class, 'migrate_from_smtp_connect' ], - 10000 );

		add_action( 'admin_footer', [ self::class, 'maybe_inject_admin_settings' ] );

		add_filter( 'cfdb_form_data', [ self::class, 'correct_cfdb_form_data_ip' ] );

		add_filter( 'comment_notification_headers', [ self::class, 'correct_comment_from' ], 11, 2 );

		add_filter(
			'comment_moderation_recipients',
			[
				self::class,
				'correct_moderation_to',
			],
			~PHP_INT_MAX,
			2
		);
		add_filter(
			'comment_notification_recipients',
			[
				self::class,
				'correct_comment_to',
			],
			~PHP_INT_MAX,
			2
		);

		self::mail_key_registrations();
	}

	/**
	 * Get the root path to the website. This is NOT ABSPATH if WordPress is in a subdirectory.
	 *
	 * @return string
	 */
	public static function root_path() {
		static $root_path;

		if ( ! $root_path ) {
			$wp_path_rel_to_home = self::get_wp_subdir();
			if ( $wp_path_rel_to_home ) {
				$pos       = strripos( str_replace( '\\', '/', ABSPATH ), trailingslashit( $wp_path_rel_to_home ) );
				$home_path = substr( ABSPATH, 0, $pos );
				$home_path = trailingslashit( $home_path );
			} else {
				$home_path = ABSPATH;
			}

			$root_path = self::nice_path( $home_path );
		}

		return $root_path;
	}

	/**
	 * Get the subdirectory WordPress is sitting in.
	 *
	 * @return string
	 */
	public static function get_wp_subdir() {
		$home    = preg_replace( '@https?://@', 'http://', get_option( 'home' ) );
		$siteurl = preg_replace( '@https?://@', 'http://', get_option( 'siteurl' ) );

		if ( ! empty( $home ) && 0 !== strcasecmp( $home, $siteurl ) ) {
			return str_ireplace( $home, '', $siteurl ); /* $siteurl - $home */
		}

		return '';
	}

	/**
	 * Cleanup a path a bit.
	 *
	 * @param string $path The path to cleanup.
	 *
	 * @return string
	 */
	public static function nice_path( $path ) {
		// Turn \ into / .
		$path = str_replace( '\\', '/', $path );
		// Remove "current" instances.
		$path = str_replace( '/./', '/', $path );
		// phpcs:ignore Generic.Commenting.Todo.TaskFound
		// @todo: remove  ../somethingotherthandotdot/ .

		return $path;
	}

	/**
	 * Get the default sender email address like WordPress generates them; wordpress@domain.tld .
	 *
	 * @return string
	 */
	public static function get_wordpress_default_emailaddress() {
		$host    = wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
		$host    = preg_replace( '/^www\./', '', $host );
		$wpemail = 'wordpress@' . $host;
		self::log( 'wp-email ' . $wpemail );

		return $wpemail;
	}

	/**
	 * Filter callback for comment email hearders.
	 *
	 * @param string $mail_headers The headers.
	 * @param int    $comment_id   (unused) the Comment ID.
	 *
	 * @return string
	 */
	public static function correct_comment_from( $mail_headers, $comment_id ) {
		$unused = $comment_id;

		$u            = wp_get_current_user();
		$mail_headers = array_map( 'trim', explode( "\n", $mail_headers ) );
		foreach ( $mail_headers as $i => $header ) {
			if ( preg_match( '/^(From|Reply-To):[ \t]*(.+)$/i', $header, $m ) ) {
				$email = $m[2];
				$email = self::rfc_decode( $email );
				if ( $email['email'] === $email['name'] ) {
					$email['name'] = $u->ID ? $u->display_name : ( $email['name'] ?: __( 'anonymous', 'wpes' ) );
				}
				if ( $u->ID && $u->user_login === $email['name'] ) {
					$email['name'] = $u->display_name;
				}
				if ( $u->ID && self::get_wordpress_default_emailaddress() === $email['email'] ) {
					$email['email'] = $u->user_email;
				}
				$mail_headers[ $i ] = $m[1] . ': ' . self::rfc_encode( $email );
			}
		}

		return implode( "\r\n", $mail_headers );
	}

	/**
	 * Fix content in CD72DB plugin.
	 *
	 * @param \WPCF7_ContactForm $cf7 The contact form.
	 *
	 * @return mixed
	 */
	public static function correct_cfdb_form_data_ip( $cf7 ) {
		// CF7 to DB tries variable X_FORWARDED_FOR which is never in use, Apache sets HTTP_X_FORWARDED_FOR
		// use our own method to get the remote_addr.
		$cf7->ip = self::server_remote_addr();

		return $cf7;
	}

	/**
	 * Get remote address, in any form it can be presented.
	 *
	 * @param bool $return_htaccess_variable Return the value (false) or the variable it was found in (true).
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
	 * Implementation of filter  wp_mail .
	 *
	 * @param array $wp_mail Array with wp_mail data. ('to', 'subject', 'message', 'headers', 'attachments') .
	 *
	 * @return array
	 */
	public static function action_wp_mail( $wp_mail ) {
		if ( ! $wp_mail ) {
			return $wp_mail;
		}
		if ( ! $wp_mail['to'] ) {
			return $wp_mail;
		}
		if ( ! $wp_mail['subject'] ) {
			return $wp_mail;
		}
		if ( ! $wp_mail['message'] ) {
			return $wp_mail;
		}

		return self::patch_wp_mail( $wp_mail );
	}

	/**
	 * Change the WP_Mail data array to suit the settings.
	 *
	 * @param array $wp_mail Array with wp_mail data. ('to', 'subject', 'message', 'headers', 'attachments') .
	 *
	 * @return array
	 */
	public static function patch_wp_mail( $wp_mail ) {

		$config = self::get_config();

		self::wp_mail_from( $config['from_email'] );
		self::wp_mail_from_name( $config['from_name'] );

		$all_headers = [];

		if ( is_string( $wp_mail['headers'] ) ) {
			$wp_mail['headers'] = explode( "\n", $wp_mail['headers'] );
		}
		if ( ! is_array( $wp_mail['headers'] ) ) {
			$wp_mail['headers'] = [];
		}

		self::log( __LINE__ . ' raw headers' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		$header_index = [];
		foreach ( $wp_mail['headers'] as $i => $header ) {
			if ( preg_match( '/([^:]+):(.*)$/U', $header, $match ) ) {
				$all_headers[ strtolower( trim( $match[1] ) ) ]  = $match[2];
				$header_index[ strtolower( trim( $match[1] ) ) ] = $i;
			}
		}

		if ( isset( $all_headers['from'] ) ) {
			self::log( __LINE__ . ' headers has FROM: ' . $all_headers['from'] );
			$from = self::rfc_decode( $all_headers['from'] );
			self::log( __LINE__ . ' decoded:' );
			self::log( wp_json_encode( $from ) );
			if ( $from['email'] && self::get_wordpress_default_emailaddress() !== $from['email'] ) {
				self::log( __LINE__ . ' set from mail' );
				self::wp_mail_from( $from['email'] );
			}
			if ( $from['name'] ) {
				self::log( __LINE__ . ' set from name' );
				self::wp_mail_from_name( $from['name'] );
			}
		}

		if ( ! array_key_exists( 'from', $header_index ) ) {
			$header_index['from'] = count( $header_index );
		}
		$wp_mail['headers'][ $header_index['from'] ] = 'From: "' . self::wp_mail_from_name() . '" <' . self::wp_mail_from() . '>';

		self::log( __LINE__ . ' headers now:' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		if ( ! array_key_exists( 'reply-to', $header_index ) ) {
			self::log( __LINE__ . ' Adding REPLY-TO:' );
			$header_index['reply-to']                        = count( $header_index );
			$wp_mail['headers'][ $header_index['reply-to'] ] = 'Reply-To: ' . self::wp_mail_from_name() . ' <' . self::wp_mail_from() . '>';
		} else {
			self::log( __LINE__ . ' Already have REPLY-TO:' );
		}

		self::log( __LINE__ . ' headers now:' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		if ( $config['make_from_valid'] ) {
			self::log( __LINE__ . ' Validifying FROM:' );
			self::wp_mail_from( self::a_valid_from( self::wp_mail_from(), $config['make_from_valid'] ) );
			$wp_mail['headers'][ $header_index['from'] ] = 'From: "' . self::wp_mail_from_name() . '" <' . self::a_valid_from( self::wp_mail_from(), $config['make_from_valid'] ) . '>';
		}

		self::log( __LINE__ . ' headers now:' );
		self::log( wp_json_encode( $wp_mail['headers'] ) );

		return $wp_mail;
	}

	/**
	 * Transform invalid from-address to a valid one.
	 *
	 * @param string|array $invalid_from An invalid from-address.
	 * @param string       $method       The method of validating it.
	 *
	 * @return mixed|string
	 */
	public static function a_valid_from( $invalid_from, $method ) {
		$url    = get_bloginfo( 'url' );
		$host   = wp_parse_url( $url, PHP_URL_HOST );
		$host   = preg_replace( '/^www[0-9]*\./', '', $host );
		$config = self::get_config();

		if ( ! self::i_am_allowed_to_send_in_name_of( $invalid_from ) ) {
			switch ( $method ) {
				case '-at-':
					$translation = [
						'@' => '-at-',
						'.' => '-dot-',
					];

					$return = strtr( $invalid_from, $translation );

					return $return . '@' . $host;
				case 'default':
					$defmail = self::wp_mail_from( $config['from_email'] );
					if ( self::i_am_allowed_to_send_in_name_of( $defmail ) ) {
						return $defmail;
					} // if test fails, bleed through to noreply, so leave this order in tact!
				case 'noreply':
					return 'noreply@' . $host;
				default:
					return $invalid_from;
			}
		}

		return $invalid_from;
	}

	/**
	 * Get the domain of an email address.
	 *
	 * @param string $email The email address.
	 *
	 * @return string
	 */
	public static function get_domain( $email ) {
		if ( preg_match( '/@(.+)$/', $email, $sending_domain ) ) {
			$sending_domain = $sending_domain[1];
		}

		return $sending_domain;
	}

	/**
	 * Get SPF record for the domain of the email given.
	 *
	 * @param string $email   The email address.
	 * @param bool   $fix     If true; give a fixed SPF record.
	 * @param bool   $as_html If true, return as richly formatted HTML.
	 *
	 * @return string
	 */
	public static function get_spf( $email, $fix = false, $as_html = false ) {
		static $lookup;
		if ( ! $lookup ) {
			$lookup = [];
		}

		$sending_domain = self::get_domain( $email );
		if ( ! $sending_domain ) {
			return false; // invalid email.
		}
		$sending_server = self::get_sending_ip();
		// we assume here that everything NOT IP4 is IP6. This will do for now, but ...
		// @phpcs:ignore Generic.Commenting.Todo.TaskFound
		// todo: actual ip6 check!.
		$ip = preg_match( '/^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$/', trim( $sending_server ) ) ? 'ip4' : 'ip6';

		if ( ! isset( $lookup[ $sending_domain ] ) ) {
			$dns = self::dns_get_record( $sending_domain, DNS_TXT );
			foreach ( $dns as $record ) {
				if ( false !== strpos( $record['txt'], 'v=spf1' ) ) {
					$lookup[ $sending_domain ] = $record['txt'];
					break;
				}
			}
		}

		if ( ! isset( $lookup[ $sending_domain ] ) ) {
			$lookup[ $sending_domain ] = '';
		}

		$spf = $lookup[ $sending_domain ];

		if ( $fix ) {
			if ( ! $spf ) {
				$spf = 'v=spf1 a mx ~all';
			}

			// insert.
			$spf      = explode( ' ', str_replace( 'include:', 'include: ', $spf ) );
			$position = false !== array_search( 'mx', $spf, true ) ? array_search( 'mx', $spf, true ) + 1 : false;
			$position = false !== $position ? $position : ( false !== array_search( 'a', $spf, true ) ? array_search( 'a', $spf, true ) + 1 : false );
			$position = false !== $position ? $position : ( false !== array_search( 'include:', $spf, true ) ? array_search( 'include:', $spf, true ) - 1 : false );
			$position = false !== $position ? $position : ( false !== array_search( 'v=spf1', $spf, true ) ? array_search( 'v=spf1', $spf, true ) + 1 : false );

			array_splice( $spf, $position, 0, $ip . ':' . $sending_server );
			$spf = str_replace( 'include: ', 'include:', implode( ' ', $spf ) );
		}

		if ( $as_html ) {
			if ( ! $spf ) {
				$spf = '<span class="error">no spf-record available</span>';
			} else {
				$spf = $sending_domain . '. IN TXT ' . $spf;
			}

			if ( $fix ) {
				$color = 'red';
			} else {
				$color = 'green';
			}
			$spf = str_replace( $ip . ':' . $sending_server, '<strong style="color:' . $color . ';">' . $ip . ':' . $sending_server . '</strong>', $spf );
		}

		return $spf;
	}

	/**
	 * Test: I (this server) is allowed to send in name of givern email address.
	 *
	 * @param string $email The email address to check.
	 *
	 * @return bool
	 */
	public static function i_am_allowed_to_send_in_name_of( $email ) {
		$config = self::get_config();

		if ( 'when_sender_not_as_set' === $config['make_from_valid_when'] ) {
			return $config['from_email'] === $email;
		}

		if ( ! $config['spf_lookup_enabled'] ) {
			// we tried and failed less than a day ago.
			// do not try again.
			return self::this_email_matches_website_domain( $email );
		}

		// try an SPF record.

		$sending_domain = [];
		preg_match( '/@(.+)$/', $email, $sending_domain );
		if ( ! $sending_domain ) {
			return false; // invalid email.
		}
		$sending_server = self::get_sending_ip();

		return self::validate_ip_listed_in_spf( $sending_domain[1], $sending_server );
	}

	/**
	 * Get the sending IP address.
	 *
	 * @return string
	 */
	public static function get_sending_ip() {
		static $sending_ip;
		if ( $sending_ip ) {
			return $sending_ip;
		}
		$url = admin_url( 'admin-ajax.php' );
		$ip  = wp_remote_retrieve_body( wp_remote_get( $url . '?action=wpes_get_ip' ) );
		if ( ! preg_match( '/^[0-9A-Fa-f.:]$/', $ip ) ) {
			$ip = false;
		}
		if ( ! $ip ) {
			$ip = wp_remote_retrieve_body( wp_remote_get( 'https://ip.remonpel.nl' ) );
			if ( '0.0.0.0' === $ip ) {
				$ip = false;
			}
		}
		if ( ! $ip ) {
			$ip = wp_remote_retrieve_body(
				wp_remote_get(
					'http://watismijnip.nl',
					[
						'httpversion' => '1.1',
						'referer'     => $_SERVER['HTTP_REFERER'],
						'user-agent'  => $_SERVER['HTTP_USER_AGENT'],
					]
				)
			);
			preg_match( '/Uw IP-Adres: <b>([.:0-9A-Fa-f]+)/', $ip, $part );
			$ip = $part[1];
		}
		if ( ! $ip ) {
			$ip = $_SERVER['SERVER_ADDR'];
		}

		$sending_ip = $ip;

		return $sending_ip;
	}

	/**
	 * Test: Does the SPF for $domain contain or refer to this $ip.
	 *
	 * @param string $domain The domain name.
	 * @param string $ip     The IP address.
	 *
	 * @return bool|null
	 */
	public static function validate_ip_listed_in_spf( $domain, $ip ) {
		$dns = self::dns_get_record( $domain, DNS_TXT );
		if ( ! $dns ) {
			return null;
		}

		foreach ( $dns as $record ) {
			$record['txt'] = strtolower( $record['txt'] );
			if ( false !== strpos( $record['txt'], 'v=spf1' ) ) {
				$sections = explode( ' ', $record['txt'] );
				foreach ( $sections as $section ) {
					if ( preg_match( '/(a|aaaa|mx):(.+)/', $section, $mx_match ) ) {
						// here we only expand the record, the actual check is done later .
						foreach ( self::dns_get_record( $mx_match[2], DNS_MX ) as $item ) {
							$sections[] = 'a/' . $item['target'];
						}
					}

					if ( 'a' === $section || 'aaaa' === $section || 'a/' === substr( $section, 0, 2 ) ) {
						list ( $_, $_domain ) = explode( '/', "$section/$domain" );
						if ( IP::is_4( $ip ) ) {
							$m_ip = self::dns_get_record( $_domain, DNS_A, true );
							if ( IP::a_4_is_4( $m_ip, $ip ) ) {
								return true;
							}
						}
						if ( IP::is_6( $ip ) ) {
							$m_ip = self::dns_get_record( $_domain, DNS_AAAA, true );
							if ( IP::a_6_is_6( $m_ip, $ip ) ) {
								return true;
							}
						}
					} elseif ( 'mx' === $section ) {
						$mx = self::dns_get_record( $domain, DNS_MX );
						foreach ( $mx as $mx_record ) {
							$target = $mx_record['target'];
							if ( IP::is_4( $ip ) ) {
								try {
									$new_target = self::dns_get_record( $domain, DNS_A, true );
								} catch ( Exception $e ) {
									$new_target = $target;
								}
								if ( IP::a_4_is_4( $ip, $new_target ) ) {
									return true;
								}
							}
							if ( IP::is_6( $ip ) ) {
								try {
									$new_target = self::dns_get_record( $domain, DNS_AAAA, true );
								} catch ( Exception $e ) {
									$new_target = $target;
								}
								if ( IP::a_6_is_6( $ip, $new_target ) ) {
									return true;
								}
							}
						}
					} elseif ( preg_match( '/ip4:([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)$/', $section, $m_ip ) ) {
						if ( IP::a_4_is_4( $ip, $m_ip[1] ) ) {
							return true;
						}
					} elseif ( preg_match( '/ip4:([0-9.]+\/[0-9]+)$/', $section, $ip_cidr ) ) {
						if ( IP::ip4_match_cidr( $ip, $ip_cidr[1] ) ) {
							return true;
						}
					} elseif ( preg_match( '/ip6:([0-9A-Fa-f:]+)$/', $section, $m_ip ) ) {
						if ( IP::is_6( $m_ip[1] ) && IP::a_6_is_6( $ip, $m_ip[1] ) ) {
							return true;
						}
					} elseif ( preg_match( '/ip6:([0-9A-Fa-f:]+\/[0-9]+)$/', $section, $ip_cidr ) ) {
						if ( IP::ip6_match_cidr( $ip, $ip_cidr[1] ) ) {
							return true;
						}
					} elseif ( preg_match( '/include:(.+)$/', $section, $include ) ) {
						if ( self::validate_ip_listed_in_spf( $include[1], $ip ) ) {
							return true;
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * Get the DNS record (cached) for a given domain.
	 *
	 * @param string $lookup        The domain to lookup.
	 * @param int    $filter        A DNS_* constant indicating which records you are looking for, DNS_A, DNS_TXT etx.
	 * @param bool   $single_output Return a single value (only works for DNS_A and DNS_A6/DNS_AAAA.
	 *
	 * @return array|mixed
	 */
	public static function dns_get_record( $lookup, $filter, $single_output = null ) {
		$transient_name = "dns_{$lookup}__TYPE{$filter}__cache";
		$transient      = get_site_transient( $transient_name );
		if ( ! $transient ) {
			$transient = dns_get_record( $lookup, $filter );
			$ttl       = count( $transient ) > 0 && is_array( $transient[0] && isset( $transient[0]['ttl'] ) ) ? $transient[0]['ttl'] : 3600;
			set_site_transient( $transient_name, $transient, $ttl );
		}
		if ( $single_output ) { // Most records are repeatable, should return array, calling code should process array.
			if ( DNS_A === $filter ) {
				return $transient[0]['ip'];
			}
			if ( DNS_A6 === $filter ) {
				return $transient[0]['ipv6'];
			}
		}

		return $transient;
	}

	/**
	 * Test: this email address matches the domain of this website.
	 *
	 * @param string $email The email address to test.
	 *
	 * @return bool
	 */
	public static function this_email_matches_website_domain( $email ) {
		$url  = get_bloginfo( 'url' );
		$host = wp_parse_url( $url, PHP_URL_HOST );
		$host = preg_replace( '/^www[0-9]*\./', '', $host );

		return ( preg_match( '/@' . $host . '$/', $email ) );
	}

	/**
	 * Implementation of action  wp_mailer_init .
	 *
	 * @param WPES_PHPMailer $mailer The PHPMailer object, either PHPMailer or PHPMailer\PHPMailer\PHPMailer ... Thank you WordPress...
	 */
	public static function action_phpmailer_init( &$mailer ) {
		// @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$config = self::get_config();

		if ( isset( $config['smtp']['timeout'] ) ) {
			$mailer->Timeout = $config['smtp']['timeout'];
		}

		if ( $config['smtp'] ) {
			$mailer->IsSMTP();

			$mailer->Host = $config['smtp']['host'];
			if ( $config['smtp']['port'] > 0 ) {
				$mailer->Port = $config['smtp']['port'];
			}
			if ( ! empty( $config['smtp']['port'] ) ) {
				$mailer->Port = $config['smtp']['port'];
			}

			if ( isset( $config['smtp']['username'] ) ) {
				if ( trim( $config['smtp']['username'] ) ) {
					$mailer->SMTPAuth = true;
					$mailer->Username = $config['smtp']['username'];
					$mailer->Password = $config['smtp']['password'];
				}
				if ( isset( $config['smtp']['secure'] ) && $config['smtp']['secure'] ) {
					$mailer->SMTPSecure = trim( $config['smtp']['secure'], '-' );
				} else {
					$mailer->SMTPAutoTLS = false;
				}
				if ( ( defined( 'WPES_ALLOW_SSL_SELF_SIGNED' ) && true === WPES_ALLOW_SSL_SELF_SIGNED ) || '-' === substr( $config['smtp']['secure'] ?? '', - 1, 1 ) ) {
					$mailer->SMTPOptions = [
						'ssl' => [
							'verify_peer'       => false,
							'verify_peer_name'  => false,
							'allow_self_signed' => true,
						],
					];
				}
			}
		}

		self::log( 'MAILER ' . __LINE__ . ' set FROM: ' . self::wp_mail_from() );
		$mailer->Sender = self::wp_mail_from();

		$mailer->Body = self::preserve_weird_url_display( $mailer->Body );

		if ( $config['is_html'] || $config['enable_history'] ) {
			$check_encoding_result = false;
			if ( 'auto' === $config['content_precode'] ) {
				$encoding_table = explode( ',', self::ENCODINGS );
				foreach ( $encoding_table as $encoding ) {
					$check_encoding_result = mb_check_encoding( $mailer->Body, $encoding );
					if ( $check_encoding_result ) {
						$check_encoding_result = $encoding;
						break;
					}
				}
			}

			$mailer->Body = self::maybe_convert_to_html( $mailer->Body, $mailer->Subject, $mailer, $check_encoding_result ?: 'utf-8' );

			$css = apply_filters_ref_array( 'wpes_css', [ '', &$mailer ] );

			if ( $config['css_inliner'] ) {
				require_once __DIR__ . '/../lib/class-css-inliner.php';
				$inliner      = new CSS_Inliner( $mailer->Body, $css );
				$mailer->Body = $inliner->convert();
			}

			$mailer->isHTML( true );
		}

		if ( $config['do_shortcodes'] ) {
			$mailer->Body = do_shortcode( $mailer->Body );
		}

		if ( $config['alt_body'] || $config['enable_history'] ) {
			$body = $mailer->Body;
			$btag = strpos( $body, '<body' );
			if ( false !== $btag ) {
				$bodystart = strpos( $body, '>', $btag );
				$bodytag   = substr( $body, $btag, $bodystart - $btag + 1 );

				list( $body, $junk ) = explode( '</body', $body );
				list( $junk, $body ) = explode( $bodytag, $body );
			}

			// images to alt tags.
			// example; <img src="/logo.png" alt="company logo" /> becomes  company logo.
			$body = preg_replace( "/<img.+alt=([\"'])(.+)(\\1).+>/U", "\\2", $body );

			// links to link-text+url.
			// example; <a href="http://nu.nl">Go to NU.nl</a> becomes:  Go to Nu.nl ( http://nu.nl ).
			$body = preg_replace( "/<a.+href=([\"'])(.+)(\\1).+>([^<]+)<\/a>/U", "\\4 (\\2)", $body );

			// remove all HTML except line breaks and line-break-ish.
			$body = strip_tags( $body, '<br><tr><li>' );

			// replace all forms of breaks, list items and table row endings to new-lines.
			$body = preg_replace( '/<br[\/ ]*>/Ui', "\n", $body );
			$body = preg_replace( '/<\/(li|tr)>/Ui', '</\1>' . "\n\n", $body );

			// remove all HTML.
			$body = strip_tags( $body, '' );

			// remove all carriage return symbols.
			$body = str_replace( "\r", '', $body );

			// convert non-breaking-space to regular space.
			$body = strtr( $body, [ '&nbsp;' => ' ' ] );

			// remove white-space at beginning and end of the lines.
			$body = explode( "\n", $body );
			foreach ( $body as $i => $line ) {
				$body[ $i ] = trim( $line );
			}
			$body = implode( "\n", $body );

			// remove newlines where more than two (two newlines make one blank line, remember that).
			$body = preg_replace( "/[\n]{2,}/", "\n\n", $body );

			// set the alternate body.
			$mailer->AltBody = $body;

			if ( $config['do_shortcodes'] ) {
				$mailer->AltBody = do_shortcode( $mailer->AltBody );
			}
		}

		// Check if this is a debug request;.
		// @phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $_POST && isset( $_POST['form_id'] ) && 'wp-email-essentials' === $_POST['form_id'] && __( 'Send sample mail', 'wpes' ) === $_POST['op'] ) {
			$mailer->Timeout   = 5;
			$mailer->SMTPDebug = 2;
		}

		$mailer->ContentType .= '; charset=' . $mailer->CharSet;

		$from = self::wp_mail_from();

		// S/MIME Signing .
		if ( $config['enable_smime'] ) {
			$id = self::get_smime_identity( $from );
			if ( $id ) {
				list( $crt, $key, $pass ) = $id;

				$mailer->sign( $crt, $key, $pass );
			}
		}

		// DKIM Signing .
		if ( $config['enable_dkim'] ) {
			$id = self::get_dkim_identity( $from );
			if ( $id ) {
				list( $crt, $key, $pass, $selector, $domain ) = $id;

				$mailer->DKIM_domain     = $domain;
				$mailer->DKIM_private    = $key;
				$mailer->DKIM_selector   = $selector;
				$mailer->DKIM_passphrase = $pass;
				$mailer->DKIM_identity   = $from;
			}
		}

		// DEBUG output .
		// @phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( $_POST && isset( $_POST['form_id'] ) && 'wp-email-essentials' === $_POST['form_id'] && __( 'Print debug output of sample mail', 'wpes' ) === $_POST['op'] ) {
			$mailer->SMTPDebug = true;
			print '<h2>' . esc_html__( 'Dump of PHP Mailer object', 'wpes' ) . '</h2><pre>';
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_var_dump
			var_dumP( $mailer );
			exit;
		}

		// @phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
	}

	/**
	 * Fix WordPress' stupid decision to show an url wrapped in < > ... I mean, c'mon!.
	 *
	 * @param string $html The HTML to fix.
	 *
	 * @return string
	 */
	private static function preserve_weird_url_display( $html ) {
		if ( preg_match( '/<(http(s)?:\/\/[^>]+)>/', $html, $m ) ) {
			$url = $m[1];
			if ( defined( 'WPES_CLEAN_LOGIN_RESET_URL' ) && true === WPES_CLEAN_LOGIN_RESET_URL ) {
				return str_replace( '<' . $url . '>', $url, $html );
			}

			return str_replace( '<' . $url . '>', '[' . $url . ']', $html );
		}

		return $html;
	}

	/**
	 * Convert a body to HTML, if not already HTML.
	 *
	 * @param string         $might_be_text The email body that might be text, might be HTML.
	 * @param string         $subject       The subject.
	 * @param WPES_PHPMailer $mailer        The PHP_Mailer object.
	 * @param string         $charset       A charset.
	 *
	 * @return mixed|string
	 */
	public static function maybe_convert_to_html( $might_be_text, $subject, $mailer, $charset = 'utf-8' ) {
		$html_preg = '<(br|a|p|body|table|div|span|body|html)';
		if ( preg_match( "/$html_preg/", $might_be_text ) ) {
			// probably html.
			$should_be_html = $might_be_text;
		} else {
			$should_be_html = nl2br( trim( $might_be_text ) );
		}

		// should have some basic HTML now, otherwise, add a P.
		if ( ! preg_match( "/$html_preg/", $should_be_html ) ) {
			$should_be_html = '<p>' . $should_be_html . '</p>';
		}

		// now check for HTML envelope.
		if ( false === strpos( $should_be_html, '<html' ) ) {

			$should_be_html = self::build_html( $mailer, $subject, $should_be_html, $charset );
		}

		return $should_be_html;
	}

	/**
	 * Build HTML for sending the email.
	 *
	 * @param WPES_PHPMailer $mailer         The mailer object.
	 * @param string         $subject        The subject.
	 * @param string         $should_be_html The email body which now should be HTML.
	 * @param string         $charset        The charset.
	 *
	 * @return string
	 */
	public static function build_html( $mailer, $subject, $should_be_html, $charset = 'utf-8' ) {
		// at this stage we will convert raw HTML part to a full HTML page.

		// you can define a file  wpes-email-template.php  in your theme to define the filters.
		locate_template( [ 'wpes-email-template.php' ], true );

		$subject = apply_filters_ref_array(
			'wpes_subject',
			[
				$subject,
				&$mailer,
			]
		);

		$css = apply_filters_ref_array(
			'wpes_css',
			[
				'',
				&$mailer,
			]
		);

		$head = apply_filters_ref_array(
			'wpes_head',
			[
				'<title>' . $subject . '</title><style type="text/css">' . $css . '</style>',
				&$mailer,
			]
		);

		$should_be_html = apply_filters_ref_array(
			'wpes_body',
			[
				$should_be_html,
				&$mailer,
			]
		);
		$should_be_html = htmlspecialchars_decode( htmlentities( $should_be_html ) );

		$should_be_html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />
<head>' . $head . '</head><body>' . $should_be_html . '</body></html>';

		return $should_be_html;
	}


	/**
	 * This is triggered when CF7 has option "send as html" on, but it interferes with the rest of WP_Email_Essentials.
	 *
	 * @optionalparam string      $header     WPCF7 Email header.
	 * @optionalparam \WPCF7_Mail $wpcf7_mail The WPCF7_Mail object.
	 *
	 * @return string
	 */
	public static function wpcf7_mail_html_header() {
		return '';
	}

	/**
	 * This is triggered when CF7 has option "send as html" on, but it interferes with the rest of WP_Email_Essentials.
	 *
	 * @optionalparam string      $footer     WPCF7 Email footer.
	 * @optionalparam \WPCF7_Mail $wpcf7_mail The WPCF7_Mail object.
	 *
	 * @return string
	 */
	public static function wpcf7_mail_html_footer() {
		return '';
	}

	/**
	 * Memory cell: the WP-Mail From: address.
	 *
	 * @param string $from The address to remember.
	 *
	 * @return mixed
	 */
	public static function wp_mail_from( $from = null ) {
		static $store;
		if ( $from ) {
			$store = $from;
		}

		return $store;
	}

	/**
	 * Memory cell: the WP-Mail From: name.
	 *
	 * @param string $from The name to remember.
	 *
	 * @return mixed
	 */
	public static function wp_mail_from_name( $from = null ) {
		static $store;
		if ( $from ) {
			$store = $from;
		}

		return $store;
	}

	/**
	 * Implementation of filter  wp_mail_from. Returns the remembered From address.
	 *
	 * @return string
	 */
	public static function filter_wp_mail_from() {
		return self::wp_mail_from();
	}

	/**
	 * Implementation of filter  wp_mail_from. Returns the remembered From name.
	 *
	 * @return string
	 */
	public static function filter_wp_mail_from_name() {
		return self::wp_mail_from_name();
	}

	/**
	 * Get the module configuration.
	 *
	 * @param bool $raw Get raw data?.
	 *
	 * @return array|mixed
	 */
	public static function get_config( $raw = false ) {
		$defaults = [
			'smtp'                 => false,
			'from_email'           => get_bloginfo( 'admin_email' ),
			'from_name'            => self::get_hostname_by_blogurl(),
			'is_html'              => false,
			'alt_body'             => false,
			'css_inliner'          => false,
			'enable_smime'         => false,
			'enable_dkim'          => false,
			'spf_lookup_enabled'   => false,
			'errors_to'            => get_bloginfo( 'admin_email' ),
			'content_precode'      => false,
			'do_shortcodes'        => false,
			'enable_history'       => false,
			'make_from_valid_when' => 'when_sender_invalid',
		];

		$defaults = apply_filters( 'wpes_defaults', $defaults );

		$settings = get_option( 'wp-email-essentials', $defaults );
		if ( ! $raw ) {
			$settings = apply_filters( 'wpes_settings', $settings );

			$settings['certificate_folder'] = $settings['certfolder'] ?? '';
			if ( '/' !== substr( $settings['certificate_folder'], 0, 1 ) ) {
				$settings['certificate_folder'] = rtrim( self::root_path(), '/' ) . '/' . $settings['certificate_folder'];
			}

			$settings['dkim_certificate_folder'] = $settings['dkimfolder'] ?? '';
			if ( '/' !== substr( $settings['dkim_certificate_folder'], 0, 1 ) ) {
				$settings['dkim_certificate_folder'] = rtrim( self::root_path(), '/' ) . '/' . $settings['dkim_certificate_folder'];
			}
		}

		$return = array_merge( $defaults, $settings );

		if ( $return['smtp'] && false !== strpos( $return['smtp']['host'], ':' ) ) {
			list ( $wpes_host, $wpes_port ) = explode( ':', $return['smtp']['host'] );

			if ( is_numeric( $wpes_port ) ) {
				$return['smtp']['port'] = $wpes_port;
				$return['smtp']['host'] = $wpes_host;
			}
		}

		return $return;
	}

	/**
	 * Write new configuration data.
	 *
	 * @param array $values The values to write.
	 * @param bool  $raw    Write raw?.
	 *
	 * @return bool|void
	 */
	private static function set_config( $values, $raw = false ) {
		if ( $raw ) {
			return update_option( 'wp-email-essentials', $values );
		}

		$values   = stripslashes_deep( $values );
		$settings = self::get_config();
		if ( isset( $values['smtp-enabled'] ) && $values['smtp-enabled'] ) {
			$settings['smtp'] = [
				'secure'   => $values['secure'],
				'host'     => $values['host'],
				'port'     => $values['port'],
				'username' => $values['username'],
				'password' => ( $values['password'] == str_repeat( '*', strlen( $values['password'] ) ) && $settings['smtp'] ) ? $settings['smtp']['password'] : $values['password'],
			];

			if ( false !== strpos( $settings['smtp']['host'], ':' ) ) {
				list ( $host, $port ) = explode( ':', $settings['smtp']['host'] );
				if ( is_numeric( $port ) ) {
					$settings['smtp']['port'] = $port;
					$settings['smtp']['host'] = $host;
				}
			}

			if ( $settings['smtp']['port'] <= 0 ) {
				$settings['smtp']['port'] = '';
			}
		} else {
			$settings['smtp'] = false;
		}
		$settings['from_name']          = array_key_exists( 'from_name', $values ) && $values['from_name'] ? $values['from_name'] : $settings['from_name'];
		$settings['from_email']         = array_key_exists( 'from_email', $values ) && $values['from_email'] ? $values['from_email'] : $settings['from_email'];
		$settings['timeout']            = array_key_exists( 'timeout', $values ) && $values['timeout'] ? $values['timeout'] : 5;
		$settings['is_html']            = array_key_exists( 'is_html', $values ) && $values['is_html'] ? true : false;
		$settings['css_inliner']        = array_key_exists( 'css_inliner', $values ) && $values['css_inliner'] ? true : false;
		$settings['content_precode']    = array_key_exists( 'content_precode', $values ) && $values['content_precode'] ? $values['content_precode'] : false;
		$settings['alt_body']           = array_key_exists( 'alt_body', $values ) && $values['alt_body'] ? true : false;
		$settings['do_shortcodes']      = array_key_exists( 'do_shortcodes', $values ) && $values['do_shortcodes'] ? true : false;
		$settings['SingleTo']           = array_key_exists( 'SingleTo', $values ) && $values['SingleTo'] ? true : false;
		$settings['spf_lookup_enabled'] = array_key_exists( 'spf_lookup_enabled', $values ) && $values['spf_lookup_enabled'] ? true : false;
		$settings['enable_history']     = array_key_exists( 'enable_history', $values ) && $values['enable_history'] ? true : false;

		$settings['enable_smime']         = array_key_exists( 'enable_smime', $values ) && $values['enable_smime'] ? '1' : '0';
		$settings['certfolder']           = array_key_exists( 'certfolder', $values ) && $values['certfolder'] ? $values['certfolder'] : '';
		$settings['enable_dkim']          = array_key_exists( 'enable_dkim', $values ) && $values['enable_dkim'] ? '1' : '0';
		$settings['dkimfolder']           = array_key_exists( 'dkimfolder', $values ) && $values['dkimfolder'] ? $values['dkimfolder'] : '';
		$settings['make_from_valid']      = array_key_exists( 'make_from_valid', $values ) && $values['make_from_valid'] ? $values['make_from_valid'] : '';
		$settings['make_from_valid_when'] = array_key_exists( 'make_from_valid_when', $values ) && $values['make_from_valid_when'] ? $values['make_from_valid_when'] : 'when_sender_invalid';
		$settings['errors_to']            = array_key_exists( 'errors_to', $values ) && $values['errors_to'] ? $values['errors_to'] : '';
		update_option( 'wp-email-essentials', $settings );
	}

	public static function get_hostname_by_blogurl() {
		$url = get_bloginfo( 'url' );
		$url = wp_parse_url( $url );

		return $url['host'];
	}

	private static function rfc_decode( $rfc ) {
		$rfc = trim( $rfc );

		// $rfc might just be an e-mail address
		if ( is_email( $rfc ) ) {
			return [
				'name'  => $rfc,
				'email' => $rfc,
			];
		}

		// $rfc is not an email, the RFC format is:
		// "Name Surname Anything here" <email@addr.ess>
		// but quotes are optional...
		// Name Surname Anything here <email@addr.ess>
		// is considered valid as well
		//
		// considering HTML, <email@addr.ess> is a tag so we can strip that out with strip_tags
		// and the remainder is the name-part.
		$name_part = strip_tags( $rfc );
		// remove the name-part from the original and the email part is known
		$email_part = str_replace( $name_part, '', $rfc );

		// strip illegal characters;
		// the name part could have had escaped quotes (like "I have a quote \" here" <some@email.com> )
		$name_part  = trim( stripslashes( $name_part ), "\n\t\r\" " );
		$email_part = trim( $email_part, "\n\t\r\"<> " );

		// verify :)
		if ( is_email( $email_part ) ) {
			return [
				'name'  => $name_part,
				'email' => $email_part,
			];
		}

		return false;
	}

	private static function rfc_explode( $string ) {
		// safequard escaped quotes
		$string = str_replace( '\\"', 'ESCAPEDQUOTE', $string );
		// get chnks
		$exploded = [];
		$i        = 0;
		// this regexp will match any comma + a string behind it.
		// therefore, to fetch all elements, we need a dummy element at the end that will be ignored.
		$string .= ', dummy';
		while ( trim( $string ) && preg_match( '/(,)(([^"]|"[^"]*")*$)/', $string, $match ) ) {
			$i ++;
			// print "Round $i; \n";
			// print "String WAS: $string \n";
			$matched_rest    = $match[0];
			$unmatched_first = str_replace( $matched_rest, '', $string );
			$string          = trim( $matched_rest, ', ' );
			$exploded[]      = str_replace( 'ESCAPEDQUOTE', '\\"', $unmatched_first );
			// var_dump('match:', $match, "mrest:", $matched_rest, "umfirst:", $unmatched_first, "string is now:", $string);
			// print '---------------------------------------------------------------------------------'. "\n";
		}

		return array_map( 'trim', $exploded );
	}

	private static function rfc_recode( $e ) {
		if ( ! is_array( $e ) ) {
			$e = self::rfc_decode( $e );
		}
		$e = self::rfc_encode( $e );

		return $e;
	}

	private static function rfc_encode( $email_array ) {
		if ( ! $email_array['name'] ) {
			return $email_array['email'];
		}

		// this is the unescaped, unencasulated RFC, as WP 4.6 and higher want it.
		$email_array['name'] = trim( stripslashes( $email_array['name'] ), '"' );
		if ( version_compare( get_bloginfo( 'version' ), '4.5', '<=' ) ) {
			// this will escape all quotes and encapsulate with quotes, for 4.5 and older
			$email_array['name'] = wp_json_encode( $email_array['name'] );
		}
		// so NO QUOTES HERE, they are there where needed.
		$return = trim( sprintf( '%s <%s>', $email_array['name'], $email_array['email'] ) );

		return $return;
	}

	public static function admin_menu() {
		add_menu_page(
			'WP-Email-Essentials',
			'Email Essentials',
			'manage_options',
			'wp-email-essentials',
			[
				self::class,
				'admin_interface',
			],
			'dashicons-email-alt'
		);

		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wp-email-essentials' && $_POST && isset( $_POST['form_id'] ) && $_POST['form_id'] == 'wp-email-essentials' ) {
			switch ( $_POST['op'] ) {
				case __( 'Save settings', 'wpes' ):
					$config  = self::get_config();
					$host    = wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
					$host    = preg_replace( '/^www[0-9]*\./', '', $host );
					$defmail = self::wp_mail_from( $_POST['settings']['from_email'] );
					if ( 'default' == $_POST['settings']['make_from_valid'] && ! self::i_am_allowed_to_send_in_name_of( $defmail ) ) {
						$_POST['settings']['make_from_valid'] = 'noreply';
					}
					self::set_config( $_POST['settings'] );
					self::$message = __( 'Settings saved.', 'wpes' );
					break;
				case __( 'Print debug output of sample mail', 'wpes' ):
				case __( 'Send sample mail', 'wpes' ):
					ob_start();
					self::$debug = true;
					$result      = wp_mail(
						get_option( 'admin_email', false ),
						__( 'Test-email', 'wpes' ),
						self::dummy_content(),
						[ 'X-Priority: 1' ]
					);
					self::$debug = ob_get_clean();
					if ( $result ) {
						self::$message = sprintf( __( 'Mail sent to %s', 'wpes' ), get_option( 'admin_email', false ) );
					} else {
						self::$error = sprintf( __( 'Mail NOT sent to %s', 'wpes' ), get_option( 'admin_email', false ) );
					}
					break;
			}
		}
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wp-email-essentials' && isset( $_GET['iframe'] ) && $_GET['iframe'] == 'content' ) {
			$mailer          = new WPES_PHPMailer();
			$config          = self::get_config();
			$subject         = __( 'Sample email subject', 'wpes' );
			$mailer->Subject = $subject;
			$body            = self::dummy_content();
			header( 'Content-Type: text/html; charset=utf-8' );

			$html = self::build_html( $mailer, $subject, $body, 'utf-8' );

			$html = self::cid_to_image( $html, $mailer );
			print $html;

			exit;
		}

		add_submenu_page(
			'wp-email-essentials',
			'WP-Email-Essentials - Alternative Admins',
			'Alternative admins',
			'manage_options',
			'wpes-admins',
			[
				self::class,
				'admin_interface_admins',
			]
		);
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wpes-admins' && $_POST && isset( $_POST['form_id'] ) && $_POST['form_id'] == 'wpes-admins' ) {
			switch ( $_POST['op'] ) {
				case __( 'Save settings', 'wpes' ):
					$keys = $_POST['settings']['keys'];
					$keys = array_filter(
						$keys,
						function ( $el ) {
							$els = explode( ',', $el );
							$els = array_map(
								function ( $el ) {
									return filter_var( $el, FILTER_VALIDATE_EMAIL );
								},
								$els
							);

							return implode( ',', $els );
						}
					);
					update_option( 'mail_key_admins', $keys );
					self::$message = __( 'Alternative Admins list saved.', 'wpes' );

					$regexps = $_POST['settings']['regexp'];
					$list    = [];

					$__regex = '/^\/[\s\S]+\/$/';
					foreach ( $regexps as $entry ) {
						if ( preg_match( $__regex, $entry['regexp'] ) ) {
							$list[ $entry['regexp'] ] = $entry['key'];
						}
					}

					update_option( 'mail_key_list', $list );
					self::$message .= ' ' . __( 'Subject-RegExp list saved.', 'wpes' );

					break;
			}
		}

		add_submenu_page(
			'wp-email-essentials',
			'WP-Email-Essentials - Alternative Moderators',
			'Alternative Moderators',
			'manage_options',
			'wpes-moderators',
			[
				self::class,
				'admin_interface_moderators',
			]
		);
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'wpes-moderators' && $_POST && isset( $_POST['form_id'] ) && $_POST['form_id'] == 'wpes-moderators' ) {
			switch ( $_POST['op'] ) {
				case __( 'Save settings', 'wpes' ):
					foreach ( $_POST['settings']['keys'] as $recipient => $_keys ) {
						foreach ( $_keys as $post_type => $keys ) {
							$_POST['settings']['keys'][ $recipient ][ $post_type ] = array_filter(
								$keys,
								function ( $el ) {
									$els = explode( ',', $el );
									$els = array_map(
										function ( $el ) {
											if ( ':blackhole:' === $el ) {
												return $el;
											}

											return filter_var( $el, FILTER_VALIDATE_EMAIL );
										},
										$els
									);

									return implode( ',', $els );
								}
							);
						}
					}
					update_option( 'mail_key_moderators', $_POST['settings']['keys'] );
					self::$message = __( 'Alternative Moderators list saved.', 'wpes' );

					break;
			}
		}
	}

	static function admin_interface() {
		include __DIR__ . '/../admin-interface.php';
	}

	static function admin_interface_admins() {
		include __DIR__ . '/../admin-admins.php';
	}

	static function admin_interface_moderators() {
		include __DIR__ . '/../admin-moderators.php';
	}

	public static function test() {
		$test = self::rfc_decode( 'ik@remonpel.nl' );
		// should return array( 'name' => 'ik@remonpel.nl', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'ik@remonpel.nl' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "simple email address verified<br />\n";
		} else {
			echo "simple email address FAILED<br />\n";
		}

		$test = self::rfc_decode( 'Remon Pel <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "RFC2822 no quotes email address verified<br />\n";
		} else {
			echo "RFC2822 no quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode( '"Remon Pel" <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "RFC2822 with quotes email address verified<br />\n";
		} else {
			echo "RFC2822 with quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode( '    "   Remon Pel   " <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl' ) {
			echo "RFC2822 too many spaces - not valid RFC but still parses? verified<br />\n";
		} else {
			echo "RFC2822 too many spaces - not valid RFC but still parses? FAILED<br />\n";
		}

		exit;
	}

	public static function migrate_from_smtp_connect() {
		$plugin = 'smtp-connect/smtp-connect.php';
		if ( is_plugin_active( $plugin ) ) {
			// plugin active, migrate
			$smtp_connect = get_option( 'smtp-connect', [] );
			if ( $smtp_connect['enabled'] ) {
				$smtp_connect['smtp-enabled'] = true;
			}
			$smtp_connect['host'] = $smtp_connect['Host'];
			unset( $smtp_connect['Host'] );
			$smtp_connect['username'] = $smtp_connect['Username'];
			unset( $smtp_connect['Username'] );
			$smtp_connect['password'] = $smtp_connect['Password'];
			unset( $smtp_connect['Password'] );
			self::set_config( $smtp_connect );

			// deactivate conflicting plugin
			deactivate_plugins( $plugin, false );

			// WordPress still thinks the plugin is active, do it the hard way
			$active = get_option( 'active_plugins', [] );
			unset( $active[ array_search( $plugin, $active ) ] );
			update_option( 'active_plugins', $active );

			// log the deactivation.
			update_option( 'recently_activated', [ $plugin => time() ] + (array) get_option( 'recently_activated' ) );
		}
	}

	public static function dummy_content() {
		return '<h1>Sample Email Body</h1><p>Some <a href="https://google.com/?s=random">råndôm</a> text Lorem Ipsum is <b>bold simply dummy</b> text of the <strong>strong printing and typesetting</strong> industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p><h2>A header-2</h2><p>Some more text</p><h3>A header-3</h3><ul><li>A list - unordered, item 1</li><li>Item 2</li></ul><h4>A header-4</h4><ol><li>A list - ordered, item 1</li><li>Item 2</li></ol>';
	}

	public static function cid_to_image( $html, $mailer ) {
		foreach ( $mailer->getAttachments() as $attachment ) {
			if ( $attachment[7] ) {
				$html = str_replace( 'cid:' . $attachment[7], 'data:' . $attachment[4] . ';' . $attachment[3] . ',' . base64_encode( file_get_contents( $attachment[0] ) ), $html );
			}
		}

		return $html;
	}

	function adminNotices() {
		$config = self::get_config();
		$onpage = is_admin() && isset( $_GET['page'] ) && $_GET['page'] == 'wp-email-essentials';

		$from = $config['from_email'];
		if ( ! $from ) {
			$url = add_query_arg( 'page', 'wp-email-essentials', admin_url( 'tools.php' ) );
			if ( $onpage ) {
				$class   = 'updated';
				$message = __( 'WP-Email-Essentials is not yet configured. Please fill out the form below.', 'wpes' );
				echo "<div class='$class'><p>$message</p></div>";
			} else {
				$class   = 'error';
				$message = sprintf( __( 'WP-Email-Essentials is not yet configured. Please go <a href="%s">here</a>.', 'wpes' ), $url );
				echo "<div class='$class'><p>$message</p></div>";
			}

			return;
		}

		// certfolder == setting, certificate_folder == real path;
		if ( $config['enable_smime'] && isset( $config['certfolder'] ) && $config['certfolder'] ) {
			if ( is_writable( $config['certificate_folder'] ) && ! get_option( 'suppress_smime_writable' ) ) {
				$class   = 'error';
				$message = __( 'The S/MIME certificate folder is writable. This is Extremely insecure. Please reconfigure, make sure the folder is not writable by Apache. If your server is running suPHP, you cannot make the folder read-only for apache. Please contact your hosting provider and ask for a more secure hosting package, one not based on suPHP.', 'wpes' );
				// echo "<div class='$class'><p>$message</p></div>";
			}

			if ( false !== strpos( realpath( $config['certificate_folder'] ), realpath( self::root_path() ) ) ) {
				$class   = 'error';
				$message = sprintf( __( 'The S/MIME certificate folder is inside the webspace. This is Extremely insecure. Please reconfigure, make sure the folder is outside the website-root %s.', 'wpes' ), self::root_path() );
				echo "<div class='$class'><p>$message</p></div>";
			}
		}

		// certfolder == setting, certificate_folder == real path;
		if ( $config['enable_smime'] && $onpage && ! function_exists( 'openssl_pkcs7_sign' ) ) {
			$class   = 'error';
			$message = __( 'The openssl package for PHP is not installed, incomplete or broken. Please contact your hosting provider. S/MIME signing is NOT available.', 'wpes' );
			echo "<div class='$class'><p>$message</p></div>";
		}

		// certfolder == setting, certificate_folder == real path;
		if ( $config['enable_smime'] && $onpage && isset( $config['smtp']['host'] ) && ( false !== strpos( $config['smtp']['host'], 'mandrillapp' ) || false !== strpos( $config['smtp']['host'], 'sparkpostmail' ) ) && function_exists( 'openssl_pkcs7_sign' ) ) {
			$class   = 'error';
			$message = __( 'Services like MandrillApp or SparkPostMail will break S/MIME signing. Please use a different SMTP-service if signing is required.', 'wpes' );
			echo "<div class='$class'><p>$message</p></div>";
		}

		// default mail identity existance
		if ( $config['enable_smime'] && $onpage && ! self::get_smime_identity( $from ) ) {
			$rawset               = self::get_config( true );
			$set                  = $rawset['certfolder'];
			$rawset['certfolder'] = __DIR__ . '/.smime';
			self::set_config( $rawset );
			if ( self::get_smime_identity( $from ) ) {
				$class   = 'error';
				$message = sprintf( __( 'There is no certificate for the default sender address <code>%s</code>. The required certificate is supplied with this plugin. Please copy it to the correct folder.', 'wpes' ), $from );
				echo "<div class='$class'><p>$message</p></div>";
			} else {
				$class   = 'error';
				$message = sprintf( __( 'There is no certificate for the default sender address <code>%s</code>. Start: <a href="https://www.comodo.com/home/email-security/free-email-certificate.php" target="_blank">here</a>.', 'wpes' ), $from );
				echo "<div class='$class'><p>$message</p></div>";
			}

			$rawset['certfolder'] = $set;
			self::set_config( $rawset, true );
		}

		// dkimfolder == setting, dkim_certificate_folder == real path;
		if ( ! empty( $config['enable_dkim'] ) && $config['enable_dkim'] && isset( $config['dkimfolder'] ) && $config['dkimfolder'] ) {
			if ( is_writable( $config['dkim_certificate_folder'] ) && ! get_option( 'suppress_dkim_writable' ) ) {
				$class   = 'error';
				$message = __( 'The DKIM certificate folder is writable. This is Extremely insecure. Please reconfigure, make sure the folder is not writable by Apache. If your server is running suPHP, you cannot make the folder read-only for apache. Please contact your hosting provider and ask for a more secure hosting package, one not based on suPHP.', 'wpes' );
				// echo "<div class='$class'><p>$message</p></div>";
			}

			if ( false !== strpos( realpath( $config['dkim_certificate_folder'] ), realpath( self::root_path() ) ) ) {
				$class   = 'error';
				$message = sprintf( __( 'The DKIM certificate folder is inside the webspace. This is Extremely insecure. Please reconfigure, make sure the folder is outside the website-root %s.', 'wpes' ), self::root_path() );
				echo "<div class='$class'><p>$message</p></div>";
			}
		}

		// default mail identity existance
		if ( ! empty( $config['enable_dkim'] ) && $config['enable_dkim'] && $onpage && ! self::get_dkim_identity( $from ) ) {
			$rawset               = self::get_config( true );
			$set                  = $rawset['dkimfolder'];
			$rawset['dkimfolder'] = $set;
			self::set_config( $rawset, true );
		}

	}

	public static function list_smime_identities() {
		$c                  = self::get_config();
		$ids                = [];
		$certificate_folder = $c['certificate_folder'];
		if ( is_dir( $certificate_folder ) ) {
			$files = glob( $certificate_folder . '/*.crt' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) && is_file( preg_replace( '/\.crt$/', '.key', $file ) ) ) {
					$ids[ basename( preg_replace( '/\.crt$/', '', $file ) ) ] = [
						$file,
						preg_replace( '/\.crt$/', '.key', $file ),
						trim( @file_get_contents( preg_replace( '/\.crt$/', '.pass', $file ) ) ),
					];
				}
			}
		}

		return $ids;
	}

	public static function get_smime_identity( $email ) {
		$ids = self::list_smime_identities();
		if ( isset( $ids[ $email ] ) ) {
			return $ids[ $email ];
		}

		return false;
	}


	public static function list_dkim_identities() {
		$c                  = self::get_config();
		$ids                = [];
		$certificate_folder = $c['dkim_certificate_folder'];
		if ( is_dir( $certificate_folder ) ) {
			$files = glob( $certificate_folder . '/*.crt' );
			foreach ( $files as $file ) {
				if ( is_file( $file ) && is_file( preg_replace( '/\.crt$/', '.key', $file ) ) ) {
					$domain         = basename( preg_replace( '/\.crt$/', '', $file ) );
					$ids[ $domain ] = [
						$file,
						preg_replace( '/\.crt$/', '.key', $file ),
						trim( @file_get_contents( preg_replace( '/\.crt$/', '.pass', $file ) ) ),
						trim( @file_get_contents( preg_replace( '/\.crt$/', '.selector', $file ) ) ),
						$domain,
					];
				}
			}
		}

		return $ids;
	}

	public static function get_dkim_identity( $email ) {
		$ids    = self::list_dkim_identities();
		$domain = explode( '@', '@' . $email );
		$domain = end( $domain );
		if ( isset( $ids[ $domain ] ) ) {
			return $ids[ $domain ];
		}

		return false;
	}


	public static function alternative_to( $email ) {
		$admin_email = get_option( 'admin_email' );

		// make sure we have a list of emails, not a single email
		if ( ! is_array( $email['to'] ) ) {
			$email['to'] = self::rfc_explode( $email['to'] );
		}

		// find the admin address
		$found_mail_item_number = - 1;
		foreach ( $email['to'] as $i => $email_address ) {
			$email['to'][ $i ] = self::rfc_recode( $email['to'][ $i ] );

			$decoded = self::rfc_decode( $email_address );
			if ( $decoded['email'] == $admin_email ) {
				$found_mail_item_number = $i;
			}
		}
		if ( $found_mail_item_number == - 1 ) {
			// not going to an admin.

			// var_dump($email, __LINE__);exit;
			return $email;
		}

		// $to is our found admin addressee
		$to = &$email['to'][ $found_mail_item_number ];
		$to = self::rfc_decode( $to );

		// this message is sent to the system admin
		// we might want to send this to a different admin
		if ( $key = self::get_mail_key( $email['subject'] ) ) {
			// we were able to determine a mailkey.
			$admins = get_option( 'mail_key_admins', [] );
			if ( isset( $admins[ $key ] ) && $admins[ $key ] ) {
				$the_admins = explode( ',', $admins[ $key ] );
				foreach ( $the_admins as $i => $the_admin ) {
					$the_admin = self::rfc_decode( $the_admin );
					if ( $i === 0 ) {
						if ( $the_admin['name'] == $the_admin['email'] && $to['name'] != $to['email'] ) {
							// not rfc, just email, but the original TO has a real name
							$the_admin['name'] = $to['name'];
						}
						$to = self::rfc_encode( $the_admin );
					} else {
						// extra
						$email['to'][] = self::rfc_encode( $the_admin );
					}
				}

				// var_dump($email, __LINE__);exit;
				return $email;
			}

			// known key, but no email set
			// we revert to the DEFAULT admin_email, and prevent matching against subjects
			// var_dump($email, __LINE__);exit;
			if ( is_array( $to ) && array_key_exists( 'email', $to ) ) {
				$to = self::rfc_encode( $to );
			}

			return $email;
		}

		// perhaps we have a regexp?
		$admin = self::mail_subject_match( $email['subject'] );
		if ( $admin ) {
			$the_admins = explode( ',', $admin );
			foreach ( $the_admins as $i => $the_admin ) {
				$the_admin = self::rfc_decode( $the_admin );
				if ( $i === 0 ) {
					if ( $the_admin['name'] == $the_admin['email'] && $to['name'] != $to['email'] ) {
						// not rfc, just email, but the original TO has a real name
						$the_admin['name'] = $to['name'];
					}
					$to = self::rfc_encode( $the_admin );
				} else {
					// extra
					$email['to'][] = self::rfc_encode( $the_admin );
				}
			}

			// var_dump($email, __LINE__);exit;
			return $email;
		}

		// sorry, we failed :(
		$fails = get_option( 'mail_key_fails', [] );
		if ( $fails ) {
			$fails = array_combine( $fails, $fails );
		}
		$fails[ $email['subject'] ] = $email['subject'];
		$fails                      = array_filter(
			$fails,
			function ( $item ) {
				return ! self::mail_subject_match( $item ) && ! self::get_mail_key( $item );
			}
		);
		update_option( 'mail_key_fails', array_values( $fails ) );

		// var_dump($email, __LINE__);exit;
		$to = self::rfc_encode( $to );

		return $email;
	}

	public static function pingback_detected( $set = null ) {
		static $static;
		if ( null !== $set ) {
			$static = $set;
		}

		return $static;
	}

	public static function correct_comment_to( $email, $comment_id ) {
		$comment = get_comment( $comment_id );

		return self::correct_moderation_and_comments( $email, $comment, 'author' );
	}

	public static function correct_moderation_to( $email, $comment_id ) {
		$comment = get_comment( $comment_id );

		return self::correct_moderation_and_comments( $email, $comment, 'moderator' );
	}

	public static function correct_moderation_and_comments( $email, $comment, $action ) {

		$post_type = 'post';
		// todo: future version; allow setting per post-type
		// trace back the post-type using: commentID -> comment -> post -> post-type

		$c = get_option( 'mail_key_moderators', [] );
		if ( ! $c || ! is_array( $c ) ) {
			return $email;
		}

		$type = $comment->comment_type;
		if ( 'pingback' === $type || 'trackback' === $type ) {
			$type = 'pingback';
		} else {
			$type = 'comment';
		}

		if ( isset( $c[ $post_type ][ $action ][ $type ] ) && $c[ $post_type ][ $action ][ $type ] ) {
			// overrule ALL recipients
			// we do this at PHP_INT_MIN, so any and all plugins can overrule this; that is THEIR choice.
			$email = [ $c[ $post_type ][ $action ][ $type ] ];
			$email = array_filter(
				$email,
				function ( $el ) {
					return filter_var( $el, FILTER_VALIDATE_EMAIL );
				}
			);
		}

		return $email;
	}

	public static function get_mail_key( $subject ) {
		// got a filter/action name?
		$mail_key = self::current_mail_key();
		if ( $mail_key ) {
			self::current_mail_key( '*CLEAR*' );
			self::log( "$subject matched to $mail_key by filter/action" );
		} else {
			$mail_key = self::mail_subject_database( $subject );
			if ( $mail_key ) {
				self::log( "$subject matched to $mail_key by subject-matching known subjects" );
			}
		}

		return $mail_key;
	}

	public static function mail_key_database() {
		// supported;
		$wp_filters = [
			'automatic_updates_debug_email',
			'auto_core_update_email',
			// 'comment_moderation_recipients',
			// 'comment_notification_recipients',
			'recovery_mode_email',
		];

		// unsupported until added, @see wp_mail_key.patch, matched by subject, @see self::mail_subject_database
		$unsupported_wp_filters = [
			'new_user_registration_admin_email',
			'password_lost_changed_email',
			'password_reset_email',
			'password_changed_email',
		];

		return array_merge( $wp_filters, $unsupported_wp_filters );
	}

	public static function mail_subject_database( $lookup ) {
		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		// FULL TEXT LOOKUP
		$keys = [
			// wp, do NOT use own text-domain here, this construction is here because these are WP translated strings
			sprintf( __( '[%s] New User Registration' ), $blogname ) => 'new_user_registration_admin_email',
			sprintf( __( '[%s] Password Reset' ), $blogname )        => 'password_reset_email', // wp 4.5 +
			sprintf( __( '[%s] Password Changed' ), $blogname )      => 'password_changed_email', // wp 4.5 +
			sprintf( __( '[%s] Password Lost/Changed' ), $blogname ) => 'password_lost_changed_email', // wp < 4.5
		];

		$key = isset( $keys[ $lookup ] ) ? $keys[ $lookup ] : '';

		if ( $key ) {
			return $key;
		}

		return false;
	}

	public static function mail_subject_match( $subject ) {
		$store = get_option( 'mail_key_list', [] );
		foreach ( $store as $regexp => $mail_key ) {
			if ( preg_match( $regexp, $subject ) ) {
				return $mail_key;
			}
		}

		return false;
	}

	public static function mail_key_registrations() {
		// this works on the mechanics that prior to sending an email, a filter or actions is hooked, a make-shift mail key
		// actions and filters are equal to WordPress, but handled with or without return values.
		foreach ( self::mail_key_database() as $filter_name ) {
			add_filter( $filter_name, [ self::class, 'now_sending___' ] );
		}
	}

	private static function current_mail_key( $set = null ) {
		static $mail_key;
		if ( $set ) {
			if ( $set == '*CLEAR*' ) {
				$set = false;
			}
			$mail_key = $set;
		}

		return $mail_key;
	}

	public static function now_sending___( $value ) {
		self::current_mail_key( current_filter() );

		return $value;
	}

	public static function log( $text ) {
		// to enable logging, create a writable file "log" in the plugin dir
		if ( defined( 'WPES_DEBUG' ) ) {
			print "LOG: $text\n";

			return;
		}

		static $fp;
		if ( file_exists( __DIR__ . '/log' ) && is_writable( __DIR__ . '/log' ) ) {
			if ( ! $fp ) {
				$fp = fopen( __DIR__ . '/log', 'a' );
			}
			if ( $fp ) {
				fwrite( $fp, date( 'r' ) . ' WPES: ' . trim( $text ) . "\n" );
			}
		}

		// error_log(' WPES: ' . $text);
	}

	public static function maybe_inject_admin_settings() {
		$host = wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
		if ( basename( $_SERVER['PHP_SELF'] ) == 'options-general.php' && ! @$_GET['page'] ) {
			?>
			<script>
				jQuery("#admin_email,#new_admin_email").after('<p class="description"><?php print sprintf( __( 'You can configure alternative administrators <a href="%s">here</a>.', 'wpes' ), add_query_arg( [ 'page' => 'wpes-admins' ], admin_url( 'admin.php' ) ) ); ?></p>');
			</script>
			<?php
		}

		$config = self::get_config();
		if ( ! isset( $config['make_from_valid'] ) ) {
			$config['make_from_valid'] = '';
		}
		switch ( $config['make_from_valid'] ) {
			case 'noreply':
				$text = sprintf( __( 'But <strong>please do not worry</strong>! <a href="%1$s" target="_blank">WP-Email-Essentials</a> will set <em class="noreply">noreply@%2$s</em> as sender and set <em>this email address</em> as Reply-To header.', 'wpes' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials', $host );
				break;
			case 'default':
				$text = sprintf( __( 'But <strong>please do not worry</strong>! <a href="%1$s" target="_blank">WP-Email-Essentials</a> will set <em class="default">%2$s</em> as sender and set <em>this email address</em> as Reply-To header.', 'wpes' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials', self::wp_mail_from( $config['from_email'] ) );
				break;
			case '-at-':
				$text = sprintf( __( 'But <strong>please do not worry</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> will set <em class="at-">example-email-at-youtserver-dot-com</em> as sender and set <em>this address</em> as Reply-To header.', 'wpes' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials' );
				break;
			default:
				$text = sprintf( __( 'You can fix this here, or you can let <a href="%s" target="_blank">WP-Email-Essentials</a> fix this automatically upon sending the email.', 'wpes' ), admin_url( 'tools.php' ) . '?page=wp-email-essentials' );
				break;
		}

		if ( basename( $_SERVER['PHP_SELF'] ) == 'admin.php' && @$_GET['page'] == 'wpcf7' ) {
			?>
			<script>
				jQuery(document).ready(function () {
					setTimeout(function () {
						var i = jQuery("#wpcf7-mail-sender,#wpcf7-mail-2-sender");
						if (i.length > 0) {
							var t = <?php print wp_json_encode( $text ); ?>,
								e = i.siblings('.config-error');

							if (e.length > 0) {
								if (e.is('ul')) {
									e.append('<li class="wpes-err-add">' + t + '</li>');
								} else {
									e.html(e.html() + '<br /><span class="wpes-err-add">' + t + '</span>');
								}
							}
						}
					}, 1000);

					var atdottify = function (rfc) {
						var email = getEmail(rfc);
						var newemail = email.replace('@', '-at-').replace(/\./g, '-dot-') + '@' + ((document.location.host).replace(/^www\./, ''));
						return rfc.replace(email, newemail);
					};

					var noreplyify = function (rfc) {
						var email = getEmail(rfc);
						var newemail = 'noreply' + '@' + ((document.location.host).replace(/^www\./, ''));
						return rfc.replace(email, newemail);
					};

					var defaultify = function (rfc) {
						var host = ((document.location.host).replace(/^www\./, ''));
						var email = getEmail(rfc);
						var newemail = <?php print wp_json_encode( self::wp_mail_from( $config['from_email'] ) ); ?>;
						if ((new RegExp('@' + host)).test(newemail))
							return rfc.replace(email, newemail);
						else
							return noreplyify(rfc);
					};

					var getEmail = function (rfc) {
						rfc = rfc.split('<');
						if (rfc.length < 2) {
							rfc.unshift('');
						}
						rfc = rfc[1].split('>');
						return rfc[0];
					};

					var i = jQuery("#wpcf7-mail-sender,#wpcf7-mail-2-sender");
					i.bind('keyup', function () {
						var e = jQuery(this).siblings('.config-error'), v = jQuery(this).val();
						if (e.length) {
							e.find('.wpes-err-add').find('em.default:nth(0)').text(noreplyify(v));
							e.find('.wpes-err-add').find('em.noreply:nth(0)').text(noreplyify(v));
							e.find('.wpes-err-add').find('em.at-:nth(0)').text(atdottify(v));
							e.find('.wpes-err-add').find('em:nth(1)').text(v);
						}
					}).trigger('keyup');

					jQuery(".wpes-err-add em").addClass('quote');
				});
			</script>
			<style>
							.wpes-err-add {

							}

							.wpes-err-add em {
								font-style: inherit;
							}

							.wpes-err-add em.quote {
								background: lightgray;
								font-family: monospace;
								font-weight: bold;
							}
			</style>
			<?php
		}
	}

	public static function ajax_get_ip() {
		print $_SERVER['REMOTE_ADDR'];
		exit;
	}

	/**
	 * This function takes a css-string and compresses it, removing
	 * unneccessary whitespace, colons, removing unneccessary px/em
	 * declarations etc.
	 *
	 * @param string $css
	 *
	 * @return string compressed css content
	 * @author Steffen Becker
	 */
	public static function minifyCss( $css ) {
		// some of the following functions to minimize the css-output are directly taken
		// from the awesome CSS JS Booster: https://github.com/Schepp/CSS-JS-Booster
		// all credits to Christian Schaefer: http://twitter.com/derSchepp
		// remove comments
		$css = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css );
		// backup values within single or double quotes
		preg_match_all( '/(\'[^\']*?\'|"[^"]*?")/ims', $css, $hit, PREG_PATTERN_ORDER );
		for ( $i = 0; $i < count( $hit[1] ); $i ++ ) {
			$css = str_replace( $hit[1][ $i ], '##########' . $i . '##########', $css );
		}
		// remove traling semicolon of selector's last property
		$css = preg_replace( '/;[\s\r\n\t]*?}[\s\r\n\t]*/ims', "}\r\n", $css );
		// remove any whitespace between semicolon and property-name
		$css = preg_replace( '/;[\s\r\n\t]*?([\r\n]?[^\s\r\n\t])/ims', ';$1', $css );
		// remove any whitespace surrounding property-colon
		$css = preg_replace( '/[\s\r\n\t]*:[\s\r\n\t]*?([^\s\r\n\t])/ims', ':$1', $css );
		// remove any whitespace surrounding selector-comma
		$css = preg_replace( '/[\s\r\n\t]*,[\s\r\n\t]*?([^\s\r\n\t])/ims', ',$1', $css );
		// remove any whitespace surrounding opening parenthesis
		$css = preg_replace( '/[\s\r\n\t]*{[\s\r\n\t]*?([^\s\r\n\t])/ims', '{$1', $css );
		// remove any whitespace between numbers and units
		$css = preg_replace( '/([\d\.]+)[\s\r\n\t]+(px|em|pt|%)/ims', '$1$2', $css );
		// shorten zero-values
		$css = preg_replace( '/([^\d\.]0)(px|em|pt|%)/ims', '$1', $css );
		// constrain multiple whitespaces
		$css = preg_replace( '/\p{Zs}+/ims', ' ', $css );
		// remove newlines
		$css = str_replace( [ "\r\n", "\r", "\n" ], '', $css );
		// Restore backupped values within single or double quotes
		for ( $i = 0; $i < count( $hit[1] ); $i ++ ) {
			$css = str_replace( '##########' . $i . '##########', $hit[1][ $i ], $css );
		}

		return $css;
	}
}
