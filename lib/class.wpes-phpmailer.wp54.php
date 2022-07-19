<?php // @phpcs:ignore Squiz.Commenting.FileComment.Missing

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
}
