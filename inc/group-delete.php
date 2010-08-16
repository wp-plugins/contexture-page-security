<?php

if ( ! current_user_can( 'manage_options' ) )
    wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$sqlGetGroupInfo = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}ps_groups` WHERE `ID` = '%s'",$_GET['groupid']);
$sqlGetGroupPageCount = $wpdb->prepare("SELECT COUNT(*) FROM `{$wpdb->prefix}ps_security` WHERE `sec_access_id` = '%s' AND `sec_access_type` = 'group'",$_GET['groupid']);

$groupInfo = $wpdb->get_row($sqlGetGroupInfo);
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
        $actionmessage = '<div class="error below-h2"><p>An error occurred. The group was not fully deleted.</p></div>';
    } else {
        $actionmessage2 = '<div id="message" class="update below-h2"><p><strong>1</strong> group was deleted. <a href="users.php?page=ps_groups">View all groups &gt;&gt;</a></p></div>';
    }
}
?>

    <div class="wrap">
        <div class="icon32" id="icon-users"><br/></div>
        <h2>Delete Group</h2>
        <?php echo $actionmessage; ?>
        <?php if(!empty($actionmessage2)){ echo $actionmessage2; }else{ ?>
        <?php 
            if (empty($groupInfo->group_title)){ //Group doesnt exist error
                echo '<div id="message" class="error below-h2"><p>A group with that id does not exist. <a href="users.php?page=ps_groups">View all groups &gt;&gt;</a></p></div>';
            }else if(isset($groupInfo->group_system_id)){ //Group is a system group error (cannot edit)
                echo '<div id="message" class="error below-h2"><p>System groups cannot be deleted. <a href="users.php?page=ps_groups">View all groups &gt;&gt;</a></p></div>';
            }else{ 
        ?>
        <form id="deletegroup" name="deletegroup" method="get" action="">
            <input type="hidden" name="page" value="ps_groups_delete"/>
            <input type="hidden" name="groupid" value="<?php echo $_GET['groupid']; ?>" />
            <input type="hidden" name="action" value="delete" />
            <p>You are about to delete the group <strong><?php echo $groupInfo->group_title; ?></strong>.</p>
            <p>Deleting this group will affect <strong><?php echo ctx_ps_count_members($groupInfo->ID); ?></strong> users and <strong><?php echo $groupPageCount; ?></strong> pages/posts. Are you sure you want to continue?</p>
            <?php wp_nonce_field('delete-group'); ?>
            <p class="submit">
                <input class="button-secondary" type="submit" value="Confirm Deletion" name="submit"/>
            </p>
        </form>
        <?php

            } //ENDS : if (empty($groupInfo->group_title))
        } //ENDS : if (!empty($actionmessage2))... else ...
        ?>
    </div>