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
 * Version: 4.1.5
 * Requires PHP: 7.1
 * Requires at least: 4.8.3
 */

spl_autoload_register(
	function ( $class_name ) {
		global $wp_version;
		$n = __NAMESPACE__;

		$class_map = [
			$n . '\\Plugin'                => __DIR__ . '/lib/class.plugin.php',
			$n . '\\Migrations'            => __DIR__ . '/lib/class.migrations.php',
			$n . '\\IP'                    => __DIR__ . '/lib/class.ip.php',
			$n . '\\History'               => __DIR__ . '/lib/class.history.php',
			$n . '\\Queue'                 => __DIR__ . '/lib/class.queue.php',
			$n . '\\Fake_Sender'           => __DIR__ . '/lib/class.wpes-phpmailer.php',
			$n . '\\WPES_Queue_List_Table' => __DIR__ . '/lib/class.wpes-queue-list-table.php',
			$n . '\\CSS_Inliner'           => __DIR__ . '/lib/class-css-inliner.php',
			$n . '\\CssToInlineStyles'     => __DIR__ . '/lib/class-csstoinlinestyles.php',
		];

		/**
		 * Depending on the WordPress version, the phpMailer object to overload is in a different file/is called differently.
		 */
		if ( version_compare( $wp_version, '5.4.99', '<' ) ) {
			$class_map[ $n . '\\WPES_PHPMailer' ] = __DIR__ . '/lib/class.wpes-phpmailer.wp54.php';
		} else {
			$class_map[ $n . '\\WPES_PHPMailer' ] = __DIR__ . '/lib/class.wpes-phpmailer.wp55.php';
		}

		if ( ! empty( $class_map[ $class_name ] ) && is_file( $class_map[ $class_name ] ) ) {
			require_once $class_map[ $class_name ];
		}
	}
);

new Plugin();

register_activation_hook( __FILE__, [ Migrations::class, 'plugin_activation_hook' ] );
