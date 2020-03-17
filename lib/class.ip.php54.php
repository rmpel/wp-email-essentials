<?php

namespace Clearsite\Tools;

class IP_54 {
	// this is the "normal" way of doing it.
	public static function ip4_match_cidr( $ip, $cidr ) {
		list( $cidr_base, $prefix ) = explode( '/', $cidr );

		$start    = ip2long( $cidr_base );
		$ip_count = 1 << ( 32 - $prefix );
		for ( $i = 0; $i < $ip_count; $i ++ ) {
			if ( long2ip( ip2long( $ip ) ) == long2ip( $start + $i ) ) {
				return true;
			}
		}

		return false;
	}

	public static function is_4( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP );
	}

	public static function is_4_cidr( $cidr ) {
		list( $cidr_base, $prefix, $mustbeCRAP ) = explode( '/', $cidr . '/CRAP' );

		return $mustbeCRAP === 'CRAP' && self::is_4( $cidr_base ) && is_numeric( $prefix ) && $prefix >= 0 && $prefix <= 32;
	}

	public static function a_4_is_4( $ip1, $ip2 ) {
		return ip2long($ip1) == ip2long($ip2);
	}
}
