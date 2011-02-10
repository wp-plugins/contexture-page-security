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
            $sqlUpdateGroup = $wpdb->prepare("UPDATE `{$wpdb->prefix}ps_groups` SET group_title = '%s', group_description = '%s' WHERE ID = '%s';",
                    $_GET['group_name'],
                    $_GET['group_description'],
                    $_GET['groupid']);
            if($wpdb->query($sqlUpdateGroup) === false){
                $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. Group Details could not be updated.','contexture-page-security').'</p></div>';
            } else {
                $linkBack = admin_url();
                $actionmessage = '<div id="message" class="updated below-h2"><p>'.__('Group details have been saved.','contexture-page-security').' <a href="'.$linkBack.'users.php?page=ps_groups">'.__('Return to group list','contexture-page-security').' &gt;&gt;</a></p></div>';
            }
            break;
        case 'addusr':
            //Make sure user exists in db
            $sqlCheckUserExists = $wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE user_login = '%s'",$_GET['add-username']);
            $UserInfo = $wpdb->query($sqlCheckUserExists);
            if($UserInfo == 0){
                $actionmessage = sprintf('<div class="error below-h2"><p>'.__('User &quot;%s&quot; does not exist.','contexture-page-security').'</p></div>',$_GET['add-username']);
            } else {

                //Make sure user isnt already in the group
                $UserInGroup = $wpdb->prepare('SELECT COUNT(*) FROM `'.$wpdb->prefix.'ps_group_relationships` WHERE grel_group_id=%s AND grel_user_id=%s',
                        $_GET['groupid'],
                        $wpdb->get_var($sqlCheckUserExists,0,0));

                //wp_die($UserInGroup.' <br/><br/> '.$wpdb->get_var($UserInGroup));

                if($wpdb->get_var($UserInGroup)>0){
                    $actionmessage = '<div class="error below-h2"><p>'.__('User is already in this group.','contexture-page-security').'</p></div>';
                }else{
                    //Add user to group
                    $sqlUpdateGroup = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}ps_group_relationships` (grel_group_id, grel_user_id) VALUES ('%s','%s');",$_GET['groupid'],$wpdb->get_var($sqlCheckUserExists,0,0));
                    if($wpdb->query($sqlUpdateGroup) === false){
                        $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. User could not be added to the group.','contexture-page-security').'</p></div>';
                    } else {
                        $actionmessage = sprintf('<div id="message" class="updated below-h2"><p>'.__('User &quot;%s&quot; has been added to the group.','contexture-page-security').'</p></div>',$_GET['add-username']);
                    }
                }
            }
            break;
        case 'rmvusr':
            $sqlRemoveUserRel = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ps_group_relationships` WHERE ID = '%s' AND grel_group_id = '%s' AND grel_user_id = '%s';",$_GET['relid'],$_GET['groupid'],$_GET['usrid']);
            if($wpdb->query($sqlRemoveUserRel) == 0){
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