<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( __( 'Uh uh uh! You didn\'t say the magic word!', 'wpes' ) );
}
global $current_user;
$c          = get_option( 'mail_key_moderators', array() );
$keys       = array( 'pingback', 'comment' );
$recipients = array( 'author' => 'notification', 'moderator' => 'moderation request' );
if ( ! get_option( 'moderation_notify' ) ) {
	// moderations disabled, so only show notification.
	unset( $recipients['moderator'] );
}
?>
<div class="wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br/>
	</div>
	<h2>WP-Email-Essentials - <?php _e( 'Alternative Moderators', 'wpes' ); ?></h2>
	<?php if ( WP_Email_Essentials::$message ) {
		print '<div class="updated"><p>' . WP_Email_Essentials::$message . '</p></div>';
	} ?>
	<?php if ( WP_Email_Essentials::$error ) {
		print '<div class="error"><p>' . WP_Email_Essentials::$error . '</p></div>';
	} ?>
	<form id="outpost" method='POST' action="">
		<input type="hidden" name="form_id" value="wpes-moderators"/>
		<table>
			<thead>
			<th>Action</th>
			<th><?php _e( 'Send to', 'wpes' ); ?></th>
			</thead>
			<tbody>
			<?php foreach ( $recipients as $recipient => $action ) {
				foreach ( $keys as $key ) {
					foreach ( array( 'post' ) as $post_type ) {
						if ( ! isset( $c[ $post_type ][ $recipient ][ $key ] ) ) {
							$c[ $post_type ][ $recipient ][ $key ] = '';
						}
						?>
						<tr>
							<td>
								<label
									for="key-<?php print $post_type; ?>-<?php print $recipient; ?>-<?php print $key; ?>"><?php print $action; ?>
									to author on <?php print $key; ?> on <?php print $post_type; ?></label>
							</td>
							<td>
								<input type="text"
									   name="settings[keys][<?php print $post_type; ?>][<?php print $recipient; ?>][<?php print $key ?>]"
									   placeholder="default: owner of <?php print $post_type; ?>"
									   value="<?php print $c[ $post_type ][ $recipient ][ $key ]; ?>"
									   id="key-<?php print $post_type; ?>-<?php print $recipient; ?>-<?php print $key; ?>"/>
							</td>
						</tr>
					<?php }
				}
			} ?>

			<tr>
				<td colspan="2">
					<input type="submit" name="op" value="<?php print esc_attr__( 'Save settings', 'wpes' ); ?>"
						   class="button-primary action"/>
				</td>
			</tr>
			</tbody>
		</table>
	</form>
	<p><code>:blackhole:</code> is allowed to disable sending the email.</p>
	<p>Moderation for pingbacks and comments
		is: <?php print ( get_option( 'moderation_notify' ) ? 'enabled' : 'disabled' ); ?>. <a
			href="<?php print admin_url( 'options-discussion.php' ) ?>#comment_order">Change this setting</a>.</p>
</div>
<style>
	#outpost input[type=text] {
		width: 400px;
	}
</style>
