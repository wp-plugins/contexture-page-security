=== Page Security by Contexture ===
Contributors: Contexture International, Matt VanAndel
Donate link: http://www.contextureintl.com/
Tags: security, permissions, users, groups, page, post, members, restricted
Requires at least: 3.0.0
Tested up to: 3.0.2
Stable tag: 1.2.4

Allows admins to create user groups and set access restrictions for any post, page or section.

== Description ==

Page Security by Contexture International adds some much-needed user groups and permissions to WordPress! Now you can easily limit access to posts, pages, or 
entire sections of your website. Create an intranet or a members-only area with just a few clicks. You can even create tiered sections with multiple levels of 
security. Page Security by Contexture (PSC) lets YOU decide which users can access which content.

PSC is designed to integrate seamlessly and intuitively with WordPress. 

Features:

1. Fully AJAX-loaded! Any changes you make to security are saved immediately!
1. Use the "Registered Users" smart group to create special "registered users only" sections!
1. Contextual help is available for every PSC feature (via WordPress's 'Help' tab)!
1. Use simple, well-documented theme functions to easily automate your group memberships (You could even create an automatic subscription system)!
1. Frequent updates and improvements!

Notice:
While we believe this plugin is secure, we make no warranty about it's effectiveness during real-world use. Use at your own risk!

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

= Can I help translate PSC into my language? =

Absolutely! PO files are now included with each PSC download. You can use a WordPress plugin like "CodeStyling Localization" or a program like "Poedit" to easily create language-specific translations. If you'd like us to include your translation in the official release, simply email it to opensource@contextureintl.com!

= Is there an easy way to make some sections admin-only? =

Yes! This is particularly handy if you're working on a new section of your website but you aren't quite ready to share it with the world. From the page's edit screen, simply find the "Restrict Access" sidebar and check "Protect this page and it's descendants". That's it! Even if you don't assign any groups, anyone who's logged in as an admin will still have full access to that page.

== Theme Functions ==

Some developers may find it useful to perform programmatic group-management tasks. For instance, have your website automatically add a user to a group under some circumstances. The following functions and documentation should help.

= psc_add_user_to_group($user_id, $group_id) =

This can be called from within a theme file to add a specific user to a specific group. It requires two parameters, a user id and a group id. The current users id can be acquired by declaring the $current_user global, then $current_user->ID. You can use psc_get_groups() or your blog's Admin > Users > Groups screen to determine an appropriate group id.

= psc_remove_user_from_group($user_id, $group_id) =

This can be called from within a theme file to remove a user from a group. Like psc_add_user_to_group(), it requires two parameters, a user id and a group id.

= psc_get_groups($user_id) =

This function can be used to generate an associative array of groups. If you specify a user id, only groups that a user is a member of will be returned. If no parameter is provided, it will return all groups.

= psc_has_protection($post_id) =

This function can be used to determine if a specific page or post is protected. This includes any inherited restrictions. It requires one parameter, the id of the page or post to check.


== Changelog ==

= 1.3.0 =
* New feature: Friendly theme functions! Now you can easily check permissions, get group lists, or add or remove users from groups programmatically!
* New feature: You can now view any group to easily see what pages it is attached to!
* New feature: The contextual help tab now includes documentation for all PSC features!
* New feature: Subscriptions! You can now create time-limited/expiring group memberships!

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

