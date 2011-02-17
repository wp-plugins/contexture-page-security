<?php
/*
Plugin Name: Page Security by Contexture
Plugin URI: http://www.contextureintl.com/open-source-projects/contexture-page-security-for-wordpress/
Description: Allows admins to create user groups and restrict access to sections of the site by group.
Version: 1.4.0
Author: Contexture Intl, Matt VanAndel, Jerrol Krause
Author URI: http://www.contextureintl.com
License: GPL2
*/
/*  Copyright 2010  Contexture Intl.  (email : webteam@contextureintl.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/***************************** SET GLOBALS ************************************/
/**The absolute local path to the plugin*/
define('CTXPSPATH',dirname(__FILE__));
/**The directory path to the plugin*/
define('CTXPSDIR',basename(CTXPSPATH));
/**The URL of the plugin directory*/
define('CTXPSURL',plugin_dir_url(__FILE__));
global $wpdb, $ctxpsdb;

/************************** LOAD WP DEPENDENCIES ******************************/
require_once(ABSPATH . WPINC . '/registration.php');
require_once(ABSPATH . WPINC . '/ms-functions.php');

/**************************** LOAD CORE FILES *********************************/
require_once 'core/model.php';          //Model instance ($ctxpsdb)
require_once 'core/model_queries.php';  //Stored db queries
require_once 'core/helpers.php';        //Common, reusable classes, methods, functions
require_once 'core/components.php';     //Bits that specifically are used for generating bits of views
require_once 'controllers/app_controller.php';//Common, reusable classes, methods, functions
require_once 'core/ajax.php';           //AJAX-specific methods
require_once 'core/routing.php';        //All requests for views are sent through here

/******************************** HOOKS ***************************************/
// Install new tables (on activate)
register_activation_hook(__FILE__,'CTXPS_Queries::plugin_install');
// Remove tables from db (on delete)
register_uninstall_hook(__FILE__,'CTXPS_Queries::plugin_delete');


// Add "Groups" option to "Users" in admin
add_action('admin_menu', 'CTXPS_App::admin_screens_init');
// Add a "Groups" view to a user's user-edit.php page
add_action('edit_user_profile', 'CTXPS_Router::user_groups');
add_action('show_user_profile', 'CTXPS_Router::user_groups');

//Add the security box sidebar to the pages section
add_action('admin_init', 'CTXPS_App::admin_init');

//Load localized language files
add_action('init','CTXPS_App::localize_init');

//Handle Ajax for Edit Page/Post page
add_action('wp_ajax_ctx_ps_add2page','CTXPS_Ajax::add_group_to_page');
add_action('wp_ajax_ctx_ps_removefrompage','CTXPS_Ajax::remove_group_from_page');
add_action('wp_ajax_ctx_ps_security_update','CTXPS_Ajax::update_security');

//Handle Ajax for Edit User page
add_action('wp_ajax_ctxps_add_group_to_user','CTXPS_Ajax::add_group_to_user');
add_action('wp_ajax_ctxps_remove_group_from_user','CTXPS_Ajax::remove_group_from_user');
add_action('wp_ajax_ctxps_update_member','CTXPS_Ajax::update_membership');

//Add basic security to all public "static" pages and posts [highest priority]
add_action('wp','CTXPS_Security::protect_content',1);

//Add basic security to dynamically displayed posts (such as on Blog Posts Page, ie: Home) [highest priority]
add_filter( 'the_posts','CTXPS_Security::filter_loops',1);

//Ensure that menus do not display protected pages (when using default menus only) [highest priority]
add_filter('get_pages','CTXPS_Security::filter_auto_menus',1);
//Ensure that menus do not display protected pages (when using WP3 custom menus only) [highest priority]
add_filter('wp_get_nav_menu_items','CTXPS_Security::filter_custom_menus',1);

//Add shortcodes!
add_shortcode('groups_attached', 'CTXPS_Shortcodes::groups_attached'); //Current page permissions only
add_shortcode('groups_required', 'CTXPS_Shortcodes::groups_required'); //Complete permissions for current page

//Update the edit.php pages & posts lists to include a "Protected" column
add_filter('manage_pages_columns','CTXPS_Components::add_list_protection_column');
add_filter('manage_posts_columns','CTXPS_Components::add_list_protection_column');
add_action('manage_pages_custom_column','CTXPS_Components::render_list_protection_column',10,2); //Priority 10, Takes 2 args (use default priority only so we can specify args)
add_action('manage_posts_custom_column','CTXPS_Components::render_list_protection_column',10,2); //Priority 10, Takes 2 args (use default priority only so we can specify args)

//Modify the global help array so we can add extra help text to default WP pages
add_action('admin_head', 'CTXPS_App::help_init');

/*********************** FUNCTIONS **********************************/


//Load theme functions
require_once 'controllers/theme-functions.php';

?>