<?php
/**
 * Class for IP operations compatible with all PHP versions.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

/**
 * The PHP 5.4 and earlier version of this class, also used as the base for the PHP 5.5+ version.
 */
class IP {
	/**
	 * Does the IP ($ip) match the CIRD ($cidr).
	 *
	 * @param string $ip   The IPv4 address.
	 * @param string $cidr The CIDR to match against.
	 *
	 * @return bool
	 */
	public static function ip4_match_cidr( $ip, $cidr ) {
		[ $cidr_base, $prefix ] = explode( '/', $cidr );

		$generator = function () use ( $cidr_base, $prefix ) {
			$start    = ip2long( $cidr_base );
			$ip_count = 1 << ( 32 - $prefix );
			for ( $i = 0; $i < $ip_count; $i++ ) {
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

	/**
	 * Test: IP ($ip) is an IPv4.
	 *
	 * @param string $ip The IP.
	 *
	 * @return mixed
	 */
	public static function is_4( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP );
	}

	/**
	 * Test: CIDR ($cidr) is a valid IPv4 CIDR.
	 *
	 * @param string $cidr The CIDR.
	 *
	 * @return mixed
	 */
	public static function is_4_cidr( $cidr ) {
		[ $cidr_base, $prefix, $must_be_string_crap ] = explode( '/', $cidr . '/CRAP' );

		return 'CRAP' === $must_be_string_crap && self::is_4( $cidr_base ) && is_numeric( $prefix ) && $prefix >= 0 && $prefix <= 32;
	}

	/**
	 * Test: two IPv4s are identical.
	 *
	 * @param string $ip1 The IP to match against the other.
	 * @param string $ip2 The IP to match against the other.
	 *
	 * @return bool
	 */
	public static function a_4_is_4( $ip1, $ip2 ) {
		return ip2long( $ip1 ) === ip2long( $ip2 );
	}

	/**
	 * Test: the IP is a valid IPv6.
	 *
	 * @param string $ip The IPv6.
	 *
	 * @return bool
	 */
	public static function is_6( $ip ) {
		$sanitized = preg_replace( '/[^0-9a-f:]+/', '', $ip );
		$trimmed   = trim($ip);
		if ( $sanitized && $trimmed && strtolower( $trimmed ) !== strtolower( $sanitized ) ) {
			return false;
		}
		$ip_parts = self::explode_ip6( $ip );
		$ip       = implode( ':', $ip_parts );

		return count( $ip_parts ) === 8 && strlen( $ip ) === ( 8 * 4 + 7 ) && preg_match( '/^[0-9a-f:]+$/', $ip );
	}

	/**
	 * Test: two IPv6s are identical.
	 *
	 * @param string $ip1 The IP to match against the other.
	 * @param string $ip2 The IP to match against the other.
	 *
	 * @return bool
	 */
	public static function a_6_is_6( $ip1, $ip2 ) {
		return self::is_6( $ip1 ) && self::expand_ip6( $ip1 ) === self::expand_ip6( $ip2 );
	}

	/**
	 * Expand a compressed IPv6 to a full 8x4 IPv6.
	 *
	 * @param string $ip The IP to expand.
	 *
	 * @return string
	 */
	public static function expand_ip6( $ip ) {
		$parts = self::explode_ip6( $ip );

		return implode( ':', $parts );
	}

	/**
	 * Explode an IPv6 to 8 4-character parts.
	 *
	 * @param string $ip The IP to explode.
	 *
	 * @return string[]
	 */
	public static function explode_ip6( $ip ) {
		// parts are allowed [0-9a-f]{1,4} or ''. If '', only 1 '' part may exist, but it could be 2 if the address starts with :: like localhost; ::1 .
		// so tp prevent weird shit, we replace :: with :ZZZZ: to indicate where the padding goes.
		$parts = explode( ':', str_replace( '::', ':ZZZZ:', $ip ) );
		if ( in_array( 'ZZZZ', $parts, true ) ) {
			$padding  = 9 - count( $parts ); // why 9 ? well 8 parts total - 2 parts we have, which includes 1 ZZZZ part, so + 1. -> 7. 9 - 2 = 7.
			$padding  = $padding > 0 ? array_fill( 0, $padding, '0' ) : [];
			$position = array_search( 'ZZZZ', $parts, true );
			array_splice( $parts, $position, 1, $padding );
		}

		// padding.
		$parts = array_map(
			function ( $part ) {
				return substr( '0000' . strtolower( $part ), -4, 4 );
			},
			$parts
		);

		return $parts;
	}

	/**
	 * Test: IPv6 ($ip) matches the CIDR ($cidr).
	 *
	 * @param string $ip   The IPv6 to test.
	 * @param string $cidr The CIDR to match against.
	 *
	 * @return bool
	 */
	public static function ip6_match_cidr( $ip, $cidr ) {
		/**
		 * 2001:0db8:0123:4567:89ab:cdef:1234:5678
		 * |||| |||| |||| |||| |||| |||| |||| ||||
		 * |||| |||| |||| |||| |||| |||| |||| |||128     Single end-points and loopback
		 * |||| |||| |||| |||| |||| |||| |||| |||127   Point-to-point links (inter-router)
		 * |||| |||| |||| |||| |||| |||| |||| ||124
		 * |||| |||| |||| |||| |||| |||| |||| |120
		 * |||| |||| |||| |||| |||| |||| |||| 116
		 * |||| |||| |||| |||| |||| |||| |||112
		 * |||| |||| |||| |||| |||| |||| ||108
		 * |||| |||| |||| |||| |||| |||| |104
		 * |||| |||| |||| |||| |||| |||| 100
		 * |||| |||| |||| |||| |||| |||96
		 * |||| |||| |||| |||| |||| ||92
		 * |||| |||| |||| |||| |||| |88
		 * |||| |||| |||| |||| |||| 84
		 * |||| |||| |||| |||| |||80
		 * |||| |||| |||| |||| ||76
		 * |||| |||| |||| |||| |72
		 * |||| |||| |||| |||| 68
		 * |||| |||| |||| |||64   Single LAN; default prefix size for SLAAC
		 * |||| |||| |||| ||60   Some (very limited) 6rd deployments (/60 = 16 /64 blocks)
		 * |||| |||| |||| |56   Minimal end sites assignment[12]; e.g. home network (/56 = 256 /64 blocks)
		 * |||| |||| |||| 52   /52 block = 4096 /64 blocks
		 * |||| |||| |||48   Typical assignment for larger sites (/48 = 65536 /64 blocks)
		 * |||| |||| ||44
		 * |||| |||| |40
		 * |||| |||| 36   possible future local Internet registry (LIR) extra-small allocations
		 * |||| |||32   LIR minimum allocations
		 * |||| ||28   LIR medium allocations
		 * |||| |24   LIR large allocations
		 * |||| 20   LIR extra large allocations
		 * |||16
		 * ||12   Regional Internet registry (RIR) allocations from IANA[15]
		 * |8
		 * 4
		 */
		if ( ! self::is_6( $ip ) ) {
			return false;
		}

		// converts inet_pton output to string with bits.
		$inet_to_bits = function ( $inet ) {
			$unpacked = unpack( 'A16', $inet );
			$unpacked = str_split( $unpacked[1] );
			$binaryip = '';
			foreach ( $unpacked as $char ) {
				$binaryip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
			}

			return substr( $binaryip . str_repeat( '0', 128 ), 0, 128 );
		};

		$ip       = inet_pton( $ip );
		$binaryip = $inet_to_bits( $ip );

		[ $net, $maskbits ] = explode( '/', $cidr );

		$net       = inet_pton( $net );
		$binarynet = $inet_to_bits( $net );

		$ip_net_bits = substr( $binaryip, 0, $maskbits );
		$net_bits    = substr( $binarynet, 0, $maskbits );

		return $ip_net_bits === $net_bits;
	}
}
