<?php
/*
Plugin Name: Page Security by Contexture
Plugin URI: http://www.contextureintl.com/open-source-projects/contexture-page-security-for-wordpress/
Description: Allows admins to create user groups and restrict access to sections of the site by group.
Version: 1.2.1
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

/** TODO: 1.3 - Allow CATEGORIES to be restricted. */
/** TODO: 1.3 - Implement "Advanced Features" for restrict access toolbar (see below) */
/** TODO: 1.3 - Restrict Access "Use me for 'Access Denied'" checkbox (should disable and nullify other restrict access options) */
/** TODO: 1.3 - AJAX username hinting in Groups > Edit add user field. */
/** TODO: 1.3 - Make plugin localization friendly */
/** TODO: 1.3 - Restrict Access Advanced: "Allow on menus" */
/** TODO: 1.3 - Restrict Access Advanced: "Set custom access denied page" option */


/** TODO: x.x - Ability to set membership expirations for individual users in a group (date where user is automatically removed from group) */

/************************* HOOKS *********************************/
// Install new tables (on activate)
register_activation_hook(__FILE__,'ctx_ps_activate');
// Remove tables from db (on delete)
register_uninstall_hook(__FILE__,'ctx_ps_uninstall');
// Add "Groups" option to "Users" in admin
add_action('admin_menu', 'ctx_ps_create_menus');
// Add a "Groups" view to a user's user-edit.php page
add_action('edit_user_profile', 'ctx_ps_generate_usergroupslist');
add_action('show_user_profile', 'ctx_ps_generate_usergroupslist');

//Add the security box sidebar to the pages section
add_action('admin_init', 'ctx_ps_admin_init');

//Handle Ajax for Edit Page/Post page
add_action('wp_ajax_ctx_ps_add2page','ctx_ps_ajax_add_group_to_page');
add_action('wp_ajax_ctx_ps_removefrompage','ctx_ps_ajax_remove_group_from_page');
add_action('wp_ajax_ctx_ps_security_update','ctx_ps_ajax_security_update');

//Handle Ajax for Edit User page
add_action('wp_ajax_ctx_ps_add2user','ctx_ps_ajax_add_group_to_user');
add_action('wp_ajax_ctx_ps_removefromuser','ctx_ps_ajax_remove_group_from_user');

//Add basic security to all public "static" pages and posts
add_action('wp','ctx_ps_security_action');

//Add basic security to dynamically displayed posts (such as on Blog Posts Page, ie: Home)
add_filter( "the_posts","ctx_ps_security_filter_blog");

//Ensure that menus do not display protected pages (when using default menus only)
add_filter('get_pages','ctx_ps_security_filter_menu');
//Ensure that menus do not display protected pages (when using WP3 custom menus only)
add_filter('wp_get_nav_menu_items','ctx_ps_security_filter_menu_custom');

//Add shortcodes!
add_shortcode('groups_attached', 'ctx_ps_tag_groups_attached'); //Current page permissions only
add_shortcode('groups_required', 'ctx_ps_tag_groups_required'); //Complete permissions for current page

//Update the edit.php pages & posts lists to include a "Protected" column
add_filter('manage_pages_columns','ctx_ps_usability_showprotection');
add_filter('manage_posts_columns','ctx_ps_usability_showprotection');
add_action('manage_pages_custom_column','ctx_ps_usability_showprotection_content',10,2); //Takes 2 args
add_action('manage_posts_custom_column','ctx_ps_usability_showprotection_content',10,2); //Takes 2 args

/*********************** FUNCTIONS **********************************/

/**
 * Adds the important tables to the wordpress database
 */
