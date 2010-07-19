=== Contexture Page Security ===
Contributors: Contexture Intl, Matt VanAndel, Jerrol Krause
Donate link: http://www.contextureintl.com/
Tags: security, permissions, users, groups, page, post
Requires at least: 3.0.0
Tested up to: 3.0.0
Stable tag: 0.8.2

Allows admins to restrict access to posts, pages, or entire sections of a site to approved user groups.

== Description ==

Contexture Page Security (CPS) adds some much-needed "page security" to WordPress. With Contexture Page Security you can now limit access
to posts, pages, or entire sections of your website.

Sort users into groups and attach those groups to any page or post. Now, only users in one or more of those groups will be able to
access that page (or any pages beneath it)! You can even create sub-sections that require the current user to be a member of additional
groups (so you can set up a secure section to have separate sub-sections with additional security).

CPS checks security on menu links, RSS feeds, and blog post excerpts as well, so any user who doesn't have the proper permissions won't even see 
the content... this includes blog excerpts on your main blog page.

CPS only effects users who are logged in. For maximum security, all secure content is automatically hidden from unauthenticated users.

CPS is simple and easy to use. Visible changes made to the Dashboard are:

1. Two new options are located under the Users category. This includes "Users > Groups" and "Users > Add Group".
1. A "Restrict Access" sidebar is visible when editing a page or post.
1. A "Group Membership" overview is added to the "Edit User" pages.

Additional features:

1. You can easily make any page or post "admin only". Simply check the "Protect this page it's descendants" checkbox but don't add any groups. By default,
all protected pages can be seen by admins - but if you don't add any groups, they will be invisible to all other users.
1. The "Restrict Access" sidebar on the "edit page" admin pages is AJAX-loaded, so any changes you make to security are saved immediately. You do not need to click
"Update" to save changes to the page's security.

This plugin is still under development and should NOT be relied on to protect mission critical data. We make
no warranty about it's effectiveness during real-world use. USE AT YOUR OWN RISK!

== Installation ==

1. Upload `contexture-page-security` folder to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You're finished! Start using Contexture Page Security.

== Screenshots ==

1. screenshot-1.png
1. screenshot-2.png

== Changelog ==

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

