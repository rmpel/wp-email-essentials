<?php

namespace RMPel\Tools;

// not optimal, but these classes are small
require __DIR__ . '/class.ip.php54.php';
require __DIR__ . '/class.ip.php55.php';

if ( version_compare( phpversion(), '5.5', '<' ) ) {
	// OLD
	class IP extends IP_54 {}
} else {
	class IP extends IP_55 {}
}
