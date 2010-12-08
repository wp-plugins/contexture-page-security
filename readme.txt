=== Page Security by Contexture ===
Contributors: Contexture International, Matt VanAndel
Donate link: http://www.contextureintl.com/
Tags: security, permissions, users, groups, page, post, members, restricted
Requires at least: 3.0.0
Tested up to: 3.0.2
Stable tag: 1.2.4

Allows admins to restrict access to posts, pages, or entire sections of a site to approved user groups.

== Description ==

Page Security by Contexture International adds some much-needed "page security" to WordPress. With Contexture Page Security you can now limit access
to posts, pages, or entire sections of your website.

Easily create "Members-only" sections of your site with Page Security's built-in "Registered Users" smart group - or create your own custom user groups
to limit access only to the users that you want. Any security restrictions you add are automatically inherited by sub-pages, enabling you to quickly
secure entire sections of your site with extraordinary flexibility.

Page Security also applies your security settings to menu links, RSS feeds, and blog post excerpts as well, ensuring your secure content doesn't accidentally
creep onto your site because of an overlooked WordPress option.

Page Security is simple, easy to use, and integrates seamlessly with WordPress. Visible changes made to the Dashboard are:

1. Two new options are located under the Users category. This includes "Groups" and "Add Group".
1. A "Restrict Access" sidebar is available when editing any page or post.
1. A "Group Membership" overview is added to the "Edit User" pages.
1. See at a glance what's protected from your admin's pages and posts screens.

Additional features:

1. The "Restrict Access" sidebar is AJAX-loaded, so any changes you make to security are saved immediately! There's no need to click
the "Update" button to save changes to a page's security.
1. Any page or post can be easily made "admin only". When editing a page, check the "Protect this page it's descendants" checkbox but don't add any groups. By default,
all protected pages can be seen by admins - but if you don't add any groups, they will be invisible to non-admin users.

While we believe this plugin is secure, we make no warranty about it's effectiveness during real-world use. USE AT YOUR OWN RISK!

== Installation ==

1. Upload `contexture-page-security` folder to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. You're finished! Start using Contexture Page Security.

== Screenshots ==

1. screenshot-1.png
1. screenshot-2.png
1. screenshot-3.png

== Frequently Asked Questions ==

= I get an error about PHP5 when I activate your plugin. What gives? =

Page Security requires PHP5 to work. If you receive an error message while activating/installing this plugin, you may need to upgrade your PHP installation (you are probably still running PHP4).
If you are using a web hosting service, simply contact your hosting provider about updating your default version of PHP (it's usually as simple as checking a box somewhere on your
hosting dashboard).

= Does Page Security work with WordPress 2.9 or earlier? =

PSC has only been tested with WordPress 3.0 and higher. It's possible that it may work on earlier versions, but we don't officially support this.

== Theme Functions ==

Some developers may find it useful to perform programmatic group-management tasks. For instance, have the website automatically add a user to a group. The following documentation should help.

= psc_add_user_to_group($user_id, $group_id) =

This can be called from within a theme file to add a user to a group. It requires two parameters, a userid and a group id. The current users id can be acquired by declaring the $current_user global, then $current_user->ID. You can use psc_get_groups() or your blog's Admin > Users > Groups screen to determine appropriate group ids.

= psc_remove_user_from_group($user_id, $group_id) =

This can be called from within a theme file to remove a user from a group. Like psc_add_user_to_group(), it requires two parameters, a user id and a group id.

= psc_get_groups($user_id) =

This function can be used to generate an associative array of groups. If you specify a user id, only groups that a user is a member of will be returned. If no parameter is provided, it will return all groups.

= psc_has_protection($post_id) =

This function can be used to determine if a specific page or post is protected. This includes any inherited restrictions. It requires one parameter, the id of the page or post to check.


== Changelog ==

= 1.3.0 =
* New feature: Friendly theme functions! Now you can easily check permissions, get group lists, or add or remove users from groups programmatically!
* New feature: The contextual help tab now includes documentation for all PSC features!
* New feature: You can now create time-limited group memberships!

= 1.2.4 =
* Fixed a redirect bug reported by the community.

= 1.2.3 =
* Bug fixes

= 1.2.1 =
* New feature: To improve usability, the "Restrict Access" sidebar now appears on "New..." screens.

= 1.2 =

* New feature: All-new settings screen with lots of new customization options.
* New feature: You can now use any page as a custom "access denied" screen
* New feature: You can now control whether menus or RSS feeds get filtered
* LOTS of new usability improvements
* Bug fixes!

= 1.1 =
* New feature: Easily manage a user's group memberships from the Edit User page
* New feature: Admins can now customize their default access denied messages!
* New feature: Added [groups_attached] and [groups_required] shortcodes, which print permissions requirements for the current page/post (great troubleshooting tool for folks with complex permissions)
* New feature: The "Restrict Access" sidebar now lists inherited permissions for easy management of security
* Lots and lots of minor usability improvements
* Fixed a bug that prevented the plugin from activating with certain PHP configurations
* Numerous bug fixes (and hopefully no new ones)

= 1.0.4 =
* Fixed a restricted access message that pointed to incorrect login page url

= 1.0.3 =
* Fixed a bug with the PHP version check.

= 1.0.2 =
* Activation now enforces PHP requirement

= 1.0.1 =
* Fixed a bug where group count was incorrectly including smart groups
* Updated readme to reflect PHP5 requirement

= 1.0 =
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

