<?php

if ( ! current_user_can( 'delete_users' ) ){
    wp_die( __( 'You do not have sufficient permissions to manage options for this site.','contexture-page-security' ) );
}

$sqlGetGroupPageCount = $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}ps_security` WHERE `sec_access_id` = '%s' AND `sec_access_type` = 'group'",$_GET['groupid']);

$groupInfo = CTXPS_Queries::get_group_info($_GET['groupid']);
$groupPageCount = $wpdb->get_var($sqlGetGroupPageCount);

$actionmessage = '';
$actionmessage2 = '';

if(!empty($_GET['action']) && !empty($_GET['submit']) && $_GET['action'] == "delete" && $_GET['submit']=="Confirm Deletion"){

    $sqlDeleteGroupRelat = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ps_group_relationships` WHERE `grel_group_id` = '%s'",$_GET['groupid']);
    $sqlDeleteGroupSecur = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ps_security` WHERE `sec_access_id` = '%s' AND `sec_access_type` = 'group'",$_GET['groupid']);
    $sqlDeleteGroup = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ps_groups` WHERE `ID` = '%s'",$_GET['groupid']);

    $sqlstatus1 = $wpdb->query($sqlDeleteGroupRelat);
    $sqlstatus2 = $wpdb->query($sqlDeleteGroupSecur);
    $sqlstatus3 = $wpdb->query($sqlDeleteGroup);

    if(!$sqlstatus3){
        $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. The group was not fully deleted.','contexture-page-security').'</p></div>';
    } else {
        $actionmessage2 = '<div id="message" class="update below-h2"><p><strong>1</strong> '.__('group was deleted.','contexture-page-security').' <a href="'.admin_url().'users.php?page=ps_groups">'.__('View all groups','contexture-page-security').' &gt;&gt;</a></p></div>';
    }
}
?>