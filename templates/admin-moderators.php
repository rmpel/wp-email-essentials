<?php
/**
 * View: moderators interface.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'wpes' ) ) );
}

global $current_user;
$wpes_config               = get_option( 'mail_key_moderators', array() );
$wpes_moderator_keys       = array( 'pingback', 'comment' );
$wpes_moderator_recipients = array(
	'author'    => 'notification',
	'moderator' => 'moderation request',
);
if ( ! get_option( 'moderation_notify' ) ) {
	// moderations disabled, so only show notification.
	unset( $wpes_moderator_recipients['moderator'] );
}
?>
<div class="wrap wpes-wrap wpes-moderators">
	<h2 class="dashicons-before dashicons-email-alt"> <?php print wp_kses_post( Plugin::plugin_data()['Name'] ); ?>
		<em><?php print wp_kses_post( Plugin::plugin_data()['Version'] ); ?></em>
		- <?php esc_html_e( 'Alternative Moderators', 'wpes' ); ?>
	</h2>
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
		<input type="hidden" name="form_id" value="wpes-moderators"/>
		<?php wp_nonce_field( 'wp-email-essentials--moderators', 'wpes-nonce' ); ?>
		<table>
			<thead>
			<th>Action</th>
			<th><?php esc_html_e( 'Send to', 'wpes' ); ?></th>
			</thead>
			<tbody>
			<?php
			foreach ( $wpes_moderator_recipients as $wpes_moderator_recipient => $wpes_moderator_action ) {
				foreach ( $wpes_moderator_keys as $wpes_moderator_key ) {
					foreach ( [ 'post' ] as $wpes_post_type ) {
						if ( ! isset( $wpes_config[ $wpes_post_type ][ $wpes_moderator_recipient ][ $wpes_moderator_key ] ) ) {
							$wpes_config[ $wpes_post_type ][ $wpes_moderator_recipient ][ $wpes_moderator_key ] = '';
						}
						// translators: %s: post-type.
						$wpes_placeholder = sprintf( __( 'default: owner of %s', 'wpes' ), $wpes_post_type );
						?>
						<tr>
							<td>
								<label
									for="key-<?php print esc_attr( $wpes_post_type ); ?>-<?php print esc_attr( $wpes_moderator_recipient ); ?>-<?php print esc_attr( $wpes_moderator_key ); ?>">
									<?php
									// translators: %1$s: e-mail type like notification or request, %2$s: comment type like comment or pingback, %3$s: post_type .
									print wp_kses_post( sprintf( __( '<em>%1$s</em> to author on <em>%2$s</em> on <em>%3$s</em>', 'wpes' ), $wpes_moderator_action, $wpes_moderator_key, $wpes_post_type ) ) . ':';
									?>
								</label>
							</td>
							<td>
								<input
									type="text"
									name="settings[keys][<?php print esc_attr( $wpes_post_type ); ?>][<?php print esc_attr( $wpes_moderator_recipient ); ?>][<?php print esc_attr( $wpes_moderator_key ); ?>]"
									placeholder="<?php print esc_attr( $wpes_placeholder ); ?>"
									value="<?php print esc_attr( $wpes_config[ $wpes_post_type ][ $wpes_moderator_recipient ][ $wpes_moderator_key ] ); ?>"
									id="key-<?php print esc_attr( $wpes_post_type ); ?>-<?php print esc_attr( $wpes_moderator_recipient ); ?>-<?php print esc_attr( $wpes_moderator_key ); ?>"/>
							</td>
						</tr>
						<?php
					}
				}
			}
			?>

			<tr>
				<td colspan="2">
					<input
						type="submit" name="op" value="<?php print esc_attr__( 'Save settings', 'wpes' ); ?>"
						class="button-primary action"/>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
	<p>
		<?php
		print wp_kses_post( sprintf( __( '%s is allowed to disable sending the e-mail.', 'wpes' ), '<code>:blackhole:</code>' ) );
		?>
	</p>
	<p>
		<?php
		print esc_html__( 'Moderation for pingbacks and comments is', 'wpes' ) . ': ';
		print wp_kses_post( '<strong>' . ( get_option( 'moderation_notify' ) ? __( 'enabled', 'wpes' ) : __( 'disabled', 'wpes' ) ) . '</strong>.' );
		?>
		<a href="<?php print esc_attr( admin_url( 'options-discussion.php' ) ); ?>#comment_order">
			<?php
			print esc_html__( 'Change this setting', 'wpes' ) . '.';
			?>
		</a>
	</p>
</div>
