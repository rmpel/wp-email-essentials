<?php
	if ( ! current_user_can('manage_options') ) {
		wp_die(__('Uh uh uh! You didn\'t say the magic word!', 'wpes'));
	}
	global $current_user;
	$c = WP_Email_Essentials::get_config();
?>
<div class="wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br />
	</div>
	<h2>WP-Email-Essentials</h2>
	<?php if ( WP_Email_Essentials::$message ) { print '<div class="updated"><p>'. WP_Email_Essentials::$message .'</p></div>'; } ?>
	<?php if ( WP_Email_Essentials::$error ) { print '<div class="error"><p>'. WP_Email_Essentials::$error .'</p></div>'; } ?>

	<?php if (false !== strpos($c['smtp']['host'], ':')) {
		list ($host,$port) = explode(':', $c['smtp']['host']);
		if (is_numeric($port)) {
			$c['smtp']['port'] = $port;
			$c['smtp']['host'] = $host;
		}
	} ?>

	<form id="outpost" method='POST' action="" enctype="multipart/form-data">
		<input type="hidden" name="form_id" value="wp-email-essentials" />
		<table>
			<tr>
				<td>
					<label for="timeout"><?php _e('phpMailer Timeout', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[timeout]" value="<?php print $c['timeout']; ?>" id="timeout" placeholder="300" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[smtp-enabled]" value="1" <?php print $c['smtp'] ? 'checked="checked" ': ''; ?>id="smtp-enabled" /><label for="smtp-enabled"><?php _e('Enable sending mail over SMTP?', 'wpes'); ?></label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-hostname"><?php _e('Hostname or -ip', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[host]" value="<?php print $c['smtp']['host']; ?>" id="smtp-hostname" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-port"><?php _e('SMTP Port', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[port]" value="<?php print $c['smtp']['port']; ?>" id="smtp-port" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-username"><?php _e('Username', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[username]" value="<?php print $c['smtp']['username']; ?>" id="smtp-username" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-password"><?php _e('Password', 'wpes'); ?></label>
				</td>
				<td>
					<input type="password" name="settings[password]" value="<?php print str_repeat( '*', strlen( $c['smtp']['password'] ) ); ?>" id="smtp-password" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-secure"><?php _e('Secure?', 'wpes'); ?></label>
				</td>
				<td>
					<select name="settings[secure]" id="smtp-secure">
						<option value=""><?php _e('No', 'wpes'); ?></option>
						<option disabled>───────────────────────</option>
						<option disabled><?php _e('Use encrypted connection', 'wpes'); ?> - <?php _e('strict SSL verify', 'wpes'); ?></option>
						<option value="ssl" <?php if ( 'ssl' == $c['smtp']['secure'] ) print 'selected="selected"'; ?>>SSL</option>
						<option value="tls" <?php if ( 'tls' == $c['smtp']['secure'] ) print 'selected="selected"'; ?>>StartTLS</option>
						<option disabled>───────────────────────</option>
						<option disabled><?php _e('Use encrypted connection', 'wpes'); ?> - <?php _e('allow self-signed SSL', 'wpes'); ?></option>
						<option value="ssl-" <?php if ( 'ssl-' == $c['smtp']['secure'] ) print 'selected="selected"'; ?>>SSL</option>
						<option value="tls-" <?php if ( 'tls-' == $c['smtp']['secure'] ) print 'selected="selected"'; ?>>StartTLS</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[SingleTo]" value="1" <?php print $c['SingleTo'] ? 'checked="checked" ': ''; ?>id="smtp-singleto" /><label for="smtp-singleto"><?php _e('Split mail with more than one Recepient into separate mails?', 'wpes'); ?></label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="from-name"><?php _e('Default from name', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[from_name]" value="<?php print esc_attr( $c['from_name'] ); ?>" id="from-name" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="from-email"><?php _e('Default from e-mail', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[from_email]" value="<?php print esc_attr( $c['from_email'] ); ?>" id="from-email" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[spf_lookup_enabled]" value="1" <?php print $c['spf_lookup_enabled'] ? 'checked="checked" ': ''; ?>id="spf_lookup_enabled" /><label for="spf_lookup_enabled"><?php _e('Use SPF-lookup to determine validity of sender instead of domainname-match.', 'wpes'); ?></label>
				</td>
			</tr>
			<?php if ($c['spf_lookup_enabled']) { ?><!-- SPF -->

			<?php if (!WP_Email_Essentials::i_am_allowed_to_send_in_name_of($c['from_email'])) { ?>
			<tr><td></td>
				<td>SPF Records are checked: you are NOT allowed to send mail with this domain.<br />
					If you really need to use this sender e-mail address, you need to change the SPF record to include the sending-IP of this server;<br />
					Old: <code><?php print WP_Email_Essentials::get_spf($c['from_email'], false, true); ?></code><br />
					New: <code><?php print WP_Email_Essentials::get_spf($c['from_email'], true , true); ?></code>
				</td>
			</tr><?php } // ! i_am_allowed, spf variant
						else { ?>
			<tr><td></td><td>SPF Record: <code><?php print WP_Email_Essentials::get_spf($c['from_email'], false, true); ?></code></td></tr>

			<?php } // ! i_am_allowed {else}, spf variant ?>

			<?php } else { ?><!-- domain match -->

			<?php if (!WP_Email_Essentials::i_am_allowed_to_send_in_name_of($c['from_email'])) { ?>
			<tr><td></td>
				<td>You are NOT allowed to send mail with this domain; it should match the domainname of the website.<br />
					If you really need to use this sender e-mail address, you need to switch to SPF-record checking and make sure the SPF for this domain matches this server.
				</td>
			</tr><?php } // ! i_am_allowed, domain variant ?>

		<?php } ?>
			<tr>
				<td>
					<label for="make_from_valid"><?php _e('Fix sender-address?', 'wpes'); ?></label>
				</td>
				<td>
					<?php _e('E-mails sent as different domain will probably be marked as spam. Fix the sender-address to always match the sending domain and send original From address as Reply-To: header?', 'wpes'); ?>
					<select name="settings[make_from_valid]" id="make_from_valid">
						<option value=""><?php _e('No, send with possibly-invalid sender as is. (might cause your mails to be marked as spam!)', 'wpes'); ?></option>
						<option disabled>────────────────────────────────────────────────────────────</option>
						<option value="-at-" <?php

						$config = WP_Email_Essentials::get_config();

						$host = parse_url(get_bloginfo('url'), PHP_URL_HOST);
						$host = preg_replace('/^www[0-9]*\./', '', $host);

						if ( '-at-' == $c['make_from_valid'] ) print 'selected="selected"'; ?>><?php print sprintf(__('Rewrite email@addre.ss to email-at-addre-dot-ss@%s', 'wpes'), $host); ?></option>
						<option value="noreply" <?php if ( 'noreply' == $c['make_from_valid'] ) print 'selected="selected"'; ?>><?php print sprintf(__('Rewrite email@addre.ss to noreply@%s', 'wpes'), $host); ?></option>
						<?php $defmail = WP_Email_Essentials::wp_mail_from($config['from_email']); if (false !== strpos($defmail, '@' . $host)) { ?>
						<option value="default" <?php if ( 'default' == $c['make_from_valid'] ) print 'selected="selected"'; ?>><?php print sprintf(__('Rewrite email@addre.ss to %s', 'wpes'), $defmail); ?></option>
						<?php } ?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[is_html]" value="1" <?php print $c['is_html'] ? 'checked="checked" ': ''; ?>id="smtp-is_html" /><label for="smtp-is_html"><?php _e('Send as HTML? (Will convert non-html body to html-ish body)', 'wpes'); ?></label>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[css_inliner]" value="1" <?php print $c['css_inliner'] ? 'checked="checked" ': ''; ?>id="smtp-css_inliner" /><label for="smtp-css_inliner"><?php _e('Convert CSS to Inline Styles (for Outlook Online, Yahoo Mail, Google Mail, Hotmail)', 'wpes'); ?></label>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[alt_body]" value="1" <?php print $c['alt_body'] ? 'checked="checked" ': ''; ?>id="smtp-alt_body" /><label for="smtp-alt_body"><?php _e('Derive plain-text alternative? (Will derive text-ish body from html body as AltBody)', 'wpes'); ?></label>
				</td>
			</tr>
<!-- 			<tr>
				<td>
					<label for="errors-to"><?php _e('Errors-To', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[errors_to]" value="<?php print esc_attr( $c['errors_to'] ); ?>" id="errors-to" />
				</td>
			</tr>
 -->			<?php if (function_exists('openssl_pkcs7_sign')) { ?>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[enable_smime]" value="1" <?php print $c['enable_smime'] ? 'checked="checked" ': ''; ?>id="enable-smime" /><label for="enable-smime"><?php _e('Sign emails with S/MIME certificate', 'wpes'); ?></label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="certfolder"><?php _e('S/MIME Certificate/Private-Key path', 'wpes'); ?></label>
				</td>
				<td>
					<input type="text" name="settings[certfolder]" value="<?php print esc_attr($c['certfolder']); ?>" id="certfolder" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<strong><?php print sprintf(__('It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s/.smime/</code> to prevent stealing your identity.', 'wpes'), dirname(ABSPATH)); ?></strong><br />
					<?php _e('You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your wordpress).', 'wpes'); ?><br />
					<?php _e('The naming convention is: certificate: <code>email@addre.ss.crt</code>, private key: <code>email@addre.ss.key</code>, (optional) passphrase: <code>email@addre.ss.pass</code>.', 'wpes'); ?>
				</td>
			</tr>
			<?php if (isset($c['certfolder'])) {
					$ids = array();
					$certificate_folder = $c['certificate_folder'];
					if (is_dir($certificate_folder)) {
						$files = glob($certificate_folder .'/*.crt');
						$ids = WP_Email_Essentials::list_smime_identities();
						$ids = array_keys($ids);
					}
					else {
							?><tr>
				<td colspan="2" style="color:red;">
					<strong><?php print sprintf(__('Set folder <code>%s</code> not found.', 'wpes'), $c['certfolder']);
												if ($certificate_folder !== $c['certfolder']) {
													print ' ' . sprintf(__('Expanded path: <code>%s</code>', 'wpes'), $certificate_folder);
												}
												print ' ' . sprintf(__('Evaluated path: <code>%s</code>', 'wpes'), realpath($certificate_folder)); ?>
				</td>
			</tr><?php
					}
					if ($ids) {
						?><tr>
				<td colspan="2">
					<?php print sprintf(__('Found S/MIME identities for the following senders: <code>%s</code>', 'wpes'), implode('</code>, <code>', $ids)); ?>
				</td>
			</tr><?php
					}
				} ?>
			<?php } else {?>
			<tr>
				<td colspan="2">
					<input type="hidden" name="settings[enable_smime]" value="0" />
				</td>
			</tr>
			<?php }?>
			<tr>
				<td colspan="2">
					<input type="submit" name="op" value="<?php print esc_attr__('Save settings', 'wpes'); ?>" class="button-primary action" />
					<!-- input type="submit" name="op" value="<?php print esc_attr__('Print debug output of sample mail', 'wpes'); ?>" class="button-secondary action" / -->
					<input type="submit" name="op" value="<?php print esc_attr__('Send sample mail', 'wpes'); ?>" class="button-secondary action" />
					<em><?php print sprintf(__('Sample mail will be sent to the <a href="%s">Site Administrator</a>; <b>%s</b>.', 'wpes'), admin_url('options-general.php'), get_option( 'admin_email', false )); ?></em>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<b><?php _e('Filters', 'wpes'); ?></b><br />
					<?php _e('DEFAULTS can be overruled with WordPress filter <code>wpes_defaults</code>, parameters: <code>Array $defaults</code>', 'wpes'); ?><br />
					<?php _e('SETTINGS can be overruled with WordPress filter <code>wpes_settings</code>, parameters: <code>Array $settings</code>', 'wpes'); ?><br />
					<?php _e('Email HEAD can be overruled with WordPress filter <code>wpes_head</code>, parameters: <code>String $head_content</code>, <code>PHPMailer $mailer</code>', 'wpes'); ?><br />
					<?php _e('Email BODY can be overruled with WordPress filter <code>wpes_body</code>, parameters: <code>String $body_content</code>, <code>PHPMailer $mailer</code>', 'wpes'); ?><br />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<pre><?php print WP_Email_Essentials::$debug; ?></pre>
				</td>
			</tr>
	</form>
</div>
<table width="90%">
	<?php
		require_once ABSPATH . WPINC . '/class-phpmailer.php';
		$mailer = new PHPMailer;
		$config = WP_Email_Essentials::get_config();
		$css = apply_filters_ref_array( 'wpes_css', array('', &$mailer ));
		$subject = __('Sample email subject', 'wpes');
		$body = WP_Email_Essentials::dummy_content();
	?>
	<tr>
		<td><?php _e('If HTML enabled: You can use WordPress filters to augment the HEAD and BODY sections of the HTML e-mail. To add information to the HEAD (or change the title) hook to filter wpes_head. For the body, hook to wpes_body', 'wpes'); ?></td>
	</tr>
	<tr>
		<th><?php _e('Example Email (actual HTML) - with your filters applied', 'wpes'); ?></th>
	</tr>
	<tr>
		<td><iframe style="width: 100%; min-width: 700px; height: auto; min-height: 600px;" src="<?php print add_query_arg("iframe","content"); ?>"></iframe></td>
	</tr>
</table>
