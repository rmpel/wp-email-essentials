<?php
/**
 * A normalised class for PHPMailer.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

// phpcs:disable Generic.Classes.DuplicateClassName.Found

use PHPMailer\PHPMailer\PHPMailer;

require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';

/**
 * A wrapper for the WP 5.5 and later version of PHPMailer
 */
class WPES_PHPMailer extends PHPMailer {
	/**
	 * Method Send overloaded to keep the SingleTo functionality that will be deprecated in phpMailer version 6.
	 * It is unclear when WordPress will upgrade to this version, or if WordPress will or will not implement a split-send themselves.
	 * This functions perfectly, and will not interfere with the way WordPress sends emails, until the phpMailer class and/or -file structure changes.
	 * This method will split send the email using the parent method send().
	 * Final note; with this change, we could remove the SMTP-restriction on the SingleTo functionality.
	 *
	 * @return bool
	 *
	 * @throws \phpmailerException This is explained in the parent method.
	 * @throws \PHPMailer\PHPMailer\Exception This is explained in the parent method.
	 */
	public function send() {
		$single_to = false;
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- not our code.
		if ( ! empty( $this->SingleTo ) ) {
			$single_to = $this->SingleTo;
			unset( $this->SingleTo );
		}
		// Don't want single to? Then just send the email.
		if ( ! $single_to ) {
			return parent::send();
		}
		$to_addresses = $this->getToAddresses();
		// Prepare to send multiple emails with the same content to single addresses.
		$sent = false;
		foreach ( $to_addresses as $to_address ) {
			// Set the address to send to.
			$this->clearAddresses();
			$this->addAddress( ...$to_address );
			// Send the email.
			$email_sent = parent::send();
			// If the email was sent, set the 'sent' flag to true.
			if ( $email_sent ) {
				$sent = true;
			}
			// Only send CC and BCC once.
			if ( $sent ) {
				$this->clearCCs();
				$this->clearBCCs();
			}
		}

		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- resume checking.

		// We accept a single email sent as a success.
		return $sent;
	}
}
