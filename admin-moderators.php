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
<div class="wrap wpes-wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br/>
	</div>
	<h2>WP-Email-Essentials - <?php esc_html_e( 'Alternative Moderators', 'wpes' ); ?></h2>
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
						?>
						<tr>
							<td>
								<label
									for="key-<?php print esc_attr( $wpes_post_type ); ?>-<?php print esc_attr( $wpes_moderator_recipient ); ?>-<?php print esc_attr( $wpes_moderator_key ); ?>"><?php print esc_html( $wpes_moderator_action ); ?>
									to author on <?php print esc_html( $wpes_moderator_key ); ?>
									on <?php print esc_html( $wpes_post_type ); ?></label>
							</td>
							<td>
								<input
									type="text"
									name="settings[keys][<?php print esc_attr( $wpes_post_type ); ?>][<?php print esc_attr( $wpes_moderator_recipient ); ?>][<?php print esc_attr( $wpes_moderator_key ); ?>]"
									placeholder="default: owner of <?php print esc_attr( $wpes_post_type ); ?>"
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
	<p><code>:blackhole:</code> is allowed to disable sending the email.</p>
	<p>Moderation for pingbacks and comments
		is: <?php print ( get_option( 'moderation_notify' ) ? 'enabled' : 'disabled' ); ?>.
		<a href="<?php print esc_attr( admin_url( 'options-discussion.php' ) ); ?>#comment_order">Change this
			setting</a>.
	</p>
</div>
<style>
	#outpost input[type=text] {
		width: 400px;
	}
</style>
