<?php
/**
 * Overloading the phpMailer object.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

global $wp_version;
/**
 * Depending on the WordPress version, the phpMailer object to overload is in a different file/is called differently.
 */
if ( version_compare( $wp_version, '5.4.99', '<' ) ) {
	require_once __DIR__ . '/class.wpes-phpmailer.wp54.php';
} else {
	require_once __DIR__ . '/class.wpes-phpmailer.wp55.php';
}

/**
 * The class that allows a phpMailer object that cannot send an email.
 */
class Fake_Sender extends WPES_PHPMailer {
	/**
	 * Overloaded method Send: this does NOT send an email ;) .
	 *
	 * @return bool
	 */
	public function send() {
		return true;
	}
}
