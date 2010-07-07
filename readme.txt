=== Contexture Page Security ===
Contributors: Contexture Intl., Matt VanAndel
Donate link: http://www.contextureintl.com/
Tags: security, permissions, users, groups, page, post
Requires at least: 3.0.0
Tested up to: 3.0.0
Stable tag: 0.7.1

Allows admins to create user groups and restrict access to sections of the site by group.

== Description ==

Contexture Page Security adds some much-needed "page security" to WordPress, allowing admins to limit or
restrict access to individual pages or entire sections of their website. To do this, admins create "groups"
of users that can be added to pages or posts. Only users that are in at least one of those groups are then
able to access those pages/sections.

For pages, all security permissions are automatically inherited by descendant pages; any groups added to
descendant pages are treated as ADDITIONAL security requirements for the page and it's descendants (and so
on). This allows you to create sub-sections with even more security requirements, for additional flexibility.

You can also restrict blog posts. Any users who do not have access to restricted posts (as defined by you)
will NOT able able to access those posts – nor will excerpts for those posts even appear in the blog lists.

For the most part, Contexture Page Security should be self-explanatory. Visible changes made to the Dashboard are:

1. Two new options are located under Users. Users > Groups and Users > Add Group.
1. A "Restrict Access" sidebar is added to the "edit page" for both pages and posts (this allows you add security to those pages/posts/sections).
1. A "Group Membership" overview is added to each user's Edit User page.

Here are some other technical details to keep in mind:

1. If you enable protection for a page/post/section but don't assign any groups to it (or to a protected ancestor), then NOBODY will be able to access that page (except admins).
1. The sidebar on the "edit page" admin pages are AJAX-loaded, so your changes are made immediately. You do not need to click "Update" to save changes to the page's security.

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

= 0.7.1 =
* Minor bug fixes.

= 0.7.0 =
* Basic security features are in place. First releasable version.

