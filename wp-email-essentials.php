<?php
/*
Plugin Name: WordPress Email Essentials
Description: A must-have plugin for WordPress to get your outgoing e-mails straightened out.
Plugin URI: https://bitbucket.org/rmpel/wp-email-essentials
Author: Remon Pel
Author URI: http://remonpel.nl
Version: 2.0.6
License: GPL2
Text Domain: Text Domain
Domain Path: Domain Path
*/

class WP_Email_Essentials
{
	static $message;
	static $error;
	static $debug;

	function __construct()
	{
		self::init();
	}

	public static function init()
	{
		add_action('init', function() {
			load_plugin_textdomain( 'wpes', false, dirname( plugin_basename( __FILE__ ) ) .'/lang' );
		});

		$config = self::get_config();
		add_action('phpmailer_init', array('WP_Email_Essentials', 'action_phpmailer_init'));
		if ($config['is_html']) {
			add_filter('wp_mail_content_type', create_function('', 'return "text/html";'));
			add_filter('wp_mail_charset', create_function('', 'return "UTF-8";'));
		}

		// set default from email and from name
		if ($config['from_email']) {
			// print "WPES FromMail: ". $config['from_email'] ."\n";
			add_filter('wp_mail_from', array('WP_Email_Essentials', 'filter_wp_mail_from'), 9999);
		}
		if ($config['from_name']) {
			// print "WPES FromName: ". $config['from_name'] ."\n";
			add_filter('wp_mail_from_name', array('WP_Email_Essentials', 'filter_wp_mail_from_name'), 9999);
		}

		add_filter('wp_mail', array('WP_Email_Essentials', 'action_wp_mail'));
		add_action('admin_menu', array('WP_Email_Essentials', 'admin_menu'), 10);

		add_action('admin_menu', array('WP_Email_Essentials', 'migrate_from_smtp_connect'), -10000);

		add_action('admin_footer', array('WP_Email_Essentials', 'maybe_inject_admin_settings'));

		add_filter('cfdb_form_data', array('WP_Email_Essentials', 'correct_cfdb_form_data_ip'));

		self::mail_key_registrations();

	}

	function correct_cfdb_form_data_ip($cf7) {
		// CF7 to DB tries variable X_FORWARDED_FOR which is never in use, Apache sets HTTP_X_FORWARDED_FOR
		// use our own method to get the remote_addr.
		$cf7->ip = self::server_remote_addr();
		return $cf7;
	}

	public static function server_remote_addr($return_htaccess_variable = false)
  {
    $possibilities = array(
      'HTTP_CF_CONNECTING_IP' => 'HTTP:CF-CONNECTING-IP',
      'HTTP_X_FORWARDED_FOR' => 'HTTP:X-FORWARDED-FOR',
      'REMOTE_ADDR' => false,
    );
    foreach ($possibilities as $option => $htaccess_variable) {
      if (isset($_SERVER[$option]) && trim($_SERVER[$option])) {
        $ip = explode(',', $_SERVER[$option]);
        return $return_htaccess_variable ? $htaccess_variable : end($ip);
      }
    }
    return $_SERVER['REMOTE_ADDR'];
  }

	public static function action_wp_mail($wp_mail)
	{
		$config = self::get_config();
		// print "WPES ". __LINE__ ."set" ."\n";
		self::wp_mail_from($config['from_email']);
		self::wp_mail_from_name($config['from_name']);

		$all_headers = array();

		if (is_string($wp_mail['headers'])) {
			$wp_mail['headers'] = explode("\n", $wp_mail['headers']);
		}
		if (!is_array($wp_mail['headers'])) {
			$wp_mail['headers'] = array();
		}
		// print "WPES ". __LINE__ ."raw headers" ."\n";
		// var_dump($wp_mail['headers']);

		$header_index = array();
		foreach ($wp_mail['headers'] as $i => $header) {
			preg_match('/([^:]+):(.*)$/U', $header, $match);
			$all_headers[strtolower(trim($match[1]))] = $match[2];
			$header_index[strtolower(trim($match[1]))] = $i;
		}

		if ($all_headers['from']) {
			// print "WPES ". __LINE__ ."headers has FROM: ". $all_headers['from'] ."\n";
			$from = self::rfc_decode($all_headers['from']);
			// print "WPES ". __LINE__ ."decoded:\n";
			// var_dumP($from);
			if ($from['email']) {
				// print "WPES ". __LINE__ ." set from mail" ."\n";
				self::wp_mail_from($from['email']);
			}
			if ($from['name']) {
				// print "WPES ". __LINE__ ." set from name" ."\n";
				self::wp_mail_from_name($from['name']);
			}
		}

		if (!array_key_exists('from', $header_index))
			$header_index['from'] = count($header_index);
		$wp_mail['headers'][$header_index['from']] = 'From: "' . self::wp_mail_from_name() . '" <' . self::wp_mail_from() . '>';

		// print "WPES ". __LINE__ ." headers now:\n";
		// var_dumP($wp_mail['headers']);

		if (!array_key_exists('reply-to', $header_index)) {
			// print "WPES ". __LINE__ ." Adding REPLY-TO:\n";
			$header_index['reply-to'] = count($header_index);
			$wp_mail['headers'][$header_index['reply-to']] = 'Reply-To: ' . self::wp_mail_from_name() . ' <' . self::wp_mail_from() . '>';
		}
		else {
			// print "WPES ". __LINE__ ." Already have REPLY-TO:\n";
		}

		// if ($config['errors_to']) {
		// 	if(!array_key_exists('errors-to', $header_index)) {
		// 		// print "WPES ". __LINE__ ." Adding ERRORS-TO:\n";
		// 		$header_index['errors-to'] = count($header_index);
		// 		$wp_mail['headers'][$header_index['errors-to']] = 'Errors-To: '. trim($config['errors_to']);
		// 	}
		// 	else {
		// 		// print "WPES ". __LINE__ ." Already have REPLY-TO:\n";
		// 	}
		// 	if (!array_key_exists('Return-Path', $header_index)) {
		// 		// print "WPES ". __LINE__ ." Adding Return-Path:\n";
		// 		$header_index['Return-Path'] = count($header_index);
		// 		$wp_mail['headers'][$header_index['Return-Path']] = 'Return-Path: '. trim($config['errors_to']);
		// 	}
		// 	else {
		// 		// print "WPES ". __LINE__ ." Already have REPLY-TO:\n";
		// 	}
		// }

		// print "WPES ". __LINE__ ." headers now:\n";
		// var_dumP($wp_mail['headers']);

		if ($config['make_from_valid']) {
			// print "WPES ". __LINE__ ." Validifying FROM:\n";
			self::wp_mail_from(self::a_valid_from(self::wp_mail_from(), $config['make_from_valid']));
			$wp_mail['headers'][$header_index['from']] = 'From: "' . self::wp_mail_from_name() . '" <' . self::a_valid_from(self::wp_mail_from(), $config['make_from_valid']) . '>';
		}

		// print "WPES ". __LINE__ ." headers now:\n";
		// var_dumP($wp_mail['headers']);

		return $wp_mail;
	}

