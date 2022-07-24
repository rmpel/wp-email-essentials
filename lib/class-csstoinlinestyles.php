<?php
/**
 * CSS to Inline Styles class
 *
 * @author         Tijs Verkoyen <php-css-to-inline-styles@verkoyen.eu>
 * @version        1.2.1
 * @copyright      Copyright (c), Tijs Verkoyen. All rights reserved.
 * @license        BSD License
 *
 * @package        WP_Email_Essentials
 */

// phpcs:disable WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase -- Adopted code.
// phpcs:disable WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Adopted code.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase -- Adopted code.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Adopted code.

namespace WP_Email_Essentials;

use DOMDocument;
use DOMXPath;

/**
 * Main class for inlining styles.
 */
class CssToInlineStyles {
	/**
	 * The CSS to use
	 *
	 * @var    string
	 */
	private $css;

	/**
	 * The processed CSS rules
	 *
	 * @var    array
	 */
	private $cssRules;

	/**
	 * Should the generated HTML be cleaned
	 *
	 * @var    bool
	 */
	private $cleanup = false;

	/**
	 * The encoding to use.
	 *
	 * @var    string
	 */
	private $encoding = 'UTF-8';

	/**
	 * The HTML to process
	 *
	 * @var    string
	 */
	private $html;

	/**
	 * Use inline-styles block as CSS
	 *
	 * @var    bool
	 */
	private $useInlineStylesBlock = false;

	/**
	 * Strip original style tags
	 *
	 * @var bool
	 */
	private $stripOriginalStyleTags = false;

	/**
	 * Exclude the media queries from the inlined styles
	 *
	 * @var bool
	 */
	private $excludeMediaQueries = false;

	/**
	 * Creates an instance, you could set the HTML and CSS here, or load it
	 * later.
	 *
	 * @param string[optional] $html The HTML to process.
	 * @param string[optional] $css  The CSS to use.
	 *
	 * @return void
	 */
	public function __construct( $html = null, $css = null ) {
		if ( null !== $html ) {
			$this->setHTML( $html );
		}
		if ( null !== $css ) {
			$this->setCSS( $css );
		}
	}

	/**
	 * Convert a CSS-selector into an xPath-query
	 *
	 * @param string $selector The CSS-selector.
	 *
	 * @return string
	 */
	private function buildXPathQuery( $selector ) {
		// redefine.
		$selector = (string) $selector;

		// the CSS selector.
		$cssSelector = [
			// E F, Matches any F element that is a descendant of an E element.
			'/(\w)\s+([\w\*])/',
			// E > F, Matches any F element that is a child of an element E.
			'/(\w)\s*>\s*([\w\*])/',
			// E:first-child, Matches element E when E is the first child of its parent.
			'/(\w):first-child/',
			// E + F, Matches any F element immediately preceded by an element.
			'/(\w)\s*\+\s*(\w)/',
			// E[foo], Matches any E element with the "foo" attribute set (whatever the value).
			'/(\w)\[([\w\-]+)]/',
			// E[foo="warning"], Matches any E element whose "foo" attribute value is exactly equal to "warning".
			'/(\w)\[([\w\-]+)\=\"(.*)\"]/',
			// div.warning, HTML only. The same as DIV[class~="warning"].
			'/(\w+|\*)+\.([\w\-]+)+/',
			// .warning, HTML only. The same as *[class~="warning"].
			'/\.([\w\-]+)/',
			// E#myid, Matches any E element with id-attribute equal to "myid".
			'/(\w+)+\#([\w\-]+)/',
			// #myid, Matches any element with id-attribute equal to "myid".
			'/\#([\w\-]+)/',
		];

		// the xPath-equivalent.
		$xPathQuery = [
			// E F, Matches any F element that is a descendant of an E element.
			'\1//\2',
			// E > F, Matches any F element that is a child of an element E.
			'\1/\2',
			// E:first-child, Matches element E when E is the first child of its parent.
			'*[1]/self::\1',
			// E + F, Matches any F element immediately preceded by an element.
			'\1/following-sibling::*[1]/self::\2',
			// E[foo], Matches any E element with the "foo" attribute set (whatever the value).
			'\1 [ @\2 ]',
			// E[foo="warning"], Matches any E element whose "foo" attribute value is exactly equal to "warning".
			'\1[ contains( concat( " ", @\2, " " ), concat( " ", "\3", " " ) ) ]',
			// div.warning, HTML only. The same as DIV[class~="warning"].
			'\1[ contains( concat( " ", @class, " " ), concat( " ", "\2", " " ) ) ]',
			// .warning, HTML only. The same as *[class~="warning"].
			'*[ contains( concat( " ", @class, " " ), concat( " ", "\1", " " ) ) ]',
			// E#myid, Matches any E element with id-attribute equal to "myid".
			'\1[ @id = "\2" ]',
			// #myid, Matches any element with id-attribute equal to "myid".
			'*[ @id = "\1" ]',
		];

		// return.
		$xPath = (string) '//' . preg_replace( $cssSelector, $xPathQuery, $selector );

		return str_replace( '] *', ']//*', $xPath );
	}

