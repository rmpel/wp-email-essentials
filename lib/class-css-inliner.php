<?php
/**
 * CSS Inliner.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

use Exception;

require_once __DIR__ . '/class-csstoinlinestyles.php';

/**
 * Support class for CssToInlineStyles
 */
class CSS_Inliner {
	/**
	 * Holds the CSS Inliner.
	 *
	 * @var CssToInlineStyles
	 */
	public $css_to_inline_styles;

	/**
	 * Holds the HTML
	 *
	 * @var string
	 */
	public $html;

	/**
	 * Holds the CSS.
	 *
	 * @var false|string
	 */
	public $css;

	/**
	 * Constructor
	 *
	 * @param string $html The HTML to process.
	 * @param string $css  (Optional) CSS content.
	 */
	public function __construct( $html, $css = false ) {
		$this->html                 = $html;
		$this->css_to_inline_styles = new CssToInlineStyles();
		if ( $css ) {
			if ( is_file( $css ) ) {
				// @phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$this->css = file_get_contents( $css );
			} else {
				$this->css = $css;
			}
		} else {
			$this->css = $this->get_css_from_html( $this->html );
		}
	}

	/**
	 * Convert HTML + CSS to Inlined CSS HTML.
	 *
	 * @return false|string
	 */
	public function convert() {
		$this->css_to_inline_styles->setHTML( $this->html );
		$this->css_to_inline_styles->setCSS( $this->css );
		try {
			$result = $this->css_to_inline_styles->convert();
		} catch ( Exception $e ) {
			$result = $this->html;
		}

		return $result;
	}

	/**
	 * Extract CSS form HTML.
	 *
	 * @param string $html The HTML.
	 *
	 * @return string
	 */
	public function get_css_from_html( $html ) {
		$css   = [];
		$start = 0;
		$pos   = strpos( strtolower( $html ), '<style', $start );
		// phpcs:ignore Squiz.PHP.DisallowSizeFunctionsInLoops.Found -- How about you mind your own business and let the professionals do their work...
		while ( false !== $pos && $start < strlen( $html ) ) {
			$part = substr( $html, $pos );
			// skip to > .
			$part = substr( $part, $skipped = strpos( $part, '>' ) + 1 );
			// find  </style .
			$end = strpos( strtolower( $part ), '</style' );
			// trim .
			$part  = substr( $part, 0, $end );
			$css[] = $part;
			$start = $pos + $skipped + $end + 8;
			$pos   = strpos( strtolower( $html ), '<style', $start );
		}

		return implode( "\n", $css );
	}
}
