<?php
/**
 * View: admin interface.
 *
 * @package WP_Email_Essentials
 */

namespace WP_Email_Essentials;

if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( wp_kses_post( __( 'Uh uh uh! You didn\'t say the magic word!', 'wpes' ) ) );
}

$wpes_config = Plugin::get_config();
if ( empty( $wpes_config['dkimfolder'] ) ) {
	$wpes_config['dkimfolder'] = '';
}
if ( empty( $wpes_config['certfolder'] ) ) {
	$wpes_config['certfolder'] = '';
}
$wpes_host             = Plugin::get_hostname_by_blogurl();
$wpes_smime_identities = [];
$wpes_dkim_identities  = [];

?>
<div class="wrap wpes-wrap wpes-settings">
	<?php
	Plugin::template_header( __( 'E-mail Configuration', 'wpes' ) );
	if ( '' !== Plugin::$message ) {
		print '<div class="updated"><p>' . wp_kses_post( Plugin::$message ) . '</p></div>';
	}
	if ( '' !== Plugin::$error ) {
		print '<div class="error"><p>' . wp_kses_post( Plugin::$error ) . '</p></div>';
	}
	?>

	<form id="outpost" class="wpes-admin" method='POST' action="" enctype="multipart/form-data">
		<input type="hidden" name="form_id" value="wp-email-essentials"/>
		<?php wp_nonce_field( 'wp-email-essentials--settings', 'wpes-nonce' ); ?>

		<?php if ( Plugin::$debug ) { ?>
			<div class="wpes-notice--info">
				<pre><?php print wp_kses_post( Plugin::$debug ); ?></pre>
			</div>
		<?php } ?>

		<div class="wpes-tools">
			<div class="wpes-tools--box">
				<input
					type="submit" name="op" value="<?php print esc_attr__( 'Save settings', 'wpes' ); ?>"
					class="button-primary action"/>
				<!-- input type="submit" name="op" value="<?php print esc_attr__( 'Print debug output of sample mail', 'wpes' ); ?>" class="button-secondary action" / -->
				<input
					type="submit" name="op" value="<?php print esc_attr__( 'Send sample mail', 'wpes' ); ?>"
					class="button-secondary action"/>
				<em>
					<?php
					$wpes_admin        = get_option( 'admin_email', false );
					$wpes_sample_email = [
						'to'      => $wpes_admin,
						'subject' => Plugin::dummy_subject(),
					];
					$wpes_sample_email = Plugin::alternative_to( $wpes_sample_email );
					$wpes_admin        = reset( $wpes_sample_email['to'] );
					// translators: %1$s: a link to the options panel, %2$s: an e-mail address.
					print wp_kses_post( sprintf( __( 'Sample mail will be sent to the <a href="%1$s">Site Administrator</a>; <b>%2$s</b>', 'wpes' ), admin_url( 'options-general.php' ), $wpes_admin ) );
					?>
				</em>
			</div>

			<div class="wpes-tools--box__toc">
				<strong>
					<?php print wp_kses_post( __( 'Jump to', 'wpes' ) ); ?>
				</strong>

				<?php
				$wpes_blocks = [
					'basic-information'                   => _x( 'Basic information', 'Item in jump-to list', 'wpes' ),
					'how-to-validate-sender'              => _x( 'How to validate sender?', 'Item in jump-to list', 'wpes' ),
					'what-to-do-in-case-sender-not-valid' => _x( 'What to do in case the sender is not valid for this domain?', 'Item in jump-to list', 'wpes' ),
					'email-history'                       => _x( 'E-mail History', 'Item in jump-to list', 'wpes' ),
					'email-queue'                         => _x( 'E-mail Throttling', 'Item in jump-to list', 'wpes' ),
					'email-settings'                      => _x( 'E-mail Settings', 'Item in jump-to list', 'wpes' ),
					'email-content'                       => _x( 'E-mail content', 'Item in jump-to list', 'wpes' ),
					'content-charset-recoding'            => _x( 'Content charset re-coding', 'Item in jump-to list', 'wpes' ),
					'content-handling'                    => _x( 'Content handling', 'Item in jump-to list', 'wpes' ),
					'digital-smime'                       => _x( 'Digital E-mail Signing (S/MIME)', 'Item in jump-to list', 'wpes' ),
					'digital-dkim'                        => _x( 'Digital E-mail Signing (DKIM)', 'Item in jump-to list', 'wpes' ),
					'email-styling-filters'               => _x( 'E-mail styling, and filters for HTML head/body', 'Item in jump-to list', 'wpes' ),
					'email-preview'                       => _x( 'Example e-mail', 'Item in jump-to list', 'wpes' ),
				];
				?>
				<ul class="toc">
					<?php foreach ( $wpes_blocks as $wpes_block_id => $wpes_block_name ) { ?>
						<li>
							<a href="#<?php echo esc_attr( $wpes_block_id ); ?>">
								<?php echo esc_html( $wpes_block_name ); ?>
							</a>
						</li>
					<?php } ?>
				</ul>
			</div>
		</div>

		<div id="poststuff">
			<div id="basic-information" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Basic information', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<div class="wpes-notice--info">
								<?php print wp_kses_post( sprintf( __( 'Out of the box, WordPress will use name "WordPress" and e-mail "wordpress@%s" as default sender. This is far from optimal. Your first step is therefore to set an appropriate name and e-mail address.', 'wpes' ), $wpes_host ) ); ?>
							</div>
							<label
								for="from-name"><?php print wp_kses_post( __( 'Default from name', 'wpes' ) ); ?></label>
							<input
								type="text"
								name="settings[from_name]"
								value="<?php print esc_attr( $wpes_config['from_name'] ); ?>"
								placeholder="WordPress"
								id="from-name"/>
						</div>
						<div class="wpes-form-item">
							<label for="from-email">
								<?php print wp_kses_post( __( 'Default from e-mail', 'wpes' ) ); ?>
							</label>
							<input
								type="text" name="settings[from_email]"
								value="<?php print esc_attr( $wpes_config['from_email'] ); ?>"
								placeholder="wordpress@<?php print esc_attr( $wpes_host ); ?>"
								id="from-email"/>
							<div
								class="wpes-notice--error on-regexp-test"
								data-regexp="(no-?reply)@"
								data-field="from-email">
								<?php print wp_kses_post( __( 'Under GDPR, from May 25th, 2018, using a no-reply@ (or any variation of a not-responded-to e-mail address) is prohibited. Please make sure the default sender address is valid and used in the setting below.', 'wpes' ) ); ?>
							</div>
						</div>

						<?php
						if ( $wpes_config['spf_lookup_enabled'] ) {
							// SPF match.
							$wpes_spf_result = Plugin::i_am_allowed_to_send_in_name_of( $wpes_config['from_email'] );
							if ( ! $wpes_spf_result ) {
								?>
								<div class="wpes-notice--error">
									<strong class="title">
										<?php print wp_kses_post( __( 'SPF Records are checked', 'wpes' ) ); ?>
									</strong>

									<p>
										<?php print wp_kses_post( __( 'you are NOT allowed to send mail with this domain.', 'wpes' ) ); ?>
										<br/>
										<?php print wp_kses_post( __( 'If you really need to use this sender e-mail address, you need to change the SPF record to include the sending-IP of this server', 'wpes' ) ); ?>
									</p>

									<table class="wpes-info-table">
										<tr>
											<th>
												<?php print wp_kses_post( __( 'Old', 'wpes' ) ); ?>
											</th>
											<td>
												<code><?php print wp_kses_post( Plugin::get_spf( $wpes_config['from_email'], false, true ) ); ?></code>
											</td>
										</tr>
										<tr>
											<th>
												<?php print wp_kses_post( __( 'New', 'wpes' ) ); ?>
											</th>
											<td>
												<code><?php print wp_kses_post( Plugin::get_spf( $wpes_config['from_email'], true, true ) ); ?></code>
											</td>
										</tr>
									</table>
								</div>
							<?php } else { ?>
								<div class="wpes-notice--info">
									<strong class="title">
										<?php print wp_kses_post( __( 'SPF Records are checked', 'wpes' ) ); ?>
									</strong>

									<p>
										<?php print wp_kses_post( __( 'You are allowed to send mail with this domain.', 'wpes' ) ); ?>
									</p>
								</div>
								<div class="wpes-notice--info">
									<strong class="title">
										<?php print wp_kses_post( __( 'SPF Record', 'wpes' ) ); ?>
									</strong>

									<p>
										<code>
											<?php print wp_kses_post( Plugin::get_spf( $wpes_config['from_email'], false, true ) ); ?>
										</code>
									</p>
								</div>
								<?php
							}
							?>
							<div class="wpes-notice--info">
								<strong class="title">
									<?php
									print wp_kses_post( __( 'Sending IP', 'wpes' ) );
									?>
								</strong>
								<p>
									<code>
										<?php
										print wp_kses_post( Plugin::get_sending_ip() );
										?>
									</code>
								</p>
								<strong class="title">
									<?php
									print wp_kses_post( __( 'Matches', 'wpes' ) );
									?>
								</strong>
								<p>
									<code>
										<?php
										print $wpes_spf_result ? wp_kses_post( $wpes_spf_result ) : esc_html_e( 'Nothing ;( - This IP is not found in any part of the SPF.', 'wpes' );
										?>
									</code>
								</p>
							</div>
							<?php
						} elseif ( ! Plugin::i_am_allowed_to_send_in_name_of( $wpes_config['from_email'] ) ) {
							// domain match.
							?>
							<div class="wpes-notice--error">
								<strong class="title">
									<?php
									print wp_kses_post( __( 'You are NOT allowed to send mail with this domain; it should match the domainname of the website.', 'wpes' ) );
									?>
								</strong>

								<p>
									<?php
									print wp_kses_post( __( 'If you really need to use this sender e-mail address, you need to switch to SPF-record checking and make sure the SPF for this domain matches this server.', 'wpes' ) );
									?>
								</p>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>


			<div id="email-settings" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'E-mail Settings', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-radio-list">
						<input
							<?php checked( isset( $wpes_config['smtp'] ) && $wpes_config['smtp'] ); ?>
							type="checkbox" name="settings[smtp-enabled]"
							value="1"
							id="smtp-enabled"/>
						<label for="smtp-enabled">
							<?php print wp_kses_post( __( 'Enable sending mail over SMTP?', 'wpes' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--info">
						<?php print wp_kses_post( __( 'Using an SMTP improves reliability, helps reducing the chance of your e-mails being marked as spam and gives the option to use an external mail service like MailJet, MailGun, SparkPost etc.', 'wpes' ) ); ?>
					</div>

					<div class="wpes-form on-smtp-enabled">
						<div class="wpes-form-item">
							<label for="smtp-hostname">
								<?php print wp_kses_post( __( 'Hostname or -ip', 'wpes' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[host]"
								value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['host'] : '' ); ?>"
								id="smtp-hostname"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-port">
								<?php print wp_kses_post( __( 'SMTP Port', 'wpes' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[port]"
								value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['port'] : '' ); ?>"
								id="smtp-port"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-username">
								<?php print wp_kses_post( __( 'Username', 'wpes' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[username]"
								value="<?php print esc_attr( $wpes_config['smtp'] ? $wpes_config['smtp']['username'] : '' ); ?>"
								id="smtp-username"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-password">
								<?php print wp_kses_post( __( 'Password', 'wpes' ) ); ?>
							</label>
							<input
								type="password"
								name="settings[password]"
								value="<?php print esc_attr( $wpes_config['smtp'] ? str_repeat( '*', strlen( $wpes_config['smtp']['password'] ) ) : '' ); ?>"
								id="smtp-password"/>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-secure">
								<?php print wp_kses_post( __( 'Use encrypted connection?', 'wpes' ) ); ?>
							</label>
							<select name="settings[secure]" id="smtp-secure">
								<option value="">
									<?php print wp_kses_post( __( 'No', 'wpes' ) ); ?>
								</option>
								<option disabled>
									───────────────────────
								</option>
								<option disabled>
									<?php print wp_kses_post( __( 'Use encrypted connection', 'wpes' ) ); ?>
									- <?php print wp_kses_post( __( 'strict SSL verify', 'wpes' ) ); ?>
								</option>
								<option
									value="ssl" <?php selected( $wpes_config['smtp'] && 'ssl' === $wpes_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'SSL', 'wpes' ) ); ?>
								</option>
								<option
									value="tls" <?php selected( $wpes_config['smtp'] && 'tls' === $wpes_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'StartTLS', 'wpes' ) ); ?>
								</option>
								<option disabled>
									───────────────────────
								</option>
								<option disabled>
									<?php print wp_kses_post( __( 'Use encrypted connection', 'wpes' ) ); ?>
									- <?php print wp_kses_post( __( 'allow self-signed SSL', 'wpes' ) ); ?>
								</option>
								<option
									value="ssl-" <?php selected( $wpes_config['smtp'] && 'ssl-' === $wpes_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'SSL', 'wpes' ) ); ?>
								</option>
								<option
									value="tls-" <?php selected( $wpes_config['smtp'] && 'tls-' === $wpes_config['smtp']['secure'] ); ?>>
									<?php print wp_kses_post( __( 'StartTLS', 'wpes' ) ); ?>
								</option>
							</select>
						</div>
						<div class="wpes-form-item">
							<label for="timeout">
								<?php print wp_kses_post( __( 'phpMailer Timeout', 'wpes' ) ); ?>
							</label>
							<select id="timeout" name="settings[timeout]">
								<?php
								$wpes_timeouts = [
									60  => __( '1 minute', 'wpes' ),
									300 => __( '5 minutes (default)', 'wpes' ),
									600 => __( '10 minutes (for very slow hosts)', 'wpes' ),
								];
								if ( ! isset( $wpes_config['timeout'] ) || ! $wpes_config['timeout'] ) {
									$wpes_config['timeout'] = 300;
								}
								foreach ( $wpes_timeouts as $wpes_key => $wpes_val ) {
									print '<option value="' . esc_attr( $wpes_key ) . '" ' . selected( (int) $wpes_config['timeout'], $wpes_key, false ) . '>' . esc_html( $wpes_val ) . '</option>';
								}
								?>
							</select>
						</div>
					</div>

					<div class="wpes-radio-list">
						<input
							<?php checked( isset( $wpes_config['SingleTo'] ) && $wpes_config['SingleTo'] ); ?>
							type="checkbox"
							name="settings[SingleTo]"
							value="1"
							id="smtp-singleto"/>
						<label for="smtp-singleto">
							<?php print wp_kses_post( __( 'Split mail with more than one Recipient into separate mails?', 'wpes' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--info">
						<?php print wp_kses_post( __( 'Sending an email to multiple recipients is often regarded as spamming, using this option will send individual e-mails and reduces the chance of the email being rejected.', 'wpes' ) ); ?>
					</div>
				</div>
			</div>

			<div id="how-to-validate-sender" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'How to validate sender?', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<p>
						<em>
							<?php print wp_kses_post( __( 'You have 2 options', 'wpes' ) ); ?>:
						</em>
					</p>

					<div class="wpes-radio-list">
						<input
							<?php checked( ! isset( $wpes_config['spf_lookup_enabled'] ) || ! $wpes_config['spf_lookup_enabled'] ); ?>
							type="radio"
							name="settings[spf_lookup_enabled]"
							value="0"
							id="spf_lookup_enabled_0"/>
						<label for="spf_lookup_enabled_0">
							<?php print wp_kses_post( '<b>' . __( 'Domain name', 'wpes' ) . '</b>:<br />' . __( 'Use a simple match on hostname; any e-mail address that matches the base domainname of this website is considered valid.', 'wpes' ) ); ?>
						</label>

						<input
							<?php checked( isset( $wpes_config['spf_lookup_enabled'] ) && $wpes_config['spf_lookup_enabled'] ); ?>
							type="radio" name="settings[spf_lookup_enabled]" value="1"
							id="spf_lookup_enabled_1"/>
						<label for="spf_lookup_enabled_1">
							<?php print wp_kses_post( '<b>' . __( 'SPF records', 'wpes' ) . '</b>:<br />' . __( 'Use SPF records to validate the sender. If the SPF record of the domain of the e-mail address used as sender matches the IP-address this website is hosted on, the e-mail address is considered valid.', 'wpes' ) ); ?>
						</label>
					</div>
				</div>
			</div>

			<div id="what-to-do-in-case-sender-not-valid" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'What to do in case the sender is not valid for this domain?', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info">
						<strong class="title">
							<?php print wp_kses_post( __( 'Fix sender-address?', 'wpes' ) ); ?>
						</strong>

						<p>
							<?php print wp_kses_post( __( 'E-mails sent as different domain will probably be marked as spam. Use the options here to fix the sender-address to always match the sending domain.', 'wpes' ) ); ?>
							<br/>
							<?php print wp_kses_post( __( 'The actual sender of the e-mail will be used as <code>Reply-To</code>; you can still use the Reply button in your e-mail application to send a reply easily.', 'wpes' ) ); ?>
						</p>
					</div>

					<p>
						<strong>
							<?php print esc_html( _x( 'When the sender e-mail address', 'start of a sentence, will be suffixed with ...', 'wpes' ) ); ?>
							...
						</strong>
					</p>

					<div class="wpes-radio-list arrow-down">
						<input
							<?php checked( 'when_sender_invalid', $wpes_config['make_from_valid_when'] ); ?>
							id="wpes-settings-make_from_valid_when-when_sender_invalid"
							type="radio"
							name="settings[make_from_valid_when]"
							value="when_sender_invalid"/>
						<label
							class="on-regexp-test"
							data-field="spf_lookup_enabled_0"
							data-regexp="0"
							for="wpes-settings-make_from_valid_when-when_sender_invalid">...
							<?php print wp_kses_post( _x( 'is not on the website domain', 'middle of a sentence, will be prefixed with ... and suffixed with ;', 'wpes' ) ); ?>
							;
						</label>
						<label
							class="on-regexp-test"
							data-field="spf_lookup_enabled_1"
							data-regexp="1"
							for="wpes-settings-make_from_valid_when-when_sender_invalid">...
							<?php print wp_kses_post( _x( 'is not allowed by SPF from this website', 'middle of a sentence, will be prefixed with ... and suffixed with ;', 'wpes' ) ); ?>
							;
						</label>

						<input
							<?php checked( 'when_sender_not_as_set', $wpes_config['make_from_valid_when'] ); ?>
							id="wpes-settings-make_from_valid_when-when_sender_not_as_set"
							type="radio"
							name="settings[make_from_valid_when]"
							value="when_sender_not_as_set"/>
						<label for="wpes-settings-make_from_valid_when-when_sender_not_as_set">...
							<?php print wp_kses_post( _x( 'is not the "Default from e-mail" as set above', 'middle of a sentence, will be prefixed with ... and suffixed with ;', 'wpes' ) ); ?>
							;
						</label>
					</div>

					<div>
						<label for="make_from_valid"></label>
						<select class="widefat" name="settings[make_from_valid]" id="make_from_valid">
							<option
								value=""><?php print wp_kses_post( __( 'Keep the possibly-invalid sender as is. (might cause your mails to be marked as spam!)', 'wpes' ) ); ?></option>
							<option disabled>────────────────────────────────────────────────────────────</option>
							<option value="-at-" <?php selected( '-at-', $wpes_config['make_from_valid'] ); ?>>
								<?php print esc_html( sprintf( __( 'Rewrite e-mail@addre.ss to e-mail-at-addre-dot-ss@%s', 'wpes' ), $wpes_host ) ); ?>
							</option>
							<option
								value="noreply" <?php selected( 'noreply', $wpes_config['make_from_valid'] ); ?>>
								<?php print esc_html( sprintf( __( 'Rewrite e-mail@addre.ss to noreply@%s', 'wpes' ), $wpes_host ) ); ?>
								<?php print esc_html( __( '(Not GDPR Compliant)', 'wpes' ) ); ?>
							</option>
							<?php
							$wpes_default_sender_mail = Plugin::wp_mail_from( $wpes_config['from_email'] );
							if ( Plugin::i_am_allowed_to_send_in_name_of( $wpes_default_sender_mail ) ) {
								?>
								<option
									value="default" <?php selected( 'default', $wpes_config['make_from_valid'] ); ?>>
									<?php print esc_html( sprintf( __( 'Rewrite e-mail@addre.ss to %s', 'wpes' ), $wpes_default_sender_mail ) ); ?>
								</option>
							<?php } ?>
						</select>
					</div>
				</div>
			</div>

			<div id="email-history" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'E-mail History', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-radio-list">
						<input
							<?php checked( $wpes_config['enable_history'] ); ?>
							type="checkbox" name="settings[enable_history]"
							value="1"
							id="enable_history"/>
						<label for="enable_history">
							<?php print wp_kses_post( __( 'Enable E-mail History', 'wpes' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--warning on-enable_history">
						<?php print wp_kses_post( __( '<strong class="title">Warning</strong> Storing e-mails in your database is a BAD idea and illegal in most countries. Use this for DEBUGGING only!', 'wpes' ) ); ?>
						<br/>
						<?php print wp_kses_post( __( 'Enabling the history feature will also add a tracker to all outgoing e-mails to check receipt.', 'wpes' ) ); ?>
						<br/>
						<?php print wp_kses_post( __( 'Disabling this feature will delete the e-mail history database tables.', 'wpes' ) ); ?>
						<br/>
						<strong class="warning">
							<?php print wp_kses_post( __( 'If you insist on storing e-mails, please note that you need to implement the appropriate protocols for compliance with GDPR. The responsibility lies with the owner of the website, not the creator or hosting company.', 'wpes' ) ); ?>
						</strong>
					</div>
				</div>
			</div>

			<div id="email-queue" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'E-mail Throttling', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-radio-list">
						<input
							<?php checked( $wpes_config['enable_queue'] ); ?>
							type="checkbox" name="settings[enable_queue]"
							value="1"
							id="enable_queue"/>
						<label for="enable_queue">
							<?php print wp_kses_post( __( 'Enable E-mail Throttling', 'wpes' ) ); ?>
						</label>
					</div>

					<div class="wpes-notice--warning on-enable_queue">
						<?php print wp_kses_post( __( 'Enabling the throttling feature will prevent sending large amounts of e-mails in quick succession, for example a spam-run.', 'wpes' ) ); ?>
						<br/>
						<?php print wp_kses_post( sprintf( __( 'Once activated, when more than %1$d e-mails are sent within %2$d seconds from the same IP-address, all other e-mails will be held until released.', 'wpes' ), Queue::get_max_count_per_time_window(), Queue::get_time_window() ) ); ?>
						<br/>
						<?php print wp_kses_post( sprintf( __( 'E-mails will be sent in batches of %d per minute, the trigger is a hit on the website, the admin panel or the cron (wp-cron.php).', 'wpes' ), Queue::get_batch_size() ) ); ?>
						<br/>
						<strong class="warning">
							<?php print wp_kses_post( __( 'This feature is new and therefore needs to be considered experimental. If you have feedback, please send to <code>remon+wpes@acato.nl</code>. Thank you.', 'wpes' ) ); ?>
						</strong>
					</div>
				</div>
			</div>

			<div id="email-content" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'E-mail content', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<label for="smtp-is_html">
								<?php print wp_kses_post( __( 'Send as HTML?', 'wpes' ) ); ?>
							</label>
							<input
								<?php checked( isset( $wpes_config['is_html'] ) && $wpes_config['is_html'] ); ?>
								type="checkbox"
								name="settings[is_html]"
								value="1"
								id="smtp-is_html"/>
						</div>
						<div class="wpes-notice--info">
							<?php print wp_kses_post( __( 'This will convert non-html body to html-ish body', 'wpes' ) ); ?>
						</div>
						<div class="wpes-form-item">
							<label for="smtp-css_inliner">
								<?php print wp_kses_post( __( 'Convert CSS to Inline Styles', 'wpes' ) ); ?>
							</label>
							<input
								<?php checked( isset( $wpes_config['css_inliner'] ) && $wpes_config['css_inliner'] ); ?>
								type="checkbox"
								name="settings[css_inliner]"
								value="1"
								id="smtp-css_inliner"/>
						</div>
						<div class="wpes-notice--info">
							<?php print wp_kses_post( __( 'Works for Outlook Online, Yahoo Mail, Google Mail, Hotmail, etc.', 'wpes' ) ); ?>
						</div>
					</div>
				</div>
			</div>

			<div id="content-charset-recoding" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Content charset re-coding', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info">
						<?php print wp_kses_post( __( 'Some servers have f*cked-up content-encoding settings, resulting in wrongly encoded diacritics. If you expect a character like &eacute; and all you get is something like &euro;&tilde;&Itilde;, experiment with this setting.', 'wpes' ) ); ?>
					</div>

					<label for="content-precoding"></label><select
						id="content-precoding" name="settings[content_precode]">
						<?php
						$wpes_encoding_table         = explode( ',', '0,auto,' . Plugin::ENCODINGS );
						$wpes_encoding_table         = array_combine( $wpes_encoding_table, $wpes_encoding_table );
						$wpes_encoding_table         = array_map(
							function ( $item ) {
								// translators: %s: a content-encoding, like UTF-8.
								return sprintf( _x( 'From: %s', 'E.g.: From: UTF-8', 'wpes' ), strtoupper( $item ) );
							},
							$wpes_encoding_table
						);
						$wpes_encoding_table['0']    = __( 'No charset re-coding (default)', 'wpes' );
						$wpes_encoding_table['auto'] = __( 'Autodetect with mb_check_encoding()', 'wpes' );
						foreach ( $wpes_encoding_table as $wpes_encoding => $wpes_nice_encoding ) {
							print '<option value="' . esc_attr( $wpes_encoding ) . '" ' . selected( $wpes_config['content_precode'], $wpes_encoding, false ) . '>' . esc_html( $wpes_nice_encoding ) . '</option>';
						}
						?>
					</select>
				</div>
			</div>

			<div id="content-handling" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php esc_html_e( 'Content handling', 'wpes' ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<label for="smtp-alt_body">
								<?php print wp_kses_post( __( 'Derive plain-text alternative?', 'wpes' ) ); ?>
							</label>
							<input
								<?php checked( isset( $wpes_config['alt_body'] ) && $wpes_config['alt_body'] ); ?>
								type="checkbox"
								name="settings[alt_body]"
								value="1"
								id="smtp-alt_body"/>
						</div>
						<div class="wpes-notice--info">
							<?php print wp_kses_post( __( 'This will derive text-ish body from html body as AltBody', 'wpes' ) ); ?>
						</div>
						<div class="wpes-form-item">
							<label for="do_shortcodes">
								<?php print wp_kses_post( __( 'Process the body with <code>do_shortcode()</code>', 'wpes' ) ); ?>
							</label>
							<input
								<?php checked( isset( $wpes_config['do_shortcodes'] ) && $wpes_config['do_shortcodes'] ); ?>
								type="checkbox"
								name="settings[do_shortcodes]"
								value="1"
								id="do_shortcodes"/>
						</div>
					</div>
				</div>
			</div>

			<?php if ( function_exists( 'openssl_pkcs7_sign' ) ) { ?>
				<div id="digital-smime" class="postbox">
					<div class="postbox-header">
						<h2>
							<?php print wp_kses_post( __( 'Digital E-mail Signing (S/MIME)', 'wpes' ) ); ?>
						</h2>
					</div>
					<div class="inside">
						<div class="wpes-form">
							<div class="wpes-form-item">
								<label for="enable-smime">
									<?php print wp_kses_post( __( 'Sign e-mails with S/MIME certificate', 'wpes' ) ); ?>
								</label>
								<input
									<?php checked( isset( $wpes_config['enable_smime'] ) && $wpes_config['enable_smime'] ); ?>
									type="checkbox"
									name="settings[enable_smime]"
									value="1"
									id="enable-smime"/>
							</div>

							<div class="wpes-form-item on-enable-smime">
								<label for="certfolder">
									<?php print wp_kses_post( __( 'S/MIME Certificate/Private-Key path', 'wpes' ) ); ?>
								</label>
								<input
									type="text"
									name="settings[certfolder]"
									value="<?php print esc_attr( $wpes_config['certfolder'] ); ?>"
									id="certfolder"/>
							</div>

							<?php
							if ( Plugin::path_is_in_web_root( $wpes_config['certificate_folder'] ) ) {
								?>
								<div class="wpes-notice--error on-enable-smime">
									<strong class="title">
										<?php print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s</code> to prevent stealing your identity.', 'wpes' ), Plugin::suggested_safe_path_for( '.smime' ) ) ); ?>
									</strong>
								</div>
								<?php
							}
							?>

							<?php
							if ( isset( $wpes_config['certfolder'] ) ) {
								$wpes_smime_certificate_folder = $wpes_config['certificate_folder'];
								if ( is_dir( $wpes_smime_certificate_folder ) ) {
									$wpes_smime_identities = Plugin::list_smime_identities();
									$wpes_smime_identities = array_keys( $wpes_smime_identities );
									?>
								<?php } else { ?>
									<div class="wpes-notice--error on-enable-smime">
										<strong class="title">
											<?php
											print wp_kses_post( sprintf( __( 'Set folder <code>%s</code> not found.', 'wpes' ), $wpes_config['certfolder'] ) );
											if ( $wpes_smime_certificate_folder !== $wpes_config['certfolder'] ) {
												print ' ' . wp_kses_post( sprintf( __( 'Expanded path: <code>%s</code>', 'wpes' ), $wpes_smime_certificate_folder ) );
											}
											print ' ' . wp_kses_post( sprintf( __( 'Evaluated path: <code>%s</code>', 'wpes' ), realpath( $wpes_smime_certificate_folder ) ) );
											?>
										</strong>
									</div>
								<?php } ?>
							<?php } ?>

							<div class="wpes-notice--info on-enable-smime">
								<p>
									<?php
									print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against the document-root of your website.', 'wpes' ) ) . '<br />';
									print wp_kses_post( __( 'The file-naming convention is', 'wpes' ) ) . ':<br />';
									?>
								</p>

								<table class="wpes-info-table">
									<tr>
										<th>
											<?php print wp_kses_post( __( 'certificate', 'wpes' ) ); ?>
										</th>
										<td>
											<code>e-mail@addre.ss.crt</code>
										</td>
									</tr>
									<tr>
										<th>
											<?php print wp_kses_post( __( 'private key', 'wpes' ) ); ?>
										</th>
										<td>
											<code>e-mail@addre.ss.key</code>
										</td>
									</tr>
									<tr>
										<th>
											<?php print wp_kses_post( __( '(optional) passphrase', 'wpes' ) ); ?>
										</th>
										<td>
											<code>e-mail@addre.ss.pass</code>
										</td>
									</tr>
								</table>

								<?php if ( isset( $wpes_config['certfolder'] ) ) { ?>
									<?php if ( $wpes_smime_identities ) { ?>
										<div class="wpes-notice--info on-enable-smime">
											<p>
												<?php print wp_kses_post( sprintf( __( 'Found S/MIME identities for the following senders: <code>%s</code>', 'wpes' ), implode( '</code>, <code>', $wpes_smime_identities ) ) ); ?>
											</p>
										</div>
									<?php } ?>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			<?php } else { ?>
				<input type="hidden" name="settings[enable_smime]" value="0"/>
			<?php } ?>

			<div id="digital-dkim" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'Digital E-mail Signing (DKIM)', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-form">
						<div class="wpes-form-item">
							<label for="enable-dkim">
								<?php print wp_kses_post( __( 'Sign e-mails with DKIM certificate', 'wpes' ) ); ?>
							</label>
							<input
								<?php checked( isset( $wpes_config['enable_dkim'] ) && $wpes_config['enable_dkim'] ); ?>
								type="checkbox"
								name="settings[enable_dkim]"
								value="1"
								id="enable-dkim"/>
						</div>
						<div class="wpes-form-item on-enable-dkim">
							<label for="dkimfolder">
								<?php print wp_kses_post( __( 'DKIM Certificate/Private-Key path', 'wpes' ) ); ?>
							</label>
							<input
								type="text"
								name="settings[dkimfolder]"
								value="<?php print esc_attr( $wpes_config['dkimfolder'] ); ?>"
								id="dkimfolder"/>
						</div>

						<?php if ( Plugin::path_is_in_web_root( $wpes_config['dkim_certificate_folder'] ) ) { ?>
							<div class="wpes-notice--error on-enable-dkim">
								<strong class="title">
									<?php print wp_kses_post( sprintf( __( 'It is highly advised to pick a folder path <u>outside</u> your website, for example: <code>%s</code> to prevent stealing your identity.', 'wpes' ), Plugin::suggested_safe_path_for( '.dkim' ) ) ); ?>
								</strong>
							</div>
						<?php } ?>

						<?php
						if ( isset( $wpes_config['dkimfolder'] ) ) {
							$wpes_dkim_certificate_folder = $wpes_config['dkim_certificate_folder'];
							if ( is_dir( $wpes_dkim_certificate_folder ) ) {
								$wpes_dkim_identities = Plugin::list_dkim_identities();
								$wpes_dkim_identities = array_keys( $wpes_dkim_identities );
							} else {
								?>
								<div class="wpes-notice--error on-enable-dkim">
									<strong class="title">
										<?php
										print wp_kses_post( sprintf( __( 'Set folder <code>%s</code> not found.', 'wpes' ), $wpes_config['dkimfolder'] ) );
										if ( $wpes_dkim_certificate_folder !== $wpes_config['dkimfolder'] ) {
											print ' ' . wp_kses_post( sprintf( __( 'Expanded path: <code>%s</code>', 'wpes' ), $wpes_dkim_certificate_folder ) );
										}
										print ' ' . wp_kses_post( sprintf( __( 'Evaluated path: <code>%s</code>', 'wpes' ), realpath( $wpes_dkim_certificate_folder ) ) );
										?>
									</strong>
								</div>
								<?php
							}
						}
						?>

						<div class="wpes-notice--info on-enable-dkim">
							<p>
								<?php print wp_kses_post( __( 'You can also type a relative path (any path not starting with a / is a relative path), this will be evaluated against ABSPATH (the root of your WordPress).', 'wpes' ) ); ?>
								<br/>
								<?php print wp_kses_post( __( 'The file-naming convention is', 'wpes' ) ); ?>
							</p>
							<table class="wpes-info-table">
								<tr>
									<th>
										<?php print wp_kses_post( __( 'certificate', 'wpes' ) ); ?>
									</th>
									<td>
										<code>domain.tld.crt</code>
									</td>
								</tr>
								<tr>
									<th>
										<?php print wp_kses_post( __( 'private key', 'wpes' ) ); ?>
									</th>
									<td>
										<code>domain.tld.key</code>
									</td>
								</tr>
								<tr>
									<th>
										<?php print wp_kses_post( __( 'DKIM Selector', 'wpes' ) ); ?>
									</th>
									<td>
										<code>domain.tld.selector</code>
									</td>
								</tr>
								<tr>
									<th>
										<?php print wp_kses_post( __( '(optional) passphrase', 'wpes' ) ); ?>
									</th>
									<td>
										<code>domain.tld.pass</code>
									</td>
								</tr>
							</table>

							<strong class="title">
								<?php print wp_kses_post( __( 'To generate DKIM keys, use', 'wpes' ) ); ?>
							</strong>

							<?php
							print wp_kses_post( '<code>openssl genrsa -aes256 -passout pass:"' . _x( 'YOUR-PASSWORD', 'A sample password', 'wpes' ) . '" -out domain.tld.key 2048</code>' );
							print wp_kses_post( '<code>openssl rsa -in domain.tld.key -pubout > domain.tld.crt</code>' );
							print wp_kses_post( '<code>echo "' . _x( 'YOUR-PASSWORD', 'A sample password', 'wpes' ) . '" > domain.tld.pass</code>' );
							print wp_kses_post( '<code>echo "' . _x( 'DKIM-SELECTOR-FOR-THIS-KEY', 'A sample DKIM selector', 'wpes' ) . '" > domain.tld.selector</code>' );
							print wp_kses_post( __( 'Upload these files to the specified path on the server and again; this should not be publicly queriable!!!', 'wpes' ) );
							?>

							<strong class="title">
								<?php esc_html_e( 'Finally, register the domain key in the DNS', 'wpes' ); ?>
							</strong>

							<?php print wp_kses_post( '<code>' . _x( 'DKIM-SELECTOR-FOR-THIS-KEY', 'A sample DKIM selector', 'wpes' ) . '._domainkey.domain.tld. IN TXT "v=DKIM1; k=rsa; p=' . _x( 'CONTENT-OF', 'A tag that tells the user to get the content of a file', 'wpes' ) . '-domain.tld.crt"</code>' ); ?>

							<?php
							// translators: %1$s and %2$s are sample content lines to be removed from the key.
							print esc_html( sprintf( __( 'Remove the lines "%1$s" and "%2$s" and place the rest of the content on a single line.', 'wpes' ), '-----BEGIN PUBLIC KEY-----', '-----END PUBLIC KEY-----' ) );
							?>

							<p>
								<?php
								// translators: %s: a URL: to a testing site.
								print wp_kses_post( sprintf( __( 'Test your settings with <a href="%s" target="_blank">DMARC Analyser</a> (unaffiliated)', 'wpes' ), esc_attr( 'https://www.dmarcanalyzer.com/dkim/dkim-check/' ) ) );
								?>
							</p>
						</div>
						<?php
						if ( isset( $wpes_config['dkimfolder'] ) && $wpes_dkim_identities ) {
							?>
							<div class="wpes-notice--info on-enable-dkim">
								<p>
									<?php
									// translators: %s: a list of domains.
									print wp_kses_post( sprintf( __( 'Found DKIM certificates for the following sender-domains: %s', 'wpes' ), '<code>' . implode( '</code>, <code>', $wpes_dkim_identities ) . '</code>' ) );
									?>
								</p>
							</div>
							<?php
						}
						?>
					</div>
				</div>
			</div>

			<div id="email-styling-filters" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( __( 'E-mail styling, and filters for HTML head/body', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<div class="wpes-notice--info on-smtp-is_html">
						<p>
							<?php print wp_kses_post( __( 'You can use WordPress filters to augment the HEAD and BODY sections of the HTML e-mail.', 'wpes' ) ); ?>
						</p>
					</div>

					<div class="wpes-notice--info not-smtp-is_html">
						<p>
							<?php print wp_kses_post( __( 'You can use WordPress filters to change the e-mail.', 'wpes' ) ); ?>
						</p>
					</div>

					<table class="wpes-info-table">
						<tr>
							<th><?php esc_html_e( 'Purpose', 'wpes' ); ?></th>
							<th><?php esc_html_e( 'WordPress filter', 'wpes' ); ?></th>
							<th colspan="2"><?php esc_html_e( 'Parameters', 'wpes' ); ?></th class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'Plugin defaults', 'wpes' ); ?></td>
							<td><code>wpes_defaults</code></td>
							<td colspan="2"><code>array $defaults</code></td class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'Plugin settings', 'wpes' ); ?></td>
							<td><code>wpes_settings</code></td>
							<td colspan="2"><code>array $settings</code></td class=last>
						</tr>
						<tr>
							<td><?php esc_html_e( 'E-mail subject', 'wpes' ); ?></td>
							<td><code>wpes_subject</code></td>
							<td colspan="2"><code>string $subject</code>, <code>PHPMailer $mailer</code></td class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'E-mail <head>', 'wpes' ); ?></td>
							<td><code>wpes_head</code></td>
							<td colspan="2"><code>string $head_content</code>, <code>PHPMailer $mailer</code>
							</td class=last>
						</tr>
						<tr class="on-smtp-is_html">
							<td><?php esc_html_e( 'E-mail <body>', 'wpes' ); ?></td>
							<td><code>wpes_body</code></td>
							<td colspan="2"><code>string $body_content</code>, <code>PHPMailer $mailer</code>
							</td class=last>
						</tr>
						<tr class="not-smtp-is_html">
							<td colspan="4" class="last">
								<?php print wp_kses_post( __( 'Turn on HTML e-mail to enable e-mail styling.', 'wpes' ) ); ?>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div id="email-preview" class="postbox">
				<div class="postbox-header">
					<h2>
						<?php print wp_kses_post( Plugin::get_config()['is_html'] ? __( 'Example E-mail (actual HTML) - with your filters applied', 'wpes' ) : __( 'Example E-mail', 'wpes' ) ); ?>
					</h2>
				</div>
				<div class="inside">
					<iframe
						class="email-preview"
						src="<?php print esc_attr( add_query_arg( 'iframe', 'content' ) ); ?>"></iframe>
				</div>
			</div>
		</div>
	</form>


</div>
