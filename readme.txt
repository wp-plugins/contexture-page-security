=== Page Security by Contexture ===
Contributors: Contexture Intl, Matt VanAndel, Jerrol Krause
Donate link: http://www.contextureintl.com/
Tags: security, permissions, users, groups, page, post, members
Requires at least: 3.0.0
Tested up to: 3.0.0
Stable tag: 1.0.2

Allows admins to restrict access to posts, pages, or entire sections of a site to approved user groups.

== Description ==

Page Security by Contexture (PSC) adds some much-needed "page security" to WordPress. With Contexture Page Security you can now limit access
to posts, pages, or entire sections of your website.

Easily create "Members-only" sections of your site with PSC' built-in "Registered Users" smart group - or create your own custom user groups
to limit access only to users that you want. Any security restrictions you add are automatically inherited by sub-pages, enabling you to quickly
secure entire sections of your site with plenty of flexibility.

PSC also applies your security settings to menu links, RSS feeds, and blog post excerpts as well, ensuring your secure content doesn't accidentally
creep onto your site because of an overlooked WordPress option.

PSC is simple and easy to use. Visible changes made to the Dashboard are:

1. Two new options are located under the Users category. This includes "Groups" and "Add Group".
1. A "Restrict Access" sidebar is available when editing any page or post.
1. A "Group Membership" overview is added to the "Edit User" pages.

Additional features:

1. The "Restrict Access" sidebar is AJAX-loaded, so any changes you make to security are saved immediately! You do not need to click
the "Update" button to save changes to the page's security.
1. Any page or post can be easily made "admin only". When editing a page, check the "Protect this page it's descendants" checkbox but don't add any groups. By default,
all protected pages can be seen by admins - but if you don't add any groups, they will be invisible to all non-admin users.

While we believe this plugin is secure, we make no warranty about it's effectiveness during real-world use. USE AT YOUR OWN RISK!

== Installation ==

1. Upload `contexture-page-security` folder to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You're finished! Start using Contexture Page Security.

== Screenshots ==

1. screenshot-1.png
1. screenshot-2.png

== Other Notes ==

= PHP5 is Required =

PSC requires PHP5 to work. If you receive an error message while activating/installing this plugin, you may need to upgrade your PHP installation. If you are using a
web hosting service, simply contact your hosting provider about updating your default version of PHP (it's usually as simple as checking a box somewhere on your hosting dashboard).

== Changelog ==

= 1.0.2 =
* Activation now enforces PHP requirement

= 1.0.1 =
* Fixed a bug where group count was incorrectly including smart groups
* Updated readme to reflect PHP5 requirement

= 1.0.0 =
* Added a system-owned "Registered Users" smart group so admins can easily limit access to logged-in users only
* Fixed a webkit-only bug where "Restrict Access" sidebar was not being properly updated when changes were made
* Groups can now be deleted
* Lots of minor usability improvements

= 0.8.3 =
* Usability improvement: Restrict Access sidebar now lets you know that your security changes are saved

= 0.8.2 =
* Fixed a bug introduced in 0.8.1 that could cause post page to display incorrectly
* Protected blog posts will no longer appear in RSS feeds

= 0.8.1 =
* Added menu filtering to WP3 custom menus. Minor bug fixes.

= 0.8.0 =
* Users will no longer see menu links to pages they are restricted from (default menus only).

= 0.7.1 =
* Minor bug fixes.

= 0.7.0 =
* Basic security features are in place. First releasable version.

