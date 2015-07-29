<?php
	/*
	Plugin Name: WordPress Email Essentials
	Description: A must-have plugin for WordPress to get your outgoing e-mails straightened out.
	Plugin URI: https://bitbucket.org/rmpel/wp-email-essentials
	Author: Remon Pel
	Author URI: http://remonpel.nl
	Version: 1.3.1
	License: GPL2
	Text Domain: Text Domain
	Domain Path: Domain Path
	*/

	/*

			Copyright (C) 2013  Remon Pel  ik@remonpel.nl

			This program is free software; you can redistribute it and/or modify
			it under the terms of the GNU General Public License, version 2, as
			published by the Free Software Foundation.

			This program is distributed in the hope that it will be useful,
			but WITHOUT ANY WARRANTY; without even the implied warranty of
			MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
			GNU General Public License for more details.

			You should have received a copy of the GNU General Public License
			along with this program; if not, write to the Free Software
			Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	*/

class WP_Email_Essentials
{
	static $message;
	static $error;
	static $debug;

	function WP_Email_Essentials()
	{
		self::init();
	}

	public static function init()
	{
		// self::test();

		$config = self::get_config();
		add_action( 'phpmailer_init', array( 'WP_Email_Essentials', 'action_phpmailer_init' ) );
		if ( $config['is_html'] )
		{
			add_filter( 'wp_mail_content_type', create_function( '', 'return "text/html; charset=UTF-8";' ) );
		}

		// set default from email and from name
		if ( $config['from_email'] ) {
			add_filter( 'wp_mail_from', array( 'WP_Email_Essentials', 'filter_wp_mail_from' ), 9999 );
		}
		if ( $config['from_name'] )
		{
			add_filter( 'wp_mail_from_name', array( 'WP_Email_Essentials', 'filter_wp_mail_from_name' ), 9999 );
		}

		add_filter( 'wp_mail', array( 'WP_Email_Essentials', 'action_wp_mail' ) );
		add_action( 'admin_menu', array( 'WP_Email_Essentials', 'admin_menu' ), 10 );

		add_action( 'admin_menu', array( 'WP_Email_Essentials', 'migrate_from_smtp_connect' ), -10000 );
	}

	public static function action_wp_mail( $wp_mail )
	{
		$config = self::get_config();
		self::wp_mail_from( $config[ 'from_email' ] );
		self::wp_mail_from_name( $config[ 'from_name' ] );

		$all_headers = array();

		if ( is_string( $wp_mail[ 'headers' ] ) )
		{
			$wp_mail[ 'headers' ] = explode( "\n", $wp_mail[ 'headers' ] );
		}
		if ( ! is_array( $wp_mail[ 'headers' ] ) )
		{
			$wp_mail[ 'headers' ] = array();
		}

		$header_index = array();
		foreach ( $wp_mail[ 'headers' ] as $i => $header )
		{
			preg_match( '/([^:]+):(.*)$/U', $header, $match );
			$all_headers[ strtolower( trim( $match[1] ) ) ] = $match[2];
			$header_index[ strtolower( trim( $match[1] ) ) ] = $i;
		}

		if ( $all_headers[ 'from' ] ) {
			$from = self::rfc_decode( $all_headers[ 'from' ] );
			if ( $from[ 'email' ] )
				self::wp_mail_from( $from[ 'email' ] );
			if ( $from[ 'name' ] )
				self::wp_mail_from_name( $from[ 'name' ] );
		}

		if ( ! array_key_exists( 'from', $header_index ) )
			$header_index['from'] = count( $header_index );
		$wp_mail['headers'][$header_index['from']] = 'From:"'. self::wp_mail_from_name() .'" <'. self::wp_mail_from() .'>';

		return $wp_mail;
	}

