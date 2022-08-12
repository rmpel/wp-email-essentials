<?php
/**
 * The Gutenkit Customizer support class.
 *
 * This is a specialized module to support the Acato Designkit v2 (Codename: gutenkit).
 * It will set default styles and body content to create a basic but website-matching HTML-email
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

use Composer\Package\Loader\ValidatingArrayLoader;
use \Exception;

/**
 * The main plugin class.
 */
class GutenkitCustomizerSupport {
	public static function instance() {
		static $instance;
		if ( ! $instance ) {
			$instance = new static();
		}

		return $instance;
	}

	private static function local_storage( $var = null, $value = null ) {
		static $storage;
		if ( ! $storage ) {
			$storage = [];
		}

		if ( ! $var ) {
			return $storage;
		}
		if ( ! $value ) {
			return $storage[ $var ];
		}
		$storage[ $var ] = $value;

		return $storage[ $var ];
	}

	public function __construct() {
		add_filter( 'wpes_head', [ $this, 'head' ], ~PHP_INT_MAX, 2 );
		add_filter( 'wpes_subject', [ $this, 'subject' ], ~PHP_INT_MAX, 2 );
		add_filter( 'wpes_body', [ $this, 'body' ], ~PHP_INT_MAX, 2 );
		add_filter( 'wpes_css', [ $this, 'css' ], ~PHP_INT_MAX, 2 );
	}

	public function head( $head, &$phpmailer ) {
		local_storage( 'subject', $phpmailer->Subject );
		local_storage( 'date', date_i18n( 'l j F, Y', current_time( 'timestamp' ) ) );

		return $head;
	}

	public function subject( $subject, &$phpmailer ) {
		return $subject;
	}

	public function body( $content, &$phpmailer ) {
		$logo_id = get_theme_mod( 'custom_logo' );
		if ( $logo_id ) {
			$logo_path = get_attached_file( $logo_id );
			$logo_url  = wp_get_attachment_url( $logo_id );
			$logo_gd   = imagecreatefromstring( file_get_contents( $logo_path ) );
			$w         = imagesx( $logo_gd );
			$h         = imagesy( $logo_gd );
			$logo_cid  = md5( $logo_path );
			$phpmailer->addEmbeddedImage( $logo_path, $logo_cid, basename( $logo_path ) );
		}

		$subject = local_storage( 'subject' );
		$date    = local_storage( 'date' );

		$site_name   = get_bloginfo( 'name' );
		$site_slogan = get_bloginfo( 'description' );

		$footer = get_theme_mod( 'email_footer_html' ) ?: '<p><strong>' . $site_name . '</strong><br />' . $site_slogan . '</p>';

		$html = <<<EOHTML
<table width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
        <td valign="top" align="center">
            <table width="600" cellspacing="0" cellpadding="0" border="0">
                <tr class="header spacer"><td valign="top" colspan="4">&nbsp;<br /></td></tr>
                <tr class="header">
                    <td colspan="4" align="center">
                        <img width="$w" height="$h" src="cid:$logo_cid" alt="$site_name" style="width: ${w}px; height: ${h}px; background: url($logo_url) no-repeat left top; background-size: ${w}px ${h}px"/>
                    </td>
                </tr>
                <tr class="header spacer"><td valign="top" colspan="4">&nbsp;</td></tr>
                <tr class="subject">
                    <td valign="top" width="40"></td>
                    <td valign="top"><br />$subject<br /><br /></td>
                    <td valign="top" align="right"><br />$date</td>
                    <td valign="top" width="40"></td>
                </tr>
                <tr class="content spacer"><td valign="top" colspan="4">&nbsp;</td></tr>
                <tr class="content">
                    <td ></td>
                    <td valign="top" colspan="2">$content<br /><br /></td>
                    <td></td>
                </tr>
                <tr class="footer spacer"><td valign="top" colspan="4">&nbsp;</td></tr>
                <tr class="footer">
                    <td></td>
                    <td valign="top" colspan="2">
                        $footer
                    </td>
                    <td></td>
                </tr>
                <tr class="footer spacer"><td valign="top" colspan="4">&nbsp;</td></tr>
                <tr class="header spacer"><td valign="top" colspan="4">&nbsp;<br /></td></tr>
            </table>
        </td>
    </tr>
</table>
EOHTML;

		$html = preg_replace( '/^[ \t]+/', '', $html );

		return $html;
	}

