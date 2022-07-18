<?php

require_once dirname( __FILE__ ) . '/CssToInlineStyles.php';

class cssInliner {
	var $CssToInlineStyles;
	var $html;
	var $css;

	function __construct( $html, $css = false ) {
		$this->html              = $html;
		$this->CssToInlineStyles = new CssToInlineStyles();
		if ( $css ) {
			if ( is_file( $css ) ) {
				$this->css = file_get_contents( $css );
			} else {
				$this->css = $css;
			}
		} else {
			$this->css = $this->getCSS( $this->html );
		}
	}

	function convert() {
		$this->CssToInlineStyles->setHTML( $this->html );
		$this->CssToInlineStyles->setCSS( $this->css );
		try {
			$result = $this->CssToInlineStyles->convert();
		} catch ( Exception $e ) {
			$result = $this->html;
		}

		return $result;
	}

	function getCSS( $html ) {
		$css   = array();
		$start = 0;
		$pos   = strpos( strtolower( $html ), '<style', $start );
		while ( $pos !== false && $start < strlen( $html ) ) {
			$part = substr( $html, $pos );
			// skip to >
			$part = substr( $part, $skipped = strpos( $part, '>' ) + 1 );
			// find  </style
			$end = strpos( strtolower( $part ), '</style' );
			// trim
			$part  = substr( $part, 0, $end );
			$css[] = $part;
			$start = $pos + $skipped + $end + 8;
			$pos   = strpos( strtolower( $html ), '<style', $start );
		}

		return implode( "\n", $css );
	}
}
