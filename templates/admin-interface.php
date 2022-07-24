<?php
/**
 * View: admin interface.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'wpes' ) ) );
}
global $current_user;
$wpes_config = Plugin::get_config();

$wpes_host = Plugin::get_hostname_by_blogurl();

?>
<div class="wrap wpes-wrap wpes-settings">
	<?php
	Plugin::template_header( __( 'E-mail Configuration', 'wpes' ) );
	if ( Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	?>
	<?php
	if ( Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}
	?>
	<form id="outpost" method='POST' action="" enctype="multipart/form-data">
		<input type="hidden" name="form_id" value="wp-email-essentials"/>
		<?php wp_nonce_field( 'wp-email-essentials--settings', 'wpes-nonce' ); ?>
		<table class="wpes-table">
			<tr>
				<td colspan="4" class="last">
					<h3><?php print wp_kses_post( __( 'Basic information', 'wpes' ) ); ?>:</h3>
				</td>
			</tr>
			<tr>
				<th>
					<label for="from-name"><?php print wp_kses_post( __( 'Default from name', 'wpes' ) ); ?></label>
				</th>
				<td>
					<input
						type="text" class="widefat" name="settings[from_name]"
						value="<?php print esc_attr( $wpes_config['from_name'] ); ?>"
						placeholder="WordPress"
						id="from-name"/>
				</td>
				<td colspan="2" rowspan="2">
					<?php print wp_kses_post( sprintf( __( 'Out of the box, WordPress will use name "WordPress" and e-mail "wordpress@%s" as default sender. This is far from optimal. Your first step is therefore to set an appropriate name and e-mail address.', 'wpes' ), $wpes_host ) ); ?>
				</td>
			</tr>
			<tr>
				<th>
					<label for="from-email"><?php print wp_kses_post( __( 'Default from e-mail', 'wpes' ) ); ?></label>
				</th>
				<td>
					<input
						type="text" class="widefat" name="settings[from_email]"
						value="<?php print esc_attr( $wpes_config['from_email'] ); ?>"
						placeholder="wordpress@<?php print esc_attr( $wpes_host ); ?>"
						id="from-email"/>
				</td>
			</tr>
			<tr class="on-regexp-test" data-regexp="(no-?reply)@" data-field="from-email">
				<td colspan="4" class="last">
					<strong
						style="color:darkred"><?php print wp_kses_post( __( 'Under GDPR, from May 25th, 2018, using a no-reply@ (or any variation of a not-responded-to e-mail address) is prohibited. Please make sure the default sender address is valid and used in the setting below.', 'wpes' ) ); ?></strong>
				</td>
			</tr>
			<?php
			if ( $wpes_config['spf_lookup_enabled'] ) {
				// SPF match.
				?>
				<?php if ( ! Plugin::i_am_allowed_to_send_in_name_of( $wpes_config['from_email'] ) ) { ?>
					<tr>
						<th>
						</th>
						<td
							colspan="3"
							class=last><?php print wp_kses_post( __( 'SPF Records are checked', 'wpes' ) ); ?>
							: <?php print wp_kses_post( __( 'you are NOT allowed to send mail with this domain.', 'wpes' ) ); ?>
							<br/>
							<?php print wp_kses_post( __( 'If you really need to use this sender e-mail address, you need to change the SPF record to include the sending-IP of this server', 'wpes' ) ); ?>
							;<br/>
							<?php print wp_kses_post( __( 'Old', 'wpes' ) ); ?>:
							<code><?php print wp_kses_post( Plugin::get_spf( $wpes_config['from_email'], false, true ) ); ?></code><br/>
							<?php print wp_kses_post( __( 'New', 'wpes' ) ); ?>:
							<code><?php print wp_kses_post( Plugin::get_spf( $wpes_config['from_email'], true, true ) ); ?></code>
						</td>
					</tr>
					<?php
				} else {
					?>
					<tr>
						<td></td>
						<td colspan="3" class=last><?php print wp_kses_post( __( 'SPF Record', 'wpes' ) ); ?>:
							<code><?php print wp_kses_post( Plugin::get_spf( $wpes_config['from_email'], false, true ) ); ?></code>
						</td>
					</tr>
					<?php
				}
			} else {
				// domain match.
				if ( ! Plugin::i_am_allowed_to_send_in_name_of( $wpes_config['from_email'] ) ) {
					?>
					<tr>
						<th>
						</th>
						<td
							colspan="3"
							class=last><?php print wp_kses_post( __( 'You are NOT allowed to send mail with this domain; it should match the domainname of the website.', 'wpes' ) ); ?>
							<br/>
							<?php print wp_kses_post( __( 'If you really need to use this sender e-mail address, you need to switch to SPF-record checking and make sure the SPF for this domain matches this server.', 'wpes' ) ); ?>
						</td>
					</tr>
					<?php
				}
			}
			?>
			<tr>
				<td colspan="4" class="last">
					<h4><?php print wp_kses_post( __( 'How to validate sender?', 'wpes' ) ); ?></h4>
				</td>
			</tr>
			<tr>
				<td colspan="4" class="last">
					<?php print wp_kses_post( __( 'You have 2 options', 'wpes' ) ); ?>:
					<ul>
						<li><input
								type="radio" name="settings[spf_lookup_enabled]" value="0"
								<?php checked( ! isset( $wpes_config['spf_lookup_enabled'] ) || ! $wpes_config['spf_lookup_enabled'] ); ?>
								id="spf_lookup_enabled_0"/>
							<label for="spf_lookup_enabled_0">
								<?php print wp_kses_post( __( '<strong>Domain name</strong>: Use a simple match on hostname; any e-mail address that matches the base domainname of this website is considered valid.', 'wpes' ) ); ?>
							</label>
						</li>
						<li><input
								type="radio" name="settings[spf_lookup_enabled]" value="1"
								<?php checked( isset( $wpes_config['spf_lookup_enabled'] ) && $wpes_config['spf_lookup_enabled'] ); ?>
								id="spf_lookup_enabled_1"/>
							<label for="spf_lookup_enabled_1">
								<?php print wp_kses_post( __( '<strong>SPF</strong>: Use SPF records to validate the sender. If the SPF record of the domain of the e-mail address used as sender matches the IP-address this website is hosted on, the e-mail address is considered valid.', 'wpes' ) ); ?>
							</label>
						</li>
					</ul>
				</td>
			</tr>
			<tr>
				<td colspan="4" class="last">
					<hr/>
				</td>
			</tr>
			<tr>
				<td colspan="4" class="last">
					<h3><?php print wp_kses_post( __( 'What to do in case the sender is not valid for this domain?', 'wpes' ) ); ?></h3>
				</td>
			</tr>
			<tr>
				<th>
					<label
						for="make_from_valid"><?php print wp_kses_post( __( 'Fix sender-address?', 'wpes' ) ); ?></label>
				</th>
				<td colspan="3" class="last">
					<?php print wp_kses_post( __( 'E-mails sent as different domain will probably be marked as spam. Use the options here to fix the sender-address to always match the sending domain.', 'wpes' ) ); ?>
					<br/>
					<?php print wp_kses_post( __( 'The actual sender of the e-mail will be used as <code>Reply-To</code>; you can still use the Reply button in your e-mail application to send a reply easily.', 'wpes' ) ); ?>
				</td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'When the sender e-mail address...', 'wpes' ); ?></td>
				<td colspan="3">
					<input
						id="wpes-settings-make_from_valid_when-when_sender_invalid"
						type="radio"
						name="settings[make_from_valid_when]"
						value="when_sender_invalid" <?php checked( 'when_sender_invalid', $wpes_config['make_from_valid_when'] ); ?>
					><label
						class="on-regexp-test" data-field="spf_lookup_enabled_0" data-regexp="0"
						for="wpes-settings-make_from_valid_when-when_sender_invalid"><?php print wp_kses_post( __( 'is not on the website domain...', 'wpes' ) ); ?>
					</label><label
						class="on-regexp-test" data-field="spf_lookup_enabled_1" data-regexp="1"
						for="wpes-settings-make_from_valid_when-when_sender_invalid"><?php print wp_kses_post( __( 'is not allowed by SPF from this website...', 'wpes' ) ); ?>
					</label>
					<br/>
					<input
						id="wpes-settings-make_from_valid_when-when_sender_not_as_set"
						type="radio"
						name="settings[make_from_valid_when]"
						value="when_sender_not_as_set" <?php checked( 'when_sender_not_as_set', $wpes_config['make_from_valid_when'] ); ?>
					><label
						for="wpes-settings-make_from_valid_when-when_sender_not_as_set"><?php print wp_kses_post( __( 'is not the "Default from e-mail" as set above...', 'wpes' ) ); ?></label>
				</td>
			</tr>
			<tr>
				<td></td>
				<td colspan="3">
					<select class="widefat" name="settings[make_from_valid]" id="make_from_valid">
						<option
							value=""><?php print wp_kses_post( __( 'Keep the possibly-invalid sender as is. (might cause your mails to be marked as spam!)', 'wpes' ) ); ?></option>
						<option disabled>────────────────────────────────────────────────────────────</option>
						<option value="-at-" <?php selected( '-at-', $wpes_config['make_from_valid'] ); ?>>
							<?php print esc_html( sprintf( __( 'Rewrite e-mail@addre.ss to e-mail-at-addre-dot-ss@%s', 'wpes' ), $wpes_host ) ); ?>
						</option>
						<option value="noreply" <?php selected( 'noreply', $wpes_config['make_from_valid'] ); ?>>
							<?php print esc_html( sprintf( __( 'Rewrite e-mail@addre.ss to noreply@%s', 'wpes' ), $wpes_host ) ); ?>
							<?php print esc_html( __( '(Not GDPR Compliant)', 'wpes' ) ); ?>
						</option>
						<?php
						$wpes_default_sender_mail = Plugin::wp_mail_from( $wpes_config['from_email'] );
						if ( Plugin::i_am_allowed_to_send_in_name_of( $wpes_default_sender_mail ) ) {
							?>
							<option value="default" <?php selected( 'default', $wpes_config['make_from_valid'] ); ?>>
								<?php print esc_html( sprintf( __( 'Rewrite e-mail@addre.ss to %s', 'wpes' ), $wpes_default_sender_mail ) ); ?>
							</option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<hr/>
				</th>
			</tr>

			<tr>
				<td colspan="4" class="last">
					<h3><?php print wp_kses_post( __( 'E-mail History', 'wpes' ) ); ?>:</h3>
				</td>
			</tr>
			<tr>
				<th>
					<input
						type="checkbox" name="settings[enable_history]" value="1"
						<?php checked( $wpes_config['enable_history'] ); ?>
						id="enable_history"/>
					<label
						for="enable_history"><?php print wp_kses_post( __( 'Enable E-mail History', 'wpes' ) ); ?></label>
				</th>
				<td colspan="3"></td class=last>
			</tr>
			<tr class="last on-enable_history">
				<td colspan="4" class="last">
					<?php print wp_kses_post( __( '<strong>Warning: </strong> Storing e-mails in your database is a BAD idea and illegal in most countries. Use this for DEBUGGING only!', 'wpes' ) ); ?>
					<br/>
					<?php print wp_kses_post( __( 'Enabling the history feature will also add a tracker to all outgoing e-mails to check receipt.', 'wpes' ) ); ?>
					<br/>
					<?php print wp_kses_post( __( 'Disabling this feature will delete the e-mail history database tables.', 'wpes' ) ); ?>
					<br/>
					<strong
						style="color: darkred"><?php print wp_kses_post( __( 'If you insist on storing e-mails, please note that you need to implement the appropriate protocols for compliance with GDPR. The responsibility lies with the owner of the website, not the creator or hosting company.', 'wpes' ) ); ?></strong>
				</th>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<hr/>
				</th>
			</tr>
			<tr>
				<td colspan="4" class="last">
					<h3><?php print wp_kses_post( __( 'E-mail Settings', 'wpes' ) ); ?>:</h3>
				</td>
			</tr>
			<tr>
				<th colspan="2">
					<input
						type="checkbox" name="settings[smtp-enabled]" value="1"
						<?php checked( isset( $wpes_config['smtp'] ) && $wpes_config['smtp'] ); ?>
						id="smtp-enabled"/><label
						for="smtp-enabled"><?php print wp_kses_post( __( 'Enable sending mail over SMTP?', 'wpes' ) ); ?></label>
				</th>
				<th colspan="2" rowspan="8" class="last on-smtp-enabled">
					<?php
					print wp_kses_post( __( 'Using an SMTP improves reliability, helps reducing the chance of your e-mails being marked as spam and gives the option to use an external mail service like MailJet, MailGun, SparkPost etc.', 'wpes' ) );
					?>
				</th>
			</tr>
			<tr class="last not-smtp-enabled">
				<th colspan="4">
					<?php
					print wp_kses_post( __( 'Using an SMTP improves reliability, helps reducing the chance of your e-mails being marked as spam and gives the option to use an external mail service like MailJet, MailGun, SparkPost etc.', 'wpes' ) );
					?>
				</th>
			</tr>
			<tr class="on-smtp-enabled">
				<th width="25%">
					<label for="smtp-hostname"><?php print wp_kses_post( __( 'Hostname or -ip', 'wpes' ) ); ?></label>
				</th>
				<td width="25%">
					<input
						type="text" class="widefat" name="settings[host]"
						value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['host'] : '' ); ?>"
						id="smtp-hostname"/>
				</td>
			</tr>
			<tr class="on-smtp-enabled">
				<th width="25%">
					<label for="smtp-port"><?php print wp_kses_post( __( 'SMTP Port', 'wpes' ) ); ?></label>
				</th>
				<td width="25%">
					<input
						type="text" class="widefat" name="settings[port]"
						value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['port'] : '' ); ?>"
						id="smtp-port"/>
				</td>
			</tr>
			<tr class="on-smtp-enabled">
				<th>
					<label for="smtp-username"><?php print wp_kses_post( __( 'Username', 'wpes' ) ); ?></label>
				</th>
				<td>
					<input
						type="text" class="widefat" name="settings[username]"
						value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['username'] : '' ); ?>"
						id="smtp-username"/>
				</td>
			</tr>
			<tr class="on-smtp-enabled">
				<th>
					<label for="smtp-password"><?php print wp_kses_post( __( 'Password', 'wpes' ) ); ?></label>
				</th>
				<td>
					<input
						type="password" class="widefat" name="settings[password]"
						value="<?php print esc_attr( $wpes_config['smtp'] ? str_repeat( '*', strlen( $wpes_config['smtp']['password'] ) ) : '' ); ?>"
						id="smtp-password"/>
				</td>
			</tr>
			<tr class="on-smtp-enabled">
				<th>
					<label
						for="smtp-secure"><?php print wp_kses_post( __( 'Use encrypted connection?', 'wpes' ) ); ?></label>
				</th>
				<td>
					<select name="settings[secure]" class="widefat" id="smtp-secure">
						<option value=""><?php print wp_kses_post( __( 'No', 'wpes' ) ); ?></option>
						<option disabled>───────────────────────</option>
						<option disabled><?php print wp_kses_post( __( 'Use encrypted connection', 'wpes' ) ); ?>
							- <?php print wp_kses_post( __( 'strict SSL verify', 'wpes' ) ); ?></option>
						<option
							value="ssl" <?php selected( $wpes_config['smtp'] && 'ssl' === $wpes_config['smtp']['secure'] ); ?>
						><?php print wp_kses_post( __( 'SSL', 'wpes' ) ); ?>
						</option>
						<option
							value="tls" <?php selected( $wpes_config['smtp'] && 'tls' === $wpes_config['smtp']['secure'] ); ?>
						><?php print wp_kses_post( __( 'StartTLS', 'wpes' ) ); ?>
						</option>
						<option disabled>───────────────────────</option>
						<option disabled><?php print wp_kses_post( __( 'Use encrypted connection', 'wpes' ) ); ?>
							- <?php print wp_kses_post( __( 'allow self-signed SSL', 'wpes' ) ); ?></option>
						<option
							value="ssl-" <?php selected( $wpes_config['smtp'] && 'ssl-' === $wpes_config['smtp']['secure'] ); ?>
						><?php print wp_kses_post( __( 'SSL', 'wpes' ) ); ?>
						</option>
						<option
							value="tls-" <?php selected( $wpes_config['smtp'] && 'tls-' === $wpes_config['smtp']['secure'] ); ?>
						><?php print wp_kses_post( __( 'StartTLS', 'wpes' ) ); ?>
						</option>
					</select>
				</td>
			</tr>
			<tr class="on-smtp-enabled">
				<th>
					<label for="timeout"><?php print wp_kses_post( __( 'phpMailer Timeout', 'wpes' ) ); ?></label>
				</th>
				<td>
					<select id="timeout" name="settings[timeout]">
						<?php
						$wpes_timeouts = [
							60  => __( '1 minute', 'wpes' ),
							300 => __( '5 minutes (default)', 'wpes' ),
							600 => __( '10 minutes (for very slow hosts)', 'wpes' ),
						];
						if ( ! isset( $wpes_config['timeout'] ) || ! $wpes_config['timeout'] ) {
							$wpes_config['timeout'] = 300;
						}
						foreach ( $wpes_timeouts as $wpes_key => $wpes_val ) {
							print '<option value="' . esc_attr( $wpes_key ) . '" ' . selected( intval( $wpes_config['timeout'] ), $wpes_key, false ) . '>' . esc_html( $wpes_val ) . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="4" class="last">
					<hr/>
				</td>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<input
						type="checkbox" name="settings[SingleTo]" value="1"
						<?php checked( isset( $wpes_config['SingleTo'] ) && $wpes_config['SingleTo'] ); ?>
						id="smtp-singleto"/><label
						for="smtp-singleto"><?php print wp_kses_post( __( 'Split mail with more than one Recipient into separate mails?', 'wpes' ) ); ?></label>
				</th>
			</tr>
			<tr>
				<td colspan="4" class="last">
					<hr/>
				</td>
			</tr>
			<tr>
				<td colspan="1" rowspan="2">
					<h3><?php print wp_kses_post( __( 'E-mail content', 'wpes' ) ); ?>:</h3>
				</td>
				<td colspan="3" class="last">
					<input
						type="checkbox" name="settings[is_html]" value="1"
						<?php checked( isset( $wpes_config['is_html'] ) && $wpes_config['is_html'] ); ?>
						id="smtp-is_html"/><label
						for="smtp-is_html"><?php print wp_kses_post( __( 'Send as HTML? (Will convert non-html body to html-ish body)', 'wpes' ) ); ?></label>
				</td>
			</tr>
			<tr>
				<td colspan="3" class="last">
					<input
						type="checkbox" name="settings[css_inliner]" value="1"
						<?php checked( isset( $wpes_config['css_inliner'] ) && $wpes_config['css_inliner'] ); ?>
						id="smtp-css_inliner"/><label
						for="smtp-css_inliner"><?php print wp_kses_post( __( 'Convert CSS to Inline Styles (for Outlook Online, Yahoo Mail, Google Mail, Hotmail)', 'wpes' ) ); ?></label>
				</td>
			</tr>
			<tr>
				<th><?php print wp_kses_post( __( 'Content charset re-coding', 'wpes' ) ); ?></th>
				<td colspan="3" class="last">
					<label
						for="content-precoding"><?php print wp_kses_post( __( 'Some servers have f*cked-up content-encoding settings, resulting in wrongly encoded diacritics. If you expect a character like &eacute; and all you get is something like &euro;&tilde;&Itilde;, experiment with this setting.', 'wpes' ) ); ?></label><br/>
					<select id="content-precoding" name="settings[content_precode]">
						<?php
						$wpes_encoding_table         = explode( ',', '0,auto,' . Plugin::ENCODINGS );
						$wpes_encoding_table         = array_combine( $wpes_encoding_table, $wpes_encoding_table );
						$wpes_encoding_table         = array_map(
							function ( $item ) {
								// translators: %s: a content-encoding, like UTF-8.
								return sprintf( _x( 'From: %s', 'E.g.: From: UTF-8', 'wpes' ), strtoupper( $item ) );
							},
							$wpes_encoding_table
						);
						$wpes_encoding_table['0']    = __( 'No charset re-coding (default)', 'wpes' );
						$wpes_encoding_table['auto'] = __( 'Autodetect with mb_check_encoding()', 'wpes' );
						foreach ( $wpes_encoding_table as $wpes_encoding => $wpes_nice_encoding ) {
							print '<option value="' . esc_attr( $wpes_encoding ) . '" ' . selected( $wpes_config['content_precode'], $wpes_encoding, false ) . '>' . esc_html( $wpes_nice_encoding ) . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th rowspan="2"><?php esc_html_e( 'Content handling', 'wpes' ); ?></th>
				<td colspan="3" class="last">
					<input
						type="checkbox" name="settings[alt_body]" value="1"
						<?php checked( isset( $wpes_config['alt_body'] ) && $wpes_config['alt_body'] ); ?>
						id="smtp-alt_body"/><label
						for="smtp-alt_body"><?php print wp_kses_post( __( 'Derive plain-text alternative? (Will derive text-ish body from html body as AltBody)', 'wpes' ) ); ?></label>
				</td>
			</tr>
			<tr>
				<td colspan="3" class="last">
					<input
						type="checkbox" name="settings[do_shortcodes]" value="1"
						<?php checked( isset( $wpes_config['do_shortcodes'] ) && $wpes_config['do_shortcodes'] ); ?>
						id="do_shortcodes"/><label
						for="do_shortcodes"><?php print wp_kses_post( __( 'Process the body with <code>do_shortcode()</code>', 'wpes' ) ); ?></label>
				</td>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<hr/>
				</th>
			</tr>

			<?php if ( function_exists( 'openssl_pkcs7_sign' ) ) { ?>
				<tr>
					<td colspan="4" class="last">
						<h3><?php print wp_kses_post( __( 'Digital E-mail Signing (S/MIME)', 'wpes' ) ); ?>:</h3>
					</td>
				</tr>
				<tr>
					<td>
						<input
							type="checkbox" name="settings[enable_smime]" value="1"
							<?php checked( isset( $wpes_config['enable_smime'] ) && $wpes_config['enable_smime'] ); ?>
							id="enable-smime"/><label
							for="enable-smime"><?php print wp_kses_post( __( 'Sign e-mails with S/MIME certificate', 'wpes' ) ); ?></label>
					</td>
					<td class="on-enable-smime">
						<label
							for="certfolder"><?php print wp_kses_post( __( 'S/MIME Certificate/Private-Key path', 'wpes' ) ); ?></label>
					</td>
					<td colspan="2" class="last on-enable-smime">
						<input
							type="text" class="widefat" name="settings[certfolder]"
							value="<?php print esc_attr( $wpes_config['certfolder'] ); ?>" id="certfolder"/>
					</td>
				</tr>
				<tr class="on-enable-smime">
					<td></td>
					<td colspan="3" class="last">
						<?php
						if ( Plugin::path_is_in_web_root( $wpes_config['certificate_folder'] ) ) {
							?>
							<strong><?php print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s</code> to prevent stealing your identity.', 'wpes' ), Plugin::suggested_safe_path_for( '.smime' ) ) ); ?></strong>
							<br/>
							<?php
						}
						?>
						<?php
						print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against the document-root of your website.', 'wpes' ) ) . '<br />';
						print wp_kses_post( __( 'The file-naming convention is', 'wpes' ) ) . ':<br />';
						print wp_kses_post( __( 'certificate: <code>e-mail@addre.ss.crt</code>', 'wpes' ) ) . ',<br />';
						print wp_kses_post( __( 'private key: <code>e-mail@addre.ss.key</code>', 'wpes' ) ) . ',<br />';
						print wp_kses_post( __( '(optional) passphrase: <code>e-mail@addre.ss.pass</code>', 'wpes' ) ) . '.';
						?>
					</td>
				</tr>
				<?php
				if ( isset( $wpes_config['certfolder'] ) ) {
					$wpes_smime_identities         = [];
					$wpes_smime_certificate_folder = $wpes_config['certificate_folder'];
					if ( is_dir( $wpes_smime_certificate_folder ) ) {
						$wpes_smime_files      = glob( $wpes_smime_certificate_folder . '/*.crt' );
						$wpes_smime_identities = Plugin::list_smime_identities();
						$wpes_smime_identities = array_keys( $wpes_smime_identities );
					} else {
						?>
						<tr class="on-enable-smime">
							<td colspan="4" class="last" style="color:red;" class="last">
								<strong>
									<?php
									print wp_kses_post( sprintf( __( 'Set folder <code>%s</code> not found.', 'wpes' ), $wpes_config['certfolder'] ) );
									if ( $wpes_smime_certificate_folder !== $wpes_config['certfolder'] ) {
										print ' ' . wp_kses_post( sprintf( __( 'Expanded path: <code>%s</code>', 'wpes' ), $wpes_smime_certificate_folder ) );
									}
									print ' ' . wp_kses_post( sprintf( __( 'Evaluated path: <code>%s</code>', 'wpes' ), realpath( $wpes_smime_certificate_folder ) ) );
									?>
							</td>
						</tr>
						<?php
					}
					if ( $wpes_smime_identities ) {
						?>
						<tr>
							<th colspan="4" class="last">
								<?php print wp_kses_post( sprintf( __( 'Found S/MIME identities for the following senders: <code>%s</code>', 'wpes' ), implode( '</code>, <code>', $wpes_smime_identities ) ) ); ?>
							</th>
						</tr>
						<?php
					}
				}
				?>
				<tr>
					<th colspan="4" class="last">
						<hr/>
					</th>
				</tr>
			<?php } else { ?>
				<tr>
					<td colspan="4" class="last">
						<input type="hidden" name="settings[enable_smime]" value="0"/>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td colspan="4" class="last">
					<h3><?php print wp_kses_post( __( 'Digital E-mail Signing (DKIM)', 'wpes' ) ); ?>:</h3>
				</td>
			</tr>
			<tr>
				<td>
					<input
						type="checkbox" name="settings[enable_dkim]" value="1"
						<?php checked( isset( $wpes_config['enable_dkim'] ) && $wpes_config['enable_dkim'] ); ?>
						id="enable-dkim"/><label
						for="enable-dkim"><?php print wp_kses_post( __( 'Sign e-mails with DKIM certificate', 'wpes' ) ); ?></label>
				</td>
				<td class="on-enable-dkim">
					<label
						for="dkimfolder"><?php print wp_kses_post( __( 'DKIM Certificate/Private-Key path', 'wpes' ) ); ?></label>
				</td>
				<td colspan="2" class="last on-enable-dkim">
					<input
						type="text" class="widefat" name="settings[dkimfolder]"
						value="<?php print esc_attr( $wpes_config['dkimfolder'] ); ?>" id="dkimfolder"/>
				</td>
			</tr>
			<tr class="on-enable-dkim">
				<td></td>
				<td colspan="3" class="last">
					<?php
					if ( Plugin::path_is_in_web_root( $wpes_config['dkimfolder'] ) ) {
						?>
						<strong><?php print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s</code> to prevent stealing your identity.', 'wpes' ), Plugin::suggested_safe_path_for( '.dkim' ) ) ); ?></strong>
						<br/>
						<?php
					}
					?>
					<?php print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your WordPress).', 'wpes' ) ); ?>
					<br/>
					<?php
					print wp_kses_post( __( 'The file-naming convention is', 'wpes' ) ) . ':<br />';
					print wp_kses_post( __( 'certificate: <code>domain.tld.crt</code>', 'wpes' ) ) . ',<br />';
					print wp_kses_post( __( 'private key: <code>domain.tld.key</code>', 'wpes' ) ) . ',<br />';
					print wp_kses_post( __( 'DKIM Selector: <code>domain.tld.selector</code>', 'wpes' ) ) . ',<br />';
					print wp_kses_post( __( '(optional) passphrase: <code>domain.tld.pass</code>', 'wpes' ) ) . '.';
					?>
				</td>
			</tr>
			<tr class="on-enable-dkim">
				<td></td>
				<td colspan="3" class="last">
					<?php
					print wp_kses_post( __( 'To generate DKIM keys, use', 'wpes' ) ) . ':<br />';
					print wp_kses_post( '<code>openssl genrsa -aes256 -passout pass:"' . _x( 'YOUR-PASSWORD', 'A sample password', 'wpes' ) . '" -out domain.tld.key 2048</code><br />' );
					print wp_kses_post( '<code>openssl rsa -in domain.tld.key -pubout > domain.tld.crt</code><br />' );
					print wp_kses_post( '<code>echo "' . _x( 'YOUR-PASSWORD', 'A sample password', 'wpes' ) . '" > domain.tld.pass</code><br />' );
					print wp_kses_post( '<code>echo "' . _x( 'DKIM-SELECTOR-FOR-THIS-KEY', 'A sample DKIM selector', 'wpes' ) . '" > domain.tld.selector</code><br />' );
					?>
				</td>
			</tr>
			<tr class="on-enable-dkim">
				<td></td>
				<td colspan="3" class="last">
					<?php
					esc_html__( 'Upload these files to the specified path on the server and again; this should not be publicly queriable!!!', 'wpes' );
					?>
				</td>
			</tr>
			<tr class="on-enable-dkim">
				<td></td>
				<td colspan="3" class="last">
					<?php esc_html_e( 'Finally, register the domain key in the DNS', 'wpes' ); ?>
					<br/>
					<?php print wp_kses_post( '<code>' . _x( 'DKIM-SELECTOR-FOR-THIS-KEY', 'A sample DKIM selector', 'wpes' ) . '._domainkey.domain.tld. IN TXT "v=DKIM1; k=rsa; p=' . _x( 'CONTENT-OF', 'A tag that tells the user to get the content of a file', 'wpes' ) . '-domain.tld.crt"</code>' ); ?>
					<br/>
					<?php
					// translators: %1$s and %2$s are sample content lines to be removed from the key.
					print esc_html( sprintf( __( 'Remove the lines "%1$s" and "%2$s" and place the rest of the content on a single line.', 'wpes' ), '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----' ) );
					?>
					<br/>
				</td>
			</tr>
			<tr class="on-enable-dkim">
				<td></td>
				<td colspan="3" class="last">
					<?php
					// translators: %s: an URL: to a testing site.
					print wp_kses_post( sprintf( __( 'Test your settings with <a href="%s" target="_blank">DMARC Analyser</a> (unaffiliated)', 'wpes' ), esc_attr( 'https://www.dmarcanalyzer.com/dkim/dkim-check/' ) ) );
					?>
				</td>
			</tr>
			<?php
			if ( isset( $wpes_config['dkimfolder'] ) ) {
				$wpes_dkim_identities         = [];
				$wpes_dkim_certificate_folder = $wpes_config['dkim_certificate_folder'];
				if ( is_dir( $wpes_dkim_certificate_folder ) ) {
					$wpes_dkim_identities = Plugin::list_dkim_identities();
					$wpes_dkim_identities = array_keys( $wpes_dkim_identities );
				} else {
					?>
					<tr class="on-enable-dkim">
						<td colspan="4" class="last" style="color:red;" class="last">
							<strong>
								<?php
								print wp_kses_post( sprintf( __( 'Set folder <code>%s</code> not found.', 'wpes' ), $wpes_config['dkimfolder'] ) );
								if ( $wpes_dkim_certificate_folder !== $wpes_config['dkimfolder'] ) {
									print ' ' . wp_kses_post( sprintf( __( 'Expanded path: <code>%s</code>', 'wpes' ), $wpes_dkim_certificate_folder ) );
								}
								print ' ' . wp_kses_post( sprintf( __( 'Evaluated path: <code>%s</code>', 'wpes' ), realpath( $wpes_dkim_certificate_folder ) ) );
								?>
						</td>
					</tr>
					<?php
				}
				if ( $wpes_dkim_identities ) {
					?>
					<tr class="on-enable-dkim">
						<td></td>
						<th colspan="3" class="last">
							<?php
							// translators: %s: a list of domains.
							print wp_kses_post( sprintf( __( 'Found DKIM certificates for the following sender-domains: %s', 'wpes' ), '<code>' . implode( '</code>, <code>', $wpes_dkim_identities ) . '</code>' ) );
							?>
						</th>
					</tr>
					<?php
				}
			}
			?>
			<tr>
				<th colspan="4" class="last">
					<hr/>
				</th>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<input
						type="submit" name="op" value="<?php print esc_attr__( 'Save settings', 'wpes' ); ?>"
						class="button-primary action"/>
					<!-- input type="submit" name="op" value="<?php print esc_attr__( 'Print debug output of sample mail', 'wpes' ); ?>" class="button-secondary action" / -->
					<input
						type="submit" name="op" value="<?php print esc_attr__( 'Send sample mail', 'wpes' ); ?>"
						class="button-secondary action"/>
					<em>
						<?php
						$wpes_admin        = get_option( 'admin_email', false );
						$wpes_sample_email = [
							'to'      => $wpes_admin,
							'subject' => Plugin::dummy_subject(),
						];
						$wpes_sample_email = Plugin::alternative_to( $wpes_sample_email );
						$wpes_admin        = reset( $wpes_sample_email['to'] );
						// translators: %1$s: a link to the options panel, %2$s: an e-mail address.
						print wp_kses_post( sprintf( __( 'Sample mail will be sent to the <a href="%1$s">Site Administrator</a>; <b>%2$s</b>.', 'wpes' ), admin_url( 'options-general.php' ), $wpes_admin ) );
						?>
					</em>
				</th>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<hr/>
				</th>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<h3>
						<?php print wp_kses_post( __( 'E-mail styling, and filters for HTML head/body', 'wpes' ) ) . ':'; ?>
					</h3>
				</th>
			</tr>
			<tr class="on-smtp-is_html">
				<td colspan="4" class="last">
					<?php print wp_kses_post( __( 'You can use WordPress filters to augment the HEAD and BODY sections of the HTML e-mail.', 'wpes' ) ); ?>
				</td>
			</tr>
			<tr class="not-smtp-is_html">
				<td colspan="4" class="last">
					<?php print wp_kses_post( __( 'You can use WordPress filters to change the e-mail.', 'wpes' ) ); ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Purpose', 'wpes' ); ?></th>
				<th><?php esc_html_e( 'WordPress filter', 'wpes' ); ?></th>
				<th colspan="2"><?php esc_html_e( 'Parameters', 'wpes' ); ?></th class=last>
			</tr>
			<tr class="on-smtp-is_html">
				<td><?php esc_html_e( 'Plugin defaults', 'wpes' ); ?></td>
				<td><code>wpes_defaults</code></td>
				<td colspan="2"><code>array $defaults</code></td class=last>
			</tr>
			<tr class="on-smtp-is_html">
				<td><?php esc_html_e( 'Plugin settings', 'wpes' ); ?></td>
				<td><code>wpes_settings</code></td>
				<td colspan="2"><code>array $settings</code></td class=last>
			</tr>
			<tr>
				<td><?php esc_html_e( 'E-mail subject', 'wpes' ); ?></td>
				<td><code>wpes_subject</code></td>
				<td colspan="2"><code>string $subject</code>, <code>PHPMailer $mailer</code></td class=last>
			</tr>
			<tr class="on-smtp-is_html">
				<td><?php esc_html_e( 'E-mail <head>', 'wpes' ); ?></td>
				<td><code>wpes_head</code></td>
				<td colspan="2"><code>string $head_content</code>, <code>PHPMailer $mailer</code></td class=last>
			</tr>
			<tr class="on-smtp-is_html">
				<td><?php esc_html_e( 'E-mail <body>', 'wpes' ); ?></td>
				<td><code>wpes_body</code></td>
				<td colspan="2"><code>string $body_content</code>, <code>PHPMailer $mailer</code></td class=last>
			</tr>
			<tr class="not-smtp-is_html">
				<td colspan="4" class="last">
					<?php print wp_kses_post( __( 'Turn on HTML e-mail to enable e-mail styling.', 'wpes' ) ); ?>
				</td>
			</tr>
			<tr>
				<th colspan="4" class="last">
					<pre><?php print wp_kses_post( Plugin::$debug ); ?></pre>
				</th>
			</tr>
	</form>
</div>
<table width="90%">
	<tr>
		<th class="last">
			<?php
			print wp_kses_post(
				Plugin::get_config()['is_html'] ? __( 'Example E-mail (actual HTML) - with your filters applied', 'wpes' ) : __( 'Example E-mail', 'wpes' )
			);
			?>
		</th>
	</tr>
	<tr>
		<td class="last">
			<iframe
				style="width: 100%; min-width: 700px; height: auto; min-height: 600px;"
				src="<?php print esc_attr( add_query_arg( 'iframe', 'content' ) ); ?>"></iframe>
		</td>
	</tr>
</table>