	/**
	 * Calculate the specifity for the CSS-selector
	 *
	 * @param string $selector The selector to calculate the specifity for.
	 *
	 * @return int
	 */
	private function calculateCSSSpecifity( $selector ) {
		// cleanup selector.
		$selector = str_replace( [ '>', '+' ], [ ' > ', ' + ' ], $selector );

		// init var.
		$specifity = 0;

		// split the selector into chunks based on spaces.
		$chunks = explode( ' ', $selector );

		// loop chunks.
		foreach ( $chunks as $chunk ) {
			if ( strstr( $chunk, '#' ) !== false ) {
				// an ID is important, so give it a high specifity.
				$specifity += 100;
			} elseif ( strstr( $chunk, '.' ) ) {
				// classes are more important than a tag, but less important then an ID.
				$specifity += 10;
			} else {
				// anything else isn't that important.
				$specifity ++;
			}
		}

		// return.
		return $specifity;
	}


	/**
	 * Cleanup the generated HTML
	 *
	 * @param string $html The HTML to cleanup.
	 *
	 * @return string
	 */
	private function cleanupHTML( $html ) {
		// remove classes.
		$html = preg_replace( '/(\s)+class="(.*)"(\s)*/U', ' ', $html );

		// remove IDs.
		$html = preg_replace( '/(\s)+id="(.*)"(\s)*/U', ' ', $html );

		// return.
		return $html;
	}


