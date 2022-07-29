=== WP Email Essentials ===
Contributors: rmpel, clearsite
Donate link: https://remonpel.nl/
Tags: email
Requires at least: 3.6.1
Tested up to: 6.0.1
Stable tag: 4.1.0
License: GPL2

WP Email Essentials helps you send better emails from your WordPress blog.

== Description ==

WP Email Essentials helps you send better emails from your WordPress blog.

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

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload \`the wp-email-essentials\` folder to the \`/wp-content/plugins/\` directory or use the plugin manager within your WordPress blog
1. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= Why did you create this plugin =

Because I needed some of these fixes is some of my sites and thought it a good idea to bundle all the fixes into a single plugin.

= I have some other fixes I'd like to include =

You can help me develop this plugin further by emailing me a diff-file. You can create diff files by

* keeping an original copy of the plugin (example: wp-email-essentials-original) and do diff -ru ../wp-email-essentials-original/ ./ > my-changes.diff from within the plugin directory
* using the WordPress Plugin SVN repository to checkout the trunk or tag and do svn diff > my-changes.diff
* clone the project on bitbucket.org and email me a patch of your changes

( I will not guarantee your fixes will be included or will be included in the fashion you suggest )

= I love this plugin, how can I make a donation =

At this time the PayPal donation link is missing from our website, we're working on it.

== Changelog ==

1.0.2 Now also correct the Envelop-From header - if your server accepts it.

1.0.0 Initial upload

== Upgrade Notice ==

Nothing to report here

== Screenshots ==

Screenshots not yet available. It's just a simple admin form.
