<?php

namespace RMPel\Tools;

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
		return ip2long( $ip1 ) == ip2long( $ip2 );
	}

	public static function is_6( $ip ) {
		$ip_parts = self::explode_ip6( $ip );
		$ip       = implode( ':', $ip_parts );

		return count( $ip_parts ) === 8 && strlen( $ip ) === ( 8 * 4 + 7 ) && preg_match( '/^[0-9a-f:]+$/', $ip );
	}

	public static function a_6_is_6( $ip1, $ip2 ) {
		return self::is_6( $ip1 ) && self::expand_ip6( $ip1 ) === self::expand_ip6( $ip2 );
	}

	public static function expand_ip6( $ip ) {
		$parts = self::explode_ip6( $ip );

		return implode( ':', $parts );
	}

	public static function explode_ip6( $ip ) {
		// parts are allowed [0-9a-f]{1,4} or ''. If '', only 1 '' part may exist, but it could be 2 if the address starts with :: like localhost; ::1
		// so tp prevent weird shit, we replace :: with :ZZZZ: to indicate where the padding goes
		$parts = explode( ':', str_replace( '::', ':ZZZZ:', $ip ) );
		if ( in_array( 'ZZZZ', $parts ) ) {
			$padding  = 9 - count( $parts ); // why 9 ? well 8 parts total - 2 parts we have, which includes 1 ZZZZ part, so + 1. -> 7. 9 - 2 = 7.
			$padding  = array_fill( 0, $padding, '0' );
			$position = array_search( 'ZZZZ', $parts );
			array_splice( $parts, $position, 1, $padding );
		}

		// padding
		$parts = array_map( function ( $part ) {
			return substr( "0000" . strtolower( $part ), - 4, 4 );
		}, $parts );

		return $parts;
	}

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

		// converts inet_pton output to string with bits
		$inet_to_bits = function ( $inet ) {
			$unpacked = unpack( 'A16', $inet );
			$unpacked = str_split( $unpacked[1] );
			$binaryip = '';
			foreach ( $unpacked as $char ) {
				$binaryip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
			}

			return $binaryip;
		};

		$ip       = inet_pton( $ip );
		$binaryip = $inet_to_bits( $ip );

		list( $net, $maskbits ) = explode( '/', $cidr );
		$net       = inet_pton( $net );
		$binarynet = $inet_to_bits( $net );

		$ip_net_bits = substr( $binaryip, 0, $maskbits );
		$net_bits    = substr( $binarynet, 0, $maskbits );

		return $ip_net_bits === $net_bits;
	}
}