	public function css( $css ) {
		$gutenkit_css = wp_upload_dir()['basedir'] . '/theme-styles.css';
		if ( is_file( $gutenkit_css ) ) {
			$css = '
:root{--font-body: "' . get_theme_mod( 'css' )['font']['body'] . '",sans-serif;}
' . file_get_contents( $gutenkit_css ) . '

body, body > table, body table tr.spacer, body table tr.spacer td, body table tr.spacer th {
	background: #ddd;
}
body > table table {
	color: var(--color-body);
	background: var(--color-body_background);

	font-family: var(--body-font-family);
	font-size: var(--body-font-size);
	font-weight: var(--body-font-weight);
	font-style: var(--body-font-style);
	line-height: var(--body-line-height);
}

h1 {
	color: var(--h1-color);
	font-family: var(--h1-font-family);
	font-size: var(--h1-font-size);
	font-weight: var(--h1-font-weight);
	font-style: var(--h1-font-style);
	line-height: var(--h1-line-height);		
}
h2 {
	color: var(--h2-color);
	font-family: var(--h2-font-family);
	font-size: var(--h2-font-size);
	font-weight: var(--h2-font-weight);
	font-style: var(--h2-font-style);
	line-height: var(--h2-line-height);		
}
h3 {
	color: var(--h3-color);
	font-family: var(--h3-font-family);
	font-size: var(--h3-font-size);
	font-weight: var(--h3-font-weight);
	font-style: var(--h3-font-style);
	line-height: var(--h3-line-height);		
}
h4 {
	color: var(--h4-color);
	font-family: var(--h4-font-family);
	font-size: var(--h4-font-size);
	font-weight: var(--h4-font-weight);
	font-style: var(--h4-font-style);
	line-height: var(--h4-line-height);		
}
h5 {
	color: var(--h5-color);
	font-family: var(--h5-font-family);
	font-size: var(--h5-font-size);
	font-weight: var(--h5-font-weight);
	font-style: var(--h5-font-style);
	line-height: var(--h5-line-height);		
}
h6 {
	color: var(--h6-color);
	font-family: var(--h6-font-family);
	font-size: var(--h6-font-size);
	font-weight: var(--h6-font-weight);
	font-style: var(--h6-font-style);
	line-height: var(--h6-line-height);		
}
';
		}

		$css = self::variables_to_values( $css );

		return $css;
	}

	/**
	 * Experimental; reduce CSS variables to their values.
	 *
	 * @param string $css The CSS.
	 *
	 * @return string
	 */
	private static function variables_to_values( $css ) {
		// Find defined variables;
		$variables = [];
		preg_match_all( '/[^(]--([^:]+):([^;}]+)/', $css, $matches, PREG_PATTERN_ORDER );
		foreach ( $matches[1] as $i => $match ) {
			$variables[ $match ] = $matches[2][ $i ];
		}

		// Find variable usage;
		$variable_use = [];
		preg_match_all('/var\(--([^,)]+)(,[^)]+)?\)/', $css,$matches, PREG_PATTERN_ORDER);
		foreach ($matches[1] as $i => $variable) {
			$variable_use[ $variable ] = $variables[ $variable ] ?? trim($match[2], ',) ');
		}

		// Evaluate variables defined by variables;
		foreach ($variable_use as $variable => &$value) {
			if (preg_match('/var\(--([^,)]+)(,[^)]+)?\)/', $value,$match ) ) {
				$value = $variable_use[ $match[1] ] ?? trim($match[2], ',) ');
			}
		}

		// Replace variables in stylesheet;
		preg_match_all('/var\(--([^,)]+)(,[^)]+)?\)/', $css,$matches, PREG_PATTERN_ORDER);
		foreach ($matches[1] as $i => $variable) {
			$css = str_replace( $matches[0][$i], $variable_use[ $variable ], $css );
		}

		// Remove variable definitions;
		preg_match_all( '/[^(](--([^:]+):([^;}]+))/', $css, $matches, PREG_PATTERN_ORDER );
		// Sort replacements by size so they are all-inclusive.
		$replacements = $matches[1];
		$sort         = array_map( 'strlen', $replacements );
		array_multisort( $sort, SORT_DESC, $replacements );
		$css = str_replace( $replacements, '', $css );
		$css = preg_replace( '/\{;*\}/', '{}', $css );

		return $css;
	}
}