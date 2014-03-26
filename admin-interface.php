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
					<input type="checkbox" name="settings[alt_body]" value="1" <?php print $c['alt_body'] ? 'checked="checked" ': ''; ?>id="smtp-alt_body" /><label for="smtp-alt_body">Derive plain-text alternative? (Will derive text-ish body from html body as AltBody)</label>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<input type="submit" name="op" value="Save settings" class="button-primary action" />
					<!-- input type="submit" name="op" value="Print debug output of sample mail" class="button-secondary action" / -->
					<input type="submit" name="op" value="Send sample mail" class="button-secondary action" />
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<?php print WP_Email_Essentials::$debug; ?>
				</td>
			</tr>
	</form>
</div>
