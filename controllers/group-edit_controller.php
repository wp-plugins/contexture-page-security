<?php
/** @var wpdb */
global $wpdb;
/** @var CTXPS_Tables */
global $ctxpsdb;

if ( ! current_user_can( 'manage_options' ) ){
    wp_die( __( 'You do not have sufficient permissions to manage options for this site.','contexture-page-security' ) );
}

//Get info about the current group
$sqlGetGroupInfo = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}ps_groups` WHERE `ID` = '%s'",$_GET['groupid']);
$actionmessage = '';

//If we're submitting a change to the group...
if(!empty($_GET['action'])){
    switch($_GET['action']){
        case 'updtgrp':
            if(CTXPS_Queries::update_group($_GET['groupid'], $_GET['group_name'], $_GET['group_description']) === false){
                $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. Group Details could not be updated.','contexture-page-security').'</p></div>';
            } else {
                $linkBack = admin_url();
                $actionmessage = '<div id="message" class="updated below-h2"><p>'.__('Group details have been saved.','contexture-page-security').' <a href="'.$linkBack.'users.php?page=ps_groups">'.__('Return to group list','contexture-page-security').' &gt;&gt;</a></p></div>';
            }
            break;
        case 'addusr':
            //Make sure user exists in db
            if(!username_exists($_GET['add-username'])){
                $actionmessage = sprintf('<div class="error below-h2"><p>'.__('User &quot;%s&quot; does not exist.','contexture-page-security').'</p></div>',$_GET['add-username']);
            } else {
                //Get the user id from the username
                $AddUserId = get_user_id_from_string($_GET['add-username']);

                //Make sure user isnt already in the group
                if(CTXPS_Queries::check_membership($AddUserId, $_GET['groupid'])>0){
                    $actionmessage = '<div class="error below-h2"><p>'.__('User is already in this group.','contexture-page-security').'</p></div>';
                }else{
                    //Add user to group
                    if(CTXPS_Queries::add_membership($AddUserId, $_GET['groupid']) === false){
                        $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. User could not be added to the group.','contexture-page-security').'</p></div>';
                    } else {
                        $actionmessage = sprintf('<div id="message" class="updated below-h2"><p>'.__('User &quot;%s&quot; has been added to the group.','contexture-page-security').'</p></div>',$_GET['add-username']);
                    }
                }
            }
            break;
        case 'rmvusr':
            //Remove the user from the group
            if(CTXPS_Queries::delete_membership($_GET['usrid'], $_GET['groupid']) === false){
                $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. User could not be removed from group.','contexture-page-security').'</p></div>';
            } else {
                $actionmessage = sprintf('<div id="message" class="updated below-h2"><p>'.__('User &quot;%s&quot; was removed from the group.','contexture-page-security').'</p></div>',$_GET['usrname']);
            }
            break;
        default: break;
    }
}

$groupInfo = $wpdb->get_row($sqlGetGroupInfo);


//  if($_GET['page']==='ps_groups_edit') //What was this supposed to do?
?>