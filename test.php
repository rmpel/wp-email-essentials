<?php

define('WPES_DEBUG', true);

if (!$argv[1]) exit;

$c = getcwd();

while (!file_exists('wp-load.php')) {
	chdir(dirname(getcwd()));
}

require 'wp-load.php';
if (!class_exists('WP_Email_Essentials')) require $c .'/wp-email-essentials.php';

var_dump(WP_Email_Essentials::i_am_allowed_to_send_in_name_of("Terberg Leasing <info@terbergleasing.nl>"));

$mail = array(
	'to' => 'Some Bloke <some@bloke.com>',
	'headers' => array("From: Terberg Leasing <info@terbergleasing.nl>"),
);

// var_dump($mail);

// var_dump(WP_Email_Essentials::patch_wp_mail($mail));

add_action('wp_mail', function( $wp_mail ){
	var_dump("BEFORE WPES", $wp_mail);

	return $wp_mail;
}, PHP_INT_MIN);

add_action('wp_mail', function( $wp_mail ){
	var_dump("AFTER WPES", $wp_mail);
	exit;
}, PHP_INT_MAX);

wp_mail($mail['to'], 'Subject', 'Body', $mail['headers']);