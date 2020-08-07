<?php
global $wp_version;
if ( version_compare( $wp_version, '5.4.99', '<' ) ) {
	require_once __DIR__ . '/_class.wpes-phpmailer.wp54.php';
} else {
	require_once __DIR__ . '/_class.wpes-phpmailer.wp55.php';
}

class WP_Email_Essentials_Fake_Sender extends WPES_PHPMailer {
	function Send() {
		return true;
	}
}