function ctx_ps_activate(){
    global $wpdb;

    $linkBack = admin_url();

    //Ensure that we're using PHP5 (plugin has reported problems with PHP4)
    if (version_compare(PHP_VERSION, '5', '<')) {
        deactivate_plugins(__FILE__);
        wp_die(
                "<span style=\"color:red;font-weight:bold;\">".__('Missing Requirement:')."</span> "
                .sprintf(__("Page Security requires PHP5 or higher. Your server is running %s. Please contact your hosting service about enabling PHP5 support."),PHP_VERSION)
                ."<a href=\"{$linkBack}plugins.php\"> ".__('Return to plugin page')." &gt;&gt;</a>");
    }

    //Name our tables
    $table_groups = $wpdb->prefix . "ps_groups";
    $table_group_relationships = $wpdb->prefix . "ps_group_relationships";
    $table_security = $wpdb->prefix . "ps_security";

    //Build our SQL scripts to create the new db tables
    $sql_create_groups = "CREATE TABLE IF NOT EXISTS `$table_groups` (
        `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `group_title` varchar(40) NOT NULL COMMENT 'The name of the group',
        `group_description` text COMMENT 'A description of or notes about the group',
        `group_creator` bigint(20) UNSIGNED default NULL COMMENT 'The id of the user who created the group',
        `group_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'The datetime the group was created',
        `group_system_id` varchar(5) UNIQUE NULL COMMENT 'A unique system id for system groups',
        PRIMARY KEY (`ID`)
    )";

    $sql_create_group_relationships = "CREATE TABLE IF NOT EXISTS `$table_group_relationships` (
        `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        `grel_group_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The group id that the user is attached to',
        `grel_user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The user id to attach to the group',
        PRIMARY KEY (`ID`)
    )";

    $sql_create_security = "CREATE TABLE IF NOT EXISTS `$table_security` (
        `ID` bigint(20) UNSIGNED NOT NULL auto_increment,
        `sec_protect_type` varchar(10) NOT NULL default 'page' COMMENT 'What type of item is being protected? (page, post, category, etc)',
        `sec_protect_id` bigint(20) unsigned NOT NULL COMMENT 'The id of the item (post, page, etc)',
        `sec_access_type` varchar(10) NOT NULL default 'group' COMMENT 'Specifies whether this security entry pertains to a user, group, or role.',
        `sec_access_id` bigint(20) NOT NULL COMMENT 'The id of the user, group, or role this pertains to.',
        `sec_setting` varchar(10) NOT NULL default 'allow' COMMENT 'Set to either allow or restrict',
        `sec_cascades` tinyint(1) NOT NULL default '1' COMMENT 'If true, these settings inherit down through the pages ancestors. If false (default), settings affect this page only.',
        PRIMARY KEY (`ID`)
    )";

    //Create the tables
    $wpdb->show_errors();
    $wpdb->query($sql_create_groups);
    $wpdb->query($sql_create_group_relationships);
    $wpdb->query($sql_create_security);

    //Record what version of the db we're using (only works if option not already set)
    add_option("contexture_ps_db_version", "1.1");

    //Set plugin options (not db version)
    ctx_ps_set_options();

    /********* START UPGRADE PATH ***********/
    $dbver = get_option("contexture_ps_db_version");
    if($dbver == "" || (float)$dbver < 1.1){
        $wpdb->query("ALTER TABLE `$table_groups` ADD COLUMN `group_system_id` varchar(5) UNIQUE NULL COMMENT 'A unique system id for system groups' AFTER `group_date`");
        update_option("contexture_ps_db_version", "1.1");
    }
    /******** END UPGRADE PATH **************/

    //Check if our "Registered Users" group already exists
    $CntRegSmrtGrp = (bool)$wpdb->get_var("SELECT COUNT(*) FROM `$table_groups` WHERE `group_system_id` = 'CPS01' LIMIT 1");
    if(!$CntRegSmrtGrp){
        //Adds the Registered Users system group (if it doesnt exist)
        $wpdb->query("INSERT INTO `$table_groups` (`group_title`,`group_description`,`group_creator`,`group_system_id`) VALUES ('".__('Registered Users')."','".__('This group automatically applies to all authenticated users.')."','0','CPS01')");
    }
}


/**
 * Handles ajax requests to add a group to a page. When successful, generates HTML to be used in the "Allowed Groups"
 * section of the "Restrict Page" sidebar. Spits out XML response for AJAX use.
 */
function ctx_ps_ajax_add_group_to_page(){
    global $wpdb;

    //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
    if(!current_user_can('manage_options')){
        //If user isn't authorized, stop and return error
        ctx_ps_ajax_response(array('code'=>'0','message'=>'Admin user is unauthorized.'));
    }

    //Add new security to the database
    $qryAddSec = $wpdb->prepare(
        "INSERT INTO {$wpdb->prefix}ps_security (
        sec_protect_type,
        sec_protect_id,
        sec_access_type,
        sec_access_id)
        VALUES (
        'page',
        '%s',
        'group',
        '%s'
        )",
        $_GET['postid'],
        $_GET['groupid']
    );
    $result = $wpdb->query($qryAddSec);

    if(!!$result){
        //Start with blank HTML output
        $OutputHTML = '';
        
        //Get security info for the specified page and it's parents
        $securityStatus = ctx_ps_getprotection( $_GET['postid'] );

        //Set $groupcount to 0, because we are about to count the number of groups attached to THIS page...
        $groupcount = 0;
        //If there's any security, count the number of groups attached to this page (including inherited groups from ancestors)
        if(!!$securityStatus) {
            foreach($securityStatus as $securityGroups){
                //Increment $groupcount by the number of groups
                $groupcount = $groupcount+count($securityGroups);
            }
        }
        //Show groups that are already added to this page
        if($groupcount===0){
            //Display this if we have no groups (inherited or otherwise)
            $OutputHTML = '<div><em>'.__('No groups have been added yet.').'</em></div>';
        }else{
            //Loop through each PAGE (starting with this one and working our way up)
            foreach($securityStatus as $securityArray->pageid => $securityArray->grouparray){
                //If the pageid in the array is this page (ie: current page)
                if($securityArray->pageid == $_GET['postid']){
                    //Loop through all groups for the CURRENT page
                    foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                        $OutputHTML .= '<div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><span class="removegrp" onclick="ctx_ps_remove_group_from_page('.$currentGroup->id.',jQuery(this))">'.__('remove').'</span></div>';
                    }
                }else{
                    //Loop through all groups for the ANCESTOR page
                    foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                        $OutputHTML .= '<div class="ctx-ps-sidebar-group inherited">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><a class="viewgrp" target="_blank" href="'.admin_url().'post.php?post='.$securityArray->pageid.'&action=edit" >'.__('ancestor').'</a></div>';
                    }
                }
            }
        }
        
        ctx_ps_ajax_response(array('code'=>0,'html'=>'<![CDATA['.$OutputHTML.']]>'));
    }
}

/**
 * Handles ajax requests to remove a group from a specified page
 */
function ctx_ps_ajax_remove_group_from_page(){
    global $wpdb;
    

    //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
    if(!current_user_can('manage_options')){
        //If user isn't authorized
        ctx_ps_ajax_response(array('code'=>'0','message'=>__('Admin user is unauthorized.')));
    }

    if($wpdb->query("DELETE FROM {$wpdb->prefix}ps_security WHERE sec_protect_id = {$_GET['postid']} AND sec_access_id = {$_GET['groupid']}") !== false){
        ctx_ps_ajax_response(array('code'=>'1','message'=>'Group removed'));
    }else{
        ctx_ps_ajax_response(array('code'=>'0','message'=>'Query failed'));
    }
    ctx_ps_ajax_response($response);
}

/**
 * Handles ajax requests to add a user to a group
 */
function ctx_ps_ajax_add_group_to_user(){
    global $wpdb;

    //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
    if(!current_user_can('manage_options')){
        //If user isn't authorized
        ctx_ps_ajax_response(array('code'=>'0','message'=>__('Admin user is unauthorized.')));
    }

    //Make sure user exists in db
    $UserInfo = (int)$wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE {$wpdb->users}.ID = '%s'",
                    $_GET['user_id']));

    //If this user doesn't exist
    if($UserInfo === 0){
        ctx_ps_ajax_response(array('code'=>'0','message'=>'User not found'));
    } else {
        //Add user to group
        $sqlUpdateGroup = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}ps_group_relationships` (grel_group_id, grel_user_id) VALUES ('%s','%s');",
                $_GET['groupid'],
                $_GET['user_id']);
        if($wpdb->query($sqlUpdateGroup) === false){
            ctx_ps_ajax_response(array('code'=>'0','message'=>'Query failed'));
        } else {
            ctx_ps_ajax_response(array('code'=>'1','message'=>__('User enrolled in group'),'html'=>'<![CDATA['.ctx_ps_display_group_list($_GET['user_id'],'users').']]>'));
        }
    }

}

/**
 * Handles ajax requests to remove a group from a users account
 */
