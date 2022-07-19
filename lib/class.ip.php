<?php
/**
 * A normalised class for IP-operations.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

// @phpcs:disable Generic.Classes.DuplicateClassName.Found
// @phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

// Not optimal, but these classes are small.
require __DIR__ . '/class.ip.php54.php';
if ( version_compare( phpversion(), '5.5', '<' ) ) {
	/**
	 * The IP class compatible with php 5.4 and earlier.
	 */
	class IP extends IP_54 {
	}
} else {
	require __DIR__ . '/class.ip.php55.php';

	/**
	 * The IP class compatible with php 5.5 and later.
	 */
	class IP extends IP_55 {
	}
}