	public static function action_phpmailer_init( &$mailer )
	{
		/** @var phpMailer $mailer */
		$config = self::get_config();
		if ( $config['smtp'] )
		{
			$mailer->IsSMTP();
			list($host, $port) = explode(':', $config['smtp']['host'] .':-1');
			$mailer->Host = $host;
			if ($port > 0)
				$mailer->Port = $port;

			if (isset($config['smtp']['username'])) {
				$mailer->SMTPAuth = true;
				$mailer->Username = $config['smtp']['username'];
				$mailer->Password = $config['smtp']['password'];
			}
		}

		$mailer->Sender = self::wp_mail_from();

		$mailer->Body = WP_Email_Essentials::preserve_weird_url_display( $mailer->Body );

		if ( $config['is_html'] ) {
			$mailer->Body = WP_Email_Essentials::maybe_convert_to_html( $mailer->Body, $mailer->Subject, $mailer );
			$css = apply_filters_ref_array( 'wpes_css', array('', &$mailer ));

			if ($config['css_inliner']) {
				require_once dirname(__FILE__) .'/lib/cssInliner.class.php';
				$cssInliner = new cssInliner( $mailer->Body, $css );
				$mailer->Body = $cssInliner->convert();
			}
			$mailer->isHTML( true );
		}

		if ( $config['alt_body'] ) {
			$body = $mailer->Body;
			$btag = strpos( $body, '<body' );
			if ( false !== $btag ) {
				list( $body, $junk ) = explode( '</body', $body );
				list( $junk, $body ) = explode( '<body', $body );
			}

			// links to link-text+url
			// example; <a href="http://nu.nl">Go to NU.nl</a> becomes:  Go to Nu.nl ( http://nu.nl )
			$body = preg_replace( '/<a.+href=("|\')([^\\1]+)\\1>([^<]+)<\/a>/U', '\3 (\2)', $body );

			// remove all HTML except line breaks
			$body = strip_tags( $body, '<br>' );

			// replace all forms of breaks, list items and table row endings to new-lines
			$body = preg_replace( '/<br[\/ ]*>/Ui', "\n", $body );
			$body = preg_replace( '/<\/( li|tr )>/Ui', '</\1>'."\n", $body );

			// set the alternate body
			$mailer->AltBody = $body;
		}

		if ( $_POST && $_POST['form_id'] == 'wp-email-essentials' && $_POST['op'] == 'Send sample mail' ) {
			$mailer->SMTPDebug = false;
		}

		if ( $_POST && $_POST['form_id'] == 'wp-email-essentials' && $_POST['op'] == 'Print debug output of sample mail' ) {
			$mailer->SMTPDebug = true;
			print '<h2>Dump of PHP Mailer object</h2><pre>';
			var_dumP( $mailer );
			exit;
		}
	}

	function preserve_weird_url_display($html)
	{
		if (preg_match('/<(http(s)?:\/\/[^>]+)>/', $html, $m)) {
			$url = $m[1];
			return str_replace('<'. $url .'>', '['. $url .']', $html);
		}
		return $html;
	}



	function maybe_convert_to_html( $might_be_text, $subject, $mailer ) {
		$html_preg = '<(br|a|p|body|table|div|span|body|html)';
		if ( preg_match( "/$html_preg/", $might_be_text ) ) {
			// probably html
			$should_be_html = $might_be_text;
		}
		else {
			$should_be_html = nl2br( trim( $might_be_text ) );
		}

		// should have some basic HTML now, otherwise, add a P
		if ( ! preg_match( "/$html_preg/", $should_be_html ) ) {
			$should_be_html = '<p>'. $should_be_html .'</p>';
		}

		// now check for HTML evelope
		if ( false === strpos( $should_be_html, '<html' ) ) {
			$should_be_html = '<html><head>'. apply_filters_ref_array( 'wpes_head', array('<title>'. $subject .'</title>', &$mailer )) . '</head><body>'. apply_filters_ref_array( 'wpes_body', array($should_be_html, &$mailer) ) .'</body></html>';
		}

		return $should_be_html;
	}


	public static function wp_mail_from( $from = null )
	{
		static $store;
		if ( $from )
		{
			$store = $from;
		}
		return $store;
	}

	public static function wp_mail_from_name( $from = null )
	{
		static $store;
		if ( $from )
		{
			$store = $from;
		}
		return $store;
	}

	public static function filter_wp_mail_from( $from )
	{
		return self::wp_mail_from();
	}

	public static function filter_wp_mail_from_name( $from )
	{
		return self::wp_mail_from_name();
	}

	public static function get_config()
	{
		$defaults = array(
			'smtp' => false,
			'from_email' => get_bloginfo( 'admin_email' ),
			'from_name' => self::get_hostname_by_blogurl(),
			'is_html' => false,
			'alt_body' => false,
			'css_inliner' => false,
		);

		$defaults = apply_filters('wpes_defaults', $defaults);

		$settings = get_option( 'wp-email-essentials', $defaults );

		$settings = apply_filters('wpes_settings', $settings);

		return $settings;
	}

