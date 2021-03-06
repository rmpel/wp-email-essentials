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
$wpes_config          = get_option( 'mail_key_admins', [] );
$wpes_mail_keys       = Plugin::mail_key_database();
$wpes_wordpress_admin = get_option( 'admin_email' );
?>
<div class="wrap wpes-wrap wpes-admins">
	<?php
	Plugin::template_header( __( 'Alternative Admins', 'wpes' ) );
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
		<table class="wpes-table">
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
							class="widefat"
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
			$wpes_regexp_list     = get_option( 'mail_key_list', [] );
			foreach ( $wpes_regexp_list as $wpes_regexp => $wpes_mail_key ) {
				?>
				<tr>
					<td>
						<input
							type="text"
							name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_0 ); ?>][regexp]"
							class="a-regexp widefat"
							value="<?php print esc_attr( $wpes_regexp ); ?>"/>
					</td>
					<td>
						<input
							type="text" name="settings[regexp][<?php print esc_attr( $wpes_loop_iterator_0 ); ?>][key]"
							class="widefat"
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
							class="a-regexp widefat"
							value=""/>
					</td>
					<td>
						<input
							type="text" class="widefat"
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
				<td colspan="2">
					<em> *)
						<?php
						// translators: %1$s: the regexp barrier "/" .
						print wp_kses_post( sprintf( __( 'You must include the boundaries, so start with %1$s and end with %1$s.', 'wpes' ), '<code>/</code>' ) );
						?>
						<br/>
						<?php
						print wp_kses_post( sprintf( __( 'You can add the %1$s flag to create a case-insensitive match (like so: %2$s).', 'wpes' ), '<code>i</code>', '<code>/some[expression]/i</code>' ) );
						?>
						<br/>
						<?php
						print wp_kses_post( __( 'If you are unfamiliar with regular expressions, you can ignore this section, ask for help or learn the magic and power of regular expressions yourself.', 'wpes' ) );
						?>
					</em>
				</td>
			</tr>
			<tr class="header">
				<th colspan="2"><?php esc_html_e( 'Unmatched subjects', 'wpes' ); ?></th>
			</tr>
			<tr>
				<td colspan="2">
					<?php print wp_kses_post( __( 'This is a list of e-mail subjects of e-mails that have been sent to the site administrator.', 'wpes' ) ); ?>
					<?php print wp_kses_post( __( 'You can use the table above to input regular expressions for e-mails that should have gone to an alternative e-mail address.', 'wpes' ) ); ?>
				</td>
			</tr>
			<?php
			$wpes_missed_subjects = get_option( 'mail_key_fails', [] );
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

