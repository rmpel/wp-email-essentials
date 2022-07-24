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
global $current_user, $wpdb;


// @phpcs:disable WordPress.Security.NonceVerification.Recommended
$wpes_view_order_field = $_GET['_ofield'] ?? 'ID';

if ( ! in_array( $wpes_view_order_field, [ 'subject', 'sender', 'thedatetime', 'recipient' ], true ) ) {
	$wpes_view_order_field = 'ID';
}

$wpes_view_order_direction = isset( $_GET['_order'] ) ? ( 'DESC' === $_GET['_order'] ? 'DESC' : 'ASC' ) : ( 'ID' === $wpes_view_order_field ? 'DESC' : 'ASC' );
$wpes_view_items_per_page  = isset( $_GET['_limit'] ) && intval( $_GET['_limit'] ) > 0 ? intval( $_GET['_limit'] ) : 25;
$wpes_view_current_page    = isset( $_GET['_page'] ) && intval( $_GET['_page'] ) > 0 ? intval( $_GET['_page'] ) : 0;
$wpes_view_first_item      = $wpes_view_current_page * $wpes_view_items_per_page;
// @phpcs:enable WordPress.Security.NonceVerification.Recommended

?>
<div class="wrap wpes-wrap wpes-emails">
	<h2 class="dashicons-before dashicons-email-alt"> <?php print wp_kses_post( Plugin::plugin_data()['Name'] ); ?>
		<em><?php print wp_kses_post( Plugin::plugin_data()['Version'] ); ?></em>
		- <?php esc_html_e( 'E-mail History', 'wpes' ); ?>
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
	<?php
	$wpes_view_total_nr_items = $wpdb->get_var( "SELECT COUNT(ID) as thecount FROM {$wpdb->prefix}wpes_hist" );
	if ( $wpes_view_first_item > $wpes_view_total_nr_items ) {
		$wpes_view_first_item = 0;
	}
	$wpes_view_nr_pages  = ceil( $wpes_view_total_nr_items / $wpes_view_items_per_page );
	$wpes_view_next_page = $wpes_view_current_page + 1;
	$wpes_view_prev_page = $wpes_view_current_page - 1;
	if ( $wpes_view_prev_page < 0 ) {
		$wpes_view_prev_page = false;
	}
	if ( $wpes_view_next_page > $wpes_view_nr_pages - 1 ) {
		$wpes_view_next_page = false;
	}
	?>
	<div class="pager">
		<span>
		<?php
		if ( false !== $wpes_view_prev_page ) {
			?>
			<a
				class="button"
				href="<?php print esc_attr( add_query_arg( '_page', $wpes_view_prev_page ) ); ?>">
					&lt; Previous page</a> <?php } ?></span>
		<span>
		<?php
		if ( false !== $wpes_view_next_page ) {
			?>
			<a
				class="button"
				href="<?php print esc_attr( add_query_arg( '_page', $wpes_view_next_page ) ); ?>">
					Next page &gt;</a> <?php } ?></span>
	</div>
	<div id="mail-viewer">
		<div class="top-panel">
			<ul id="mail-index">
				<li id="email-header">
					<span class="eml"><span class="dashicons dashicons-email-alt"></span></span>
					<span class="thedatetime"><?php esc_html_e( 'Date/Time', 'wpes' ); ?></span>
					<span class="recipient"><?php esc_html_e( 'Recipient', 'wpes' ); ?></span>
					<span class="sender"><?php esc_html_e( 'Sender', 'wpes' ); ?></span>
					<span class="subject"><?php esc_html_e( 'Subject', 'wpes' ); ?></span>
					<span class="status"><?php esc_html_e( 'Status', 'wpes' ); ?></span>
				</li>
				<?php
				// @phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- All data is sanitized before injection.
				$wpes_view_emails_list = $wpdb->get_results( "SELECT subject, sender, thedatetime, recipient, ID, body, alt_body, headers, status, `debug`, errinfo, eml FROM {$wpdb->prefix}wpes_hist ORDER BY $wpes_view_order_field $wpes_view_order_direction LIMIT $wpes_view_first_item,$wpes_view_items_per_page" );
				$wpes_view_email_stati = array(
					History::MAIL_NEW    => _x( 'Sent ??', 'E-mail log: this e-mail is Sent', 'wpes' ),
					History::MAIL_SENT   => _x( 'Sent Ok', 'E-mail log: this e-mail is Sent OK', 'wpes' ),
					History::MAIL_FAILED => _x( 'Failed', 'E-mail log: this e-mail Failed sending', 'wpes' ),
					History::MAIL_OPENED => _x( 'Opened', 'E-mail log: this e-mail is Opened by the receiver', 'wpes' ),
				);
				foreach ( $wpes_view_emails_list as $wpes_view_email ) {
					?>
					<li class="email-item" id="email-<?php print esc_attr( $wpes_view_email->ID ); ?>">
						<span class="eml">
						<?php
						if ( $wpes_view_email->eml ) {
							print '<a href="' . esc_attr( add_query_arg( 'download_eml', $wpes_view_email->ID ) ) . '" class="dashicons dashicons-download"></a>';
						}
						?>
						</span>
						<span
							class="thedatetime"><?php print esc_html( $wpes_view_email->thedatetime ); ?>&nbsp;</span>
						<span
							class="recipient"><?php print esc_html( $wpes_view_email->recipient ); ?>&nbsp;</span>
						<span
							class="sender"><?php print esc_html( $wpes_view_email->sender ); ?>&nbsp;</span>
						<span
							class="subject"><?php print esc_html( $wpes_view_email->subject ); ?>&nbsp;</span>
						<span
							class="status"><?php print esc_html( $wpes_view_email_stati[ $wpes_view_email->status ] ); ?> <?php print wp_kses_post( $wpes_view_email->errinfo ); ?>&nbsp;</span>
					</li>
					<?php
				}
				?>
			</ul>
		</div>
		<div id="mail-data-viewer">
			<?php
			$wpes_mailer = new WPES_PHPMailer();
			$wpes_css    = apply_filters_ref_array( 'wpes_css', array( '', &$wpes_mailer ) );

			foreach ( $wpes_view_emails_list as $wpes_view_email ) {
				// @phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- phpMailer thing. cannot help it.
				$wpes_mailer->Subject   = $wpes_view_email->subject;
				$wpes_view_email->debug = json_decode( $wpes_view_email->debug );
				if ( ! $wpes_view_email->debug ) {
					$wpes_view_email->debug = new \stdClass();
				}
				$wpes_view_email->debug = wp_json_encode( $wpes_view_email->debug, JSON_PRETTY_PRINT );

				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- how else am I supposed to base64_encode?.
				$wpes_email_data_base64 = base64_encode(
					str_ireplace(
						[
							'onload',
							'<script',
							'</script>',
						],
						[
							'nonload',
							'[SCRIPT',
							'[/SCRIPT]',
						],
						Plugin::maybe_convert_to_html( $wpes_view_email->body, $wpes_view_email->subject, $wpes_mailer )
					)
				);
				?>
				<div class="email-data" id="email-data-<?php print esc_attr( $wpes_view_email->ID ); ?>">
					<span class="headers"><pre><?php print esc_html( $wpes_view_email->headers ); ?></pre></span>
					<span class="alt_body"><pre><?php print wp_kses_post( $wpes_view_email->alt_body ); ?></pre></span>
					<span class="body">
					<iframe
						class="autofit" width="100%" height="100%" border="0" frameborder="0"
						src="data:text/html;headers=<?php print rawurlencode( 'Content-Security-Policy: script-src none;' ); ?>;base64,<?php print $wpes_email_data_base64; /* @phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>">
					</iframe>
				</span>
					<span class="debug"><pre><?php print esc_html( $wpes_view_email->debug ); ?></pre></span>
				</div>
				<?php
			}
			?>
		</div><!-- /mdv -->
	</div><!-- /mv -->
	<p>
		*) <?php esc_html_e( 'A sender with an asterisk is rewritten to the site default sender and used as Reply-To address.', 'wpes' ); ?>
	</p>
