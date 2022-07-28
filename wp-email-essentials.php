<?php
/**
 * The main plugin file.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

/**
 * Plugin Name: WordPress Email Essentials
 * Description: A must-have plugin for WordPress to get your outgoing e-mails straightened out.
 * Plugin URI: https://github.com/clearsite/wp-email-essentials
 * Upstream URI: https://github.com/rmpel/wp-email-essentials
 * Author: Remon Pel
 * Author URI: http://remonpel.nl
 * Version: 4.0.1
 * Requires PHP: 7.1
 * Requires at least: 4.8.3
 */

spl_autoload_register(
	function( $class_name ) {
		global $wp_version;
		$class_map = [];
		$class_map[ __NAMESPACE__ .'\\Plugin' ] = __DIR__ . '/lib/class.plugin.php';
		$class_map[ __NAMESPACE__ .'\\IP' ] = __DIR__ . '/lib/class.ip.php';
		$class_map[ __NAMESPACE__ .'\\History' ] = __DIR__ . '/lib/class.history.php';
		$class_map[ __NAMESPACE__ .'\\Queue' ] = __DIR__ . '/lib/class.queue.php';
		$class_map[ __NAMESPACE__ .'\\Fake_Sender' ] = __DIR__ . '/lib/class.wpes-phpmailer.php';
		$class_map[ __NAMESPACE__ .'\\WPES_Queue_List_Table' ] = __DIR__ . '/lib/class.wpes-queue-list-table.php';
		$class_map[ __NAMESPACE__ .'\\CSS_Inliner' ] = __DIR__ . '/lib/class-css-inliner.php';
		$class_map[ __NAMESPACE__ .'\\CssToInlineStyles' ] = __DIR__ . '/lib/class-csstoinlinestyles.php';

		/**
		 * Depending on the WordPress version, the phpMailer object to overload is in a different file/is called differently.
		 */
		if ( version_compare( $wp_version, '5.4.99', '<' ) ) {
			$class_map[ __NAMESPACE__ .'\\WPES_PHPMailer' ] = __DIR__ . '/lib/class.wpes-phpmailer.wp54.php';
		} else {
			$class_map[ __NAMESPACE__ .'\\WPES_PHPMailer' ] = __DIR__ . '/lib/class.wpes-phpmailer.wp55.php';
		}

		if ( !empty( $class_map[ $class_name ]) && is_file( $class_map[ $class_name ]) ) {
			require_once $class_map[ $class_name ];
		}
	}
);

new Plugin();
