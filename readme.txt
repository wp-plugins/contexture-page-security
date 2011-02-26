=== Page Security by Contexture ===
Contributors: Contexture International, Matt VanAndel
Donate link: http://www.contextureintl.com/
Tags: security, permissions, users, groups, page, post, members, restricted
Requires at least: 3.0.0
Tested up to: 3.1
Stable tag: 1.3.4

Allows admins to create user groups and set access restrictions for any post, page or section.

== Description ==

Page Security by Contexture International (PSC) lets YOU decide which users can access which content. PSC adds much-needed user groups and permissions features to WordPress!

Create groups to organize your users how YOU see fit. Use groups to easily limit access to posts, pages, or entire sections of your website. Create an intranet or a members-only
area with just a few clicks - or build a subscription based system with automatically expiring memberships. You can even create multiple levels of security for granualar protection
of any sub-section on your site.

PSC is created to be simple, yet powerful - and is designed to integrate seamlessly and intuitively with WordPress. If you know how to use WordPress, you know how to use PSC.

Features:

1. Subscription support! Assign an expiration date to any membership!
1. Custom "Access Denied" pages!
1. Fully AJAX-loaded! Any changes you make to security are saved immediately!
1. Use the "Registered Users" smart group to create special "registered users only" sections!
1. Contextual help is available for every PSC feature (via WordPress's 'Help' tab)!
1. Use simple, well-documented theme functions to easily automate your group memberships (You could even create an automatic subscription system)!
1. Frequent updates and improvements!

Notice:
While we believe this plugin is secure, we make no warranty about it's effectiveness during real-world use. Use at your own risk!

== Installation ==

= Via WordPress Admin =

1. From your sites admin, go to Plugins > Add New
1. In the search box, type 'Page Security by Contexture' and press enter
1. Locate the entry for 'Page Security by Contexture' (there should be only one) and click the 'Install' link
1. When installation is finished, click the 'Activate' link

= Manual Install =

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

= How does "Protect Entire Site" Work? =

We added this option specifically for those who want to create private, intranet-like sites. When this option is enabled, only groups that you approve will be able to access any part of the website. Anonymous users are automatically denied access, while users who are logged in have their group permissions checked. 

It's important to remember that ALL OTHER SECURITY RESTRICTIONS are still applied. For instance, if I am in a group with site access, but try to access protected content that isn't attached to my group, I will STILL be denied access.

This allows you create an intranet-style site while keeping multiple levels of security for the site content.

= Can I help translate PSC into my language? =

Absolutely! PO files are now included with each PSC download. You can use a WordPress plugin like "CodeStyling Localization" or a program like "Poedit" to easily create language-specific translations. If you'd like us to include your translation in the official release, simply email it to opensource@contextureintl.com!

= Is there an easy way to make some sections admin-only? =

Yes! This is particularly handy if you're working on a new section of your website but you aren't quite ready to share it with the world. From the page's edit screen, simply find the "Restrict Access" sidebar and check "Protect this page and it's descendants". That's it! Even if you don't assign any groups, anyone who's logged in as an admin will still have full access to that page.

= I found a bug or need a feature, what do I do? =

Please visit our official support page at http://goo.gl/Cw7v7 and we'd be glad to help you out.

== Theme Functions ==

Some developers may find it useful to perform programmatic group-management tasks. For instance, have your website automatically add a user to a group under some circumstances. The following functions and documentation should help (for more detail, see PSC's views/theme-functions.php file)).

= psc_add_user_to_group($user_id, $group_id, $expires) =

This can be used to add a specified user to a specified group. It requires two parameters, a user id and a group id (The current users id can be acquired by declaring the $current_user global, then $current_user->ID). You can use psc_get_groups() or your blog's Admin > Users > Groups screen to determine an appropriate group id. $expires is optional, but takes either a date string (formatted YYYY-MM-DD) or NULL. If left empty, users membership will never expire.

= psc_update_user_membership($user_id,$group_id,$expires) =

This function can be used to update a users membership details (usually expiration date). $expires is optional and can be either NULL or a string-formatted date (YYYY-MM-DD).

= psc_remove_user_from_group($user_id, $group_id) =

Use this function to remove a user from a group. Both parameters are required.

= psc_get_groups($user_id) =

This function can be used to generate an associative array of groups (group id=>name). If you specify a user id, only groups that a user is a member of will be returned. If no parameter is provided, it will return all groups.

= psc_has_protection($post_id) =

This function can be used to determine if a specific page or post is protected (including any inherited restrictions). $post_id is optional - if left blank, the function will try to automatically use the post id from the current loop, if available. This function returns true if protected, false if not.


== Changelog ==

= 1.4.0 =
* COMPLETE CODE REWRITE! Code base is now now MUCH more flexible so new features should come more quickly.
* New feature: You can now protect custom post types!
* New feature: You can now display access denied messages without a redirect (content replacement)!
* New feature: You can now protect an ENTIRE SITE with one click. Useful for intranet implementations.
* Updated permissions for all actions to be much more common sense.
* Lots and lots and lots of minor usability improvements.
* MORE FEATURES COMING SOON!

= 1.3.4 =
* Fixed a bug that could cause query strings to become too long when adding users from the Group Edit screen

= 1.3.3 =
* Bug fixes (thanks, Alex)

= 1.3.2 =
* Somewhat remedied an issue that could cause slower sites to "blink" during AD page redirects
* Other minor bug fixes

= 1.3.1 =
* Fixed a bug that could cause search results to return an AD in certain conditions.

= 1.3.0 =
* Merry Christmas! Lots of new features.
* New feature: Subscription support! You can now assign expiration dates to group memberships!
* New feature: New theme-friendly functions! Now you can easily check permissions, get group lists, or add or remove users from groups programmatically!
* New feature: Group screens now show which pages they are assigned to!
* New feature: The contextual help tab now includes documentation for all PSC features!

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

