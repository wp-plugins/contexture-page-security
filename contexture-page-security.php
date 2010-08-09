<?php
/*
Plugin Name: Page Security by Contexture
Plugin URI: http://www.contextureintl.com/open-source-projects/contexture-page-security-for-wordpress/
Description: Allows admins to create user groups and restrict access to sections of the site by group.
Version: 1.0.5
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

//Handle Ajax when adding group to a page
add_action('wp_ajax_ctx_ps_add2page','ctx_ps_ajax_add_group_to_page');
add_action('wp_ajax_ctx_ps_removefrompage','ctx_ps_ajax_remove_group_from_page');
add_action('wp_ajax_ctx_ps_security_update','ctx_ps_ajax_security_update');

//Add basic security to all public "static" pages and posts
add_action('wp','ctx_ps_security_action');

//Add basic security to dynamically displayed posts (such as on Blog Posts Page, ie: Home)
add_filter( "the_posts","ctx_ps_security_filter_blog");

//Ensure that menus do not display protected pages (when using default menus only)
add_filter('get_pages','ctx_ps_security_filter_menu');
//Ensure that menus do not display protected pages (when using WP3 custom menus only)
add_filter('wp_get_nav_menu_items','ctx_ps_security_filter_menu_custom');



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

    if(!current_user_can('manage_options') && !is_home() && !is_category() && !is_tag()) {
        /**Groups that this user is a member of*/
        $useraccess = ctx_ps_get_usergroups($current_user->ID);
        /**Groups required to access this page*/
        $pagereqs = ctx_ps_getprotection($post->ID);

        //wp_die(print_r($pagereqs,true));
        
        if(!!$pagereqs){
            $secureallowed = ctx_ps_determine_access($useraccess,$pagereqs);

            //wp_die(print_r($secureallowed,true));

            if($secureallowed){
                //If we're allowed to access this page
                //$content = "<p>Access Granted</p><p>User access: ".print_r($useraccess,true).". Page Requirements: ".print_r($pagereqs,true)."</p>".$content;
            }else{
                //If we're NOT allowed to access this page
                /*
                if($current_user->ID == 0){
                    header('Location: /wp-login.php?ctx_ps_msg=nogroup');
                }else{
                    //Redirect to insufficient permissions page
                }*/
                if($current_user->ID == 0){
                    $blogurl = get_bloginfo('url');
                    //header('Location: /wp-login.php?ctx_ps_msg=nogroup');
                    wp_die('You do not have the appropriate group permissions to access this page. Please try <a href="'.$blogurl.'/wp-login.php">logging in</a> or contact an administrator for assistance.<a style="display:block;font-size:0.7em;padding-top:1em;" href="'.$blogurl.'">&lt;&lt; Go to home page</a>');
                }else{
                    wp_die('You do not have the appropriate group permissions to access this page. If you believe you <em>should</em> have access to this page, please contact an administrator for assistance.<a style="display:block;font-size:0.7em;padding-top:1em;" href="'.$blogurl.'">&lt;&lt; Go to home page</a>');
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

    if( !current_user_can('manage_options') && ( is_home() || is_category() || is_tag() || is_feed() ) ) {
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
 * in the menu and removes it if it's restricted to the current user.
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

    if( !current_user_can('manage_options') ) {
        foreach($content as $post->key => $post->value){

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

    if( !current_user_can('manage_options') ) {
        foreach($content as $post->key => $post->value){

            /**Groups that this user is a member of*/
            $useraccess = ctx_ps_get_usergroups($current_user->ID);
            /**Groups required to access this page*/
            $pagereqs = ctx_ps_getprotection($post->value->object_id);

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

    return $content;
}

/**
 * Adds the security box on the right side of the 'edit page' admin section
 */
function ctx_ps_admin_init(){
    add_action('admin_head', 'ctx_ps_admin_head_js');
    add_action('admin_head', 'ctx_ps_admin_head_css');
    //We MUST have a post id in the querystring in order for this to work
    if(isset($_GET['post']) && intval($_GET['post']) == $_GET['post']){
        add_meta_box('ctx_ps_sidebar_security', 'Restrict Access', 'ctx_ps_sidebar_security', 'page', 'side', 'low');
        add_meta_box('ctx_ps_sidebar_security', 'Restrict Access', 'ctx_ps_sidebar_security', 'post', 'side', 'low');
    }
}

/**
 * Adds some custom JS to the header, primarily AJAX
 */
function ctx_ps_admin_head_js(){
    ?>
    <script type="text/javascript">
        jQuery(function(){
            jQuery('#groups_available')
                .data('options',jQuery('#groups_available').html())
                .children('.detach')
                    .remove();
            jQuery('#add_group_page').click(function(){ctx_ps_add_group_to_page()});
            jQuery('#ctx_ps_protectmy').click(function(){ctx_ps_togglesecurity()});
        });

        //Will display a "Security Updated" message in the sidebar when successful change to security
        function showSavemsg(){
            if(jQuery('#ctx_ps_sidebar_security h3.hndle .ctx-ajax-status').length==0){
                jQuery('#ctx_ps_sidebar_security h3.hndle')
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
                            showSavemsg();
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
                                showSavemsg();
                            }
                        },'xml'
                    );
                }else{
                    //If user cancelled, re-check the box
                    jQuery('#ctx_ps_protectmy').attr('checked','checked');
                }
            }
            
        }

        //Adds a group to a page with security
        function ctx_ps_add_group_to_page(){
            var igroupid = parseInt(jQuery('#groups_available').val());
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

                            var grpsAvail = jQuery('#groups_available');

                            grpsAvail
                                .html(grpsAvail.data('options'))
                                .children('option[value="'+igroupid+'"]')
                                    .addClass('detach')
                                .end()
                                .data('options',grpsAvail.html())
                                .children('.detach')
                                    .remove();

                            showSavemsg();
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
                            
                           var grpsAvail = jQuery('#groups_available');
                            grpsAvail
                                .html(grpsAvail.data('options'))
                                .children('option[value="'+igroupid+'"]')
                                    .removeClass('detach')
                                .end()
                                .data('options',grpsAvail.html())
                                .children('.detach')
                                    .remove();
                            me.parent().remove();

                            showSavemsg();
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
        #groups_available .detach { display:none; visibility:hidden; }
    </style>
    <?php
}

/**
 * Handles ajax requests to add a group to a page
 */
function ctx_ps_ajax_add_group_to_page(){
    global $wpdb;
    
    $qryAddSec = "INSERT INTO {$wpdb->prefix}ps_security (
        sec_protect_type,
        sec_protect_id,
        sec_access_type,
        sec_access_id)
        VALUES (
        'page',
        '{$wpdb->escape($_GET['postid'])}',
        'group',
        '{$wpdb->escape($_GET['groupid'])}'
        )";
    $result = $wpdb->query($qryAddSec);

    if(!!$result){
        //See what groups are already attached to the page
        $currentGroups = array();
        $OutputHTML = '';
        $qryGetGroups = "
            SELECT *
            FROM {$wpdb->prefix}ps_security
            JOIN {$wpdb->prefix}ps_groups
                ON {$wpdb->prefix}ps_security.sec_access_id = {$wpdb->prefix}ps_groups.ID
            WHERE sec_protect_id = '{$wpdb->escape($_GET['postid'])}'
        ";
        foreach($wpdb->get_results($qryGetGroups) as $curGrp){
            $OutputHTML .= '<div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title">'.$curGrp->group_title.'</span><span class="removegrp" onclick="ctx_ps_remove_group_from_page('.$curGrp->sec_access_id.',jQuery(this))">remove</span></div>';
        }
        ctx_ps_ajax_response(array('code'=>0,'html'=>'<![CDATA['.$OutputHTML.']]>'));
    }
}

/**
 * Handles ajax requests to remove a group from a specified page
 */
function ctx_ps_ajax_remove_group_from_page(){
    global $wpdb;
    if($wpdb->query("DELETE FROM {$wpdb->prefix}ps_security WHERE sec_protect_id = {$_GET['postid']} AND sec_access_id = {$_GET['groupid']}") !== false){
        $response['code'] = '1';
        $response['message'] = 'Group removed';
    }else{
        $response['code'] = '0';
        $response['message'] = 'Query failed';
    }
    die(ctx_ps_ajax_response($response));
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
            AND group_system_id IS NULL
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

    if($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ps_groups WHERE group_title = '{$wpdb->escape($name)}'") == '0'){
        $current_user = wp_get_current_user();
        $sql_addgroup = "
            INSERT INTO {$wpdb->prefix}ps_groups
            (`group_title`,
            `group_description`,
            `group_creator`)
            VALUES
            ('{$wpdb->escape($name)}',
            '{$wpdb->escape($description)}',
            '{$current_user->ID}')
        ";
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
    add_submenu_page('users.php', 'Group Management', 'Groups', 'manage_options', 'ps_groups', 'ctx_ps_page_groups_view');
    add_submenu_page('users.php', 'Add a Group', 'Add Group', 'manage_options', 'ps_groups_add', 'ctx_ps_page_groups_add');
    add_submenu_page('', 'Edit Group', 'Edit Group', 'manage_options', 'ps_groups_edit', 'ctx_ps_page_groups_edit');
    add_submenu_page('', 'Delete Group', 'Delete Group', 'manage_options', 'ps_groups_delete', 'ctx_ps_page_groups_delete');
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
 * @return string Returns the html
 */
function ctx_ps_display_group_list($memberid=''){
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
    $countmembers = '';
    $alternatecss = ' class="alternate" ';
    $countusers = count_users();

    foreach($groups as $group){
        $countmembers = (!isset($group->group_system_id)) ? ctx_ps_count_members($group->ID) : $countusers['total_users'];
        $grouplink = (!isset($group->group_system_id)) 
                ? "<a href=\"{$linkBack}?page=ps_groups_edit&groupid={$group->ID}\"><strong>{$group->group_title}</strong></a><div class=\"row-actions\"><span class=\"edit\"><a href=\"?page=ps_groups_edit&groupid={$group->ID}\">Edit</a> | </span><span class=\"delete\"><a class=\"submitdelete\" href=\"?page=ps_groups_delete&groupid={$group->ID}\">Delete</a></span></div>" //User group
                : "<a id=\"$group->group_system_id\" class=\"ctx-ps-sysgroup\"><strong>{$group->group_title}</strong></a>"; //System group
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
 * @param int $GroupID The id of the group we need a member list for.
 * @return string Html to go inside tbody.
 */
function ctx_ps_display_member_list($GroupID){
    global $wpdb;



    $sqlGetMembers = "
        SELECT
            {$wpdb->users}.ID AS ID,
            {$wpdb->prefix}ps_group_relationships.id AS rel_id,
            {$wpdb->users}.user_login,
            {$wpdb->users}.user_email
        FROM `{$wpdb->prefix}ps_group_relationships`
        JOIN `{$wpdb->users}`
            ON {$wpdb->prefix}ps_group_relationships.grel_user_id = {$wpdb->users}.ID
        WHERE grel_group_id = '{$wpdb->escape($GroupID)}'";
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

    /*** ADD SMART GROUPS (AKA SYSTEM GROUPS ***/
    //Registered Users Smart Group
    if($current_user->ID != 0){
        //Get the ID for CPS01
        $newArray = ctx_ps_get_sysgroup('CPS01');
        //Add CPS01 to the current users permissions array
        $array += array($newArray->ID => $newArray->group_title);
    }

    return $array;
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
    $parentid = $wpdb->get_var("SELECT post_parent FROM {$wpdb->posts} WHERE `ID` = {$wpdb->escape($postid)}");
    /**Gets the ctx_ps_security data for this post (if it exists) - used to determine if this is the topmost secured page*/
    //$amisecure = get_post_meta($postid,'ctx_ps_security',true);

    //1. If I am secure, get my groups
    //if(!empty($amisecure)){
        //Get Group relationship info for this page from wp_ps_security, join wp_posts on postid
        $query = "
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
            WHERE {$wpdb->prefix}ps_security.sec_protect_id = '{$postid}'
        ";
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
    require_once 'group-new.php';
}

/**
 * Creates the "Groups" page
 */
function ctx_ps_page_groups_view(){
    global $wpdb;
    require_once 'groups.php';
}

/**
 * Creates the "Edit Group" page
 */
function ctx_ps_page_groups_edit(){
    global $wpdb;
    require_once 'group-edit.php';
}

/**
 * Creates the "Delete Group" page
 */
function ctx_ps_page_groups_delete(){
    global $wpdb;
    require_once 'group-delete.php';
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
    global $wpdb;

    if(!empty($_GET['post'])){

        //Create an array of groups that are already attached to the page
        $currentGroups = array();
        foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}ps_security JOIN {$wpdb->prefix}ps_groups ON {$wpdb->prefix}ps_security.sec_access_id = {$wpdb->prefix}ps_groups.ID WHERE sec_protect_id = '{$wpdb->escape($_GET['post'])}'") as $curGrp){
            $currentGroups[$curGrp->sec_access_id] = $curGrp->group_title;
        }

        $securityStatus = ctx_ps_getprotection( $_GET['post'] );
        //print_r($securityStatus);

        echo '<div class="new-admin-wp25">';
        echo '  <input type="hidden" id="ctx_ps_post_id" name="ctx_ps_post_id" value="'.$_GET['post'].'" />';
        echo '  <label for="ctx_ps_protectmy">';
        echo '      <input type="checkbox" id="ctx_ps_protectmy" name="ctx_ps_protectmy"';
        if ( !!$securityStatus )
            echo ' checked="checked" ';
        if ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') )
            echo ' disabled="disabled"';
        echo '/>';
        echo ' Protect this page and it\'s descendants';
        echo '  </label>';
        /**TODO: Add link to parent that has this setting enabled, if this isnt that page*/
        echo '  <div id="ctx_ps_pagegroupoptions" style="border-top:#EEEEEE 1px solid;margin-top:0.5em;';
        if ( !!$securityStatus )
            echo ' display:block ';
        echo '">';
        echo '      <h5>Available Groups</h5>';
        echo '      <select id="groups_available" name="groups_available">';
        echo            '<option value="0">-- Select -- </option>';
        //Loop through all groups in the db to populate the drop-down list
        foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}ps_groups ORDER BY `group_system_id` DESC, `group_title` ASC") as $group){
            //Generate the option HTML, hiding it if it's already in our $currentGroups array
            echo        '<option '.((!empty($currentGroups[$group->ID]))?'class="detach"':'').' value="'.$group->ID.'">'.$group->group_title.'</option>';
        }
        echo       '</select>';
        echo '      <input type="button" id="add_group_page" class="button-secondary action" value="Add" />';
        echo '      <h5>Allowed Groups</h5>';
        echo '      <div id="ctx-ps-page-group-list">';
        //Set this to 0, we are going to count the number of groups attached to this page next...
        $groupcount = 0;
        //Count the number of groups attached to this page (including inherited groups)
        if(!!$securityStatus)
            foreach($securityStatus as $securityGroups){ $groupcount = $groupcount+count($securityGroups); }
        //Show groups that are already added to this page
        if($groupcount===0){
            //Display this if we have no groups (inherited or otherwise)
            echo '          <div><em>No groups have been added yet.</em></div>';
        }else{
            foreach($securityStatus as $securityArray->pageid => $securityArray->grouparray){
                if($securityArray->pageid == $_GET['post']){
                    foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                        echo '          <div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><span class="removegrp" onclick="ctx_ps_remove_group_from_page('.$currentGroup->id.',jQuery(this))">remove</span></div>';
                    }
                }else{
                    foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                        echo '          <div class="ctx-ps-sidebar-group inherited">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><a class="viewgrp" target="_blank" href="post.php?post='.$securityArray->pageid.'&action=edit" >view</a></div>';
                    }
                }
            }
        }
        echo '      </div>';
        echo '  </div>';
        echo '</div>';
    }
}

/**
 * Adds the important tables to the wordpress database
 */
function ctx_ps_activate(){
    global $wpdb;

    $linkBack = admin_url();

    //Ensure that we're using PHP5 (plugin has reported problems with PHP4)
    if (version_compare(PHP_VERSION, '5', '<')) {
        deactivate_plugins(__FILE__);
        wp_die("<span style=\"color:red;font-weight:bold;\">Missing Requirement: </span> Page Security by Contexture requires PHP5 or higher. Your server is running ".PHP_VERSION.". Please contact your hosting service about enabling PHP5 support. <a href=\"{$linkBack}plugins.php\"> Return to plugin page &gt;&gt;</a>");
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
        $wpdb->query("INSERT INTO `$table_groups` (`group_title`,`group_description`,`group_creator`,`group_system_id`) VALUES ('Registered Users','This group automatically applies to all authenticated users.','0','CPS01')");
    }
}

/**
 *
 * @param <type> $content
 * @return <type> 
 */
function ctx_ps_filter_checksecurity($content){
    
    return $content;
}


/**
 * 
 */
function ctx_ps_generate_usergroupslist(){
    require_once("user-edit-groups.php");
    //echo "<h3 style=\"color:red;font-weight:bold;\">Hello world!!!</h3>";
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

    //Build our SQL scripts to create the new db tables
    $sql_drop_groups = "DROP TABLE IF EXISTS `" . $table_groups . "`";
    $sql_drop_group_relationships = "DROP TABLE IF EXISTS `" . $table_group_relationships . "`";
    $sql_drop_security = "DROP TABLE IF EXISTS `" . $table_security . "`";

    //Use dbDelta to create the tables
    $wpdb->show_errors();
    $wpdb->query($sql_drop_groups);
    $wpdb->query($sql_drop_group_relationships);
    $wpdb->query($sql_drop_security);

    //Record what version of the db we're using
    delete_option("contexture_ps_db_version");

}




?>