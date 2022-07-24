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
 * Version: 4.0.0
 * Requires PHP: 7.1
 * Requires at least: 4.8.3
 */

require_once __DIR__ . '/lib/class.ip.php';
require_once __DIR__ . '/lib/class.plugin.php';
require_once __DIR__ . '/lib/class.history.php';
require_once __DIR__ . '/lib/class.queue.php';
require_once __DIR__ . '/lib/class.wpes-phpmailer.php';

$wp_email_essentials = new Plugin();
add_action( 'admin_notices', [ $wp_email_essentials, 'admin_notices' ] );

add_filter( 'wp_mail', [ Plugin::class, 'alternative_to' ] );

add_action( 'wp_ajax_nopriv_wpes_get_ip', [ Plugin::class, 'ajax_get_ip' ] );

History::instance();

/* this section enables mail_queue, which is not yet finished */
add_filter( '__disabled__wp_mail', [ Queue::class, 'wp_mail' ], PHP_INT_MAX - 2000 );
