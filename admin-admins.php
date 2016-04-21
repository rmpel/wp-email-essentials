<?php
	global $current_user;
	$c = get_option('mail_key_admins', array());
	$keys = WP_Email_Essentials::mail_key_database();
	$admin = get_option('admin_email');
?>
<div class="wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br />
	</div>
	<h2>WP-Email-Essentials - Alternative Admins</h2>
	<?php if ( WP_Email_Essentials::$message ) { print '<div class="updated"><p>'. WP_Email_Essentials::$message .'</p></div>'; } ?>
	<?php if ( WP_Email_Essentials::$error ) { print '<div class="error"><p>'. WP_Email_Essentials::$error .'</p></div>'; } ?>
	<form id="outpost" method='POST' action="">
		<input type="hidden" name="form_id" value="wpes-admins" />
		<table>
			<thead>
				<th>Mail Key</th>
				<th>Send to</th>
			</thead>
			<tbody>
				<?php foreach ($keys as $key) { ?>
			<tr>
				<td>
					<label for="key-<?php print $key; ?>"><?php print $key; ?></label>
				</td>
				<td>
					<input type="text" name="settings[keys][<?php print $key ?>]" placeholder="<?php print esc_attr($admin); ?>" value="<?php print $c[$key]; ?>" id="key-<?php print $key; ?>" />
				</td>
			</tr>
				<?php } ?>

			<tr class="header">
				<th>RegExp matched against subject</th>
				<th>Send to</th>
			</tr>
				<?php $i = 0; $exps = get_option('mail_key_list', array()); foreach ($exps as $regexp => $key) { ?>
			<tr>
				<td>
					<input type="text" name="settings[regexp][<?php print $i ?>][regexp]" value="<?php print esc_attr($regexp); ?>" />
				</td>
				<td>
					<input type="text" name="settings[regexp][<?php print $i ?>][key]" value="<?php print esc_attr($key); ?>" />
				</td>
			</tr>
				<?php $i ++; } ?>
				<?php for ($j = 0; $j < 5; $j++) { ?>
			<tr>
				<td>
					<input type="text" name="settings[regexp][<?php print $j+$i ?>][regexp]" value="" />
				</td>
				<td>
					<input type="text" name="settings[regexp][<?php print $j+$i ?>][key]" value="" />
				</td>
			</tr>
				<?php } ?>
			<tr>
				<td colspan="2">
					<input type="submit" name="op" value="Save settings" class="button-primary action" />
				</td>
			</tr>
			<tr class="header">
				<th>Unmatched subjects</th>
			</tr>
				<?php
				$fails = get_option('mail_key_fails', array());
				$fails = array_filter( $fails, function($item) { return ! WP_Email_Essentials::mail_subject_match($item) && ! WP_Email_Essentials::get_mail_key($item); } );
				update_option('mail_key_fails', array_values( $fails ));
				foreach ($fails as $fail) { ?>
			<tr>
				<td>
					<code><?php print $fail; ?></code>
				</td>
			</tr>
				<?php } ?>
			<tr>
		</tbody>
	</form>
</div>
