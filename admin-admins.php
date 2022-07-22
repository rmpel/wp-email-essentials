<?php
/**
 * View: alternative admins.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'wpes' ) ) );
}
global $current_user;
$wpes_config          = get_option( 'mail_key_admins', array() );
$wpes_mail_keys       = Plugin::mail_key_database();
$wpes_wordpress_admin = get_option( 'admin_email' );
?>
<div class="wrap wpes-wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br/>
	</div>
	<h2>WP-Email-Essentials - <?php esc_html_e( 'Alternative Admins', 'wpes' ); ?></h2>
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

	<form id="outpost" method='POST' action="">
		<input type="hidden" name="form_id" value="wpes-admins"/>
		<?php wp_nonce_field( 'wp-email-essentials--admins', 'wpes-nonce' ); ?>
		<table>
			<thead>
			<th><?php esc_html_e( 'Mail Key', 'wpes' ); ?></th>
			<th><?php esc_html_e( 'Send to', 'wpes' ); ?></th>
			</thead>
			<tbody>
			<?php
			foreach ( $wpes_mail_keys as $wpes_mail_key ) {
				if ( ! isset( $wpes_config[ $wpes_mail_key ] ) ) {
					$wpes_config[ $wpes_mail_key ] = '';
				}
				?>
				<tr>
					<td>
						<label
							for="key-<?php print esc_attr( $wpes_mail_key ); ?>"><?php print esc_html( $wpes_mail_key ); ?></label>
					</td>
					<td>
						<input
							type="text" name="settings[keys][<?php print esc_attr( $wpes_mail_key ); ?>]"
							placeholder="<?php print esc_attr( $wpes_wordpress_admin ); ?>"
							value="<?php print esc_attr( $wpes_config[ $wpes_mail_key ] ); ?>"
							id="key-<?php print esc_attr( $wpes_mail_key ); ?>"/>
					</td>
				</tr>
			<?php } ?>

			<tr class="header">
				<th><?php esc_html_e( 'RegExp matched against subject', 'wpes' ); ?>*</th>
				<th><?php esc_html_e( 'Send to', 'wpes' ); ?></th>
			</tr>
			<?php
			$wpes_loop_iterator_0 = 0;
			$wpes_regexp_list     = get_option( 'mail_key_list', array() );
			foreach ( $wpes_regexp_list as $wpes_regexp => $wpes_mail_key ) {
				?>
				<tr>
					<td>
						<input
							type="text"
							name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_0 ); ?>][regexp]"
							class="a-regexp"
							value="<?php print esc_attr( $wpes_regexp ); ?>"/>
					</td>
					<td>
						<input
							type="text" name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_0 ); ?>][key]"
							value="<?php print esc_attr( $wpes_mail_key ); ?>"/>
					</td>
				</tr>
				<?php
				$wpes_loop_iterator_0 ++;
			}
			?>
			<?php for ( $wpes_loop_iterator_1 = 0; $wpes_loop_iterator_1 < 5; $wpes_loop_iterator_1 ++ ) { ?>
				<tr>
					<td>
						<input
							type="text"
							name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_1 + $wpes_loop_iterator_0 ); ?>][regexp]"
							class="a-regexp"
							value=""/>
					</td>
					<td>
						<input
							type="text"
							name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_1 + $wpes_loop_iterator_0 ); ?>][key]"
							value=""/>
					</td>
				</tr>
			<?php } ?>
			<tr>
				<td colspan="2">
					<input
						type="submit" name="op" value="<?php print esc_attr__( 'Save settings', 'wpes' ); ?>"
						class="button-primary action"/>
				</td>
			</tr>
			<tr>
				<td><em> *) You must include the barriers, so start with / and end with /</em></td>
			</tr>
			<tr class="header">
				<th><?php esc_html_e( 'Unmatched subjects', 'wpes' ); ?></th>
			</tr>
			<?php
			$wpes_missed_subjects = get_option( 'mail_key_fails', array() );
			$wpes_missed_subjects = array_filter(
				$wpes_missed_subjects,
				function ( $item ) {
					return ! Plugin::mail_subject_match( $item ) && ! Plugin::get_mail_key( $item );
				}
			);
			update_option( 'mail_key_fails', array_values( $wpes_missed_subjects ) );
			foreach ( $wpes_missed_subjects as $wpes_missed_subject ) {
				?>
				<tr>
					<td>
						<code class="a-fail"><?php print esc_html( $wpes_missed_subject ); ?></code>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
	</form>
</div>
<script>
	jQuery(document).ready(function () {
		var t = function () {
			if (/^\/[\s\S]+\/$/.test((jQuery(this).val() || ""))) {
				var that = this;
				var re = jQuery(that).val();
				// re matches /asdsadasdasd/, so use substring to get rid of those /-es
				re = new RegExp(re.substr(1, re.length - 2));

				jQuery(".a-fail").each(function () {
					jQuery(this).toggleClass('match', re.test((jQuery(this).text() || "")));
				});
			} else {
				jQuery(".a-fail").removeClass('match');
			}
		};
		jQuery(".a-regexp").bind('blur', function () {
			var val = (jQuery(this).val() || "");
			if ("" === val) {
				return jQuery(this).removeClass('error match');
			}
			jQuery(this).toggleClass('error', !/^\/[\s\S]+\/$/.test(val)).not('.error').addClass('match');
		}).bind('focus', function (e) {
			jQuery(".a-fail,.a-regexp").removeClass('match');
			jQuery(this).removeClass('error match');
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
