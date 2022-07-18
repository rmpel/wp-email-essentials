<?php
/**
 * Class for IP operations compatible with all PHP versions >= php 5.5.
 *
 * @package RMPel\Tools
 */

namespace RMPel\Tools;

class IP_55 extends IP_54 {
	// using a generator slightly improves memory consumption
	public static function ip4_match_cidr( $ip, $cidr ) {
		list( $cidr_base, $prefix ) = explode( '/', $cidr );

		$generator = function () use ( $cidr_base, $prefix ) {
			$start    = ip2long( $cidr_base );
			$ip_count = 1 << ( 32 - $prefix );
			for ( $i = 0; $i < $ip_count; $i ++ ) {
				yield long2ip( $start + $i );
			}
		};
		foreach ( $generator() as $_ip ) {
			if ( $_ip == long2ip( ip2long( $ip ) ) ) {
				return true;
			}
		}

		return false;
	}
}