	public static function set_config( $values )
	{
		$values = stripslashes_deep( $values );
		$settings = self::get_config();
		if ( $values['smtp-enabled'] )
		{
			$settings['smtp'] = array(
				'host'     => $values['host'],
				'username' => $values['username'],
				'password' => ( $values['password'] == str_repeat( '*', strlen( $values['password'] ) ) && $settings['smtp'] ) ? $settings['smtp']['password'] : $values['password'],
			);
		}
		else
		{
			$settings['smtp'] = false;
		}
		$settings['from_name'] = $values['from_name'] ?: $settings['from_name'];
		$settings['from_email'] = $values['from_email'] ?: $settings['from_email'];
		$settings['is_html'] = $values['is_html'] ? true: false;
		$settings['css_inliner'] = $values['css_inliner'] ? true: false;
		$settings['alt_body'] = $values['alt_body'] ? true: false;
		$settings['SingleTo'] = $values['SingleTo'] ? true: false;
		update_option( 'wp-email-essentials', $settings );
	}

	public static function get_hostname_by_blogurl()
	{
		$url = get_bloginfo( 'url' );
		$url = parse_url( $url );
		return $url[ 'host' ];
	}

	private static function rfc_decode( $rfc ) {
		// $rfc might just be an e-mail address
		if ( is_email( $rfc ) ) {
			return array( 'name' => $rfc, 'email' => $rfc );
		}

		// $rfc is not an email, the RFC format is:
		//  "Name Surname Anything here" <email@addr.ess>
		// but quotes are optional...
		//  Name Surname Anything here <email@addr.ess>
		// is considered valid as well
		//
		// considering HTML, <email@addr.ess> is a tag so we can strip that out with strip_tags
		// and the remainder is the name-part.
		$name_part = strip_tags( $rfc );
		// remove the name-part from the original and the email part is known
		$email_part = str_replace( $name_part, '', $rfc );
		// strip illegal characters;
		$name_part = trim( $name_part, ' "' );
		$email_part = trim( $email_part, ' <>' );
		// verify :)
		if ( is_email( $email_part ) ) {
			return array( 'name' => stripslashes( $name_part ), 'email' => $email_part );
		}
		return false;
	}

	public static function admin_menu()
	{
		add_submenu_page( 'tools.php', 'WP-Email-Essentials', 'Email-Essentials', 'manage_options', 'wp-email-essentials', array( 'WP_Email_Essentials', 'admin_interface' ) );
		if ( $_GET[ 'page' ] == 'wp-email-essentials' && $_POST && $_POST[ 'form_id' ] == 'wp-email-essentials' )
		{
			switch ( $_POST[ 'op' ] )
			{
				case 'Save settings':
					self::set_config( $_POST['settings'] );
					self::$message = 'Settings saved.';
				break;
				case 'Print debug output of sample mail':
				case 'Send sample mail':
					ob_start();
					self::$debug = true;
					$result = wp_mail( get_option( 'admin_email', false ), 'Test-email', self::dummy_content() );
					self::$debug = ob_get_clean();
					if ( $result ) {
						self::$message = 'Mail sent to ' . get_option( 'admin_email', false );
					}
					else
					{
						self::$error = 'Mail NOT sent to ' . get_option( 'admin_email', false );
					}
				break;
			}
		}
		if ( $_GET[ 'page' ] == 'wp-email-essentials' && $_GET[ 'iframe' ] == 'content') {
			require_once ABSPATH . WPINC . '/class-phpmailer.php';
			$mailer = new PHPMailer;
			$config = WP_Email_Essentials::get_config();
			$css = apply_filters_ref_array( 'wpes_css', array('', &$mailer ));
			$subject = 'Sample email subject';
			$mailer->Subject = $subject;
			$body = WP_Email_Essentials::dummy_content();
			?><html><head><?php
			print apply_filters_ref_array( 'wpes_head', array('<title>'. $subject .'</title>', &$mailer ));
			?></head><body><?php
			$bodyhtml = apply_filters_ref_array( 'wpes_body', array($body, &$mailer));

			if ($config['css_inliner']) {
				require_once dirname(__FILE__) .'/lib/cssInliner.class.php';
				$cssInliner = new cssInliner( $bodyhtml, $css );
				$bodyhtml = $cssInliner->convert();
			}
			$bodyhtml = WP_Email_Essentials::cid_to_image($bodyhtml, $mailer);
			print $bodyhtml;
			?></body></html><?php
			exit;
		}
	}