	public static function a_valid_from($invalid_from, $method) {
		$url = get_bloginfo('url');
		$host = parse_url($url, PHP_URL_HOST);
		$host = preg_replace('/^www[0-9]*\./', '', $host);
		$config = self::get_config();

		if ( !preg_match( '/@'. $host .'$/', $invalid_from) ) {
			switch ($method) {
				case '-at-':
					return strtr($invalid_from, array('@' => '-at-', '.' => '-dot-')) . '@'. $host;
				case 'default':
					$defmail = WP_Email_Essentials::wp_mail_from($config['from_email']);
					if (false !== strpos($defmail, '@' . $host)) {
						return WP_Email_Essentials::wp_mail_from($config['from_email']);
					}
					// if test fails, bleed through to noreply, so leave this order in tact!
				case 'noreply':
					return 'noreply@'. $host;
				default:
					return $invalid_from;
			}
		}
		return $invalid_from;
	}

	public static function action_phpmailer_init(&$mailer)
	{
		/** @var phpMailer $mailer */
		$config = self::get_config();
		if ($config['smtp']) {
			$mailer->IsSMTP();
			list($host, $port) = explode(':', $config['smtp']['host'] . ':-1');
			$mailer->Host = $host;
			if ($port > 0)
				$mailer->Port = $port;

			if (isset($config['smtp']['username'])) {
				$mailer->SMTPAuth = true;
				$mailer->Username = $config['smtp']['username'];
				$mailer->Password = $config['smtp']['password'];
				if (isset($config['smtp']['secure']) && $config['smtp']['secure']) {
					$mailer->SMTPSecure = trim($config['smtp']['secure'], '-');
				}
				else {
					$mailer->SMTPAutoTLS = false;
				}
				if (true === WPES_ALLOW_SSL_SELF_SIGNED || substr($config['smtp']['secure'], -1, 1) == '-') {
					$mailer->SMTPOptions = array(
				    'ssl' => array(
				        'verify_peer' => false,
				        'verify_peer_name' => false,
				        'allow_self_signed' => true
				    )
					);
				}
			}
		}

		// print "WPES MAILER ". __LINE__ ." set FROM: ". self::wp_mail_from() ."\n";
		$mailer->Sender = self::wp_mail_from();

		$mailer->Body = self::preserve_weird_url_display($mailer->Body);

		if ($config['is_html']) {
			$mailer->Body = self::maybe_convert_to_html($mailer->Body, $mailer->Subject, $mailer);
			$css = apply_filters_ref_array('wpes_css', array('', &$mailer));

			if ($config['css_inliner']) {
				require_once dirname(__FILE__) . '/lib/cssInliner.class.php';
				$cssInliner = new cssInliner($mailer->Body, $css);
				$mailer->Body = $cssInliner->convert();
			}
			$mailer->isHTML(true);
		}

		if ($config['alt_body']) {
			$body = $mailer->Body;
			$btag = strpos($body, '<body');
			if (false !== $btag) {
				$bodystart = strpos($body, '>', $btag);
				$bodytag = substr($body, $btag, $bodystart - $btag + 1);
				list($body, $junk) = explode('</body', $body);
				list($junk, $body) = explode($bodytag, $body);
			}

			// links to link-text+url
			// example; <a href="http://nu.nl">Go to NU.nl</a> becomes:  Go to Nu.nl ( http://nu.nl )
			$body = preg_replace('/<a.+href=("|\')([^\\1]+)\\1>([^<]+)<\/a>/U', '\3 (\2)', $body);

			// remove all HTML except line breaks
			$body = strip_tags($body, '<br>');

			// replace all forms of breaks, list items and table row endings to new-lines
			$body = preg_replace('/<br[\/ ]*>/Ui', "\n", $body);
			$body = preg_replace('/<\/( li|tr )>/Ui', '</\1>' . "\n", $body);

			// set the alternate body
			$mailer->AltBody = $body;
		}

		if ($_POST && $_POST['form_id'] == 'wp-email-essentials' && $_POST['op'] == __('Send sample mail', 'wpes') ) {
			$mailer->SMTPDebug = false;
		}

		$mailer->ContentType .= '; charset=' . $mailer->CharSet;

		$from = self::wp_mail_from();

		// S/MIME Signing
		if ($config['enable_smime'] && $id = self::get_smime_identity($from)) {
			list($crt, $key, $pass) = $id;
			$mailer->sign($crt, $key, $pass);
		}

		// DEBUG output

		if ($_POST && $_POST['form_id'] == 'wp-email-essentials' && $_POST['op'] == __('Print debug output of sample mail', 'wpes') ) {
			$mailer->SMTPDebug = true;
			print '<h2>'. __('Dump of PHP Mailer object', 'wpes') .'</h2><pre>';
			var_dumP($mailer);
			exit;
		}
	}

