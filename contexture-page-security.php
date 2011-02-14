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
define('CTXPSPATH',dirname(__FILE__));
define('CTXPSDIR',basename(CTXPSPATH));
define('CTXPSURL',plugin_dir_url(__FILE__));
global $wpdb, $ctxpsdb;

/************************** LOAD WP DEPENDENCIES ******************************/
require_once(ABSPATH . WPINC . '/registration.php');
require_once(ABSPATH . WPINC . '/ms-functions.php');

/**************************** LOAD CORE FILES *********************************/
require_once 'core/model.php';          //Model instance ($ctxpsdb)
require_once 'core/model_queries.php';  //Stored db queries
require_once 'core/helpers.php';        //Common, reusable classes, methods, functions
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
add_action('init','ctx_ps_localization');

//Handle Ajax for Edit Page/Post page
add_action('wp_ajax_ctx_ps_add2page','CTXPS_Ajax::add_group_to_page');
add_action('wp_ajax_ctx_ps_removefrompage','CTXPS_Ajax::remove_group_from_page');
add_action('wp_ajax_ctx_ps_security_update','CTXPS_Ajax::update_security');

//Handle Ajax for Edit User page
add_action('wp_ajax_ctx_ps_add2user','CTXPS_Ajax::add_group_to_user');
add_action('wp_ajax_ctx_ps_removefromuser','CTXPS_Ajax::remove_group_from_user');
add_action('wp_ajax_ctx_ps_updatemember','CTXPS_Ajax::update_membership');

//Add basic security to all public "static" pages and posts [highest priority]
add_action('wp','CTXPS_Security::protect_content',1);

//Add basic security to dynamically displayed posts (such as on Blog Posts Page, ie: Home) [highest priority]
add_filter( 'the_posts','CTXPS_Security::filter_loops',1);

//Ensure that menus do not display protected pages (when using default menus only) [highest priority]
add_filter('get_pages','CTXPS_Security::filter_auto_menus',1);
//Ensure that menus do not display protected pages (when using WP3 custom menus only) [highest priority]
add_filter('wp_get_nav_menu_items','CTXPS_Security::filter_custom_menus',1);

//Add shortcodes!
add_shortcode('groups_attached', 'ctx_ps_tag_groups_attached'); //Current page permissions only
add_shortcode('groups_required', 'ctx_ps_tag_groups_required'); //Complete permissions for current page

//Update the edit.php pages & posts lists to include a "Protected" column
add_filter('manage_pages_columns','ctx_ps_usability_showprotection');
add_filter('manage_posts_columns','ctx_ps_usability_showprotection');
add_action('manage_pages_custom_column','ctx_ps_usability_showprotection_content',10,2); //Priority 10, Takes 2 args (use default priority only so we can specify args)
add_action('manage_posts_custom_column','ctx_ps_usability_showprotection_content',10,2); //Priority 10, Takes 2 args (use default priority only so we can specify args)

//Modify the global help array so we can add extra help text to default WP pages
add_action('admin_head', 'CTXPS_App::help_init');

/*********************** FUNCTIONS **********************************/


/**
 * Creates a new group
 *
 * @global wpdb $wpdb
 * @param string $name A short, meaningful name for the group
 * @param string $description A more detailed description for the group
 * @return <type>
 */
function ctx_ps_create_group($name, $description){
    global $wpdb;

    if(!CTXPS_Queries::check_group_exists($name)){
        $current_user = wp_get_current_user();

        if(CTXPS_Queries::add_group($name, $description, $current_user->ID) !== FALSE){
            return '<div id="message" class="updated"><p>'.__('New group created','contexture-page-security').'</p></div>';
        }else{
            return '<div id="message" class="error below-h2"><p>'.__('Unable to create group. There was an unspecified system error.','contexture-page-security').'</p></div>';
        }
    } else {
        return '<div id="message" class="error below-h2"><p>'.__('Unable to create group. A group with that name already exists.','contexture-page-security').'</p></div>';
    }
}



/**
 * Generates html for use in the grouptable tbody element that lists all current
 * groups in the database.
 *
 * @global wpdb $wpdb
 *
 * @param string $memberid If set, only shows groups that have a specific user as a member
 * @param string $forpage Whether to generate html for the 'groups' page or the 'users' page (default 'groups')
 * @param bool $showactions If set to false, will not show the actions (default true)
 *
 * @return string Returns the html
 */
