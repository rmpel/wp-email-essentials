<?php

require_once ( dirname( __FILE__ ) .'/CssToInlineStyles.php' );

class cssInliner
{
	var $CssToInlineStyles;
	var $html;
	function __construct( $html ) {
		$this->html = $html;
		$this->CssToInlineStyles = new CssToInlineStyles();
	}

	function convert() {
		$this->CssToInlineStyles->setHTML( $this->html );
		$this->CssToInlineStyles->setCSS( $this->getCSS( $this->html ) );
		$result = $this->CssToInlineStyles->convert();
		return $result;
	}

	function getCSS( $html ) {
		$css = array();
		$start = 0;
		$pos = strpos( strtolower( $html ), '<style', $start );
		while ($pos !== false && $start < strlen( $html ) ) {
			$part = substr( $html, $pos );
			// skip to >
			$part = substr( $part, $skipped = strpos( $part, '>' ) +1 );
			// find  </style
			$end = strpos( strtolower( $part ), '</style' );
			// trim
			$part = substr( $part, 0, $end );
			$css[] = $part;
			$start = $pos + $skipped + $end + 8;
			$pos = strpos( strtolower( $html ), '<style', $start );
		}
		return implode("\n", $css);
	}
}
