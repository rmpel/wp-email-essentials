<?php
if ( ! current_user_can('manage_options') ) {
	wp_die(__('Uh uh uh! You didn\'t say the magic word!', 'wpes'));
}
global $current_user;
$c = get_option('mail_key_admins', array());
$keys = WP_Email_Essentials::mail_key_database();
$admin = get_option('admin_email');
?>
<div class="wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br />
	</div>
	<h2>WP-Email-Essentials - <?php _e('Alternative Admins', 'wpes'); ?></h2>
	<?php if ( WP_Email_Essentials::$message ) { print '<div class="updated"><p>'. WP_Email_Essentials::$message .'</p></div>'; } ?>
	<?php if ( WP_Email_Essentials::$error ) { print '<div class="error"><p>'. WP_Email_Essentials::$error .'</p></div>'; } ?>
	<form id="outpost" method='POST' action="">
		<input type="hidden" name="form_id" value="wpes-admins" />
		<table>
			<thead>
			<th><?php _e('Mail Key', 'wpes'); ?></th>
			<th><?php _e('Send to', 'wpes'); ?></th>
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
				<th><?php _e('RegExp matched against subject', 'wpes'); ?>*</th>
				<th><?php _e('Send to', 'wpes'); ?></th>
			</tr>
			<?php $i = 0; $exps = get_option('mail_key_list', array()); foreach ($exps as $regexp => $key) { ?>
				<tr>
					<td>
						<input type="text" name="settings[regexp][<?php print $i ?>][regexp]" class="a-regexp" value="<?php print esc_attr($regexp); ?>" />
					</td>
					<td>
						<input type="text" name="settings[regexp][<?php print $i ?>][key]" value="<?php print esc_attr($key); ?>" />
					</td>
				</tr>
				<?php $i ++; } ?>
			<?php for ($j = 0; $j < 5; $j++) { ?>
				<tr>
					<td>
						<input type="text" name="settings[regexp][<?php print $j+$i ?>][regexp]" class="a-regexp" value="" />
					</td>
					<td>
						<input type="text" name="settings[regexp][<?php print $j+$i ?>][key]" value="" />
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td colspan="2">
					<input type="submit" name="op" value="<?php print esc_attr__('Save settings', 'wpes'); ?>" class="button-primary action" />
				</td>
			</tr>
			<tr>
				<td><em> *) You must include the barriers, so start with / and end with /</em></td>
			</tr>
			<tr class="header">
				<th><?php _e('Unmatched subjects', 'wpes'); ?></th>
			</tr>
			<?php
			$fails = get_option('mail_key_fails', array());
			$fails = array_filter( $fails, function($item) { return ! WP_Email_Essentials::mail_subject_match($item) && ! WP_Email_Essentials::get_mail_key($item); } );
			update_option('mail_key_fails', array_values( $fails ));
			foreach ($fails as $fail) { ?>
				<tr>
					<td>
						<code class="a-fail"><?php print $fail; ?></code>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</form>
</div>
<script>
	jQuery(document).ready(function(){
		var t = function(){
			if (/^\/[\s\S]+\/$/.test( (jQuery(this).val()||"") )) {
				var that = this;
				var re = jQuery(that).val();
				// re matches /asdsadasdasd/, so use substring to get rid of those /-es
				re = new RegExp(re.substr(1, re.length-2));

				jQuery(".a-fail").each(function(){
					jQuery(this).toggleClass( 'match', re.test( (jQuery(this).text()||"") ));
				});
			}
			else {
				jQuery(".a-fail").removeClass('match');
			}
		};
		jQuery(".a-regexp").bind('blur', function(){
			var val = (jQuery(this).val()||"");
			if ("" == val) {
				return jQuery(this).removeClass('error match');
			}
			jQuery(this).toggleClass( 'error', ! /^\/[\s\S]+\/$/.test( val )).not('.error').addClass('match');
		}).bind('focus', function(e){
			jQuery(".a-fail,.a-regexp").removeClass('match');
			jQuery(this).removeClass( 'error match' );
			t.apply(this, [e]);
		}).bind('keyup', t);
	});
</script>
<style>
	.a-regexp {
		width: 300px;
	}
	.a-regexp.error {
		border-color: red;
		background: #ff9e8b;
	}
	.a-regexp.match {
		border-color: #00a800;
		background: #97e396;
	}
	.a-fail.match {
		background-color: #80ff8e
	}
</style>