function ctx_ps_display_group_list($memberid='',$forpage='groups',$showactions=true){
    global $wpdb;

    $linkBack = admin_url('users.php');

    $groups = CTXPS_Queries::get_groups($memberid);

    $html = '';
    $htmlactions = '';
    $countmembers = '';
    $alternatecss = ' class="alternate" ';
    $countusers = count_users();

    foreach($groups as $group){
        $countmembers = (!isset($group->group_system_id)) ? CTXPS_Queries::count_members($group->ID) : $countusers['total_users'];

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
 * Returns html for tbody element of group member list.
 *
 * @global wpdb $wpdb
 *
 * @param int $group_id The id of the group we need a member list for.
 * @return string Html to go inside tbody.
 */
function ctx_ps_display_member_list($group_id){
    global $wpdb;

    $members = CTXPS_Queries::get_group_members($group_id);

    $html = '';
    $countmembers = '';
    $alternatecss = ' class="alternate" ';

    foreach($members as $member){
        $fname = get_user_meta($member->ID, 'first_name', true);
        $lname = get_user_meta($member->ID, 'last_name', true);
        $rawdate = strtotime($member->grel_expires);
        $jj = (!empty($rawdate)) ? date('d',$rawdate) : ''; //Day
        $mm = (!empty($rawdate)) ? date('m',$rawdate) : ''; //Month
        $aa = (!empty($rawdate)) ? date('Y',$rawdate) : ''; //Year
        if(!empty($rawdate) && $rawdate < time()){
            $displaydate = 'Expired';
        }else{
            $displaydate = (empty($rawdate) ? 'Never' : sprintf('%s-%s-%s',$mm,$jj,$aa));
        }

        $html .= sprintf('
        <tr id="user-%1$s" %2$s>
            <td class="username column-username">
                <a href="%8$suser-edit.php?user_id=%1$s&wp_httpd_referer=%9$s"><strong>%3$s</strong></a>
                <div class="row-actions">
                    <span class="membership"><a href="#" class="editmembership" title="Change membership options">'.__('Membership','contexture-page-security').'</a> | </span>
                    <span class="trash"><a class="row-actions" href="%8$s?page=ps_groups_edit&groupid=%6$s&action=rmvusr&usrid=%1$s&relid=%7$s&usrname=%3$s">'.__('Unenroll','contexture-page-security').'</a> | </span>
                    <span class="view"><a href="%8$suser-edit.php?user_id=%1$s&wp_httpd_referer=%9$s" title="View User">'.__('View','contexture-page-security').'</a></span>
                </div>
                <div id="inline_%1$s" class="hidden">
                    <div class="username">%3$s</div>
                    <div class="jj">%11$s</div>
                    <div class="mm">%12$s</div>
                    <div class="aa">%13$s</div>
                    <div class="grel">%7$s</div>
                </div>
            </td>
            <td class="name column-name">%4$s</td>
            <td class="email column-email"><a href="mailto:%5$s">%5$s</a></td>
            <td class="expires column-expires">%10$s</td>
        </tr>',
            /*1*/$member->ID,
            /*2*/$alternatecss,
            /*3*/$member->user_login,
            /*4*/$fname.' '.$lname,
            /*5*/$member->user_email,
            /*6*/$_GET['groupid'],
            /*7*/$member->grel_id,
            /*8*/admin_url(),
            /*9*/admin_url('users.php?page=ps_groups_edit&groupid='.$_GET['groupid']),
            /*10*/$displaydate,
            /*11*/$jj,
            /*12*/$mm,
            /*13*/$aa
            );

        //Alternate css style for odd-numbered rows
        $alternatecss = ($alternatecss != '') ? '' : ' class="alternate" ';
    }
    return $html;
}


/**
 * Returns html for tbody element of group-page list.
 *
 * @global wpdb $wpdb
 *
 * @param int $group_id The id of the group we need a member list for.
 * @return string Html to go inside tbody.
 */
function ctx_ps_display_page_list($group_id){
    global $wpdb;

    //$sql = sprintf('SELECT * FROM `%1$s` JOIN `%2$s` ON `%1$s`.`sec_protect_id` = `%2$s`.`ID` WHERE `sec_access_id`=\'%3$s\'', $wpdb->prefix.'ps_security', $wpdb->posts, $group_id);

    //$pagelist = $wpdb->get_results($sql);
    $pagelist = CTXPS_Queries::get_content_by_group($group_id);

    $html = '';
    $countpages = '';
    $alternatecss = ' class="alternate" ';

    /**TODO: Must detect if this page is directly protected, or inherrited.*/

    foreach($pagelist as $page){
        $page_title = $page->post_title;
        $html .= sprintf('
        <tr id="page-%1$s" %2$s>
            <td class="post-title page-title column-title">
                <strong><a href="%3$s">%4$s</a></strong>
                <div class="row-actions">
                    <span class="edit"><a href="%8$spost.php?post=%1$s&action=edit" title="Edit this page">'.__('Edit','contexture-page-security').'</a> | </span>
                    <span class="trash"><a href="#" onclick="ctx_ps_remove_page_from_group(%1$s,jQuery(this))" title="Remove current group from this page\'s security">'.__('Remove','contexture-page-security').'</a> | </span>
                    <span class="view"><a href="%7$s" title="View the page">'.__('View','contexture-page-security').'</a></span>
                </div>
            </td>
            <td class="protected column-protected">%5$s</td>
            <td class="type column-type">%6$s</td>
        </tr>',
            /*1*/$page->sec_protect_id,
            /*2*/$alternatecss,
            /*3*/admin_url('post.php?post='.$page->sec_protect_id.'&action=edit'),
            /*4*/$page_title,
            /*5*/'',
            /*6*/$page->post_type,
            /*7*/get_permalink($page->sec_protect_id),
            /*8*/admin_url()
        );

        //Alternate css style for odd-numbered rows
        $alternatecss = ($alternatecss != '') ? '' : ' class="alternate" ';
    }
    return $html;//'<td colspan="2">There are pages attached, but this feature is not yet working.</td>';
}

/**
 * This function will check the security for the specified page and all parent pages.
 * If security exists, a multi-dimensional array will be returned following the format
 * array( pageid=>array(groupid=>groupname) ), with the first item being the current
 * page and additional items being parents. If no security is present for any ancestor
 * then the function will return false.
 *
 * @global wpdb $wpdb
 *
 * @param int $post_id The id of the post to get permissions for.
 * @return mixed Returns an array with all the required permissions to access this page. If no security is present, returns false.
 */
function ctx_ps_getprotection($post_id){
    //If this branch isn't protected, just stop now and save all that processing power
    if (!CTXPS_Queries::check_section_protection($post_id)){
        return false;
    }

    //If we're still going, then it means something above us is protected, so lets get the list of permissions
    global $wpdb;
    $array = array();
    $grouparray = array();
    /**Gets the parent id of the current page/post*/
    $parent_id = $wpdb->get_var($wpdb->prepare("SELECT post_parent FROM {$wpdb->posts} WHERE `ID` = '%s'",$post_id));
    /**Gets the ctx_ps_security data for this post (if it exists) - used to determine if this is the topmost secured page*/
    //$amisecure = get_post_meta($postid,'ctx_ps_security',true);

    //1. If I am secure, get my groups
    //if(!empty($amisecure)){
        //Get Group relationship info for this page from wp_ps_security, join wp_posts on postid
        $groups = CTXPS_Queries::get_groups_by_post($post_id, true);

        //If 0 results, dont do anything. Otherwise...
        if(!empty($groups)){
            foreach($groups as $group){
                $grouparray[$group->group_id] = $group->group_title;
            }
        }
    //}
    //Add an item to the array. 'pageid'=>array('groupid','groupname')
    $array[(string)$post_id] = $grouparray;
    unset($grouparray);
    //2. If I have a parent, recurse
        //Using our earlier results, check post_parent. If it's != 0 then recurse this function, adding the return value to $array
        if($parent_id != '0'){
            //$recursedArray = ctx_ps_getprotection($parentid);
            //$array = array_merge($array,$recursedArray);
            $parentArray = ctx_ps_getprotection($parent_id);
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
 *
 * @global wpdb $wpdb
 */
function ctx_ps_page_groups_add(){
    global $wpdb;
    require_once 'views/group-new.php';
}


/**
 * Creates the "Delete Group" page
 *
 * @global wpdb $wpdb
 */
function ctx_ps_page_groups_delete(){
    global $wpdb;
    require_once 'views/group-delete.php';
}


/**
 * Creates the "Edit Group" page
 *
 * @global wpdb $wpdb
 */
function ctx_ps_page_groups_edit(){
    global $wpdb;
    require_once 'views/group-edit.php';
}


/**
 * Creates the "Groups" page
 *
 * @global wpdb $wpdb
 */
function ctx_ps_page_groups_view(){
    global $wpdb;
    require_once 'views/groups.php';
}


/**
 * Creates the "Settings" page
 *
 * @global wpdb $wpdb
 */
function ctx_ps_page_options(){
    require_once 'views/options.php';
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
 * Loads localized language files, if available
 */
function ctx_ps_localization(){
   if (function_exists('load_plugin_textdomain')) {
      load_plugin_textdomain('contexture-page-security', false, dirname(plugin_basename(__FILE__)).'/languages' );
   }
}


/**
 * Creates the "Restrict Access" sidebar for the Edit Post screen
 *
 * @global wpdb $wpdb
 */
function ctx_ps_sidebar_security(){
    global $wpdb, $post;

    //We MUST have a post id in the querystring in order for this to work (ie: this wont appear for the "create new" pages, as the page doesnt exist yet)
    if(!empty($_GET['post']) && intval($_GET['post']) == $_GET['post']){

        //Create an array of groups that are already attached to the page
        $currentGroups = array();
        foreach(CTXPS_Queries::page_groups($_GET['post']) as $curGrp){
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
            echo __(' Protect this page and it\'s descendants','contexture-page-security');
            echo '  </label>';
            /** TODO: Add "Use as Access Denied page" option */

            //If the checkbox is disabled, give admin the option to go straight to the parent
            if ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') ){
                echo '<a href="'.admin_url().'post.php?post='.$post->post_parent.'&action=edit" style="display:block;font-size:0.75em;text-align:left;padding-left:20px;">',__('Edit Parent','contexture-page-security'),'</a>';
            }
            //Start on "Available Groups" select box
            echo '  <div id="ctx_ps_pagegroupoptions" style="border-top:#EEEEEE 1px solid;margin-top:0.5em;';
            if ( !!$securityStatus )
                echo ' display:block ';
            echo '">';
            echo    sprintf('<h5>%1$s <a href="%3$s" title="%2$s" style="text-decoration:none;">+</a></h5>',__('Available Groups','contexture-page-security'),__('New Group','contexture-page-security'),admin_url('users.php?page=ps_groups_add'));
            echo '      <select id="groups-available" name="groups-available">';
            echo '<option value="0">-- '.__('Select','contexture-page-security').' -- </option>';
            //Loop through all groups in the db to populate the drop-down list
            foreach(CTXPS_Queries::get_groups() as $group){
                //Generate the option HTML, hiding it if it's already in our $currentGroups array
                echo        '<option '.((!empty($currentGroups[$group->ID]))?'class="detach"':'').' value="'.$group->ID.'">'.$group->group_title.'</option>';
            }
            echo       '</select>';
            echo       '<input type="button" id="add_group_page" class="button-secondary action" value="',__('Add','contexture-page-security'),'" />';
            echo       sprintf('<h5>%s</h5>',__('Allowed Groups','contexture-page-security'));
            echo       '<div id="ctx-ps-page-group-list">';
            //Set this to 0, we are going to count the number of groups attached to this page next...
            $groupcount = 0;
            //Count the number of groups attached to this page (including inherited groups)
            if(!!$securityStatus)
                foreach($securityStatus as $securityGroups){ $groupcount = $groupcount+count($securityGroups); }
            //Show groups that are already added to this page
            if($groupcount===0){
                //Display this if we have no groups (inherited or otherwise)
                echo '<div><em>'.__('No groups have been added yet.','contexture-page-security').'</em></div>';
            }else{
                foreach($securityStatus as $securityArray->pageid => $securityArray->grouparray){
                    if($securityArray->pageid == $_GET['post']){
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            echo '<div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title"><a href="'.admin_url('/users.php?page=ps_groups_edit&groupid='.$currentGroup->id).'">'.$currentGroup->name.'</a></span><span class="removegrp" onclick="ctx_ps_remove_group_from_page('.$currentGroup->id.',jQuery(this))">'.__('remove','contexture-page-security').'</span></div>';
                        }
                    }else{
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            echo '<div class="ctx-ps-sidebar-group inherited">&bull; <span class="ctx-ps-sidebar-group-title"><a href="'.admin_url('/users.php?page=ps_groups_edit&groupid='.$currentGroup->id).'">'.$currentGroup->name.'</a></span><a class="viewgrp" target="_blank" href="'.admin_url('post.php?post='.$securityArray->pageid.'&action=edit').'" >'.__('ancestor','contexture-page-security').'</a></div>';
                        }
                    }
                }
            }
            echo '      </div>';
            echo '  </div>';
        }else{
            echo sprintf(__('<p>This is currently an Access Denied page. You cannot restrict it.</p><p><a href="%s">View Security Settings</a></p>','contexture-page-security'),admin_url('options-general.php?page=ps_manage_opts'));
        }
        echo '</div>';
        /***END BUILDING HTML****************************/
    }else{
        echo '<div class="new-admin-wp25">';
        echo __('<p>You need to publish before you can update security settings.</p>','contexture-page-security');
        echo '</div>';
    }
}

/**
 * This tag will output a list of groups attached to the current page.
 *
 * @global wpdb $wpdb
 * @global array $post
 */
function ctx_ps_tag_groups_attached($atts){
    global $wpdb, $post;

    //Attribute defaults
    $output = shortcode_atts(
    array(
        'public' => 'false',
        'label' => __('Groups attached to this page:','contexture-page-security'),
    ), $atts);

    //Create an array of groups that are already attached to the page
    $currentGroups = '';
    foreach(CTXPS_Queries::get_post_groups($post->ID) as $curGrp){
        $currentGroups .= "<li>".$curGrp->group_title." (id:{$curGrp->sec_access_id})</li>";
    }
    $currentGroups = (empty($currentGroups)) ? '<li><em>'.__('No groups attached.','contexture-page-security').'</em></li>' : $currentGroups;
    $return = "<div class=\"ctx-ps-groupvis\"><h3>{$output['label']}</h3><ol>{$currentGroups}</ol></div>";
    if($output['public']==='true'){
        return $return;
    }else{
        return (current_user_can('manage_options')) ? $return : '';
    }
}

/**
 * This tag will output a list of groups required to access the current page
 *
 * @global wpdb $wpdb
 * @global array $post
 * @attr public
 * @attr label
 */
function ctx_ps_tag_groups_required($atts){
    global $wpdb, $post;

    //Attribute defaults
    $output = shortcode_atts(
    array(
        'public' => 'false',
        'label' => __('Groups Required:','contexture-page-security'),
        'description' => __('To access this page, users must be a member of at least one group from each set of groups.','contexture-page-security'),
        'showempty' => 'true',
    ), $atts);

    $requiredGroups = ctx_ps_getprotection( $post->ID );

    //Set this var to count groups for current page
    $groupcount = 0;

    $return = "<div class=\"ctx-ps-groupvis\"><h3>{$output['label']}</h3><p>{$output['description']}</p><ul>";

    foreach($requiredGroups as $pageGroup->ID => $pageGroups->groups){

        //List the page title
        $return .= "<li><strong>".get_the_title($pageGroup->ID)." (id:{$pageGroup->ID})</strong><ul>";

        foreach($pageGroups->groups as $curGrp->ID => $curGrp->title){
            ++$groupcount;
            $return .= "<li>".$curGrp->title." (id:{$curGrp->ID})</li>";
        }

        //If there were no groups attached, show that there's no access at that level
        if(empty($groupcount) && $output['showempty']==='true'){
            $return .= "<li><em>".__('No groups attached','contexture-page-security')."</em></li>";
        }

        //Reset groupcount
        $groupcount = 0;

        $return .= '</ul></li>';
    }

    $return .= '</ul></div>';

    if($output['public']==='true'){
        return $return;
    }else{
        return (current_user_can('manage_options')) ? $return : '';
    }
}


/**
 * Update the admin pages/posts columns with an icon if a page is protected. This adds
 * a "Protected" icon (lock) immediately after the "Comments" icon (word bubble).
 */
function ctx_ps_usability_showprotection($columns){

    //Peel of the date (set temp var, remove from array)
    $date = $columns['date'];
    unset($columns['date']);
    //Add new column
    $columns['protected'] = '<div class="vers"><img alt="Protected" src="'.plugins_url('images/protected.png',__FILE__).'" /></div>';
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
            echo '<img alt="Protected" title="Protected" src="'.plugins_url('images/protected-inline.png',__FILE__).'" />';
        //If this page isnt protected, but an ancestor is, return a lighter icon
        else if(CTXPS_Queries::check_section_protection($pageid))
            echo '<img alt="Protected (inherited)" title="Inheriting an ancestors protection" src="'.plugins_url('images/protected-inline-descendant.png',__FILE__).'" />';
    }
}


//Load theme functions
require_once 'controllers/theme-functions.php';

?>