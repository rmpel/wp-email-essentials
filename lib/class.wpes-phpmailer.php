<?php
/**
 * Overloading the phpMailer object.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

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
