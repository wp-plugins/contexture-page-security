<?php

/*************************** THEME FUNCTIONS *****************************
 * These are generally "friendlier", more-generic versions of basic plugin 
 * functions that theme designers can use to more easily customize PSC.
 *************************************************************************/

if(!function_exists('psc_add_user_to_group')){
/**
 * Can be used by developers to add a user to a group programatically.
 * 
 * @param int $user_id The id of the user to add to a group.
 * @param int $group_id The id of the group to add the user to.
 * @return bool Returns true if user was successfully added to a group.
 */
function psc_add_user_to_group($user_id,$group_id){
    global $wpdb;

    //If either value isnt an int, fail
    if(!is_numeric($user_id) || !is_numeric($group_id)){
        return false;
    }
    
    //Make sure user exists in db
    $UserInfo = (int)$wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE {$wpdb->users}.ID = '%s'",
            $user_id
        )
    );

    //If this user doesn't exist
    if($UserInfo === 0){
        return false;
    } else {
        //Add user to group
        $sqlUpdateGroup = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}ps_group_relationships` (grel_group_id, grel_user_id) VALUES ('%s','%s');",
            $group_id,
            $user_id
        );
        if($wpdb->query($sqlUpdateGroup) === false){
            return false;
        } else {
            return true;
        }
    }

}
}

if(!function_exists('psc_remove_user_from_group')){
/**
 * Removes a user from a group.
 *
 * @param int $user_id
 * @param int $group_id
 * @return bool Returns true if the query succeeded. False if it failed. 
 */
function psc_remove_user_from_group($user_id,$group_id){
    global $wpdb;
    //If either value isnt an int, fail
    if(!is_numeric($user_id) || !is_numeric($group_id)){
        return false;
    }
    $sqlRemoveUserRel = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ps_group_relationships` WHERE grel_group_id = '%s' AND grel_user_id = '%s';",
            $group_id,
            $user_id);
    return $wpdb->query($sqlRemoveUserRel) == 0;
}
}

if(!function_exists('psc_get_groups')){
/**
 * Gets an assoc array with all the groups.
 * 
 * @param int $user_id Optional. Include if you want groups a user is attached to. Leave blank for all groups.
 * @return array Associative array with groups. Format: Group_ID => Group_Title
 */
function psc_get_groups($user_id=null){
    global $wpdb, $current_user;
    $array = array();
    
    //Determine if we're looking up groups for a user, or all groups
    if(is_numeric($userid)){
        $groups = $wpdb->get_results("
            SELECT * FROM `{$wpdb->prefix}ps_group_relationships`
            JOIN `{$wpdb->prefix}ps_groups`
                ON {$wpdb->prefix}ps_group_relationships.grel_group_id = {$wpdb->prefix}ps_groups.ID
            WHERE {$wpdb->prefix}ps_group_relationships.grel_user_id = '{$userid}'
        ");
    }else{
        $groups = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}ps_groups`");
    }

    //We only need an ID and a name as a key/value..., so we'll build a new array
    foreach($groups as $group){
        $array += array($group->ID => $group->group_title);
    }

    //If multisite is enabled we can better support it...
    if(function_exists('is_user_member_of_blog')){
        //Make sure user is a member of this blog (in addition to being logged in)
        $multisitemember = is_user_member_of_blog($current_user->ID);
    }else{
        //Assume user is member of blog
        $multisitemember = true;
    }

    /*** ADD SMART GROUPS (AKA SYSTEM GROUPS) ***/
    //Registered Users Smart Group
    if($current_user->ID != 0 && $multisitemember){
        //Get the ID for CPS01 (added in 1.1, so cant assume 1)
        $newArray = ctx_ps_get_sysgroup('CPS01');
        //Add CPS01 to the current users permissions array
        $array += array($newArray->ID => $newArray->group_title);
    }

    return $array;
}
}

if(!function_exists('psc_has_protection')){
/**
 * Recursively checks security for this page/post and it's ancestors. Returns true
 * if any of them are protected or false if none of them are protected.
 * 
 * @param int $post_id The id of the page or post to check.
 * @return bool If this page or it's ancestors has the "protected page" flag
 */
function psc_has_protection($post_id){
    global $wpdb;
    //Fail if the post id isn't numeric
    if(!is_numeric($post_id)){ return false; }
    
    //Check permissions for current page
    if(get_post_meta($post_id,'ctx_ps_security')){
        return true;
    } else {
        //If this isn't protected, lets see if there's a parent...
        $parent_id = $wpdb->get_var(sprintf('SELECT post_parent FROM $wpdb->posts WHERE `ID` = %s',$post_id));
        //If we have a parent, repeat this check with the parent.
        if ($parent_id != 0)
            return ctx_ps_isprotected_section($parent_id);
        else
            return false;
    }
}
}

?>