	/**
	 * Converts the loaded HTML into an HTML-string with inline styles based on the loaded CSS
	 *
	 * @param bool $outputXHTML (optional) Should we output valid XHTML?..
	 *
	 * @return string
	 *
	 * @throws \Exception When html is empty.
	 */
	public function convert( $outputXHTML = false ) {
		// redefine.
		$outputXHTML = (bool) $outputXHTML;

		// validate.
		if ( null === $this->html ) {
			throw new \Exception( 'No HTML provided.' );
		}

		// should we use inline style-block.
		if ( $this->useInlineStylesBlock ) {
			// init var.
			$matches = [];

			// match the style blocks.
			preg_match_all( '|<style(.*)>(.*)</style>|isU', $this->html, $matches );

			// any style-blocks found?.
			if ( ! empty( $matches[2] ) ) {
				// add.
				foreach ( $matches[2] as $match ) {
					$this->css .= trim( $match ) . "\n";
				}
			}
		}

		// process css.
		$this->processCSS();

		// create new DOMDocument.
		$document = new DOMDocument( '1.0', $this->getEncoding() );

		// set error level.
		libxml_use_internal_errors( true );

		// load HTML.
		$document->loadHTML( $this->html );

		// create new XPath.
		$xPath = new DOMXPath( $document );

		// any rules?.
		if ( ! empty( $this->cssRules ) ) {
			// loop rules.
			foreach ( $this->cssRules as $rule ) {
				// init var.
				$query = $this->buildXPathQuery( $rule['selector'] );

				// validate query.
				if ( false === $query ) {
					continue;
				}

				// search elements.
				$elements = $xPath->query( $query );

				// validate elements.
				if ( false === $elements ) {
					continue;
				}

				// loop found elements.
				foreach ( $elements as $element ) {
					// no styles stored?.
					if ( null === $element->attributes->getNamedItem( 'data-css-to-inline-styles-original-styles' ) ) {
						// init var.
						$originalStyle = '';
						if ( null !== $element->attributes->getNamedItem( 'style' ) ) {
							$originalStyle = $element->attributes->getNamedItem( 'style' )->value;
						}

						// store original styles.
						$element->setAttribute(
							'data-css-to-inline-styles-original-styles',
							$originalStyle
						);

						// clear the styles.
						$element->setAttribute( 'style', '' );
					}

					// init var.
					$properties = [];

					// get current styles.
					$stylesAttribute = $element->attributes->getNamedItem( 'style' );

					// any styles defined before?.
					if ( null !== $stylesAttribute ) {
						// get value for the styles attribute.
						$definedStyles = (string) $stylesAttribute->value;

						// split into properties.
						$definedProperties = explode( ';', $definedStyles );

						// loop properties.
						foreach ( $definedProperties as $property ) {
							// validate property.
							if ( '' === $property ) {
								continue;
							}

							// split into chunks.
							$chunks = explode( ':', trim( $property ), 2 );

							// validate.
							if ( ! isset( $chunks[1] ) ) {
								continue;
							}

							// loop chunks.
							$properties[ $chunks[0] ] = trim( $chunks[1] );
						}
					}

					// add new properties into the list.
					foreach ( $rule['properties'] as $key => $value ) {
						$properties[ $key ] = $value;
					}

					// build string.
					$propertyChunks = [];

					// build chunks.
					foreach ( $properties as $key => $values ) {
						foreach ( (array) $values as $value ) {
							$propertyChunks[] = $key . ': ' . $value . ';';
						}
					}

					// build properties string.
					$propertiesString = implode( ' ', $propertyChunks );

					// set attribute.
					if ( '' !== $propertiesString ) {
						$element->setAttribute( 'style', $propertiesString );
					}
				}
			}

			// reapply original styles.
			$query = $this->buildXPathQuery(
				'*[@data-css-to-inline-styles-original-styles]'
			);

			// validate query.
			if ( false === $query ) {
				return false;
			}

			// search elements.
			$elements = $xPath->query( $query );

			// loop found elements.
			foreach ( $elements as $element ) {
				// get the original styles.
				$originalStyle = $element->attributes->getNamedItem(
					'data-css-to-inline-styles-original-styles'
				)->value;

				if ( '' !== $originalStyle ) {
					$originalProperties = [];
					$originalStyles     = explode( ';', $originalStyle );

					foreach ( $originalStyles as $property ) {
						// validate property.
						if ( '' === $property ) {
							continue;
						}

						// split into chunks.
						$chunks = explode( ':', trim( $property ), 2 );

						// validate.
						if ( ! isset( $chunks[1] ) ) {
							continue;
						}

						// loop chunks.
						$originalProperties[ $chunks[0] ] = trim( $chunks[1] );
					}

					// get current styles.
					$stylesAttribute = $element->attributes->getNamedItem( 'style' );
					$properties      = [];

					// any styles defined before?.
					if ( null !== $stylesAttribute ) {
						// get value for the styles attribute.
						$definedStyles = (string) $stylesAttribute->value;

						// split into properties.
						$definedProperties = explode( ';', $definedStyles );

						// loop properties.
						foreach ( $definedProperties as $property ) {
							// validate property.
							if ( '' === $property ) {
								continue;
							}

							// split into chunks.
							$chunks = explode( ':', trim( $property ), 2 );

							// validate.
							if ( ! isset( $chunks[1] ) ) {
								continue;
							}

							// loop chunks.
							$properties[ $chunks[0] ] = trim( $chunks[1] );
						}
					}

					// add new properties into the list.
					foreach ( $originalProperties as $key => $value ) {
						$properties[ $key ] = $value;
					}

					// build string.
					$propertyChunks = [];

					// build chunks.
					foreach ( $properties as $key => $values ) {
						foreach ( (array) $values as $value ) {
							$propertyChunks[] = $key . ': ' . $value . ';';
						}
					}

					// build properties string.
					$propertiesString = implode( ' ', $propertyChunks );

					// set attribute.
					if ( '' !== $propertiesString ) {
						$element->setAttribute(
							'style',
							$propertiesString
						);
					}
				}

				// remove placeholder.
				$element->removeAttribute(
					'data-css-to-inline-styles-original-styles'
				);
			}
		}

		// should we output XHTML?.
		if ( $outputXHTML ) {
			// set formating.
			$document->formatOutput = true;

			// get the HTML as XML.
			$html = $document->saveXML( null, LIBXML_NOEMPTYTAG );

			// get start of the XML-declaration.
			$startPosition = strpos( $html, '<?xml' );

			// valid start position?.
			if ( false !== $startPosition ) {
				// get end of the xml-declaration.
				$endPosition = strpos( $html, '?>', $startPosition );

				// remove the XML-header.
				$html = ltrim( substr( $html, $endPosition + 1 ) );
			}
		} else {
			// just regular HTML 4.01 as it should be used in newsletters.
			// get the HTML.
			$html = $document->saveHTML();
		}

		// cleanup the HTML if we need to.
		if ( $this->cleanup ) {
			$html = $this->cleanupHTML( $html );
		}

		// strip original style tags if we need to.
		if ( $this->stripOriginalStyleTags ) {
			$html = $this->stripOriginalStyleTags( $html );
		}

		// return.
		return $html;
	}


