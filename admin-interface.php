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
?>
<div class="wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br/>
	</div>
	<h2>WP-Email-Essentials</h2>
	<?php
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
		<table>
			<tr>
				<td colspan="4">
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
						for="enable_history"><?php print wp_kses_post( __( 'Enable Email History', 'wpes' ) ); ?></label>
				</th>
				<td colspan="3"></td>
			</tr>
			<tr>
				<th colspan="4">
					<?php print wp_kses_post( __( '<strong>Warning: </strong> Storing e-mails in your database is a BAD idea and illegal in most countries. Use this for DEBUGGING only!', 'wpes' ) ); ?>
					<br/>
					<?php print wp_kses_post( __( 'Disabling this feature will delete the mail-store.', 'wpes' ) ); ?>
					<strong
						style="color: darkred"><?php print wp_kses_post( __( 'If you insist on storing emails, please note that you need to implement the appropriate protocols for compliance with GDPR. The responsibility lies with the owner of the website, not the creator or hosting company.', 'wpes' ) ); ?></strong>
				</th>
			</tr>
			<tr>
				<th colspan="4">
					<hr/>
				</th>
			</tr>
			<tr>
				<td colspan="4">
					<h3><?php print wp_kses_post( __( 'E-mail Settings', 'wpes' ) ); ?>:</h3>
				</td>
			</tr>
			<tr>
				<th>
					<label for="timeout"><?php print wp_kses_post( __( 'phpMailer Timeout', 'wpes' ) ); ?></label>
				</th>
				<td colspan="3">
					<select id="timeout" name="settings[timeout]">
						<?php
						$wpes_timeouts = array(
							60  => __( '1 minute', 'wpes' ),
							300 => __( '5 minutes (default)', 'wpes' ),
							600 => __( '10 minutes (for very slow hosts)', 'wpes' ),
						);
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
				<th colspan="4">
					<input
						type="checkbox" name="settings[smtp-enabled]" value="1"
						<?php checked( isset( $wpes_config['smtp'] ) && $wpes_config['smtp'] ); ?>
						id="smtp-enabled"/><label
						for="smtp-enabled"><?php print wp_kses_post( __( 'Enable sending mail over SMTP?', 'wpes' ) ); ?></label>
				</th>
			</tr>
			<tr>
				<th width="25%">
					<label for="smtp-hostname"><?php print wp_kses_post( __( 'Hostname or -ip', 'wpes' ) ); ?></label>
				</th>
				<td width="25%">
					<input
						type="text" class="widefat" name="settings[host]"
						value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['host'] : '' ); ?>"
						id="smtp-hostname"/>
				</td>
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
			<tr>
				<th>
					<label for="smtp-username"><?php print wp_kses_post( __( 'Username', 'wpes' ) ); ?></label>
				</th>
				<td>
					<input
						type="text" class="widefat" name="settings[username]"
						value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['username'] : '' ); ?>"
						id="smtp-username"/>
				</td>
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
			<tr>
				<th>
					<label for="smtp-secure"><?php print wp_kses_post( __( 'Secure?', 'wpes' ) ); ?></label>
				</th>
				<td colspan="3">
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
			<tr>
				<td colspan="4">
					<hr/>
				</td>
			</tr>
			<tr>
				<th colspan="4">
					<input
						type="checkbox" name="settings[SingleTo]" value="1"
						<?php checked( isset( $wpes_config['SingleTo'] ) && $wpes_config['SingleTo'] ); ?>
						id="smtp-singleto"/><label
						for="smtp-singleto"><?php print wp_kses_post( __( 'Split mail with more than one Recipient into separate mails?', 'wpes' ) ); ?></label>
				</th>
			</tr>
			<tr>
				<td colspan="4">
					<strong
						style="color:darkred"><?php print wp_kses_post( __( 'Under GDPR, from May 25th, 2018, using a no-reply@ (or any variation of a not-responded-to email address) is prohibited. Please make sure the default sender address is valid and used in the setting below.', 'wpes' ) ); ?></strong>
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
						id="from-name"/>
				</td>
				<th>
					<label for="from-email"><?php print wp_kses_post( __( 'Default from e-mail', 'wpes' ) ); ?></label>
				</th>
				<td>
					<input
						type="text" class="widefat" name="settings[from_email]"
						value="<?php print esc_attr( $wpes_config['from_email'] ); ?>"
						id="from-email"/>
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
						<td colspan="3"><?php print wp_kses_post( __( 'SPF Records are checked', 'wpes' ) ); ?>
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
						<td colspan="3"><?php print wp_kses_post( __( 'SPF Record', 'wpes' ) ); ?>:
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
						<td colspan="3"><?php print wp_kses_post( __( 'You are NOT allowed to send mail with this domain; it should match the domainname of the website.', 'wpes' ) ); ?>
							<br/>
							<?php print wp_kses_post( __( 'If you really need to use this sender e-mail address, you need to switch to SPF-record checking and make sure the SPF for this domain matches this server.', 'wpes' ) ); ?>
						</td>
					</tr>
					<?php
				}
			}
			?>
			<tr>
				<td colspan="4">
					<h3><?php print wp_kses_post( __( 'How to validate sender?', 'wpes' ) ); ?></h3>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<?php print wp_kses_post( __( 'You have 2 options', 'wpes' ) ); ?>:
					<ul>
						<li><input
								type="radio" name="settings[spf_lookup_enabled]" value="1"
								<?php checked( isset( $wpes_config['spf_lookup_enabled'] ) && $wpes_config['spf_lookup_enabled'] ); ?>
								id="spf_lookup_enabled_1"/>
							<label for="spf_lookup_enabled_1">
								<?php print wp_kses_post( __( '<strong>SPF</strong>: Use SPF records to validate the sender. If the SPF record of the domain of the email address used as sender matches the IP-address this website is hosted on, the email address is considered valid.', 'wpes' ) ); ?>
							</label>
						</li>
						<li><input
								type="radio" name="settings[spf_lookup_enabled]" value="0"
								<?php checked( ! isset( $wpes_config['spf_lookup_enabled'] ) || ! $wpes_config['spf_lookup_enabled'] ); ?>
								id="spf_lookup_enabled_0"/>
							<label for="spf_lookup_enabled_0">
								<?php print wp_kses_post( __( '<strong>Domain name</strong>: Use a simple match on hostname; any email adress that matches the base domainname of this website is considered valid.', 'wpes' ) ); ?>
							</label>
						</li>
					</ul>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<hr/>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<h3><?php print wp_kses_post( __( 'What to do in case the sender is not valid for this domain?', 'wpes' ) ); ?></h3>
				</td>
			</tr>
			<tr>
				<th>
					<label
						for="make_from_valid"><?php print wp_kses_post( __( 'Fix sender-address?', 'wpes' ) ); ?></label>
				</th>
				<td colspan="3">
					<?php print wp_kses_post( __( 'E-mails sent as different domain will probably be marked as spam. Fix the sender-address to always match the sending domain and send original From address as Reply-To: header?', 'wpes' ) ); ?>
					<?php
					$wpes_host = wp_parse_url( get_bloginfo( 'url' ), PHP_URL_HOST );
					$wpes_host = preg_replace( '/^www[0-9]*\./', '', $wpes_host );
					?>
					<select name="settings[make_from_valid_when]">
						<option
							value="when_sender_invalid" <?php selected( 'when_sender_invalid', $wpes_config['make_from_valid_when'] ); ?>
						><?php print wp_kses_post( __( 'When sender email domain/SPF does not match', 'wpes' ) ); ?></option>
						<option
							value="when_sender_not_as_set" <?php selected( 'when_sender_not_as_set', $wpes_config['make_from_valid_when'] ); ?>
						><?php print wp_kses_post( __( 'When sender email is not equal to above', 'wpes' ) ); ?></option>
					</select>
					<select name="settings[make_from_valid]" id="make_from_valid">
						<option
							value=""><?php print wp_kses_post( __( 'Keep the possibly-invalid sender as is. (might cause your mails to be marked as spam!)', 'wpes' ) ); ?></option>
						<option disabled>────────────────────────────────────────────────────────────</option>
						<option value="-at-" <?php selected( '-at-', $wpes_config['make_from_valid'] ); ?>>
							<?php print esc_html( sprintf( __( 'Rewrite email@addre.ss to email-at-addre-dot-ss@%s', 'wpes' ), $wpes_host ) ); ?>
						</option>
						<option value="noreply" <?php selected( 'noreply', $wpes_config['make_from_valid'] ); ?>>
							<?php print esc_html( sprintf( __( 'Rewrite email@addre.ss to noreply@%s', 'wpes' ), $wpes_host ) ); ?>
							<?php print esc_html( __( '(Not GDPR Compliant)', 'wpes' ) ); ?>
						</option>
						<?php
						$wpes_default_sender_mail = Plugin::wp_mail_from( $wpes_config['from_email'] );
						if ( Plugin::i_am_allowed_to_send_in_name_of( $wpes_default_sender_mail ) ) {
							?>
							<option value="default" <?php selected( 'default', $wpes_config['make_from_valid'] ); ?>>
								<?php print esc_html( sprintf( __( 'Rewrite email@addre.ss to %s', 'wpes' ), $wpes_default_sender_mail ) ); ?>
							</option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<th colspan="4">
					<hr/>
				</th>
			</tr>
			<tr>
				<td colspan="4">
					<h3><?php print wp_kses_post( __( 'E-mail content', 'wpes' ) ); ?>:</h3>
				</td>
			</tr>
			<tr>
				<th colspan="4">
					<input
						type="checkbox" name="settings[is_html]" value="1"
						<?php checked( isset( $wpes_config['is_html'] ) && $wpes_config['is_html'] ); ?>
						id="smtp-is_html"/><label
						for="smtp-is_html"><?php print wp_kses_post( __( 'Send as HTML? (Will convert non-html body to html-ish body)', 'wpes' ) ); ?></label>
				</th>
			</tr>
			<tr>
				<th colspan="4">
					<input
						type="checkbox" name="settings[css_inliner]" value="1"
						<?php checked( isset( $wpes_config['css_inliner'] ) && $wpes_config['css_inliner'] ); ?>
						id="smtp-css_inliner"/><label
						for="smtp-css_inliner"><?php print wp_kses_post( __( 'Convert CSS to Inline Styles (for Outlook Online, Yahoo Mail, Google Mail, Hotmail)', 'wpes' ) ); ?></label>
				</th>
			</tr>
			<tr>
				<th><?php print wp_kses_post( __( 'Content pre-coding (for lack of a better word)', 'wpes' ) ); ?></th>
				<td colspan="3">
					<label
						for="content-precoding"><?php print wp_kses_post( __( 'Some servers have f*cked-up content-encoding settings, resulting in wrongly encoded diacritics. If you expect a character like &eacute; and all you get is something like &euro;&tilde;&Itilde;, experiment with this setting.', 'wpes' ) ); ?></label><br/>
					<select id="content-precoding" name="settings[content_precode]">
						<?php
						$wpes_encoding_table         = explode( ',', '0,auto,' . Plugin::ENCODINGS );
						$wpes_encoding_table         = array_combine( $wpes_encoding_table, $wpes_encoding_table );
						$wpes_encoding_table['0']    = __( 'No precoding (default)', 'wpes' );
						$wpes_encoding_table['auto'] = __( 'Autodetect with mb_check_encoding()', 'wpes' );
						foreach ( $wpes_encoding_table as $wpes_encoding => $wpes_nice_encoding ) {
							print '<option value="' . esc_attr( $wpes_encoding ) . '" ' . selected( $wpes_config['content_precode'], $wpes_encoding, false ) . '>' . esc_html( $wpes_nice_encoding ) . '</option>';
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<th colspan="4">
					<input
						type="checkbox" name="settings[alt_body]" value="1"
						<?php checked( isset( $wpes_config['alt_body'] ) && $wpes_config['alt_body'] ); ?>
						id="smtp-alt_body"/><label
						for="smtp-alt_body"><?php print wp_kses_post( __( 'Derive plain-text alternative? (Will derive text-ish body from html body as AltBody)', 'wpes' ) ); ?></label>
				</th>
			</tr>
			<tr>
				<th colspan="4">
					<input
						type="checkbox" name="settings[do_shortcodes]" value="1"
						<?php checked( isset( $wpes_config['do_shortcodes'] ) && $wpes_config['do_shortcodes'] ); ?>
						id="do_shortcodes"/><label
						for="do_shortcodes"><?php print wp_kses_post( __( 'Process the body with <code>do_shortcode()</code>', 'wpes' ) ); ?></label>
				</th>
			</tr>
			<tr>
				<th colspan="4">
					<hr/>
				</th>
			</tr>

			<?php if ( function_exists( 'openssl_pkcs7_sign' ) ) { ?>
				<tr>
					<td colspan="4">
						<h3><?php print wp_kses_post( __( 'Digital E-mail Signing (S/MIME)', 'wpes' ) ); ?>:</h3>
					</td>
				</tr>
				<tr>
					<td colspan="4">
						<input
							type="checkbox" name="settings[enable_smime]" value="1"
							<?php checked( isset( $wpes_config['enable_smime'] ) && $wpes_config['enable_smime'] ); ?>
							id="enable-smime"/><label
							for="enable-smime"><?php print wp_kses_post( __( 'Sign emails with S/MIME certificate', 'wpes' ) ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<label
							for="certfolder"><?php print wp_kses_post( __( 'S/MIME Certificate/Private-Key path', 'wpes' ) ); ?></label>
					</td>
					<td colspan="3">
						<input
							type="text" class="widefat" name="settings[certfolder]"
							value="<?php print esc_attr( $wpes_config['certfolder'] ); ?>" id="certfolder"/>
					</td>
				</tr>
				<tr>
					<td colspan="4">
						<strong><?php print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s/.smime/</code> to prevent stealing your identity.', 'wpes' ), dirname( ABSPATH ) ) ); ?></strong><br/>
						<?php print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your WordPress).', 'wpes' ) ); ?>
						<br/>
						<?php print wp_kses_post( __( 'The naming convention is: certificate: <code>email@addre.ss.crt</code>, private key: <code>email@addre.ss.key</code>, (optional) passphrase: <code>email@addre.ss.pass</code>.', 'wpes' ) ); ?>
					</td>
				</tr>
				<?php
				if ( isset( $wpes_config['certfolder'] ) ) {
					$wpes_smime_identities         = array();
					$wpes_smime_certificate_folder = $wpes_config['certificate_folder'];
					if ( is_dir( $wpes_smime_certificate_folder ) ) {
						$wpes_smime_files      = glob( $wpes_smime_certificate_folder . '/*.crt' );
						$wpes_smime_identities = Plugin::list_smime_identities();
						$wpes_smime_identities = array_keys( $wpes_smime_identities );
					} else {
						?>
						<tr>
							<td colspan="4" style="color:red;">
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
							<td colspan="4">
								<?php print wp_kses_post( sprintf( __( 'Found S/MIME identities for the following senders: <code>%s</code>', 'wpes' ), implode( '</code>, <code>', $wpes_smime_identities ) ) ); ?>
							</td>
						</tr>
						<?php
					}
				}
				?>
				<tr>
					<th colspan="4">
						<hr/>
					</th>
				</tr>
			<?php } else { ?>
				<tr>
					<td colspan="4">
						<input type="hidden" name="settings[enable_smime]" value="0"/>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td colspan="4">
					<h3><?php print wp_kses_post( __( 'Digital E-mail Signing (DKIM)', 'wpes' ) ); ?>:</h3>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<input
						type="checkbox" name="settings[enable_dkim]" value="1"
						<?php checked( isset( $wpes_config['enable_dkim'] ) && $wpes_config['enable_dkim'] ); ?>
						id="enable-dkim"/><label
						for="enable-dkim"><?php print wp_kses_post( __( 'Sign emails with DKIM certificate', 'wpes' ) ); ?></label>
				</td>
			</tr>
			<tr>
				<td>
					<label
						for="dkimfolder"><?php print wp_kses_post( __( 'DKIM Certificate/Private-Key path', 'wpes' ) ); ?></label>
				</td>
				<td colspan="3">
					<input
						type="text" class="widefat" name="settings[dkimfolder]"
						value="<?php print esc_attr( $wpes_config['dkimfolder'] ); ?>" id="dkimfolder"/>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<strong><?php print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s/.dkim/</code> to prevent stealing your identity.', 'wpes' ), dirname( ABSPATH ) ) ); ?></strong><br/>
					<?php print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your WordPress).', 'wpes' ) ); ?>
					<br/>
					<?php print wp_kses_post( __( 'The naming convention is: certificate: <code>domain.tld.crt</code>, private key: <code>domain.tld.key</code>, DKIM Selector: <code>domain.tld.selector</code>, (optional) passphrase: <code>domain.tld.pass</code>.', 'wpes' ) ); ?>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					To generate DKIM keys, use: <br/>
					<code>openssl genrsa -aes256 -passout pass:"YOUR-PASSWORD" -out domain.tld.key 2048</code><br/>
					<code>openssl rsa -in domain.tld.key -pubout > domain.tld.crt</code><br/>
					<code>echo "YOUR-PASSWORD" > domain.tld.pass</code><br/>
					<code>echo "DKIM-SELECTOR-FOR-THIS-KEY" > domain.tld.selector</code>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					upload these files to the specified path on the server and again; this should not be publicly
					queriable!!!
				</td>
			</tr>
			<tr>
				<td colspan="4">
					Finally, register the domain key in the DNS<br/>
					<code>DKIM-SELECTOR-FOR-THIS-KEY._domainkey.domain.tld. IN TXT "v=DKIM1; k=rsa;
						p=FULL-CONTENT-OF-domain.tld.crt"</code><br/>
					remove linebreaks and ignore the ---BEGIN KEY and END KEY lines
				</td>
			</tr>
			<tr>
				<td colspan="4">
					test your settings with <a href="https://www.dmarcanalyzer.com/dkim/dkim-check/" target="_blank">DMARC
						Analyser</a> (unaffiliated)
				</td>
			</tr>
			<?php
			if ( isset( $wpes_config['dkimfolder'] ) ) {
				$wpes_dkim_identities         = array();
				$wpes_dkim_certificate_folder = $wpes_config['dkim_certificate_folder'];
				if ( is_dir( $wpes_dkim_certificate_folder ) ) {
					$wpes_dkim_files      = glob( $wpes_dkim_certificate_folder . '/*.crt' );
					$wpes_dkim_identities = Plugin::list_dkim_identities();
					$wpes_dkim_identities = array_keys( $wpes_dkim_identities );
				} else {
					?>
					<tr>
						<td colspan="4" style="color:red;">
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
					<tr>
						<td colspan="4">
							<?php print wp_kses_post( sprintf( __( 'Found DKIM certificates for the following sender-domains: <code>%s</code>', 'wpes' ), implode( '</code>, <code>', $wpes_dkim_identities ) ) ); ?>
						</td>
					</tr>
					<?php
				}
			}
			?>
			<tr>
				<th colspan="4">
					<hr/>
				</th>
			</tr>
			<tr>
				<th colspan="4">
					<input
						type="submit" name="op" value="<?php print esc_attr__( 'Save settings', 'wpes' ); ?>"
						class="button-primary action"/>
					<!-- input type="submit" name="op" value="<?php print esc_attr__( 'Print debug output of sample mail', 'wpes' ); ?>" class="button-secondary action" / -->
					<input
						type="submit" name="op" value="<?php print esc_attr__( 'Send sample mail', 'wpes' ); ?>"
						class="button-secondary action"/>
					<em><?php print wp_kses_post( sprintf( __( 'Sample mail will be sent to the <a href="%1$s">Site Administrator</a>; <b>%2$s</b>.', 'wpes' ), admin_url( 'options-general.php' ), get_option( 'admin_email', false ) ) ); ?></em>
				</th>
			</tr>
			<tr>
				<th colspan="4">
					<hr/>
				</th>
			</tr>
			<tr>
				<th colspan="4">
					<h3><?php print wp_kses_post( __( 'E-mail styling, and filters for HTML head/body', 'wpes' ) ); ?>
						:</h3>
				</th>
			</tr>

			<tr>
				<th valign="top">
					<h4><?php print wp_kses_post( __( 'Filters', 'wpes' ) ); ?></h4>
				</th>
				<td colspan="3">
					<?php print wp_kses_post( sprintf( __( 'DEFAULTS can be overruled with WordPress filter %1$s, parameters: %2$s', 'wpes' ), '<code>wpes_defaults</code>', '<code>Array $defaults</code>' ) ); ?>
					<br/>
					<?php print wp_kses_post( sprintf( __( 'SETTINGS can be overruled with WordPress filter %1$s, parameters: %2$s', 'wpes' ), '<code>wpes_settings</code>', '<code>Array $settings</code>' ) ); ?>
					<br/>
					<?php print wp_kses_post( sprintf( __( 'Email HEAD can be overruled with WordPress filter %1$s, parameters: %2$s, %3$s', 'wpes' ), '<code>wpes_head</code>', '<code>String $head_content</code>', '<code>PHPMailer $mailer</code>' ) ); ?>
					<br/>
					<?php print wp_kses_post( sprintf( __( 'Email BODY can be overruled with WordPress filter %1$s, parameters: %2$s, %3$s', 'wpes' ), '<code>wpes_body</code>', '<code>String $body_content</code>', '<code>PHPMailer $mailer</code>' ) ); ?>
					<br/>
				</td>
			</tr>
			<tr>
				<th colspan="4">
					<pre><?php print wp_kses_post( Plugin::$debug ); ?></pre>
				</th>
			</tr>
	</form>
</div>
<table width="90%">
	<?php
	$wpes_mailer      = new WPES_PHPMailer();
	$wpes_wpes_config = Plugin::get_config();
	$wpes_css         = apply_filters_ref_array( 'wpes_css', array( '', &$wpes_mailer ) );
	$wpes_subject     = __( 'Sample email subject', 'wpes' );
	$wpes_body        = Plugin::dummy_content();
	?>
	<tr>
		<td><?php print wp_kses_post( __( 'If HTML enabled: You can use WordPress filters to augment the HEAD and BODY sections of the HTML e-mail. To add information to the HEAD (or change the title) hook to filter wpes_head. For the body, hook to wpes_body', 'wpes' ) ); ?></td>
	</tr>
	<tr>
		<th><?php print wp_kses_post( __( 'Example Email (actual HTML) - with your filters applied', 'wpes' ) ); ?></th>
	</tr>
	<tr>
		<td>
			<iframe style="width: 100%; min-width: 700px; height: auto; min-height: 600px;"
					src="<?php print esc_attr( add_query_arg( 'iframe', 'content' ) ); ?>"></iframe>
		</td>
	</tr>
</table>
<style>
	table th {
		text-align: left;
	}
</style>