	private static function preserve_weird_url_display($html)
	{
		if (preg_match('/<(http(s)?:\/\/[^>]+)>/', $html, $m)) {
			$url = $m[1];
			if (defined('WPES_CLEAN_LOGIN_RESET_URL') && WPES_CLEAN_LOGIN_RESET_URL === true) {
				return str_replace('<' . $url . '>', $url, $html);
			}
			return str_replace('<' . $url . '>', '[' . $url . ']', $html);
		}
		return $html;
	}


	private static function maybe_convert_to_html($might_be_text, $subject, $mailer)
	{
		$html_preg = '<(br|a|p|body|table|div|span|body|html)';
		if (preg_match("/$html_preg/", $might_be_text)) {
			// probably html
			$should_be_html = $might_be_text;
		} else {
			$should_be_html = nl2br(trim($might_be_text));
		}

		// should have some basic HTML now, otherwise, add a P
		if (!preg_match("/$html_preg/", $should_be_html)) {
			$should_be_html = '<p>' . $should_be_html . '</p>';
		}

		// now check for HTML evelope
		if (false === strpos($should_be_html, '<html')) {
			$should_be_html = '<html><head>' . apply_filters_ref_array('wpes_head', array('<title>' . $subject . '</title>', &$mailer)) . '</head><body>' . apply_filters_ref_array('wpes_body', array($should_be_html, &$mailer)) . '</body></html>';
		}
		$should_be_html = htmlspecialchars_decode(htmlentities($should_be_html));

		return $should_be_html;
	}


	public static function wp_mail_from($from = null)
	{
		static $store;
		if ($from) {
			$store = $from;
		}
		return $store;
	}

	public static function wp_mail_from_name($from = null)
	{
		static $store;
		if ($from) {
			$store = $from;
		}
		return $store;
	}

	public static function filter_wp_mail_from($from)
	{
		return self::wp_mail_from();
	}

	public static function filter_wp_mail_from_name($from)
	{
		return self::wp_mail_from_name();
	}

	public static function get_config($raw = false)
	{
		$defaults = array(
			'smtp' => false,
			'from_email' => get_bloginfo('admin_email'),
			'from_name' => self::get_hostname_by_blogurl(),
			'is_html' => false,
			'alt_body' => false,
			'css_inliner' => false,
			'enable_smime' => false,
			'errors_to' => 'postmaster@clearsite.nl',
		);

		$defaults = apply_filters('wpes_defaults', $defaults);

		$settings = get_option('wp-email-essentials', $defaults);
		if (!$raw) {
			$settings = apply_filters('wpes_settings', $settings);

			$settings['certificate_folder'] = $settings['certfolder'];
			if ('/' !== substr($settings['certificate_folder'], 0, 1)) {
				$settings['certificate_folder'] = rtrim(ABSPATH, '/') . '/' . $settings['certificate_folder'];
			}
		}
		return array_merge($defaults, $settings);
	}

	private static function set_config($values, $raw = false)
	{
		if ($raw) {
			return update_option('wp-email-essentials', $values);
		}

		$values = stripslashes_deep($values);
		$settings = self::get_config();
		if ($values['smtp-enabled']) {
			$settings['smtp'] = array(
				'secure' => $values['secure'],
				'host' => $values['host'],
				'username' => $values['username'],
				'password' => ($values['password'] == str_repeat('*', strlen($values['password'])) && $settings['smtp']) ? $settings['smtp']['password'] : $values['password'],
			);
		} else {
			$settings['smtp'] = false;
		}
		$settings['from_name'] = $values['from_name'] ?: $settings['from_name'];
		$settings['from_email'] = $values['from_email'] ?: $settings['from_email'];
		$settings['is_html'] = $values['is_html'] ? true : false;
		$settings['css_inliner'] = $values['css_inliner'] ? true : false;
		$settings['alt_body'] = $values['alt_body'] ? true : false;
		$settings['SingleTo'] = $values['SingleTo'] ? true : false;
		$settings['enable_smime'] = $values['enable_smime'];
		$settings['certfolder'] = $values['certfolder'];
		$settings['make_from_valid'] = $values['make_from_valid'];
		$settings['errors_to'] = $values['errors_to'];
		update_option('wp-email-essentials', $settings);
	}

	public static function get_hostname_by_blogurl()
	{
		$url = get_bloginfo('url');
		$url = parse_url($url);
		return $url['host'];
	}

