# WordPress Mail Essentials
A plugin to make WordPress outgoing emails better.

# BREAKING CHANGES:

From version 4.1.0 on, the plugin is fully WP Coding standards compliant and fully Namespaced.
The side effect is that While versions 4.0.0 - 4.0.2 are backwards compatible; version 4.1.0 is NOT -- IF -- you access the WP_Email_Essentials methods directly.

Please TEST your website with the latest version of WPES locally or on a test-server _BEFORE_ you update your live website.

# Introduction:

The main purpose is to vastly reduce the chances of your emails being marked as spam or being rejected.

# This plugin offers your WP-site...
* A good From name,
* A good From email address,
* The correct envelope-from e-mail address,
* Reformatting as HTML, if needed, with proper plain text alternative,
* process shortcodes,
* UTF8-recoding,
* filters for adding CSS, header, footer and body template,
* convert CSS to inline styles for better support in tools like GMail, Outlook Online, Hotmail etc.),
* SMTP configuration,
* Send emails with multiple addressees as separate emails (less spammy),
* S/MIME signing,
* DKIM signing,
* Altering destination of certain emails normally addressed to the site-admin,
* Keeping a history of outgoing emails with their results (for debugging, history is cleared on deactivation of this function),
* When history is enabled, add a tracker to track correct receipt of emails *1,

# Important note:
This tool is created for people that know what to do and why they do it. If you don't know what a feature does, ask for help :)

*1) Under GDPR, storing and tracking emails is prohibited, the history feature is meant for investigative purposes only!

# WordPress Filters:

`wpes_settings`

Parameters:
- (array) `$settings` The current settings of the plugin.

Expected return:
- (array) The new settings of the plugin.

---

`wpes_defaults`

Parameters:
- (array) `$defaults` The current default settings of the plugin.

Expected return:
- (array) The new default settings of the plugin.

---

`wpes_body`

Parameters:
- (string) `$should_be_html` A text that should be html, but might not yet be, your job to make a nice HTML body.
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) A text that should be html.

---

`wpes_head`

Parameters:
- (string) `$the_head_section` HTML that is the HEAD section of the HTML email.
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) The altered HEAD section of the HTML email.

---

`wpes_css`

Parameters:
- (string) `$the_css` CSS for the email (empty by default).
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) The (altered) CSS.

---

`wpes_subject`

Parameters:
- (string) `$the_subject` Subject for the email.
- (PHPMailer) `$mailer` The PHPMailer object (by reference).

Expected return:
- (string) The (altered) Subject.

# Changelog:

5.2.4: Code improvements for PHP 8.0 compatibility

5.2.3: Added filter to disable the HTML envelope set by GravityForms, so we can use our own.