	/**
	 * Get the encoding to use
	 *
	 * @return string
	 */
	private function getEncoding() {
		return $this->encoding;
	}


	/**
	 * Process the loaded CSS
	 *
	 * @return void
	 */
	private function processCSS() {
		// init vars.
		$css = (string) $this->css;

		// remove newlines.
		$css = str_replace( [ "\r", "\n" ], '', $css );

		// replace double quotes by single quotes.
		$css = str_replace( '"', '\'', $css );

		// remove comments.
		$css = preg_replace( '|/\*.*?\*/|', '', $css );

		// remove spaces.
		$css = preg_replace( '/\s\s+/', ' ', $css );

		if ( $this->excludeMediaQueries ) {
			$css = preg_replace( '/@media [^{]*{([^{}]|{[^{}]*})*}/', '', $css );
		}

		// rules are splitted by }.
		$rules = explode( '}', $css );

		// init var.
		$i = 1;

		// loop rules.
		foreach ( $rules as $rule ) {
			// split into chunks.
			$chunks = explode( '{', $rule );

			// invalid rule?.
			if ( ! isset( $chunks[1] ) ) {
				continue;
			}

			// set the selectors.
			$selectors = trim( $chunks[0] );

			// get cssProperties.
			$cssProperties = trim( $chunks[1] );

			// split multiple selectors.
			$selectors = explode( ',', $selectors );

			// loop selectors.
			foreach ( $selectors as $selector ) {
				// cleanup.
				$selector = trim( $selector );

				// build an array for each selector.
				$ruleSet = [];

				// store selector.
				$ruleSet['selector'] = $selector;

				// process the properties.
				$ruleSet['properties'] = $this->processCSSProperties(
					$cssProperties
				);

				// calculate specifity.
				$ruleSet['specifity'] = $this->calculateCSSSpecifity( $selector ) + $i;

				// add into global rules.
				$this->cssRules[] = $ruleSet;
			}

			// increment.
			$i ++;
		}

		// sort based on specifity.
		if ( ! empty( $this->cssRules ) ) {
			usort( $this->cssRules, [ __CLASS__, 'sortOnSpecifity' ] );
		}
	}