	private static function rfc_decode($rfc)
	{
		// $rfc might just be an e-mail address
		if (is_email($rfc)) {
			return array('name' => $rfc, 'email' => $rfc);
		}

		// $rfc is not an email, the RFC format is:
		//  "Name Surname Anything here" <email@addr.ess>
		// but quotes are optional...
		//  Name Surname Anything here <email@addr.ess>
		// is considered valid as well
		//
		// considering HTML, <email@addr.ess> is a tag so we can strip that out with strip_tags
		// and the remainder is the name-part.
		$name_part = strip_tags($rfc);
		// remove the name-part from the original and the email part is known
		$email_part = str_replace($name_part, '', $rfc);
		// strip illegal characters;
		$name_part = trim($name_part, ' "');
		// the name part could have had escaped quotes (like "I have a quote \" here" <some@email.com> )
		$name_part = stripslashes($name_part);

		$email_part = trim($email_part, ' <>');
		// verify :)
		if (is_email($email_part)) {
			return array('name' => stripslashes($name_part), 'email' => $email_part);
		}
		return false;
	}

	private static function rfc_encode($email_array)
	{
		if (!$email_array['name'])
			return $email_array['email'];

		$email_array['name'] = json_encode($email_array['name']);
		$return = trim(sprintf("%s <%s>", $email_array['name'], $email_array['email']));
		return $return;
	}

	public static function admin_menu()
	{
		add_menu_page( 'WP-Email-Essentials', 'CLS Email-Essent', 'manage_options', 'wp-email-essentials', array('WP_Email_Essentials', 'admin_interface'), 'dashicons-email-alt' );

		if ($_GET['page'] == 'wp-email-essentials' && $_POST && $_POST['form_id'] == 'wp-email-essentials') {
			switch ($_POST['op']) {
				case __('Save settings', 'wpes'):
					$config = WP_Email_Essentials::get_config();
					$host = parse_url(get_bloginfo('url'), PHP_URL_HOST);
					$host = preg_replace('/^www[0-9]*\./', '', $host);
					$defmail = WP_Email_Essentials::wp_mail_from($_POST['settings']['from_email']);
					if ('default' == $_POST['settings']['make_from_valid'] && false === strpos($defmail, '@' . $host)) {
						$_POST['settings']['make_from_valid'] = 'noreply';
					}
					self::set_config($_POST['settings']);
					self::$message = __('Settings saved.', 'wpes');
					break;
				case __('Print debug output of sample mail', 'wpes'):
				case __('Send sample mail', 'wpes'):
					ob_start();
					self::$debug = true;
					$result = wp_mail(get_option('admin_email', false), __('Test-email', 'wpes'), self::dummy_content());
					self::$debug = ob_get_clean();
					if ($result) {
						self::$message = sprintf(__('Mail sent to %s', 'wpes'), get_option('admin_email', false));
					} else {
						self::$error = sprintf(__('Mail NOT sent to %s', 'wpes'), get_option('admin_email', false));
					}
					break;
			}
		}
		if (@$_GET['page'] == 'wp-email-essentials' && @$_GET['iframe'] == 'content') {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$mailer = new PHPMailer;
			$config = WP_Email_Essentials::get_config();
			$css = apply_filters_ref_array('wpes_css', array('', &$mailer));
			$subject = __('Sample email subject', 'wpes');
			$mailer->Subject = $subject;
			$body = WP_Email_Essentials::dummy_content();
			header("Content-Type: html; charset=utf-8");
			?>
			<html>
		<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><?php
			print apply_filters_ref_array('wpes_head', array('<title>' . $subject . '</title>', &$mailer));
			?></head>
		<body><?php
		$bodyhtml = utf8_decode(apply_filters_ref_array('wpes_body', array($body, &$mailer)));

		if ($config['css_inliner']) {
			require_once dirname(__FILE__) . '/lib/cssInliner.class.php';
			$cssInliner = new cssInliner($bodyhtml, $css);
			$bodyhtml = $cssInliner->convert();
		}
		$bodyhtml = WP_Email_Essentials::cid_to_image($bodyhtml, $mailer);
		print $bodyhtml;
		?></body></html><?php
			exit;
		}

		add_submenu_page('wp-email-essentials', 'WP-Email-Essentials - Alternative Admins', 'Alternative admins', 'manage_options', 'wpes-admins', array('WP_Email_Essentials', 'admin_interface_admins'));
		if (@$_GET['page'] == 'wpes-admins' && $_POST && @$_POST['form_id'] == 'wpes-admins') {
			switch ($_POST['op']) {
				case __('Save settings', 'wpes'):
					$keys = $_POST['settings']['keys'];
					$keys = array_filter($keys, function ($el) {
						return filter_var($el, FILTER_VALIDATE_EMAIL);
					});
					update_option('mail_key_admins', $keys);
					self::$message = __('Alternative Admins list saved.', 'wpes');

					$regexps = $_POST['settings']['regexp'];
					$list = array();

					$__regex = "/^\/[\s\S]+\/$/";
					foreach ($regexps as $entry) {
						if (preg_match($__regex, $entry['regexp']))
							$list[$entry['regexp']] = $entry['key'];
					}

					update_option('mail_key_list', $list);
					self::$message .= " ". __('Subject-RegExp list saved.', 'wpes');

					break;
			}
		}
	}

	static function admin_interface()
	{
		include 'admin-interface.php';
	}

	static function admin_interface_admins()
	{
		include 'admin-admins.php';
	}

