=== SpamReferrerBlock ===
Contributors: dsampaolo
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=ET69EBG2GM7AQ
Requires at least: 3.0
Tested up to: 4.1
Stable tag: trunk
License: GPLv2 or later
Tags: spam, referrer, analytics, statistics, dsampaolo

This plugin prevents spam referrer attacks by filtering your incoming traffic.

== Installation ==
1. Upload all files to a subdirectory of your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Adjust settings in the dedicated section (Settings > Spam Referrer Block)

You're protected !

== Description ==
This plugin uses a blacklist to filter your incoming traffic and block "spam referrer" attacks.

This blacklist has been compiled using the author's website and a few others : during the plugin's
*beta-testing phase*, a few webmasters sent their reports to the author in order to find a suitable list of
domains actually using false referrers to spam (at least) some websites statistics.

= Terms of use =

There aren't any terms, conditions or restrictions to the use of this plugin and/or the Blacklist : feel free to reuse
our work ! If you want to use the SpamReferrerBlock Blacklist for other purposes than this plugin, we simply ask that
you consider linking to this page, to the plugin homepage, or to our blog. That's all :)

= The Webservice =
This plugin uses a Webservice. Please be advised :

* You **will** retrieve information from our server. The "Blacklist" consists of a plain JSON file.
* No need to register or provide personal information.
* Free of charge, forever.
* Caution - We technically **can** access some information while you request the file : your server's IP address and
  server name, and so on. But we (honestly) **DO NOT** use **ANY** information that can be sent to our server during the downloading process, in any way.

= Updates =
The blacklist will be updated as often as possible, we expect to release weekly updates.
You can choose to filter all connexions or only the first of every session.
Webmasters are encouraged to submit spammy URLs to help us improve the blacklist (see FAQ).

= Why this plugin ? =
I wrote a post about spam referrer attacks and how to avoid them on my blog : http://www.didcode.com/code/stop-spam-referrer.html (fr).
Some webmasters asked me (on Twitter) to write this plugin. They were also targeted by "spam referrer attacks" and sent me the originating domains.
I compiled a list and put it in a JSON file, which is called by the plugin.

[Plugin home page](http://www.didcode.com/spam-referrer-block) (fr)

== Frequently Asked Questions ==

= Do I need to create an account, or get an API key, to use this plugin ? =
**NO**. We are open-source minded. No need to register.

= Will the plugin send any information to your server ? =
**NO**. Just to clarify : in order for a plugin to pull down a JSON containing the domains to filter, it's
actually sending data to the server to get the file.  But that's all, we don't save or process anything.
Your privacy is important to us.

= How can I contribute to the Blacklist ? =
Simply send an email to the author ( didier@didcode.com ) with the list of domains that you wish to add. After a manuel
review, the domains will be added to the blacklist.

= Do you accept features request ? =
Of course ! Simply send an email to the author ( didier@didcode.com ) - explain what you want, why you want it, then cross your
fingers. Good things DO happen !

== Changelog ==
= 1.0 =
*Release Date : 23 december 2014*

Initial version.

= 0.5 =
*Release Date : 3 november 2014*

Beta version. Beta-testers are encouraged to send their lists to the author.