<?php
/**
 * View: e-mail log.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'wpes' ) ) );
}

?>
<div class="wrap wpes-wrap wpes-queue">
	<?php
	Plugin::template_header( __( 'E-mail Queue', 'wpes' ) );
	if ( '' !== Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	if ( '' !== Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}

	require_once __DIR__ . '/../lib/class.wpes-queue-list-table.php';
	$wpes_queue_list_table = new WPES_Queue_List_Table();

	?>
	<div class="wpes-notice--warning">
		<strong class="warning">
			<?php print wp_kses_post( __( 'This feature is new and therefore needs to be considered experimental. If you have feedback, please send to <code>remon+wpes@acato.nl</code>. Thank you.', 'wpes' ) ); ?>
		</strong>
		<?php print wp_kses_post( __( 'Enabling the throttling feature will prevent sending large amounts of e-mails in quick succession, for example a spam-run.', 'wpes' ) ); ?>
		<br/>
		<?php print wp_kses_post( sprintf( __( 'Once activated, when more than %1$d e-mails are sent within %2$d seconds from the same IP-address, all other e-mails will be held until released.', 'wpes' ), Queue::get_max_count_per_time_window(), Queue::get_time_window() ) ); ?>
		<br/>
		<?php print wp_kses_post( sprintf( __( 'E-mails will be sent in batches of %d per minute, the trigger is a hit on the website, the admin panel or the cron (wp-cron.php).', 'wpes' ), Queue::get_batch_size() ) ); ?>
		<br/>
		<?php print wp_kses_post( __( 'E-mails with high priority will be sent as usual, if you have mission-critical e-mails, set priority to high using the following header;', 'wpes' ) ); ?>
		<code class="inline">X-Priority: 1</code>
	</div>
	<form
		action="<?php print esc_attr( add_query_arg( 'wpes-action', 'form-post' ) ); ?>"
		method="post">
		<?php
		wp_nonce_field( 'wp-email-essentials--queue', 'wpes-nonce' );
		$wpes_queue_list_table->process_bulk_action();
		$wpes_queue_list_table->prepare_items();
		$wpes_queue_list_table->display();
		?>
	</form>
</div>
