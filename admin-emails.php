<?php
	if ( ! current_user_can('manage_options') ) {
		wp_die(__('Uh uh uh! You didn\'t say the magic word!', 'wpes'));
	}
	global $current_user, $wpdb;
	$c = WP_Email_Essentials::get_config();
	$ofield = @$_GET['_ofield'] ?: 'ID';
	if (!in_array($ofield, array('subject', 'sender', 'thedatetime', 'recipient'))) {
		$ofield = 'ID';
	}
	$order = @$_GET['_order'] ?: ($ofield == 'ID' ? 'DESC' : 'ASC');
	$limit = intval($_GET['_limit']) > 0 ? intval($_GET['_limit']) : 25;
	$page  = intval($_GET['_page']) > 0 ? intval($_GET['_page']) : 0;
	$start = $page * $limit;
?>
<div class="wrap">
	<div class="icon32 icon32-posts-group" id="icon-edit">
		<br />
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

	#mail-viewer #mail-index li .thedatetime,
	#mail-viewer #mail-index li .recipient,
	#mail-viewer #mail-index li .sender,
	#mail-viewer #mail-index li .subject,
	#mail-viewer #mail-index li .status{
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
	#mail-viewer #mail-index li.active .status{
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
	}

	#mail-viewer .show-debug .debug,
	#mail-viewer .show-body .body,
	#mail-viewer .show-alt-body .alt_body,
	#mail-viewer .show-headers .headers {
		display: block;
	}

</style>
	<h2>WP-Email-Essentials - Email history</h2>
	<?php if ( WP_Email_Essentials::$message ) { print '<div class="updated"><p>'. WP_Email_Essentials::$message .'</p></div>'; } ?>
	<?php if ( WP_Email_Essentials::$error ) { print '<div class="error"><p>'. WP_Email_Essentials::$error .'</p></div>'; } ?>
	<?php
		$total = $wpdb->get_var("SELECT COUNT(ID) as thecount FROM {$wpdb->prefix}wpes_hist");
		if ($start > $total) $start = 0;
		?>
<div id="mail-viewer">
	<div class="top-panel">
	<ul id="mail-index">
		<li id="email-header">
			<span class="thedatetime"><?php _e('Date/Time', 'wpes'); ?></span>
			<span class="recipient"><?php _e('Recipient', 'wpes'); ?></span>
			<span class="sender"><?php _e('Sender', 'wpes'); ?></span>
			<span class="subject"><?php _e('Subject', 'wpes'); ?></span>
			<span class="status"><?php _e('Status', 'wpes'); ?></span>
		</li>
		<?php
			$list = $wpdb->get_results("SELECT subject, sender, thedatetime, recipient, ID, body, alt_body, headers, status, `debug`, errinfo FROM {$wpdb->prefix}wpes_hist ORDER BY $ofield $order LIMIT $start,$limit");
			$stati = array(
				__('Sent ??'),
				__('Sent Ok'),
				__('Failed'),
			);
			foreach ($list as $item) {
				?><li class="email-item" id="email-<?php print $item->ID; ?>">
					<span class="thedatetime"><?php print esc_html($item->thedatetime); ?>&nbsp;</span>
					<span class="recipient"><?php print esc_html($item->recipient); ?>&nbsp;</span>
					<span class="sender"><?php print esc_html($item->sender); ?>&nbsp;</span>
					<span class="subject"><?php print esc_html($item->subject); ?>&nbsp;</span>
					<span class="status"><?php print $stati[$item->status]; ?> <?php print $item->errinfo; ?>&nbsp;</span>
				</li><?php
			} ?></ul></div>
			<div id="mail-data-viewer">
				<?php
			foreach ($list as $item) {
				$item->debug = json_decode($item->debug);
				if (!$item->debug) {
					$item->debug = new stdClass();
				}
				$item->debug->Password = '********';
				$item->debug = json_encode($item->debug, JSON_PRETTY_PRINT);
				?><div class="email-data" id="email-data-<?php print $item->ID; ?>">
					<span class="headers"><pre><?php print esc_html($item->headers); ?></pre></span>
					<span class="alt_body"><pre><?php print nl2br($item->alt_body); ?></pre></span>
					<span class="body"><?php print $item->body; ?>&nbsp;</span>
					<span class="debug"><pre><?php print esc_html($item->debug); ?></pre></span>
				</div><?php
			} ?>
	</div><!-- /mdv -->
</div><!-- /mv -->
<script>
	jQuery(document).ready(function($) {
		$(".email-item").bind('click', function(e){
			var alt = e.altKey || false;
			$(this).addClass('active').siblings().removeClass('show-body').removeClass('show-debug').removeClass('show-headers').removeClass('show-alt-body').removeClass('active');
			var id = '#' + $(".email-item.active").attr('id').replace('email-', 'email-data-');
			var that = $(id);
			$('#mail-data-viewer .email-data').removeClass('show-body').removeClass('show-debug').removeClass('show-headers').removeClass('show-alt-body');

			if (alt) {
				$(this).removeClass('show-body').removeClass('show-alt-body').removeClass('show-headers').addClass('show-debug');
				$(that).removeClass('show-body').removeClass('show-alt-body').removeClass('show-headers').addClass('show-debug');
			}
			else if ($(this).is('.show-body')) {
				$(this).removeClass('show-body').addClass('show-headers');
				$(that).removeClass('show-body').addClass('show-headers');
			}
			else if ($(this).is('.show-headers')) {
				$(this).removeClass('show-headers').addClass('show-alt-body');
				$(that).removeClass('show-headers').addClass('show-alt-body');
			}
			else if ($(this).is('.show-alt-body')) {
				$(this).removeClass('show-alt-body').addClass('show-body');
				$(that).removeClass('show-alt-body').addClass('show-body');
			}
			else {
				$(this).addClass('show-body');
				$(that).addClass('show-body');
			}

		});
	});

</script>