	public static function test()
	{
		$test = self::rfc_decode('ik@remonpel.nl');
		// should return array( 'name' => 'ik@remonpel.nl', 'email' => 'ik@remonpel.nl' )
		if ($test['name'] == 'ik@remonpel.nl' && $test['email'] == 'ik@remonpel.nl') {
			echo "simple email address verified<br />\n";
		} else {
			echo "simple email address FAILED<br />\n";
		}

		$test = self::rfc_decode('Remon Pel <ik@remonpel.nl>');
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ($test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl') {
			echo "RFC2822 no quotes email address verified<br />\n";
		} else {
			echo "RFC2822 no quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode('"Remon Pel" <ik@remonpel.nl>');
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ($test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl') {
			echo "RFC2822 with quotes email address verified<br />\n";
		} else {
			echo "RFC2822 with quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode('    "   Remon Pel   " <ik@remonpel.nl>');
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ($test['name'] == 'Remon Pel' && $test['email'] == 'ik@remonpel.nl') {
			echo "RFC2822 too many spaces - not valid RFC but still parses? verified<br />\n";
		} else {
			echo "RFC2822 too many spaces - not valid RFC but still parses? FAILED<br />\n";
		}

		exit;
	}

	public static function migrate_from_smtp_connect()
	{
		$plugin = 'smtp-connect/smtp-connect.php';
		if (is_plugin_active($plugin)) {
			// plugin active, migrate
			$smtp_connect = get_option('smtp-connect', array());
			if ($smtp_connect['enabled']) {
				$smtp_connect['smtp-enabled'] = true;
			}
			$smtp_connect['host'] = $smtp_connect['Host'];
			unset($smtp_connect['Host']);
			$smtp_connect['username'] = $smtp_connect['Username'];
			unset($smtp_connect['Username']);
			$smtp_connect['password'] = $smtp_connect['Password'];
			unset($smtp_connect['Password']);
			self::set_config($smtp_connect);

			// deactivate conflicting plugin
			deactivate_plugins($plugin, false);

			// wordpress still thinks the plugin is active, do it the hard way
			$active = get_option('active_plugins', array());
			unset($active[array_search($plugin, $active)]);
			update_option('active_plugins', $active);

			// log the deactivation.
			update_option('recently_activated', array($plugin => time()) + (array)get_option('recently_activated'));
		}
	}

	public static function dummy_content()
	{
		return '<h1>Sample Email Body</h1><p>Some råndôm text Lorem Ipsum is <b>bold simply dummy</b> text of the <strong>strong printing and typesetting</strong> industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p><h2>A header-2</h2><p>Some more text</p><h3>A header-3</h3><ul><li>A list - unordered, item 1</li><li>Item 2</li></ul><h4>A header-4</h4><ol><li>A list - ordered, item 1</li><li>Item 2</li></ol>';
	}

	public static function cid_to_image($html, $mailer)
	{
		foreach ($mailer->getAttachments() as $attachment) {
			if ($attachment[7]) {
				$html = str_replace('cid:' . $attachment[7], 'data:' . $attachment[4] . ';' . $attachment[3] . ',' . base64_encode(file_get_contents($attachment[0])), $html);
			}
		}
		return $html;
	}

	function adminNotices()
	{
		$config = WP_Email_Essentials::get_config();
		$onpage = is_admin() && ($_GET['page'] == 'wp-email-essentials');

		$from = $config['from_email'];
		if (!$from) {
			$url = add_query_arg('page', 'wp-email-essentials', admin_url('tools.php'));
			if ($onpage) {
				$class = "updated";
				$message = __('WP-Email-Essentials is not yet configured. Please fill out the form below.', 'wpes');
				echo "<div class='$class'><p>$message</p></div>";
			} else {
				$class = "error";
				$message = sprintf(__('WP-Email-Essentials is not yet configured. Please go <a href="%s">here</a>.', 'wpes'), $url);
				echo "<div class='$class'><p>$message</p></div>";
			}
			return;
		}


		// certfolder == setting, certificate_folder == real path;
		if ($config['enable_smime'] && isset($config['certfolder']) && $config['certfolder']) {
			if (is_writable($config['certificate_folder']) && !get_option('suppress_smime_writable')) {
				$class = "error";
				$message = __('The S/MIME certificate folder is writable. This is Extremely insecure. Please reconfigure, make sure the folder is not writable by Apache. If your server is running suPHP, you cannot make the folder read-only for apache. Please contact your hosting provider and ask for a more secure hosting package, one not based on suPHP.', 'wpes');
				echo "<div class='$class'><p>$message</p></div>";
			}

			if (false !== strpos(realpath($config['certificate_folder']), realpath(ABSPATH))) {
				$class = "error";
				$message = sprintf(__('The S/MIME certificate folder is inside the webspace. This is Extremely insecure. Please reconfigure, make sure the folder is outside the website-root %s.', 'wpes'), ABSPATH);
				echo "<div class='$class'><p>$message</p></div>";
			}
		}

		// certfolder == setting, certificate_folder == real path;
		if ($config['enable_smime'] && $onpage && !function_exists('openssl_pkcs7_sign')) {
			$class = "error";
			$message = __('The openssl package for PHP is not installed, incomplete or broken. Please contact your hosting provider. S/MIME signing is NOT available.', 'wpes');
			echo "<div class='$class'><p>$message</p></div>";
		}

		// certfolder == setting, certificate_folder == real path;
		if ($config['enable_smime'] && $onpage && isset($config['smtp']['host']) && (false !== strpos($config['smtp']['host'], 'mandrillapp') || false !== strpos($config['smtp']['host'], 'sparkpostmail')) && function_exists('openssl_pkcs7_sign')) {
			$class = "error";
			$message = __('Services like MandrillApp or SparkPostMail will break S/MIME signing. Please use a different SMTP-service if signing is required.', 'wpes');
			echo "<div class='$class'><p>$message</p></div>";
		}

		// default mail identity existance
		if ($config['enable_smime'] && $onpage && !self::get_smime_identity($from)) {
			$rawset = self::get_config(true);
			$set = $rawset['certfolder'];
			$rawset['certfolder'] = __DIR__ . '/.smime';
			self::set_config($rawset);
			if (self::get_smime_identity($from)) {
				$class = "error";
				$message = sprintf(__('There is no certificate for the default sender address <code>%s</code>. The required certificate is supplied with this plugin. Please copy it to the correct folder.', 'wpes'), $from);
				echo "<div class='$class'><p>$message</p></div>";
			} else {
				$class = "error";
				$message = sprintf(__('There is no certificate for the default sender address <code>%s</code>. Start: <a href="https://www.comodo.com/home/email-security/free-email-certificate.php" target="_blank">here</a>.', 'wpes'), $from);
				echo "<div class='$class'><p>$message</p></div>";
			}

			$rawset['certfolder'] = $set;
			self::set_config($rawset, true);
		}

	}

	public static function list_smime_identities()
	{
		$c = self::get_config();
		$ids = array();
		$certificate_folder = $c['certificate_folder'];
		if (is_dir($certificate_folder)) {
			$files = glob($certificate_folder . '/*.crt');
			foreach ($files as $file) {
				if (is_file($file) && is_file(preg_replace('/\.crt$/', '.key', $file))) {
					$ids[basename(preg_replace('/\.crt$/', '', $file))] = array($file, preg_replace('/\.crt$/', '.key', $file), trim(@file_get_contents(preg_replace('/\.crt$/', '.pass', $file))));
				}
			}
		}
		return $ids;
	}

	public static function get_smime_identity($email)
	{
		$ids = self::list_smime_identities();
		if (isset($ids[$email])) {
			return $ids[$email];
		}
		return false;
	}


	public static function alternative_to($email)
	{
		$admin_email = get_option('admin_email');

		// make sure we have a list of emails, not a single email
		if (!is_array($email['to'])) {
			$email['to'] = array($email['to']);
		}

		// find the admin address
		$found_mail_item_number = -1;
		foreach ($email['to'] as $i => $email_address) {
			$decoded = self::rfc_decode($email_address);
			if ($decoded['email'] == $admin_email) {
				$found_mail_item_number = $i;
			}
		}
		if ($found_mail_item_number == -1) {
			// var_dump($email, __LINE__);exit;
			return $email;
		}

		// $to is our found admin addressee
		$to = &$email['to'][ $found_mail_item_number ];
		$to = self::rfc_decode($to);

		// this message is sent to the system admin
		// we might want to send this to a different admin
		if ($key = self::get_mail_key($email['subject'])) {
			// we were able to determine a mailkey.
			$admins = get_option('mail_key_admins', array());
			if (@$admins[$key]) {
				$the_admin = self::rfc_decode($admins[$key]);
				if ($the_admin['name'] == $the_admin['email'] && $to['name'] != $to['email']) {
					// not rfc, just email, but the original TO has a real name
					$the_admin['name'] = $to['name'];
				}
				$to = self::rfc_encode($the_admin);
				// var_dump($email, __LINE__);exit;
				return $email;
			}
			// known key, but no email set
			// we revert to the DEFAULT admin_email, and prevent matching against subjects
			// var_dump($email, __LINE__);exit;
			if (is_array($to) && array_key_exists('email', $to))
				$to = self::rfc_encode($to);
			return $email;
		}

		// perhaps we have a regexp?
		$admin = self::mail_subject_match($email['subject']);
		if ($admin) {
			$the_admin = self::rfc_decode($admin);
			if ($the_admin['name'] == $the_admin['email'] && $to['name'] != $to['email']) {
				// not rfc, just email, but the original TO has a real name
				$the_admin['name'] = $to['name'];
			}
			$to = self::rfc_encode($the_admin);
			// var_dump($email, __LINE__);exit;
			return $email;
		}

		// sorry, we failed :(
		$fails = get_option('mail_key_fails', array());
		if ($fails)
			$fails = array_combine($fails, $fails);
		$fails[$email['subject']] = $email['subject'];
		$fails = array_filter($fails, function ($item) {
			return !WP_Email_Essentials::mail_subject_match($item) && !WP_Email_Essentials::get_mail_key($item);
		});
		update_option('mail_key_fails', array_values($fails));

		// var_dump($email, __LINE__);exit;
		$to = self::rfc_encode($to);
		return $email;
	}

	public static function get_mail_key($subject)
	{
		// got a filter/action name?
		$mail_key = self::current_mail_key();
		if ($mail_key) {
			self::current_mail_key('*CLEAR*');
			self::log("$subject matched to $mail_key by filter/action");
		} else {
			$mail_key = self::mail_subject_database($subject);
			if ($mail_key) {
				self::log("$subject matched to $mail_key by subject-matching known subjects");
			}
		}

		return $mail_key;
	}

	public static function mail_key_database()
	{
		// supported;
		$wp_filters = array('automatic_updates_debug_email', 'auto_core_update_email', 'comment_moderation_recipients', 'comment_notification_recipients');

		// unsupported until added, @see wp_mail_key.patch, matched by subject, @see self::mail_subject_database
		$unsupported_wp_filters = array('new_user_registration_admin_email', 'password_lost_changed_email', 'password_reset_email', 'password_changed_email');

		return array_merge($wp_filters, $unsupported_wp_filters);
	}

	public static function mail_subject_database($lookup)
	{
		$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

		// FULL TEXT LOOKUP
		$keys = array(
			// wp, do NOT use own text-domain here, this construction is here because these are WP translated strings
			sprintf(__('[%s] New User Registration'), $blogname) => 'new_user_registration_admin_email',
			sprintf(__('[%s] Password Reset'), $blogname) => 'password_reset_email', // wp 4.5 +
			sprintf(__('[%s] Password Changed'), $blogname) => 'password_changed_email', // wp 4.5 +
			sprintf(__('[%s] Password Lost/Changed'), $blogname) => 'password_lost_changed_email', // wp < 4.5
		);

		$key = @$keys[$lookup];

		if ($key)
			return $key;

		// prepared just in case system fails.
		// // REGEXP_LOOKUP
		// $regexp_keys = array(
		// 	// keys here are modified versions of the original gettext-string.
		// 	sprintf(__('/\[%1\$s\] Please moderate: ".+"/'), $blogname) => 'comment_moderation_recipients', // pluggable.php:1616
		// 	sprintf(__('/\[%1\$s\] Comment: ".+"/'), $blogname) => 'comment_notification_recipients', // pluggable.php:1454
		// );

		// foreach ($regexp_keys as $regexp => $key) {
		// 	if (preg_match($regexp, $lookup)) {
		// 		return $key;
		// 	}
		// }

		return false;
	}

	public static function mail_subject_match($subject)
	{
		$store = get_option('mail_key_list', array());
		foreach ($store as $regexp => $mail_key) {
			if (preg_match($regexp, $subject))
				return $mail_key;
		}
		return false;
	}

	public static function mail_key_registrations()
	{
		// this works on the mechanics that prior to sending an email, a filter or actions is hooked, a make-shift mail key
		// actions and filters are equal to wordpress, but handled with or without return values.
		foreach (self::mail_key_database() as $filter_name) {
			add_filter($filter_name, array('WP_Email_Essentials', 'now_sending___'));
		}
	}

	private static function current_mail_key($set = null)
	{
		static $mail_key;
		if ($set) {
			if ($set == '*CLEAR*') $set = false;
			$mail_key = $set;
		}
		return $mail_key;
	}

	public static function now_sending___($value)
	{
		self::current_mail_key(current_filter());
		return $value;
	}

	public function log($text)
	{
		// error_log( $text );
	}

	public static function maybe_inject_admin_settings()
	{
		$host = parse_url( get_bloginfo('url'), PHP_URL_HOST );
		if (basename($_SERVER['PHP_SELF']) == 'options-general.php' && !@$_GET['page']) {
			?>
			<script>
				jQuery("#admin_email").after('<p class="description"><?php print sprintf(__('You can configure alternative administrators <a href="%s">here</a>.', 'wpes'), add_query_arg(array('page' => 'wpes-admins'), admin_url('admin.php'))); ?></p>');
			</script>
			<?php
		}

		$config = self::get_config();
		switch ($config['make_from_valid']) {
			case 'noreply':
				$text = sprintf(__('But <strong>please do not worry</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> will set <em class="noreply">noreply@%s</em> as sender and set <em>this email address</em> as Reply-To header.', 'wpes'), admin_url('tools.php') .'?page=wp-email-essentials', $host);
				break;
			case 'default':
				$text = sprintf(__('But <strong>please do not worry</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> will set <em class="default">%s</em> as sender and set <em>this email address</em> as Reply-To header.', 'wpes'), admin_url('tools.php') .'?page=wp-email-essentials', WP_Email_Essentials::wp_mail_from($config['from_email']));
				break;
			case '-at-':
				$text = sprintf(__('But <strong>please do not worry</strong>! <a href="%s" target="_blank">WP-Email-Essentials</a> will set <em class="at-">example-email-at-youtserver-dot-com</em> as sender and set <em>this address</em> as Reply-To header.', 'wpes'), admin_url('tools.php') .'?page=wp-email-essentials');
				break;
			default:
				$text = sprintf(__('You can fix this here, or you can let <a href="%s" target="_blank">WP-Email-Essentials</a> fix this automatically upon sending the email.', 'wpes'), admin_url('tools.php') .'?page=wp-email-essentials');
				break;
		}

		if (basename($_SERVER['PHP_SELF']) == 'admin.php' && @$_GET['page'] == 'wpcf7') {
			?><script>
			jQuery(document).ready(function(){
				setTimeout(function(){
					var i = jQuery("#wpcf7-mail-sender,#wpcf7-mail-2-sender");
					if (i.length > 0) {
						var t = <?php print json_encode($text); ?>,
					  	  e = i.siblings('.config-error');

						if ( e.length > 0) {
							if (e.is('ul')) {
								e.append('<li class="wpes-err-add">' + t + '</li>');
							}
							else {
								e.html( e.html() + '<br /><span class="wpes-err-add">' + t + '</span>');
							}
						}
					}
				}, 1000);

				var atdottify = function( rfc ) {
					var email = getEmail(rfc);
					var newemail = email.replace('@', '-at-').replace(/\./g, '-dot-') + '@' + ( (document.location.host).replace(/^www\./, '') );
					return rfc.replace(email, newemail)	;
				}

				var noreplyify = function( rfc ) {
					var email = getEmail(rfc);
					var newemail = 'noreply' + '@' + ( (document.location.host).replace(/^www\./, '') );
					return rfc.replace(email, newemail)	;
				}

				var defaultify = function( rfc ) {
					var host = ( (document.location.host).replace(/^www\./, '') );
					var email = getEmail(rfc);
					var newemail = <?php print json_encode(WP_Email_Essentials::wp_mail_from($config['from_email'])); ?>;
					if ( (new RegExp('@' + host)).test( newemail ))
						return rfc.replace(email, newemail)	;
					else
						return noreplyify( rfc );
				}

				var getEmail = function( rfc ) {
					rfc = rfc.split('<');
					if (rfc.length < 2) {
						rfc.unshift('');
					}
					rfc = rfc[1].split('>');
					return rfc[0];
				}

				var i = jQuery("#wpcf7-mail-sender,#wpcf7-mail-2-sender");
				i.bind('keyup', function() {
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
		</style><?php
		}
	}
}

$wp_email_essentials = new WP_Email_Essentials();
add_action('admin_notices', array($wp_email_essentials, 'adminNotices'));
add_filter('wp_mail', array('WP_Email_Essentials', 'alternative_to'));

class WP_Email_Essentials_History {
	public static function getInstance() {
		static $instance;
		if (!$instance) {
			$instance = new self();
		}
		return $instance;
	}

	public function __construct() {
		self::init();
	}

	private static function last_insert( $set = null ) {
		static $id;
		if ($set)
			$id = $set;
		return $id;
	}

	private static function init() {
		/** mail history */
		if (get_option('wpes_hist_rev', 0) < 1) {
			global $wpdb;
			$wpdb->query("CREATE TABLE `{$wpdb->prefix}wpes_hist` (
			  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `sender` varchar(256) NOT NULL DEFAULT '',
			  `recipient` varchar(256) NOT NULL DEFAULT '',
			  `subject` varchar(256) NOT NULL DEFAULT '',
			  `headers` text NOT NULL,
			  `body` text NOT NULL,
			  `alt_body` text NOT NULL,
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
			)");
			update_option('wpes_hist_rev', 1);
		}

		add_action('phpmailer_init', array('WP_Email_Essentials_History', 'phpmailer_init'), 10000000000);
		add_filter('wp_mail', array('WP_Email_Essentials_History', 'wp_mail'), 10000000000);
		add_action('wp_mail_failed', array('WP_Email_Essentials_History', 'wp_mail_failed'), 10000000000);

		add_action('shutdown', array('WP_Email_Essentials_History', 'shutdown'));

		add_action('admin_menu', array('WP_Email_Essentials_History', 'admin_menu'));
	}

	public static function shutdown() {
		global $wpdb;
		$wpdb->query("DELETE FROM `{$wpdb->prefix}wpes_hist` WHERE thedatetime <  NOW() - INTERVAL 1 MONTH");
	}

	public static function admin_menu() {
		add_submenu_page('wp-email-essentials', 'WP-Email-Essentials - Email History', 'Email History', 'manage_options', 'wpes-emails', array('WP_Email_Essentials_History', 'admin_interface'));
	}

	public static function admin_interface()
	{
		include 'admin-emails.php';
	}

	public static function phpmailer_init(PHPMailer $phpmailer) {
		global $wpdb;
		$data = json_encode($phpmailer, JSON_PRETTY_PRINT);
		$recipient = implode(',', $phpmailer->getToAddresses());
		$sender = $phpmailer->Sender ?: $phpmailer->from_name .'<'. $phpmailer->from_email .'>';

		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = 1, alt_body = %s, debug = %s WHERE ID = %d AND subject = %s LIMIT 1", $phpmailer->AltBody, $data, self::last_insert(), $phpmailer->Subject ) );
	}

	public static function wp_mail($data) {
		global $wpdb;
		//  'to', 'subject', 'message', 'headers', 'attachments'
		extract($data);
		$from = '';
		foreach($headers as $header) {
			if (preg_match('/^From:(.+)$/', $header, $m)) {
				$from = trim($m[1]);
			}
		}
		$_headers = trim(implode("\n", $headers));
		$wpdb->query( $wpdb->prepare( "INSERT INTO `{$wpdb->prefix}wpes_hist` (status, sender, recipient, subject, headers, body, thedatetime) VALUES (0, %s, %s, %s, %s, %s, %s);", $from, is_array($to) ? implode(',', $to) : $to, $subject, $_headers, $message, mysql2date('Y-m-d H:i:s', current_time( 'timestamp' ) ) ) );
		self::last_insert( $wpdb->insert_id );

		return $data;
	}

	public static function wp_mail_failed($error) {
		global $wpdb;
		$data = $error->get_error_data();
		$errormsg = $error->get_error_message();
		if (!$data) {
			$errormsg = 'Unknown error';
		}
		//  'to', 'subject', 'message', 'headers', 'attachments'
		$wpdb->query( $wpdb->prepare( "UPDATE `{$wpdb->prefix}wpes_hist` SET status = 2, errinfo = CONCAT(%s, errinfo) WHERE ID = %d LIMIT 1",  $errormsg ."\n", self::last_insert() ) );
	}


}

WP_Email_Essentials_History::getInstance();
