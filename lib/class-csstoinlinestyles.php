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

namespace WP_Email_Essentials;

use DOMDocument;
use DOMXPath;
use Exception;

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
	private $css_rules;

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
	private $use_inline_styles_block = false;

	/**
	 * Strip original style tags
	 *
	 * @var bool
	 */
	private $strip_original_style_tags = false;

	/**
	 * Exclude the media queries from the inlined styles
	 *
	 * @var bool
	 */
	private $exclude_media_queries = false;

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
			$this->set_html( $html );
		}
		if ( null !== $css ) {
			$this->set_css( $css );
		}
	}

	/**
	 * Convert a CSS-selector into an xPath-query
	 *
	 * @param string $selector The CSS-selector.
	 *
	 * @return string
	 */
	private function build_xpath_query( $selector ) {
		// redefine.
		$selector = (string) $selector;

		// the CSS selector.
		$css_selector = [
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
		$xpath_query = [
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
		$xpath = '//' . preg_replace( $css_selector, $xpath_query, $selector );

		return str_replace( '] *', ']//*', $xpath );
	}

	/**
	 * Calculate the specifity for the CSS-selector
	 *
	 * @param string $selector The selector to calculate the specifity for.
	 *
	 * @return int
	 */
	private function calculate_css_specificity( $selector ) {
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
	private function cleanup_html( $html ) {
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
	 * @param bool $output_xhtml (optional) Should we output valid XHTML?..
	 *
	 * @return string
	 *
	 * @throws Exception When html is empty.
	 */
	public function convert( $output_xhtml = false ) {
		// redefine.
		$output_xhtml = (bool) $output_xhtml;

		// validate.
		if ( null === $this->html ) {
			throw new Exception( 'No HTML provided.' );
		}

		// should we use inline style-block.
		if ( $this->use_inline_styles_block ) {
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
		$this->process_css();

		// create new DOMDocument.
		$document = new DOMDocument( '1.0', $this->get_encoding() );

		// set error level.
		libxml_use_internal_errors( true );

		// load HTML.
		$document->loadHTML( $this->html );

		// create new XPath.
		$xpath = new DOMXPath( $document );

		// any rules?.
		if ( ! empty( $this->css_rules ) ) {
			// loop rules.
			foreach ( $this->css_rules as $rule ) {
				// init var.
				$query = $this->build_xpath_query( $rule['selector'] );

				// validate query.
				if ( false === $query ) {
					continue;
				}

				// search elements.
				$elements = $xpath->query( $query );

				// validate elements.
				if ( false === $elements ) {
					continue;
				}

				// loop found elements.
				foreach ( $elements as $element ) {
					// no styles stored?.
					if ( null === $element->attributes->getNamedItem( 'data-css-to-inline-styles-original-styles' ) ) {
						// init var.
						$original_style = '';
						if ( null !== $element->attributes->getNamedItem( 'style' ) ) {
							$original_style = $element->attributes->getNamedItem( 'style' )->value;
						}

						// store original styles.
						$element->setAttribute(
							'data-css-to-inline-styles-original-styles',
							$original_style
						);

						// clear the styles.
						$element->setAttribute( 'style', '' );
					}

					// init var.
					$properties = [];

					// get current styles.
					$styles_attribute = $element->attributes->getNamedItem( 'style' );

					// any styles defined before?.
					if ( null !== $styles_attribute ) {
						// get value for the styles attribute.
						$defined_styles = (string) $styles_attribute->value;

						// split into properties.
						$defined_properties = explode( ';', $defined_styles );

						// loop properties.
						foreach ( $defined_properties as $property ) {
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
					$property_chunks = [];

					// build chunks.
					foreach ( $properties as $key => $values ) {
						foreach ( (array) $values as $value ) {
							$property_chunks[] = $key . ': ' . $value . ';';
						}
					}

					// build properties string.
					$properties_string = implode( ' ', $property_chunks );

					// set attribute.
					if ( '' !== $properties_string ) {
						$element->setAttribute( 'style', $properties_string );
					}
				}
			}

			// reapply original styles.
			$query = $this->build_xpath_query(
				'*[@data-css-to-inline-styles-original-styles]'
			);

			// validate query.
			if ( false === $query ) {
				return false;
			}

			// search elements.
			$elements = $xpath->query( $query );

			// loop found elements.
			foreach ( $elements as $element ) {
				// get the original styles.
				$original_style = $element->attributes->getNamedItem(
					'data-css-to-inline-styles-original-styles'
				)->value;

				if ( '' !== $original_style ) {
					$original_properties = [];
					$original_styles     = explode( ';', $original_style );

					foreach ( $original_styles as $property ) {
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
						$original_properties[ $chunks[0] ] = trim( $chunks[1] );
					}

					// get current styles.
					$styles_attribute = $element->attributes->getNamedItem( 'style' );
					$properties       = [];

					// any styles defined before?.
					if ( null !== $styles_attribute ) {
						// get value for the styles attribute.
						$defined_styles = (string) $styles_attribute->value;

						// split into properties.
						$defined_properties = explode( ';', $defined_styles );

						// loop properties.
						foreach ( $defined_properties as $property ) {
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
					foreach ( $original_properties as $key => $value ) {
						$properties[ $key ] = $value;
					}

					// build string.
					$property_chunks = [];

					// build chunks.
					foreach ( $properties as $key => $values ) {
						foreach ( (array) $values as $value ) {
							$property_chunks[] = $key . ': ' . $value . ';';
						}
					}

					// build properties string.
					$properties_string = implode( ' ', $property_chunks );

					// set attribute.
					if ( '' !== $properties_string ) {
						$element->setAttribute(
							'style',
							$properties_string
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
		if ( $output_xhtml ) {
			// set formating.
			$document->formatOutput = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMDocument.

			// get the HTML as XML.
			$html = $document->saveXML( null, LIBXML_NOEMPTYTAG );

			// get start of the XML-declaration.
			$start_position = strpos( $html, '<?xml' );

			// valid start position?.
			if ( false !== $start_position ) {
				// get end of the xml-declaration.
				$end_position = strpos( $html, '?>', $start_position );

				// remove the XML-header.
				$html = ltrim( substr( $html, $end_position + 1 ) );
			}
		} else {
			// just regular HTML 4.01 as it should be used in newsletters.
			// get the HTML.
			$html = $document->saveHTML();
		}

		// cleanup the HTML if we need to.
		if ( $this->cleanup ) {
			$html = $this->cleanup_html( $html );
		}

		// strip original style tags if we need to.
		if ( $this->strip_original_style_tags ) {
			$html = $this->strip_original_style_tags( $html );
		}

		// return.
		return $html;
	}


	/**
	 * Get the encoding to use
	 *
	 * @return string
	 */
	private function get_encoding() {
		return $this->encoding;
	}


	/**
	 * Process the loaded CSS
	 *
	 * @return void
	 */
	private function process_css() {
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

		if ( $this->exclude_media_queries ) {
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
			$css_properties = trim( $chunks[1] );

			// split multiple selectors.
			$selectors = explode( ',', $selectors );

			// loop selectors.
			foreach ( $selectors as $selector ) {
				// cleanup.
				$selector = trim( $selector );

				// build an array for each selector.
				$rule_set = [];

				// store selector.
				$rule_set['selector'] = $selector;

				// process the properties.
				$rule_set['properties'] = $this->process_css_properties(
					$css_properties
				);

				// calculate specifity.
				$rule_set['specifity'] = $this->calculate_css_specificity( $selector ) + $i;

				// add into global rules.
				$this->css_rules[] = $rule_set;
			}

			// increment.
			$i ++;
		}

		// sort based on specifity.
		if ( ! empty( $this->css_rules ) ) {
			usort( $this->css_rules, [ self::class, 'sort_on_specificity' ] );
		}
	}

	/**
	 * Process the CSS-properties
	 *
	 * @param string $property_string The CSS-properties.
	 *
	 * @return array
	 */
	private function process_css_properties( $property_string ) {
		// split into chunks.
		$properties = explode( ';', $property_string );

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
	public function set_cleanup( $on = true ) {
		$this->cleanup = (bool) $on;
	}

	/**
	 * Set CSS to use
	 *
	 * @param string $css The CSS to use.
	 *
	 * @return void
	 */
	public function set_css( $css ) {
		$this->css = (string) $css;
	}

	/**
	 * Set the encoding to use with the DOMDocument
	 *
	 * @param string $encoding The encoding to use.
	 *
	 * @return void
	 */
	public function set_encoding( $encoding ) {
		$this->encoding = (string) $encoding;
	}

	/**
	 * Set HTML to process
	 *
	 * @param string $html The HTML to process.
	 *
	 * @return void
	 */
	public function set_html( $html ) {
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
	public function set_use_inline_styles_block( $on = true ) {
		$this->use_inline_styles_block = (bool) $on;
	}

	/**
	 * Set strip original style tags
	 * If this is enabled the class will remove all style tags in the HTML.
	 *
	 * @param bool $on (optional) Should we process inline styles?.
	 *
	 * @return void
	 */
	public function set_strip_original_style_tags( $on = true ) {
		$this->strip_original_style_tags = (bool) $on;
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
	public function set_exclude_media_queries( $on = true ) {
		$this->exclude_media_queries = (bool) $on;
	}

	/**
	 * Strip style tags into the generated HTML
	 *
	 * @param string $html The HTML to strip style tags.
	 *
	 * @return string
	 */
	private function strip_original_style_tags( $html ) {
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
	private static function sort_on_specificity( $e1, $e2 ) {
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
