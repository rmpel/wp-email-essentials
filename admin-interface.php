<?php
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
	<form id="outpost" method='POST' action="" enctype="multipart/form-data">
		<input type="hidden" name="form_id" value="wp-email-essentials" />
		<table>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[smtp-enabled]" value="1" <?php print $c['smtp'] ? 'checked="checked" ': ''; ?>id="smtp-enabled" /><label for="smtp-enabled">Enable sending mail over SMTP?</label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-hostname">Hostname or -ip</label>
				</td>
				<td>
					<input type="text" name="settings[host]" value="<?php print $c['smtp']['host']; ?>" id="smtp-hostname" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-username">Username</label>
				</td>
				<td>
					<input type="text" name="settings[username]" value="<?php print $c['smtp']['username']; ?>" id="smtp-username" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-password">Password</label>
				</td>
				<td>
					<input type="password" name="settings[password]" value="<?php print str_repeat( '*', strlen( $c['smtp']['password'] ) ); ?>" id="smtp-password" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="smtp-secure">Secure?</label>
				</td>
				<td>
					<select name="settings[secure]" id="smtp-secure">
						<option value="">No</option>
						<option value="ssl" <?php if ( 'ssl' == $c['smtp']['secure'] ) print 'selected="selected"'; ?>>SSL</option>
						<option value="tls" <?php if ( 'tls' == $c['smtp']['secure'] ) print 'selected="selected"'; ?>>StartTLS</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[SingleTo]" value="1" <?php print $c['SingleTo'] ? 'checked="checked" ': ''; ?>id="smtp-singleto" /><label for="smtp-singleto">Split mail with more than one Recepient into separate mails?</label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="from-name">Default from name</label>
				</td>
				<td>
					<input type="text" name="settings[from_name]" value="<?php print esc_attr( $c['from_name'] ); ?>" id="from-name" />
				</td>
			</tr>
			<tr>
				<td>
					<label for="from-email">Default from e-mail</label>
				</td>
				<td>
					<input type="text" name="settings[from_email]" value="<?php print esc_attr( $c['from_email'] ); ?>" id="from-email" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[is_html]" value="1" <?php print $c['is_html'] ? 'checked="checked" ': ''; ?>id="smtp-is_html" /><label for="smtp-is_html">Send as HTML? (Will convert non-html body to html-ish body)</label>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[css_inliner]" value="1" <?php print $c['css_inliner'] ? 'checked="checked" ': ''; ?>id="smtp-css_inliner" /><label for="smtp-css_inliner">Convert CSS to Inline Styles (for Outlook Online, Yahoo Mail, Google Mail, Hotmail)</label>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[alt_body]" value="1" <?php print $c['alt_body'] ? 'checked="checked" ': ''; ?>id="smtp-alt_body" /><label for="smtp-alt_body">Derive plain-text alternative? (Will derive text-ish body from html body as AltBody)</label>
				</td>
			</tr>
			<?php if (function_exists('openssl_pkcs7_sign')) { ?>
			<tr>
				<td colspan="2">
					<input type="checkbox" name="settings[enable_smime]" value="1" <?php print $c['enable_smime'] ? 'checked="checked" ': ''; ?>id="enable-smime" /><label for="enable-smime">Sign emails with S/MIME certificate</label>
				</td>
			</tr>
			<tr>
				<td>
					<label for="certfolder">S/MIME Certificate/Private-Key path</label>
				</td>
				<td>
					<input type="text" name="settings[certfolder]" value="<?php print esc_attr($c['certfolder']); ?>" id="certfolder" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<strong>It is highly advised to pick a folder path <u>outside</u> your website, for example: <code><?php print dirname(ABSPATH); ?>/.smime/</code> to prevent stealing your identity </strong><br />
					You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your wordpress).<br />
					naming convention: certificate: <code>email@addre.ss.crt</code>, private key: <code>email@addre.ss.key</code>, (optional) passphrase: <code>email@addre.ss.pass</code>.
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
					<strong>Set folder <code><?php print $c['certfolder']; ?></code> not found. <?php if ($certificate_folder !== $c['certfolder']) { ?>Expanded path: <code><?php print $certificate_folder; ?></code><?php } ?> Evaluated path: <code><?php print realpath($certificate_folder); ?></code>
				</td>
			</tr><?php
					}
					if ($ids) {
						?><tr>
				<td colspan="2">
					Found S/MIME identities for the following senders: <code><?php print implode('</code>, <code>', $ids); ?></code>
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
					<input type="submit" name="op" value="Save settings" class="button-primary action" />
					<!-- input type="submit" name="op" value="Print debug output of sample mail" class="button-secondary action" / -->
					<input type="submit" name="op" value="Send sample mail" class="button-secondary action" />
					<em>Sample mail will be sent to the <a href="<?php print admin_url('options-general.php'); ?>">Site Administrator</a>; <b><?php print get_option( 'admin_email', false ); ?></b>.</em>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<b>Filters</b><br />
					DEFAULTS can be overruled with WordPress filter <code>wpes_defaults</code>, parameters: <code>Array $defaults</code><br />
					SETTINGS can be overruled with WordPress filter <code>wpes_settings</code>, parameters: <code>Array $settings</code><br />
					Email HEAD can be overruled with WordPress filter <code>wpes_head</code>, parameters: <code>String $head_content</code>, <code>PHPMailer $mailer</code><br />
					Email BODY can be overruled with WordPress filter <code>wpes_body</code>, parameters: <code>String $body_content</code>, <code>PHPMailer $mailer</code><br />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php print WP_Email_Essentials::$debug; ?>
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
		$subject = 'Sample email subject';
		$body = WP_Email_Essentials::dummy_content();
	?>
	<tr>
		<td>If HTML enabled: You can use WordPress filters to augment the HEAD and BODY sections of the HTML e-mail. To add information to the HEAD (or change the title) hook to filter wpes_head. For the body, hook to wpes_body</td>
	</tr>
	<tr>
		<th>Example Email (actual HTML) - with your filters applied</th>
	</tr>
	<tr>
		<td><iframe style="width: 100%; min-width: 700px; height: auto; min-height: 600px;" src="<?php print add_query_arg("iframe","content"); ?>"></iframe></td>
	</tr>
	<!-- tr>
		<th>Example HEAD - with your filters applied</th>
	</tr>
	<tr>
		<td><?php print htmlspecialchars('<head>'. apply_filters_ref_array( 'wpes_head', array('<title>'. $subject .'</title>', &$mailer )) . '</head>'); ?></td>
	</tr>
	<tr>
		<th>Example BODY (raw HTML) - with your filters applied</th>
	</tr>
	<tr>
		<td><?php print htmlspecialchars('<body>'. apply_filters_ref_array( 'wpes_body', array($body, &$mailer )) .'</body>'); ?></td>
	</tr -->
</table>
