<?php

$domain   = $argv[1] ?? 'remonpel.nl';
$selector = 'website-' . substr( md5( random_bytes( 32 ) ), rand( 0, 24 ), 7 );

$key = openssl_pkey_new(
	[
		'private_key_bits' => 2048,
		'private_key_type' => OPENSSL_KEYTYPE_RSA,
	]
);

$public_key = openssl_pkey_get_details( $key )['key'];
openssl_pkey_export( $key, $private_key );
$public_key_dns = array_map( 'trim', explode( "\n", str_replace( "\r", "\n", trim( $public_key ) ) ) );
$public_key_dns = array_diff( $public_key_dns, [ '', '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----' ] );
$public_key_dns = implode( '', $public_key_dns );

print "Store in DB: \n";
print "wpes_domain_selector_$domain => '$selector'\n";
print "wpes_private_key_$domain => '$private_key'\n";
print "Store in DNS: \n";
print "$selector._domainkey.$domain. IN TXT \"v=DKIM1; k=rsa; p=$public_key_dns\" \n";
