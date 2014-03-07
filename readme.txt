=== Sender ===
Contributors: bestwebsoft
Donate link: https://www.2checkout.com/checkout/purchase?sid=1430388&quantity=10&product_id=13
Tags: mail, send mail, email
Requires at least: 3.1
Tested up to: 3.8.1
Stable tag: 0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin sends mail to registered users. 

== Description ==

You can send mails to all users or to certain categories of users.
To send letters, you can use the php functions such as sending emails, wordpress functions, or send an email through the SMTP server.
You can also do a bulk mailing for a certain period of time.

<a href="http://wordpress.org/plugins/sender/faq/" target="_blank">FAQ</a>

<a href="http://support.bestwebsoft.com" target="_blank">Support</a>

= Recommended Plugins =

The author of the Sender also recommends the following plugins:

* <a href="http://wordpress.org/plugins/updater/">Updater</a> - This plugin updates WordPress core and the plugins to the recent versions. You can also use the auto mode or manual mode for updating and set email notifications.
There is also a premium version of the plugin <a href="http://bestwebsoft.com/plugin/updater-pro/?k=94d3b6d567ade1cd7a988b80874cdee7">Updater Pro</a> with more useful features available. It can make backup of all your files and database before updating. Also it can forbid some plugins or WordPress Core update.

= Translate =

* Russian (ru_RU)

If you would like to create your own language pack or update the existing one, you can send <a href="http://codex.wordpress.org/Translating_WordPress" target="_blank">the text of PO and MO files</a> for <a href="http://support.bestwebsoft.com" target="_blank">BestWebSoft</a> and we'll add it to the plugin. You can download the latest version of the program for work with PO and MO files  <a href="http://www.poedit.net/download.php" target="_blank">Poedit</a>.

= Technical support =

Dear users, our plugins are available for free download. If you have any questions or recommendations regarding the functionality of our plugins (existing options, new options, current issues), please feel free to contact us. Please note that we accept requests in English only. All messages in another languages won't be accepted.

If you notice any bugs in the plugins, you can notify us about it and we'll investigate and fix the issue then. Your request should contain URL of the website, issues description and WordPress admin panel credentials.
Moreover we can customize the plugin according to your requirements. It's a paid service (as a rule it costs $40, but the price can vary depending on the amount of the necessary changes and their complexity). Please note that we could also include this or that feature (developed for you) in the next release and share with the other users then. 
We can fix some things for free for the users who provide translation of our plugin into their native language (this should be a new translation of a certain plugin, you can check available translations on the official plugin page).

== Installation ==

1. Upload `sender` folder to the `/wp-content/plugins/` directory
2. Activate the plugin via the 'Plugins' menu in WordPress.
3. Plugin settings are located in 'BWS Plugins', 'Sender'.

== Frequently Asked Questions ==

= How to use the plugin? =

1. Install and activate the plugin.
2. Go to the plugin settings page ( on dashboard "BWS Plugins" -> "Sender" ) and edit the necessary options.
3. Go to the edit mail page ( on Dashboard "Sender" -> "Sender" ) and then:
	- select the users ( by roles ) you want to send a mail to
	- enter the subject and text of the mail
	- click the "Send" button
4. You can view a report about mailout on "Sender" -> "Reports" page

= How can i make sure that the subscriber has read my letter? =

This function will be added in the stable version of plugin.

= Why are my letters sent so long =

For sending letters in plugin used wp_cron - Wordpress function for periodic execution of any planned actions. This function depends on the traffic of your site: the more visitors, the faster the letters will be sent.

= Why am I unable to send letters to all users at the same time? =

1. Simultaneous sending of a large number of messages can slow down your site. 
2. Your site can be identified as a source of spamming, which can lead to blocking of your website or hosting-account.

= I have some problems with the plugin's work. What Information should I provide to receive proper support? =

Please make sure that the problem hasn't been discussed yet on our forum (<a href="http://support.bestwebsoft.com" target="_blank">http://support.bestwebsoft.com</a>). If no, please provide the following data along with your problem's description:
1. the link to the page where the problem occurs
2. the name of the plugin and its version. If you are using a pro version - your order number.
3. the version of your WordPress installation
4. copy and paste into the message your system status report. Please read more here: <a href="https://docs.google.com/document/d/1Wi2X8RdRGXk9kMszQy1xItJrpN0ncXgioH935MaBKtc/edit?pli=1" target="_blank">System Status</a>

== Screenshots ==

1. Plugin`s settings page.
2. Plugin`s "Edit mail" page.
3. Plugin`s "Report" page.
4. Plugin`s "Report" page with subscribers list. 

== Changelog ==

= V0.3 - 06.03.2014 =
* NEW: The possibility to attach a message links in the text to unsubscribe from newsletter ( if Subscriber plugin By BestWebSoft is installed ).
* Update: Optimized functions register and update the plugin settings.

= V0.2 - 20.02.2014 =
* Update: Database tables is renamed. 
* Update: Changed structure of settings page.

= V0.1 =
* NEW: Russian language files were added to the plugin.

== Upgrade Notice ==

= V0.3 =
The possibility to attach a message links in the text to unsubscribe from newsletter ( if Subscriber plugin By BestWebSoft is installed  ). Optimized functions register and update the plugin settings.

= V0.2 =
Database tables is renamed.  Changed structure of settings page.

= V0.1 =
Russian language files were added to the plugin.