	static function admin_interface()
	{
		include 'admin-interface.php';
	}

	public static function test()
	{
		$test = self::rfc_decode( 'ik@remonpel.nl' );
		// should return array( 'name' => 'ik@remonpel.nl', 'email' => 'ik@remonpel.nl' )
		if ( $test[ 'name' ] == 'ik@remonpel.nl' && $test[ 'email' ] == 'ik@remonpel.nl' )
		{
			echo "simple email address verified<br />\n";
		}
		else
		{
			echo "simple email address FAILED<br />\n";
		}

		$test = self::rfc_decode( 'Remon Pel <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test[ 'name' ] == 'Remon Pel' && $test[ 'email' ] == 'ik@remonpel.nl' )
		{
			echo "RFC2822 no quotes email address verified<br />\n";
		}
		else
		{
			echo "RFC2822 no quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode( '"Remon Pel" <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test[ 'name' ] == 'Remon Pel' && $test[ 'email' ] == 'ik@remonpel.nl' )
		{
			echo "RFC2822 with quotes email address verified<br />\n";
		}
		else
		{
			echo "RFC2822 with quotes email address FAILED<br />\n";
		}

		$test = self::rfc_decode( '    "   Remon Pel   " <ik@remonpel.nl>' );
		// should return array( 'name' => 'Remon Pel', 'email' => 'ik@remonpel.nl' )
		if ( $test[ 'name' ] == 'Remon Pel' && $test[ 'email' ] == 'ik@remonpel.nl' )
		{
			echo "RFC2822 too many spaces - not valid RFC but still parses? verified<br />\n";
		}
		else
		{
			echo "RFC2822 too many spaces - not valid RFC but still parses? FAILED<br />\n";
		}

		exit;
	}

	public static function migrate_from_smtp_connect() {
		$plugin = 'smtp-connect/smtp-connect.php';
		if ( is_plugin_active( $plugin ) ) {
			// plugin active, migrate
			$smtp_connect = get_option( 'smtp-connect', array() );
			if ( $smtp_connect['enabled'] ) {
				$smtp_connect['smtp-enabled'] = true;
			}
			$smtp_connect['host'] = $smtp_connect['Host']; unset( $smtp_connect['Host'] );
			$smtp_connect['username'] = $smtp_connect['Username']; unset( $smtp_connect['Username'] );
			$smtp_connect['password'] = $smtp_connect['Password']; unset( $smtp_connect['Password'] );
			self::set_config( $smtp_connect );

			// deactivate conflicting plugin
			deactivate_plugins( $plugin, false );

			// wordpress still thinks the plugin is active, do it the hard way
			$active = get_option( 'active_plugins', array() );
			unset($active[ array_search($plugin, $active) ]);
			update_option( 'active_plugins', $active );

			// log the deactivation.
			update_option( 'recently_activated', array( $plugin => time() ) + (array) get_option( 'recently_activated' ) );
		}
	}

	public static function dummy_content(){
		return '<h1>Sample Email Body</h1><p>Some random text Lorem Ipsum is <b>bold simply dummy</b> text of the <strong>strong printing and typesetting</strong> industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.</p><h2>A header-2</h2><p>Some more text</p><h3>A header-3</h3><ul><li>A list - unordered, item 1</li><li>Item 2</li></ul><h4>A header-4</h4><ol><li>A list - ordered, item 1</li><li>Item 2</li></ol>';
	}

	public static function cid_to_image( $html, $mailer ) {
		foreach ($mailer->getAttachments() as $attachment) {
			if ($attachment[7]) {
				$html = str_replace('cid:'. $attachment[7], 'data:'. $attachment[4].';'. $attachment[3] .',' .base64_encode(file_get_contents($attachment[0])), $html);
			}
		}
		return $html;
	}
}

$wp_email_essentials = new WP_Email_Essentials();
