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
	if ( Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	?>
	<?php
	if ( Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}

	require_once __DIR__ . '/../lib/class.wpes-queue-list-table.php';
	$wpes_queue_list_table = new WPES_Queue_List_Table();

	?>
	<p>
		This is an experimental feature;
	</p>
	<ul>
		<li>E-mails with high priority will be sent as usual, if you have mission-critial emails, set <code>X-Priority:
				1</code>.
		</li>
		<li>Normal e-mails will be queued and sent in batches of 25 emails per minute.</li>
		<li>If a single user (based on IP-address) sends too many e-mails in quick succession, the remainder will be
			blocked. This it to prevent spamming. You can unblock the emails below.
		</li>
		<li>Stale e-mails can be resent.</li>
	</ul>
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
