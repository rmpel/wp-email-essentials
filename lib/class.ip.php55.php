<?php
/**
 * Class for IP operations compatible with all PHP versions >= php 5.5.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

/**
 * The PHP 5.5+ version of the class, uses a generator to slightly improves memory consumption.
 */
class IP_55 extends IP_54 {
	/**
	 * Does the IP ($ip) match the CIRD ($cidr).
	 *
	 * @param string $ip   The IPv4 address.
	 * @param string $cidr The CIDR to match against.
	 *
	 * @return bool
	 */
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
			if ( long2ip( ip2long( $ip ) ) === $_ip ) {
				return true;
			}
		}

		return false;
	}
}
