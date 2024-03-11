<?php // phpcs:ignore WordPress.Files.FileName.NotHyphenatedLowercase -- This is a normalised class for PHPMailer.
/**
 * A normalised class for PHPMailer.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

use PHPMailer;

// phpcs:disable Generic.Classes.DuplicateClassName.Found

require_once ABSPATH . WPINC . '/class-phpmailer.php';

/**
 * A wrapper for the WP 5.4 and earlier version of PHPMailer
 */
class WPES_PHPMailer extends PHPMailer {
	// The observant developer will note that there is no SingleTo patch here;
	// This is of course because old WordPress versions will not get an upgrade to the 6.0 version of PHPMailer that no longer has the SingleTo functionality.
}
