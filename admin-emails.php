<?php
/**
 * View: email log.
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
<div class="wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br/>
	</div>
	<style>
			#mail-viewer {
				position: relative;
				border: 1px solid grey;
				height: 600px;
				width: 100%;
			}

			#mail-viewer .top-panel {
				position: relative;
				height: 30%;
				overflow: auto;
				width: 100%;
				max-width: 100%;
			}

			#mail-viewer #mail-index {
				margin: 0;
				padding: 0;
				list-style: none;
				display: table;
			}

			#mail-viewer #mail-index li {
				margin: 0;
				padding: 0;
				line-height: 16px;
				cursor: pointer;
				display: table-row;
			}

			#mail-viewer #mail-index li.active {

			}

			#mail-viewer #mail-index #email-header {
				background: grey;
				color: white;
				font-weight: bold;
			}

			#mail-viewer #mail-index li:nth-child(2n+1) {
				background: #e0e0e0;
			}

			#mail-viewer #mail-index li.active {
				background: black;
				color: white;
				font-weight: bold
			}

			#mail-viewer #mail-index li:hover {
				background: darkgrey;
				color: white;
			}

			#mail-viewer #mail-index li:hover .dashicons {
				color: white;
			}

			#mail-viewer #mail-index li .sender,
			#mail-viewer #mail-index li .thedatetime,
			#mail-viewer #mail-index li .recipient {
			}

			#mail-data-viewer {
				position: absolute;
				top: 30%;
				left: 0;
				right: 0;
				height: 69%;
				border-top: 1px solid grey;
				overflow: auto;
			}

			#mail-data-viewer .email-data.show-debug,
			#mail-data-viewer .show-body,
			#mail-data-viewer .show-alt-body,
			#mail-data-viewer .show-headers {
				height: 100%;
				width: 100%;
			}

			#mail-viewer #mail-index li .thedatetime,
			#mail-viewer #mail-index li .recipient,
			#mail-viewer #mail-index li .sender,
			#mail-viewer #mail-index li .subject,
			#mail-viewer #mail-index li .eml,
			#mail-viewer #mail-index li .status {
				overflow: hidden;
				/*white-space: nowrap;*/
				display: table-cell;
				border-right: 1px solid grey;
				padding: 3px;
				height: 100%;
			}

			#mail-viewer #mail-index li.active .thedatetime,
			#mail-viewer #mail-index li.active .recipient,
			#mail-viewer #mail-index li.active .sender,
			#mail-viewer #mail-index li.active .subject,
			#mail-viewer #mail-index li.active .status {
				overflow-y: auto;
				white-space: initial;
			}

			#mail-viewer #mail-index li .thedatetime {
				width: 12%;
			}

			#mail-viewer #mail-index li .recipient {
				width: 20%;
			}

			#mail-viewer #mail-index li .sender {
				width: 20%;
			}

			#mail-viewer #mail-index li .subject {
				width: 25%;
			}

			#mail-viewer #mail-index li .status {
				width: 20%;
				border-right: none;
			}

			#mail-viewer .debug,
			#mail-viewer .body,
			#mail-viewer .alt_body,
			#mail-viewer .headers {
				display: none;
				height: 0;
				width: 0;
			}

			#mail-viewer .show-debug .debug,
			#mail-viewer .show-body .body,
			#mail-viewer .show-alt-body .alt_body,
			#mail-viewer .show-headers .headers {
				display: block;
				height: 100%;
				width: 100%;
			}

	</style>
	<h2>WP-Email-Essentials - Email history</h2>
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
				href="<?php print esc_attr( add_query_arg( '_page', $wpes_view_prev_page ) ); ?>">
					&lt; Previous page</a> <?php } ?></span>
		<span>
		<?php
		if ( false !== $wpes_view_next_page ) {
			?>
			<a
				href="<?php print esc_attr( add_query_arg( '_page', $wpes_view_next_page ) ); ?>">
					&lt; Next page</a> <?php } ?></span>
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
					History::MAIL_NEW => __( 'Sent ??', 'wpes' ),
					History::MAIL_SENT => __( 'Sent Ok', 'wpes' ),
					History::MAIL_FAILED => __( 'Failed', 'wpes' ),
					History::MAIL_OPENED => __( 'Opened' ),
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
					<span class="alt_body"><pre><?php print wp_kses_post( nl2br( $wpes_view_email->alt_body ) ); ?></pre></span>
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
	<p>*) A sender with an asterisk is rewritten to the site default sender and used as Reply-To address.</p>
	<script>
		jQuery(document).ready(function ($) {
			$(".email-item").bind('click', function (e) {
				var alt = e.altKey || false;
				$(this).addClass('active').siblings().removeClass('show-body').removeClass('show-debug').removeClass('show-headers').removeClass('show-alt-body').removeClass('active');
				var id = '#' + $(".email-item.active").attr('id').replace('email-', 'email-data-');
				var that = $(id);
				$('#mail-data-viewer .email-data').removeClass('show-body').removeClass('show-debug').removeClass('show-headers').removeClass('show-alt-body');

				if (alt) {
					$(this).removeClass('show-body').removeClass('show-alt-body').removeClass('show-headers').addClass('show-debug');
					$(that).removeClass('show-body').removeClass('show-alt-body').removeClass('show-headers').addClass('show-debug');
				} else if ($(this).is('.show-body')) {
					$(this).removeClass('show-body').addClass('show-headers');
					$(that).removeClass('show-body').addClass('show-headers');
					t
				} else if ($(this).is('.show-headers')) {
					$(this).removeClass('show-headers').addClass('show-alt-body');
					$(that).removeClass('show-headers').addClass('show-alt-body');
				} else if ($(this).is('.show-alt-body')) {
					$(this).removeClass('show-alt-body').addClass('show-body');
					$(that).removeClass('show-alt-body').addClass('show-body');
				} else {
					$(this).addClass('show-body');
					$(that).addClass('show-body');
				}
				$(window).trigger('resize');
			});

			$(window).bind('resize', function () {
				$(".autofit").each(function () {
					$(this).css('width', $(this).parent().innerWidth());
					$(this).css('height', $(this).parent().innerHeight());
				});
			}).trigger('resize');

		});

	</script>