function ctx_ps_ajax_remove_group_from_user(){
    global $wpdb;

    //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
    if(!current_user_can('manage_options')){
        //If user isn't authorized
        ctx_ps_ajax_response(array('code'=>'0','message'=>__('Admin user is unauthorized.')));
    }

    $sqlRemoveUserRel = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ps_group_relationships` WHERE grel_group_id = '%s' AND grel_user_id = '%s';",
            $_GET['groupid'],
            $_GET['user_id']);
    if($wpdb->query($sqlRemoveUserRel) == 0){
        ctx_ps_ajax_response(array('code'=>'0','message'=>'User not found'));
    } else {
        $html = ctx_ps_display_group_list($_GET['user_id'],'users');
        if(empty($html)){
            $html = '<td colspan="4">'.__('This user has not been added to any static groups. Select a group above or visit any <a href="users.php?page=ps_groups">group detail page</a>.</td>');
        }
        ctx_ps_ajax_response(array('code'=>'1','message'=>__('User unenrolled from group'),'html'=>'<![CDATA['.$html.']]>'));
    }
}

/**
 * Takes an associative array and outputs xml
 */
function ctx_ps_ajax_response($AssocArray=''){
    if(!isset($AssocArray['code'])){
        $AssocArray['code'] = 0;
    }

    @header('Content-Type: text/xml; charset=' . get_option('blog_charset'));
    $response = "<?xml version='1.0' standalone='yes'?><ajax>";
    foreach($AssocArray as $element=>$value){
        $element = strtolower($element);
        $element = sanitize_title_with_dashes($element);
        $response .= "<{$element}>{$value}</{$element}>";
    }
    $response .= "</ajax>";
    die($response);
}

/**
 * Toggles page security on or off - removes all groups from page if toggled off
 */
function ctx_ps_ajax_security_update(){
    global $wpdb;

    //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
    if(!current_user_can('manage_options')){
        //If user isn't authorized
        ctx_ps_ajax_response(array('code'=>'0','message'=>__('Admin user is unauthorized.')));
    }


    $response = array();
    switch($_GET['setting']){
        case 'on':
            $response['code'] = add_post_meta($_GET['postid'],'ctx_ps_security','1');
            $response['message'] = 'Security enabled';
            break;
        case 'off':
            if($wpdb->query("DELETE FROM {$wpdb->prefix}ps_security WHERE sec_protect_id = {$_GET['postid']}") !== false){
                $response['code'] = delete_post_meta($_GET['postid'],'ctx_ps_security');
                $response['message'] = 'Security disabled';
            }else{
                $response['code'] = '0';
                $response['message'] = 'Query failed';
            }
            break;
        default:
            $response['code'] = '0';
            $response['message'] = 'Data does not validate';
            break;
    }
    ctx_ps_ajax_response($response);
}


/**
 * Handles creating or updating the options array
 *
 * @param array $array_overrides An associative array containing key=>value pairs to override originals
 * @return string
 */
function ctx_ps_set_options($arrayOverrides=false){

    //Set defaults
    $defaultOpts = array(
        "ad_msg_usepages"=>"false",
        "ad_msg_anon"=>'You do not have the appropriate group permissions to access this page. Please try <a href="'.wp_login_url( get_permalink() ).'">logging in</a> or contact an administrator for assistance.',
        "ad_msg_auth"=>'You do not have the appropriate group permissions to access this page. If you believe you <em>should</em> have access to this page, please contact an administrator for assistance.',
        "ad_page_anon_id"=>"",
        "ad_page_auth_id"=>""
    );

    //Let's see if the options already exist...
    $dbOpts = get_option('contexture_ps_options');

    if(!$dbOpts){
        //There's no options! Let's build them...
        if($arrayOverrides!=false && is_array($arrayOverrides)){
            //If we have some custom settings, use those
            $defaultOpts = array_merge($defaultOpts, $arrayOverrides);
        }
        //Now add them to the db
        return add_option('contexture_ps_options',$defaultOpts);
    }else{
        //db options exist, so let's merge it with the defaults (just to be sure we have all the latest options
        $defaultOpts = array_merge($defaultOpts, $dbOpts);
        //Now let's add our custom settings (if appropriate)
        if($arrayOverrides!=false && is_array($arrayOverrides)){
            //If we have some custom settings, use those
            $defaultOpts = array_merge($defaultOpts, $arrayOverrides);
        }
        return update_option('contexture_ps_options',$defaultOpts);
    }

}


/**
 * Same as ctx_ps_security_filter, but modified for use as an action. Controls
 * page/post access for general users.
 *
 * @global object $post Gets db information about this post (used to determind post_type)
 * @global object $current_user Info for the currently logged in user
 * @param string $content
 * @return string
 */
function ctx_ps_security_action(){
    global $post;
    global $page;
    global $id;
    global $current_user;
    $secureallowed = true;

    if(!current_user_can('manage_options') && !is_home() && !is_category() && !is_tag() && !is_admin() && !is_404()) {
        /**Groups that this user is a member of*/
        $useraccess = ctx_ps_get_usergroups($current_user->ID);
        /**Groups required to access this page*/
        $pagereqs = ctx_ps_getprotection($post->ID);

        //wp_die(print_r($pagereqs,true));
        
        if(!!$pagereqs){
            //Determine if user can access this content
            $secureallowed = ctx_ps_determine_access($useraccess,$pagereqs);

            //wp_die(print_r($secureallowed,true));

            if($secureallowed){
                //If we're allowed to access this page (do nothing)
            }else{
                //If we're NOT allowed to access this page

                //Get AD messages from options
                $dbOpt = get_option('contexture_ps_options');

                //If user is NOT logged in...
                if($current_user->ID == 0 && !is_user_logged_in()){
                    //Check options to determine if we're using a PAGE or a MESSAGE
                    if($dbOpt['ad_msg_usepages']==='true'){
                        //Send user to the new page
                        if(is_numeric($dbOpt['ad_page_anon_id']))
                            wp_safe_redirect('/?page_id='.$dbOpt['ad_page_anon_id']);
                        else
                            //Just in case theres a config problem...
                            wp_die($ADMsg['ad_msg_anon'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; Go to home page</a>');
                    }else{
                        //If user is anonymous, show this message
                        $blogurl = get_bloginfo('url');
                        wp_die($ADMsg['ad_msg_anon'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; Go to home page</a>');
                    }
                }else{
                    //Check options to determine if we're using a PAGE or a MESSAGE
                    if($dbOpt['ad_msg_usepages']==='true'){
                        //Send user to the new page
                        if(is_numeric($dbOpt['ad_page_auth_id']))
                            wp_safe_redirect('/?page_id='.$dbOpt['ad_page_auth_id']);
                        else
                            //Just in case theres a config problem...
                            wp_die($dbOpt['ad_msg_auth'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; Go to home page</a>');
                    }else{
                        //If user is authenticated, show this message
                        wp_die($dbOpt['ad_msg_auth'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; Go to home page</a>');
                    }
                }
            }
        }
    }
}

/**
 * Hooks to the loop and removes data for posts that are protected when the security
 * doesn't pass muster.
 *
 * @global object $post
 * @global <type> $page
 * @global <type> $id
 * @global object $current_user
 * @param <type> $content
 * @return <type>
 */
function ctx_ps_security_filter_blog($content){
    global $current_user;

    //Do this only if user is not an admin, or if this is the blog page, category page, tag page, or feed (and isnt an admin page)
    if( !current_user_can('manage_options') && ( is_home() || is_category() || is_tag() || is_feed() )  && !is_admin()) {
        foreach($content as $post->key => $post->value){
            //Fun with manipulating the array
            //$post->value->post_content = "<h2>{$post->value->ID}</h2>".$post->value->post_content;
        
            /**Groups that this user is a member of*/
            $useraccess = ctx_ps_get_usergroups($current_user->ID);
            /**Groups required to access this page*/
            $pagereqs = ctx_ps_getprotection($post->value->ID);

            if(!!$pagereqs){
                $secureallowed = ctx_ps_determine_access($useraccess,$pagereqs);

                if($secureallowed){
                    //If we're allowed to access this page
                }else{
                    //If we're NOT allowed to access this page
                    unset($content[$post->key]);
                }
            }
        }
    }

    //Adjust top-level array key numbers to be concurrent (since a gap between numbers can cause wp to freak out)
    $content = array_merge($content,array());

    return $content;
}

/**
 * When the default menu is being used, this checks restrictions for each page
 * in the menu and removes it if it's restricted for the current user.
 *
 * @global object $post
 * @global <type> $page
 * @global <type> $id
 * @global object $current_user
 * @param <type> $content
 * @return The array of wordpress posts used to build the default menu
 */
function ctx_ps_security_filter_menu($content){
    global $current_user;

    //print_r($content);

    //Do this filtering only if the user isn't an admin (and isn't in admin section)
    if( !current_user_can('manage_options')  && !is_admin()) {

        //Loop through the content array
        foreach($content as $post->key => $post->value){

            //Get groups that this user is a member of
            $useraccess = ctx_ps_get_usergroups($current_user->ID);
            //Get groups required to access this page
            $pagereqs = ctx_ps_getprotection($post->value->ID);

            //So long as $pagereqs is anything but false
            if(!!$pagereqs){

                //Determine user access
                $secureallowed = ctx_ps_determine_access($useraccess,$pagereqs);

                if($secureallowed){
                    //If we're allowed to access this page
                }else{
                    //If we're NOT allowed to access this page
                    unset($content[$post->key]); //Remove content from array
                }
            }
            
            //If this is an AD page, strip it too
            if($dbOpts['ad_msg_usepages']==='true'){
                if($post->value->object_id==$dbOpts['ad_page_auth_id'] || $post->value->object_id==$dbOpts['ad_page_anon_id']){
                    unset($content[$post->key]);
                }
            }
        }
    }

    return $content;
}


/**
 * When a WP3 custom menu is being used, this checks restrictions for each page
 * in the menu and removes it if it's restricted to the current user.
 *
 * @global object $post
 * @global <type> $page
 * @global <type> $id
 * @global object $current_user
 * @param <type> $content
 * @return The array of wordpress posts used to build the custom menu.
 */
function ctx_ps_security_filter_menu_custom($content){
    global $current_user;

    //Do this filtering only if user isn't an admin
    if( !current_user_can('manage_options') && !is_admin() ) {

        //Get options (in case we need to strip access denied pages)
        $dbOpts = get_option('contexture_ps_options');

        foreach($content as $post->key => $post->value){

            //Get groups that this user is a member of
            $useraccess = ctx_ps_get_usergroups($current_user->ID);
            //Get groups required to access this page
            $pagereqs = ctx_ps_getprotection($post->value->object_id);

            //So long as $pagereqs is anything but false
            if(!!$pagereqs){

                //Determine user access
                $secureallowed = ctx_ps_determine_access($useraccess,$pagereqs);

                if($secureallowed){
                    //If we're allowed to access this page
                }else{
                    //If we're NOT allowed to access this page
                    unset($content[$post->key]);
                }
            }
            //If this is an AD page, strip it too
            if($dbOpts['ad_msg_usepages']==='true'){
                if($post->value->object_id==$dbOpts['ad_page_auth_id'] || $post->value->object_id==$dbOpts['ad_page_anon_id']){
                    unset($content[$post->key]);
                }
            }
        }
    }

    return $content;
}

/**
 * Adds the security box on the right side of the 'edit page' admin section
 */
function ctx_ps_admin_init(){
    add_action('admin_head', 'ctx_ps_admin_head_js');
    add_action('admin_head', 'ctx_ps_admin_head_css');

    add_meta_box('ctx_ps_sidebar_security', 'Restrict Access', 'ctx_ps_sidebar_security', 'page', 'side', 'low');
    add_meta_box('ctx_ps_sidebar_security', 'Restrict Access', 'ctx_ps_sidebar_security', 'post', 'side', 'low');
}

/**
 * Adds some custom JS to the header, primarily AJAX
 */
function ctx_ps_admin_head_js(){
    ?>
    <script type="text/javascript">
        //When DOM Ready...
        jQuery(function(){
            jQuery('#groups-available') //On restrict-access sidebar AND edit-users page
                .data('options',jQuery('#groups-available').html())
                .children('.detach')
                    .remove();
            jQuery('#add_group_page').click(function(){ctx_ps_add_group_to_page()});
            jQuery('#ctx_ps_protectmy').click(function(){ctx_ps_togglesecurity()});
            jQuery('label[for="ctx_ps_protectmy"]').click(function(){
                //If the checkbox is disabled, it's because an ancestor is protected - let the user know
                if(jQuery('#ctx_ps_protectmy:disabled').length > 0){
                    alert('You cannot unprotect this page. It is protected by a parent or ancestor.');
                }
            });
            jQuery('#btn-add-grp-2-user').click(function(){ctx_ps_add_group_to_user()});
        });

        //Will display a "Security Updated" message in the sidebar when successful change to security
        function ctx_showSavemsg(selector){
            if(jQuery(selector+' .ctx-ajax-status').length==0){
                jQuery(selector)
                    .append('<span class="ctx-ajax-status">Saved</span>')
                    .find('.ctx-ajax-status')
                    .fadeIn(500,function(){
                        jQuery(this)
                            .delay(750).fadeOut(500,function(){
                                jQuery(this).remove();
                            });
                    });
            }
        }

        //Updates the security status of the page
        function ctx_ps_togglesecurity(){
            var ipostid = parseInt(jQuery('#ctx_ps_post_id').val());

            if(jQuery('#ctx_ps_protectmy:checked').length !== 0){
                //Turn security ON for this group
                jQuery.get('admin-ajax.php',
                    {
                        action:'ctx_ps_security_update',
                        setting:'on',
                        postid:ipostid
                    },
                    function(data){ data = jQuery(data);
                        if(data.find('code').text() == '1'){
                            jQuery("#ctx_ps_pagegroupoptions").show();
                            ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle')
                        }
                    },'xml'
                );
            }else{
                if(confirm('This will completely erase this page\'s security settings and make it accessible to the public. Continue?')){
                    //Turn security OFF for this group
                    jQuery.get('admin-ajax.php',
                        {
                            action:'ctx_ps_security_update',
                            setting:'off',
                            postid:ipostid
                        },
                        function(data){
                            data = jQuery(data);
                            if(data.find('code').text() =='1'){
                                jQuery("#ctx_ps_pagegroupoptions").hide();
                                ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle')
                            }
                        },'xml'
                    );
                }else{
                    //If user cancelled, re-check the box
                    jQuery('#ctx_ps_protectmy').attr('checked','checked');
                }
            }
            
        }

        //Adds a group to a user
        function ctx_ps_add_group_to_user(){
            var igroupid = parseInt(jQuery('#groups-available').val());
            var iusrid = parseInt(jQuery('#ctx-group-user-id').val());
            if(igroupid!=0){
                jQuery('#btn-add-grp-2-user').attr('disabled','disabled');
                //alert("The group you want to add is: "+$groupid);
                jQuery.get('admin-ajax.php',
                    {
                        action:'ctx_ps_add2user',
                        groupid:igroupid,
                        user_id:iusrid
                    },
                    function(response){
                        response = jQuery(response);
                        if(response.find('html').length > 0){
                            
                            //Add group to the Allowed Groups list from our stored data
                            jQuery('#grouptable > tbody').html(response.find('html').text());

                            //Load the select drop down list
                            var grpsAvail = jQuery('#groups-available');

                            grpsAvail
                                .html(grpsAvail.data('options')) //Set the ddl content = saved data
                                .children('option[value="'+igroupid+'"]') //Select option that we just added
                                    .addClass('detach') //Add detach class to it
                                .end() //Reselect ddl again
                                .data('options',grpsAvail.html()) //Re-save the options html as data
                                .children('.detach') //Select all detached options
                                    .remove(); //Remove them

                            jQuery('#btn-add-grp-2-user').removeAttr('disabled');
                            ctx_showSavemsg('.ctx-ps-tablenav');
                        }
                    },'xml'
                );
            }else{
                alert('You must select a group to add.');
            }
        }

        //Removes a group from a user
        function ctx_ps_remove_group_from_user(igroupid,iuserid,me){
            jQuery.get('admin-ajax.php',
                {
                    action:'ctx_ps_removefromuser',
                    groupid:igroupid,
                    user_id:iuserid
                },
                function(response){
                    response = jQuery(response);
                    if(response.find('code').text() == '1'){

                       var grpsAvail = jQuery('#groups-available');
                        grpsAvail
                            .html(grpsAvail.data('options'))
                            .children('option[value="'+igroupid+'"]')
                                .removeClass('detach')
                            .end()
                            .data('options',grpsAvail.html())
                            .children('.detach')
                                .remove();

                        //Take me out of the table
                        me.parents('tr:first').fadeOut(500,function(){
                            //Rebuild the group
                            jQuery('#grouptable > tbody').html(response.find('html').text());
                        });

                        ctx_showSavemsg('.ctx-ps-tablenav');
                    }
                },'xml'
            );
                
        }


        //Adds a group to a page with security
        function ctx_ps_add_group_to_page(){
            var igroupid = parseInt(jQuery('#groups-available').val());
            var ipostid = parseInt(jQuery('#ctx_ps_post_id').val());
            if(igroupid!=0){
                //alert("The group you want to add is: "+$groupid);
                jQuery.get('admin-ajax.php',
                    {
                        action:'ctx_ps_add2page',
                        groupid:igroupid,
                        postid:ipostid
                    },
                    function(data){
                        data = jQuery(data);
                        if(data.find('html').length > 0){
                            //Add group to the Allowed Groups list
                            jQuery('#ctx-ps-page-group-list').html(data.find('html').text());

                            var grpsAvail = jQuery('#groups-available');

                            grpsAvail
                                .html(grpsAvail.data('options'))
                                .children('option[value="'+igroupid+'"]')
                                    .addClass('detach')
                                .end()
                                .data('options',grpsAvail.html())
                                .children('.detach')
                                    .remove();

                            ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle');
                        }
                    },'xml'
                );
            }else{
                alert('You must select a group to add.');
            }
        }

        //Removes a group from a page with security
        function ctx_ps_remove_group_from_page(igroupid,me){
            if(confirm('Are you sure you want to remove group "'+me.parents('.ctx-ps-sidebar-group:first').children('.ctx-ps-sidebar-group-title').text()+'" from this page?')){
                var ipostid = parseInt(jQuery('#ctx_ps_post_id').val());
                //alert("The group you want to add is: "+$groupid);
                jQuery.get('admin-ajax.php',
                    {
                        action:'ctx_ps_removefrompage',
                        groupid:igroupid,
                        postid:ipostid
                    },
                    function(data){
                        data = jQuery(data);
                        if(data.find('code').text() == '1'){
                            
                           var grpsAvail = jQuery('#groups-available');
                            grpsAvail
                                .html(grpsAvail.data('options'))
                                .children('option[value="'+igroupid+'"]')
                                    .removeClass('detach')
                                .end()
                                .data('options',grpsAvail.html())
                                .children('.detach')
                                    .remove();
                            me.parent().fadeOut(500,function(){jQuery(this).remove();});

                            ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle');
                        }
                    },'xml'
                );
            }
        }
    </script>
    <?php
}

/**
 * Adds some custom CSS to the header
 */
function ctx_ps_admin_head_css(){
    ?>
    <style type="text/css">
        #ctx_ps_pagegroupoptions {display:none;}
        #ctx-ps-page-group-list > div {}
        #ctx-ps-page-group-list > div.inherited {color:silver;}
        #ctx-ps-page-group-list > div .removegrp {float:right;color:red;font-family:arial,helvetica,sans-serif;display:none;font-weight:bold;}
        #ctx-ps-page-group-list > div .viewgrp {float:right;color:gray;font-family:arial,helvetica,sans-serif;display:none;font-weight:bold;text-decoration:none;}
        #ctx-ps-page-group-list > div:hover .removegrp,
        #ctx-ps-page-group-list > div:hover .viewgrp{display:inline;cursor:pointer;}
        .ctx-ajax-status { display:none; float:right; color:green; font-size:0.8em; margin-right:5px; margin-top:-5px; }
        .ctx-ps-sysgroup { color:gray; }
        .ctx-ps-sysgroup:hover { color:gray; }
        option.cts-ps-system-group { font-weight:bold; }
        .ctx-ps-sidebar-group { padding-bottom:2px; }
        .ctx-ps-sidebar-group:hover { background:#FFFCE0; }
        #groups-available .detach { display:none; visibility:hidden; }

        .widefat th#protected { vertical-align:middle; width:30px; }
        .widefat td.protected { vertical-align:top; }
        .widefat td.protected img { margin-top:4px; }
    </style>
    <?php
}

/**
 * Gets a count of the number of groups currently in the db
 * @return int The number of groups in the db
 */
function ctx_ps_count_groups($memberid=''){
    global $wpdb;
    if($memberid==''){
        return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ps_groups WHERE group_system_id IS NULL"));
    }else{
        return $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->prefix}ps_group_relationships
            WHERE {$wpdb->prefix}ps_group_relationships.grel_user_id = '{$memberid}'
        ");
    }
}


/**
 * Gets a count of the number of users currently in a group
 * @param int $groupid The group id to count users for
 * @return int The number of users attached to the group
 */
function ctx_ps_count_members($groupid){
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ps_group_relationships WHERE grel_group_id = '{$groupid}'"));
}


/**
 * Creates a new group
 * 
 * @param <type> $name
 * @param <type> $description
 * @return <type>
 */
function ctx_ps_create_group($name, $description){
    global $wpdb;

    if($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ps_groups WHERE group_title = '%s'",$name)) == '0'){
        $current_user = wp_get_current_user();
        $sql_addgroup = $wpdb->prepare("
            INSERT INTO {$wpdb->prefix}ps_groups
            (`group_title`,
            `group_description`,
            `group_creator`)
            VALUES
            ('%s',
            '%s',
            '%s')
        ",
        $name,
        $description,
        $current_user->ID);
        if($wpdb->query($sql_addgroup) !== FALSE){
            return '<div id="message" class="updated"><p>New group created</p></div>';
        }else{
            return '<div id="message" class="error below-h2"><p>Unable to create group. There was an unspecified system error.</p></div>';
        }
    } else {
        return '<div id="message" class="error below-h2"><p>Unable to create group. A group with that name already exists.</p></div>';
    }
}


/**
 * Adds the "Groups" functionality to the admin section under "Users"
 */
function ctx_ps_create_menus(){
    //Add Groups option to the WP admin menu under Users
    add_submenu_page('users.php', __('Group Management'), __('Groups'), 'manage_options', 'ps_groups', 'ctx_ps_page_groups_view');
    add_submenu_page('users.php', __('Add a Group'), __('Add Group'), 'manage_options', 'ps_groups_add', 'ctx_ps_page_groups_add');
    add_submenu_page('', 'Edit Group', 'Edit Group', 'manage_options', 'ps_groups_edit', 'ctx_ps_page_groups_edit');
    add_submenu_page('', 'Delete Group', 'Delete Group', 'manage_options', 'ps_groups_delete', 'ctx_ps_page_groups_delete');

    add_options_page('Page Security by Contexture', 'Page Security', 'manage_options', 'ps_manage_opts', 'ctx_ps_page_options');
    //add_submenu_page('options-general.php', 'Page Security', 'Page Security', 'manage_options', 'ps_manage_opts', 'ctx_ps_page_options');
}


/**
 * This function takes an array of user groups and an array of page-required groups
 * and determines if the user should be allowed to access the specified content.
 * 
 * @param array $UserGroupsArray The array returned by ctx_ps_get_usergroups()
 * @param array $PageSecurityArray The array returned by ctx_ps_get_protection()
 * @return bool Returns true if user has necessary permissions to access the page, false if not.
 */
function ctx_ps_determine_access($UserGroupsArray,$PageSecurityArray){

    //Testing...
    //wp_die(print_r($UserGroupsArray,true).' | '.print_r($PageSecurityArray,true));

    //If our page-security array is empty, automatically return false
    if(!!$PageSecurityArray && count($PageSecurityArray) == 0){return false;}

    //Used to count each page that has at least one group
    $loopswithgroups = 0;

    //Loop through each page's permissions, starting with current page and going up the heirarchy...
    foreach($PageSecurityArray as $security->page => $security->secarray){
        //If the current page has group settings attached...
        if(count($security->secarray) != 0){
            //Increment our group tracking var
            $loopswithgroups += 1;
            //If any of this user's groups do not match any of this page's groups...
            if( count(array_intersect($UserGroupsArray,$security->secarray)) == 0 ){
                //We return false as the user does not have access
                return false;
            }
        }
    }

    //If no pages have groups, then no-one can access the page
    if($loopswithgroups === 0){return false;}

    //If we haven't triggered a false return already, return true
    return true;
    
}


/**
 * Generates html for use in the grouptable tbody element that lists all current
 * groups in the database.
 *
 * @param string $memberid If set, only shows groups that have a specific user as a member
 * @param string $forpage Whether to generate html for the 'groups' page or the 'users' page (default 'groups')
 * @param bool $showactions If set to false, will not show the actions (default true)
 *
 * @return string Returns the html
 */
function ctx_ps_display_group_list($memberid='',$forpage='groups',$showactions=true){
    global $wpdb;
    
    $linkBack = admin_url();

    if($memberid==''){
        $groups = $wpdb->get_results("
            SELECT * FROM `{$wpdb->prefix}ps_groups`
            ORDER BY `{$wpdb->prefix}ps_groups`.`group_system_id` DESC, `{$wpdb->prefix}ps_groups`.`group_title` ASC
        ");
    }else{
        $groups = $wpdb->get_results("
            SELECT * FROM `{$wpdb->prefix}ps_group_relationships`
            JOIN `{$wpdb->prefix}ps_groups`
                ON {$wpdb->prefix}ps_group_relationships.grel_group_id = {$wpdb->prefix}ps_groups.ID
            WHERE {$wpdb->prefix}ps_group_relationships.grel_user_id = '{$memberid}'
            ORDER BY `{$wpdb->prefix}ps_groups`.`group_system_id` DESC, `{$wpdb->prefix}ps_groups`.`group_title` ASC
        ");
    }

    $html = '';
    $htmlactions = '';
    $countmembers = '';
    $alternatecss = ' class="alternate" ';
    $countusers = count_users();

    foreach($groups as $group){
        $countmembers = (!isset($group->group_system_id)) ? ctx_ps_count_members($group->ID) : $countusers['total_users'];

        //Only create the actions if $showactions is true
        if($showactions){
            switch($forpage){
                case 'users':
                    //Button for "Remove" takes user out of group (ajax)
                    $htmlactions = "<div class=\"row-actions\"><span class=\"edit\"><a href=\"{$linkBack}?page=ps_groups_edit&groupid={$group->ID}\">Edit</a> | </span><span class=\"delete\"><a class=\"submitdelete\" id=\"unenroll-{$group->ID}\" onclick=\"ctx_ps_remove_group_from_user({$group->ID},{$_GET['user_id']},jQuery(this))\">Unenroll</a></span></div>";
                    break;
                case 'groups':
                    //Button for "Delete" removes group from db (postback)
                    //If $showactions is false, we dont show the actions row at all
                    $htmlactions = "<div class=\"row-actions\"><span class=\"edit\"><a href=\"{$linkBack}?page=ps_groups_edit&groupid={$group->ID}\">Edit</a> | </span><span class=\"delete\"><a class=\"submitdelete\" href=\"?page=ps_groups_delete&groupid={$group->ID}\">Delete</a></span></div>";
                    break;
                default:break;
            }
        }

        //If user isnt admin, we wont even link to group edit page (useful for profile pages)
        if ( current_user_can('manage_options') ){
            //User is admin - determined if link is system or not
            $grouplink = (!isset($group->group_system_id))
                //This is a user group (editable)
                ? "<a href=\"{$linkBack}?page=ps_groups_edit&groupid={$group->ID}\"><strong>{$group->group_title}</strong></a>{$htmlactions}"
                //This is a system group (not editable)
                : "<a id=\"$group->group_system_id\" class=\"ctx-ps-sysgroup\"><strong>{$group->group_title}</strong></a>";
        }else{
            //User is not admin - no links
            $grouplink = "<a id=\"$group->group_system_id\"><strong>{$group->group_title}</strong></a>";
        }

        $html .= "<tr {$alternatecss}>
            <td class=\"id\">{$group->ID}</td>
            <td class=\"name\">{$grouplink}</td>
            <td class=\"description\">{$group->group_description}</td>
            <td class=\"user-count\">$countmembers</td>
        </tr>";

        //Alternate css style for odd-numbered rows
        $alternatecss = ($alternatecss != '') ? '' : ' class="alternate" ';
    }
    return $html;
}


/**
 * Adds the "Group Management" feature to Edit User pages
 */
function ctx_ps_generate_usergroupslist(){
    require_once("inc/user-edit-groups.php");
}


/**
 * Returns html for tbody element of group member list.
 *
 * @param int $GroupID The id of the group we need a member list for.
 * @return string Html to go inside tbody.
 */
function ctx_ps_display_member_list($GroupID){
    global $wpdb;

    $sqlGetMembers = $wpdb->prepare("
        SELECT
            {$wpdb->users}.ID AS ID,
            {$wpdb->prefix}ps_group_relationships.id AS rel_id,
            {$wpdb->users}.user_login,
            {$wpdb->users}.user_email
        FROM `{$wpdb->prefix}ps_group_relationships`
        JOIN `{$wpdb->users}`
            ON {$wpdb->prefix}ps_group_relationships.grel_user_id = {$wpdb->users}.ID
        WHERE grel_group_id = '%s'",
    $GroupID);

    $members = $wpdb->get_results($sqlGetMembers);

    $html = '';
    $countmembers = '';
    $alternatecss = ' class="alternate" ';

    foreach($members as $member){
        $fname = get_user_meta($member->ID, 'first_name', true);
        $lname = get_user_meta($member->ID, 'last_name', true);
        $html .= "
        <tr id=\"user-{$member->ID}\" {$alternatecss}>
            <td class=\"username column-username\"><a href=\"user-edit.php?user_id={$member->ID}&wp_httpd_referer=\"><strong>{$member->user_login}</strong></a></td>
            <td class=\"name column-name\">{$fname} {$lname}</td>
            <td class=\"email column-email\"><a href=\"mailto:{$member->user_email}\">{$member->user_email}</a></td>
            <td class=\"group-actions\"><a class=\"row-actions\" href=\"?page=ps_groups_edit&groupid={$_GET['groupid']}&action=rmvusr&usrid={$member->ID}&relid={$member->rel_id}&usrname={$member->user_login}\">Remove</a></td>
        </tr>";

        //Alternate css style for odd-numbered rows
        $alternatecss = ($alternatecss != '') ? '' : ' class="alternate" ';
    }
    return $html;
}


/**
 * Gets an array with all the groups that the user belongs to.
 * 
 * @param int $userid The user id of the user to check
 * @return array Returns an array with all the groups that the specified user belongs to.
 */
function ctx_ps_get_usergroups($userid){
    global $wpdb, $current_user;
    $array = array();
    $groups = $wpdb->get_results("
        SELECT * FROM `{$wpdb->prefix}ps_group_relationships`
        JOIN `{$wpdb->prefix}ps_groups`
            ON {$wpdb->prefix}ps_group_relationships.grel_group_id = {$wpdb->prefix}ps_groups.ID
        WHERE {$wpdb->prefix}ps_group_relationships.grel_user_id = '{$userid}'
    ");

    //We only need an ID and a name as a key/value...
    foreach($groups as $group){
        $array += array($group->ID => $group->group_title);
    }

    //If multisite is enabled we can better support it...
    if(function_exists('is_user_member_of_blog')){
        $multisitemember = is_user_member_of_blog($current_user->ID);
    }else{
        $multisitemember = true;
    }

    /*** ADD SMART GROUPS (AKA SYSTEM GROUPS ***/
    //Registered Users Smart Group
    if($current_user->ID != 0 && $multisitemember){
        //Get the ID for CPS01
        $newArray = ctx_ps_get_sysgroup('CPS01');
        //Add CPS01 to the current users permissions array
        $array += array($newArray->ID => $newArray->group_title);
    }

    return $array;
}

/**
 * Returns an array containing all pages with protection
 * @param string $type The return type. String or array (array is default)
 */
function ctx_ps_get_protected_pages($type='array'){
    global $wpdb;
    $results = $wpdb->get_results("SELECT DISTINCT(post_id) FROM `{$wpdb->postmeta}` WHERE `meta_key` = 'ctx_ps_security'",ARRAY_N);
    //IF WE WANT A STRING (CSV)
    if($type==='string'){
        $string = '';
        foreach($results as $page){
            $string .= "{$page[0]},";
        }
        //get rid of the last comma before returning
        return preg_replace('/,$/','',$string);
    //HANDLE DEFAULT (ARRAY)
    }else{
        //We get back an unnecessary multidimensional array, so we will collapse this into a simple array
        $array = array();
        foreach($results as $page){
            $array[] = $page[0];
        }
        return $array;
    }
    
}

/**
 * Gets database record for the specified system group
 *
 * @param string $system_id The group_system_id for the smart group to select (ie "CPS01")
 * @return object Returns $wpdb object for the selected system group
 */
function ctx_ps_get_sysgroup($system_id){
    global $wpdb;
    $array = $wpdb->get_results("
        SELECT * FROM `{$wpdb->prefix}ps_groups`
        WHERE group_system_id = '{$system_id}'
        LIMIT 1
    ");
    return $array[0];
}


/**
 * This function will check the security for the specified page and all parent pages.
 * If security exists, a multi-dimensional array will be returned following the format
 * array( pageid=>array(groupid=>groupname) ), with the first item being the current
 * page and additional items being parents. If no security is present for any ancestor
 * then the function will return false.
 *
 * @param int $postid The id of the post to get permissions for.
 * @return mixed Returns an array with all the required permissions to access this page. If no security is present, returns false.
 */
function ctx_ps_getprotection($postid){
    //If this branch isn't protected, just stop now and save all that processing power
    if (!ctx_ps_isprotected_section($postid)){
        return false;
    }

    //If we're still going, then it means something above us is protected, so lets get the list of permissions
    global $wpdb;
    $array = array();
    $grouparray = array();
    /**Gets the parent id of the current page/post*/
    $parentid = $wpdb->get_var($wpdb->prepare("SELECT post_parent FROM {$wpdb->posts} WHERE `ID` = '%s'",$postid));
    /**Gets the ctx_ps_security data for this post (if it exists) - used to determine if this is the topmost secured page*/
    //$amisecure = get_post_meta($postid,'ctx_ps_security',true);

    //1. If I am secure, get my groups
    //if(!empty($amisecure)){
        //Get Group relationship info for this page from wp_ps_security, join wp_posts on postid
        $query = $wpdb->prepare("
            SELECT
                {$wpdb->posts}.id AS post_id,
                {$wpdb->posts}.post_parent AS post_parent_id,
                {$wpdb->prefix}ps_groups.ID AS group_id,
                {$wpdb->prefix}ps_groups.group_title
            FROM {$wpdb->prefix}ps_security
            JOIN {$wpdb->posts}
                ON {$wpdb->prefix}ps_security.sec_protect_id = {$wpdb->posts}.ID
            JOIN {$wpdb->prefix}ps_groups
                ON {$wpdb->prefix}ps_security.sec_access_id = {$wpdb->prefix}ps_groups.ID
            WHERE {$wpdb->prefix}ps_security.sec_protect_id = '%s'
        ",$postid);
        $groups = $wpdb->get_results($query);

        //If 0 results, dont do anything. Otherwise...
        if(!empty($groups)){
            foreach($groups as $group){
                $grouparray[$group->group_id] = $group->group_title;
            }
        }
    //}
    //Add an item to the array. 'pageid'=>array('groupid','groupname')
    $array[(string)$postid] = $grouparray;
    unset($grouparray);
    //2. If I have a parent, recurse
        //Using our earlier results, check post_parent. If it's != 0 then recurse this function, adding the return value to $array
        if($parentid != '0'){
            //$recursedArray = ctx_ps_getprotection($parentid);
            //$array = array_merge($array,$recursedArray);
            $parentArray = ctx_ps_getprotection($parentid);
            if(!!$parentArray){
              $array += $parentArray;
            }
        }
        
    //3. Return the completed $array
    return $array;
}


/**
 * Filters menu made by WP3 custom menu system (NOT IMPLEMENTED)
 * @param array $args
 * @return string
 */
function ctx_ps_menu_filter_custom($array){
    //wp_die("<array>".print_r($array,true)."</array>");
    return $array;
}


/**
 * Creates the "Add Group" page
 */
function ctx_ps_page_groups_add(){
    global $wpdb;
    require_once 'inc/group-new.php';
}


/**
 * Creates the "Delete Group" page
 */
function ctx_ps_page_groups_delete(){
    global $wpdb;
    require_once 'inc/group-delete.php';
}


/**
 * Creates the "Edit Group" page
 */
function ctx_ps_page_groups_edit(){
    global $wpdb;
    require_once 'inc/group-edit.php';
}


/**
 * Creates the "Groups" page
 */
function ctx_ps_page_groups_view(){
    global $wpdb;
    require_once 'inc/groups.php';
}


/**
 * Creates the "Settings" page
 */
function ctx_ps_page_options(){
    require_once 'inc/options.php';
}

/**
 * Checks this page/post's metadata to see if it's protected. Returns true if
 * protected false if not.
 * 
 * @return bool Whether this page has the "protected page" flag
 */
function ctx_ps_isprotected($postid){
    return (bool)get_post_meta($postid,'ctx_ps_security');
}


/**
 * Recursively checks security for this page/post and it's ancestors. Returns true
 * if any of them are protected or false if none of them are protected.
 * 
 * @return bool If this page or it's ancestors has the "protected page" flag
 */
function ctx_ps_isprotected_section($postid){
    global $wpdb;
    if(get_post_meta($postid,'ctx_ps_security')){
        return true;
    } else {
        $parentid = $wpdb->get_var("SELECT post_parent FROM $wpdb->posts WHERE `ID` = $postid");
        if ($parentid != 0)
            return ctx_ps_isprotected_section($parentid);
        else
            return false;
    }
}


/**
 * Creates the "security" sidebar for Pages
 */
function ctx_ps_sidebar_security(){
    global $wpdb, $post;


        //We MUST have a post id in the querystring in order for this to work (ie: this wont appear for the "create new" pages, as the page doesnt exist yet)
    if(!empty($_GET['post']) && intval($_GET['post']) == $_GET['post']){

        //Create an array of groups that are already attached to the page
        $currentGroups = array();
        foreach($wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}ps_security
                        JOIN {$wpdb->prefix}ps_groups
                            ON {$wpdb->prefix}ps_security.sec_access_id = {$wpdb->prefix}ps_groups.ID
                        WHERE sec_protect_id = '%s'",
                        $_GET['post']
                )
            ) as $curGrp){
            $currentGroups[$curGrp->sec_access_id] = $curGrp->group_title;
        }

        //Get array with security requirements for this page
        $securityStatus = ctx_ps_getprotection( $_GET['post'] );

        //Get options
        $dbOpts = get_option('contexture_ps_options');
        //ad_page_auth_id     ad_page_anon_id

        /***START BUILDING HTML****************************/
        echo '<div class="new-admin-wp25">';

        //Only print restriction options if this ISN'T set as an access denied page
        if($dbOpts['ad_page_anon_id']!=$_GET['post'] && $dbOpts['ad_page_auth_id']!=$_GET['post']){
            echo '  <input type="hidden" id="ctx_ps_post_id" name="ctx_ps_post_id" value="'.$_GET['post'].'" />';
            //Build "Protect this page" label
            echo '  <label for="ctx_ps_protectmy">';
            echo '      <input type="checkbox" id="ctx_ps_protectmy" name="ctx_ps_protectmy"';
            if ( !!$securityStatus )
                echo ' checked="checked" ';
            if ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') ){
                echo ' disabled="disabled" ';
            }
            echo '/>';
            echo __(' Protect this page and it\'s descendants');
            echo '  </label>';
            /** TODO: Add "Use as Access Denied page" option */

            //If the checkbox is disabled, give admin the option to go straight to the parent
            if ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') ){
                echo '<a href="'.admin_url().'post.php?post='.$post->post_parent.'&action=edit" style="display:block;font-size:0.75em;text-align:left;padding-left:20px;">',__('Edit Parent'),'</a>';
            }
            //Start on "Available Groups" select box
            echo '  <div id="ctx_ps_pagegroupoptions" style="border-top:#EEEEEE 1px solid;margin-top:0.5em;';
            if ( !!$securityStatus )
                echo ' display:block ';
            echo '">';
            echo    sprintf(__('<h5>Available Groups <a href="%s" title="New Group" style="text-decoration:none;">+</a></h5>'),admin_url('users.php?page=ps_groups_add'));
            echo '      <select id="groups-available" name="groups-available">';
            echo '<option value="0">-- '.__('Select').' -- </option>';
            //Loop through all groups in the db to populate the drop-down list
            foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}ps_groups ORDER BY `group_system_id` DESC, `group_title` ASC") as $group){
                //Generate the option HTML, hiding it if it's already in our $currentGroups array
                echo        '<option '.((!empty($currentGroups[$group->ID]))?'class="detach"':'').' value="'.$group->ID.'">'.$group->group_title.'</option>';
            }
            echo       '</select>';
            echo       '<input type="button" id="add_group_page" class="button-secondary action" value="',__('Add'),'" />';
            echo    __('<h5>Allowed Groups</h5>');
            echo       '<div id="ctx-ps-page-group-list">';
            //Set this to 0, we are going to count the number of groups attached to this page next...
            $groupcount = 0;
            //Count the number of groups attached to this page (including inherited groups)
            if(!!$securityStatus)
                foreach($securityStatus as $securityGroups){ $groupcount = $groupcount+count($securityGroups); }
            //Show groups that are already added to this page
            if($groupcount===0){
                //Display this if we have no groups (inherited or otherwise)
                echo '<div><em>'.__('No groups have been added yet.').'</em></div>';
            }else{
                foreach($securityStatus as $securityArray->pageid => $securityArray->grouparray){
                    if($securityArray->pageid == $_GET['post']){
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            echo '<div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><span class="removegrp" onclick="ctx_ps_remove_group_from_page('.$currentGroup->id.',jQuery(this))">'.__('remove').'</span></div>';
                        }
                    }else{
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            echo '<div class="ctx-ps-sidebar-group inherited">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><a class="viewgrp" target="_blank" href="'.admin_url().'post.php?post='.$securityArray->pageid.'&action=edit" >'.__('ancestor').'</a></div>';
                        }
                    }
                }
            }
            echo '      </div>';
            echo '  </div>';
        }else{
            echo '<p>This is currently an Access Denied page. You cannot restrict it.</p><p><a href="'.admin_url('options-general.php?page=ps_manage_opts').'">View Security Settings</a></p>';
        }
        echo '</div>';
        /***END BUILDING HTML****************************/
    }else{
        echo '<div class="new-admin-wp25">';
        echo '<p>Security settings can not be added to unpublished items.</p>';
        echo '</div>';
    }
}

/**
 * This tag will output a list of groups attached to the current page.
 * @attr public
 * @attr label
 */
function ctx_ps_tag_groups_attached($atts){
    global $wpdb, $post;

    //Attribute defaults
    $output = shortcode_atts(
    array(
        'public' => 'false',
        'label' => __('Groups attached to this page:'),
    ), $atts);

    //Create an array of groups that are already attached to the page
    $currentGroups = '';
    foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}ps_security JOIN {$wpdb->prefix}ps_groups ON {$wpdb->prefix}ps_security.sec_access_id = {$wpdb->prefix}ps_groups.ID WHERE sec_protect_id = '{$post->ID}'") as $curGrp){
        $currentGroups .= "<li>".$curGrp->group_title." (id:{$curGrp->sec_access_id})</li>";
    }
    $return = "<div class=\"ctx-ps-groupvis\"><h3>{$output['label']}</h3><ol>{$currentGroups}</ol>";
    if($output['public']==='true'){
        return $return;
    }else{
        return (current_user_can('manage_options')) ? $return : '';
    }
}

/**
 * This tag will output a list of groups required to access the current page
 * @attr public
 * @attr label
 */
function ctx_ps_tag_groups_required($atts){
    global $wpdb, $post;

    //Attribute defaults
    $output = shortcode_atts(
    array(
        'public' => 'false',
        'label' => __('Groups Required:'),
        'description' => __('To access this page, users must be a member of at least one group from each set of groups.'),
        'showempty' => 'true',
    ), $atts);

    $requiredGroups = ctx_ps_getprotection( $post->ID );

    //Set this var to count groups for current page
    $groupcount = 0;

    $return = "<div><h3>{$output['label']}</h3><p>{$output['description']}</p><ul>";

    foreach($requiredGroups as $pageGroup->ID => $pageGroups->groups){

        //List the page title
        $return .= "<li><strong>".get_the_title($pageGroup->ID)." (id:{$pageGroup->ID})</strong></li><ul>";

        foreach($pageGroups->groups as $curGrp->ID => $curGrp->title){
            ++$groupcount;
            $return .= "<li>".$curGrp->title." (id:{$curGrp->ID})</li>";
        }

        //If there were no groups attached, show that there's no access at that level
        if($groupcount===0 && $output['showempty']==='true'){
            $return .= "<li><em>".__("No groups attached")."</em></li>";
        }

        //Reset groupcount
        $groupcount = 0;

        $return .= '</ul>';
    }

    $return .= '</ul></div>';

    if($output['public']==='true'){
        return $return;
    }else{
        return (current_user_can('manage_options')) ? $return : '';
    }
}


/**
 * Updated the admin pages/posts columns with an icon if a page is protected. This adds
 * a "Protected" icon (lock) immediately after the "Comments" icon (word bubble).
 */
function ctx_ps_usability_showprotection($columns){

    //Peel of the date (set temp var, remove from array)
    $date = $columns['date'];
    unset($columns['date']);
    //Add new column
    $columns['protected'] = '<div class="vers"><img alt="Protected" src="'.plugins_url('protected.png',__FILE__).'" /></div>';
    //Add date back on (now at end of array);
    $columns['date'] = $date;

    return $columns;
}

/**
 * Generates the "lock" symbol for protected pages. See template.php -> display_page_row() for  more.
 */
function ctx_ps_usability_showprotection_content($column_name, $pageid){

    //wp_die($columnname.' GOOGLE '.$pageid);

    //Only do this if we've got the right column
    if($column_name==='protected'){
        //If page is protected, return lock icon
        if(ctx_ps_isprotected($pageid))
            echo '<img alt="Protected" title="Protected" src="'.plugins_url('protected-inline.png',__FILE__).'" />';
        //If this page isnt protected, but an ancestor is, return a lighter icon
        else if(ctx_ps_isprotected_section($pageid))
            echo '<img alt="Protected (inherited)" title="Inheriting an ancestors protection" src="'.plugins_url('protected-inline-descendant.png',__FILE__).'" />';
    }
}

 /**
 * Removes custom tables and options from the WP database.
 */
function ctx_ps_uninstall(){
    global $wpdb;

    //Name our tables
    $table_groups = $wpdb->prefix . "ps_groups";
    $table_group_relationships = $wpdb->prefix . "ps_group_relationships";
    $table_security = $wpdb->prefix . "ps_security";

    //Build our SQL scripts to delete the old db tables
    $sql_drop_groups = "DROP TABLE IF EXISTS `" . $table_groups . "`";
    $sql_drop_group_relationships = "DROP TABLE IF EXISTS `" . $table_group_relationships . "`";
    $sql_drop_security = "DROP TABLE IF EXISTS `" . $table_security . "`";

    //Run our cleanup queries
    $wpdb->show_errors();
    $wpdb->query($sql_drop_groups);
    $wpdb->query($sql_drop_group_relationships);
    $wpdb->query($sql_drop_security);

    //Remove our db version reference from options
    delete_option("contexture_ps_db_version");
    delete_option("contexture_ps_options");
}




?>
