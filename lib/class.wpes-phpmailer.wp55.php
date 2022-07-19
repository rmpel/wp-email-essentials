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
}
