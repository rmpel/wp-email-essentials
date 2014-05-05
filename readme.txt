=== WP Email Essentials ===
Contributors: clearsite
Donate link: http://clearsite.nl/wordpress-development/
Tags: email
Requires at least: 3.6.1
Tested up to: 3.8.1
Stable tag: 1.0.3
License: GPL2

WP Email Essentials helps you send better emails from your WordPress blog.

== Description ==

WP Email Essentials helps you send better emails from your WordPress blog.

* Allows you to set a (default) From name. Instead of 'WordPress' you can set your own name.
* Allows you to set a (default) From email, instead of 'no-reply@blogdomain.com'.
* Both can be overridden when wp_mail is called with a valid From: header. If From: is just an email addres, only the email address is overridden, if From: is an RFC2822 email addres+name, both will be.

* Allows you to send all emails through an STMP server, my favorite is mandrillapp.com.
* Allows you to convert emails to HTML. You can hook filters wpes_head and wpes_body to add to the head and body section of the HTML email.
* Optionally an Alt body is derived from the body-part of the email (this plain text alternative makes your emails less spammy)

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