	/**
	 * Process the CSS-properties
	 *
	 * @param string $propertyString The CSS-properties.
	 *
	 * @return array
	 */
	private function processCSSProperties( $propertyString ) {
		// split into chunks.
		$properties = explode( ';', $propertyString );

		// init var.
		$pairs = [];

		// loop properties.
		foreach ( $properties as $property ) {
			// split into chunks.
			$chunks = explode( ':', $property, 2 );

			// validate.
			if ( ! isset( $chunks[1] ) ) {
				continue;
			}

			// cleanup.
			$chunks[0] = trim( $chunks[0] );
			$chunks[1] = trim( $chunks[1] );

			// add to pairs array.
			if ( ! isset( $pairs[ $chunks[0] ] ) || ! in_array( $chunks[1], $pairs[ $chunks[0] ], true ) ) {
				$pairs[ $chunks[0] ][] = $chunks[1];
			}
		}

		// sort the pairs.
		ksort( $pairs );

		// return.
		return $pairs;
	}

	/**
	 * Should the IDs and classes be removed?
	 *
	 * @param bool $on (optional) Should we enable cleanup?.
	 *
	 * @return void
	 */
	public function setCleanup( $on = true ) {
		$this->cleanup = (bool) $on;
	}

	/**
	 * Set CSS to use
	 *
	 * @param string $css The CSS to use.
	 *
	 * @return void
	 */
	public function setCSS( $css ) {
		$this->css = (string) $css;
	}

	/**
	 * Set the encoding to use with the DOMDocument
	 *
	 * @param string $encoding The encoding to use.
	 *
	 * @return void
	 */
	public function setEncoding( $encoding ) {
		$this->encoding = (string) $encoding;
	}

	/**
	 * Set HTML to process
	 *
	 * @param string $html The HTML to process.
	 *
	 * @return void
	 */
	public function setHTML( $html ) {
		$this->html = (string) $html;
	}

	/**
	 * Set use of inline styles block
	 * If this is enabled the class will use the style-block in the HTML.
	 *
	 * @param bool $on (optional) Should we process inline styles?.
	 *
	 * @return void
	 */
	public function setUseInlineStylesBlock( $on = true ) {
		$this->useInlineStylesBlock = (bool) $on;
	}

	/**
	 * Set strip original style tags
	 * If this is enabled the class will remove all style tags in the HTML.
	 *
	 * @param bool $on (optional) Should we process inline styles?.
	 *
	 * @return void
	 */
	public function setStripOriginalStyleTags( $on = true ) {
		$this->stripOriginalStyleTags = (bool) $on;
	}

	/**
	 * Set exclude media queries
	 *
	 * If this is enabled the media queries will be removed before inlining the rules
	 *
	 * @param bool $on (optional).
	 *
	 * @return void
	 */
	public function setExcludeMediaQueries( $on = true ) {
		$this->excludeMediaQueries = (bool) $on;
	}

	/**
	 * Strip style tags into the generated HTML
	 *
	 * @param string $html The HTML to strip style tags.
	 *
	 * @return string
	 */
	private function stripOriginalStyleTags( $html ) {
		return preg_replace( '|<style(.*)>(.*)</style>|isU', '', $html );
	}

	/**
	 * Sort an array on the specifity element
	 *
	 * @param array $e1 The first element.
	 * @param array $e2 The second element.
	 *
	 * @return int
	 */
	private static function sortOnSpecifity( $e1, $e2 ) {
		// validate.
		if ( ! isset( $e1['specifity'] ) || ! isset( $e2['specifity'] ) ) {
			return 0;
		}

		// lower.
		if ( $e1['specifity'] < $e2['specifity'] ) {
			return - 1;
		}

		// higher.
		if ( $e1['specifity'] > $e2['specifity'] ) {
			return 1;
		}

		// fallback.
		return 0;
	}
}
