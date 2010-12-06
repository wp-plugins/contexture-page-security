<?php

if ( ! current_user_can( 'manage_options' ) )
    wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$sqlGetGroupInfo = $wpdb->prepare("SELECT * FROM `{$wpdb->prefix}ps_groups` WHERE `ID` = '%s'",$_GET['groupid']);
$actionmessage = '';

if(!empty($_GET['action'])){
    switch($_GET['action']){
        case 'updtgrp':
            $sqlUpdateGroup = $wpdb->prepare("UPDATE `{$wpdb->prefix}ps_groups` SET group_title = '%s', group_description = '%s' WHERE ID = '%s';",
                    $_GET['group_name'],
                    $_GET['group_description'],
                    $_GET['groupid']);
            if($wpdb->query($sqlUpdateGroup) === false){
                $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. Group Details could not be updated.').'</p></div>';
            } else {
                $linkBack = admin_url();
                $actionmessage = '<div id="message" class="updated below-h2"><p>'.__('Group details have been saved.').' <a href="'.$linkBack.'users.php?page=ps_groups">'.__('Return to group list').' &gt;&gt;</a></p></div>';
            }
            break;
        case 'addusr':
            //Make sure user exists in db
            $sqlCheckUserExists = $wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE user_login = '%s'",$_GET['add-username']);
            $UserInfo = $wpdb->query($sqlCheckUserExists);
            if($UserInfo == 0){
                $actionmessage = sprintf('<div class="error below-h2"><p>'.__('User &quot;%s&quot; does not exist.').'</p></div>',$_GET['add-username']);
            } else {
                //Add user to group
                $sqlUpdateGroup = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}ps_group_relationships` (grel_group_id, grel_user_id) VALUES ('%s','%s');",$_GET['groupid'],$wpdb->get_var($sqlCheckUserExists,0,0));
                if($wpdb->query($sqlUpdateGroup) === false){
                    $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. User could not be added to the group.').'</p></div>';
                } else {
                    $actionmessage = sprintf('<div id="message" class="updated below-h2"><p>'.__('User &quot;%s&quot; has been added to the group.').'</p></div>',$_GET['add-username']);
                }
            }
            break;
        case 'rmvusr':
            $sqlRemoveUserRel = $wpdb->prepare("DELETE FROM `{$wpdb->prefix}ps_group_relationships` WHERE ID = '%s' AND grel_group_id = '%s' AND grel_user_id = '%s';",$_GET['relid'],$_GET['groupid'],$_GET['usrid']);
            if($wpdb->query($sqlRemoveUserRel) == 0){
                $actionmessage = '<div class="error below-h2"><p>'.__('An error occurred. User could not be removed from group.').'</p></div>';
            } else {
                $actionmessage = sprintf('<div id="message" class="updated below-h2"><p>'.__('User &quot;%s&quot; was removed from the group.').'</p></div>',$_GET['usrname']);
            }
            break;
        default: break;
    }
}

$groupInfo = $wpdb->get_row($sqlGetGroupInfo);

?>
    <style type="text/css">
        .group-actions {
            text-align:right;
        }
        .group-actions a {
            color:red;
            font-weight:bold;
        }
        #grouptable tbody tr:hover td {
            background:#fffce0;
        }
    </style>

    <div class="wrap">
        <div class="icon32" id="icon-users"><br/></div>
        <h2>Editing a Group</h2>
        <?php echo $actionmessage; ?>
        <?php 
            if (empty($groupInfo->group_title)){ //Group doesnt exist error
                echo '<div id="message" class="error below-h2"><p>',__('A group with that id does not exist.'),' <a href="'.admin_url().'users.php?page=ps_groups">',__('View all groups'),' &gt;&gt;</a></p></div>';
            }else if(isset($groupInfo->group_system_id)){ //Group is a system group error (cannot edit)
                echo '<div id="message" class="error below-h2"><p>',__('System groups cannot be edited.'),' <a href="'.admin_url().'users.php?page=ps_groups">',__('View all groups'),' &gt;&gt;</a></p></div>';
            }else{ 
        ?>

        <form id="editgroup" name="editgroup" class="validate" method="get" action="">
            <?php _e('<h3>Group Details</h3>'); ?>
            <input id="action" name="page" type="hidden" value="ps_groups_edit" />
            <input id="action" name="action" type="hidden" value="updtgrp" />
            <input id="groupid" name="groupid" type="hidden" value="<?php echo $_GET['groupid']; ?>" />
            <?php wp_nonce_field('edit-group'); ?>
            <table class="form-table">
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="group_name">
                            <?php _e('Group Name <span class="description">(required)</span>'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="group_name" name="group_name" type="text" aria-required="true" class="regular-text" value="<?php echo $groupInfo->group_title; ?>" maxlength="30" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th scope="row">
                        <label for="group_description">
                            <?php _e('Description'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="group_description" name="group_description" type="text" aria-required="false" class="regular-text" value="<?php echo $groupInfo->group_description; ?>" maxlength="400" />
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input id="savegroupsub" name="savegroupsub" class="button-primary" type="submit" value="Save Changes" onclick="return validateForm(jQuery(this).parents('#addgroup'));"/>
            </p>
        </form>
        <p></p>
        <form id="addgroupmember" name="addgroupmember" method="get" action="">
            <?php _e('<h3>Group Members</h3>'); ?>
            <div class="tablenav">
                <input id="action" name="page" type="hidden" value="ps_groups_edit" />
                <input id="action" name="action" type="hidden" value="addusr" />
                <input id="groupid" name="groupid" type="hidden" value="<?php echo $_GET['groupid']; ?>" />
                <input id="add-username" name="add-username" class="regular-text" type="text" value="username" onclick="if(jQuery(this).val()=='username'){jQuery(this).val('')}" onblur="if(jQuery(this).val().replace(' ','')==''){jQuery(this).val('username')}" /> <input type="submit" class="button-secondary action" value="<?php _e('Add User'); ?>" onclick="if(jQuery('#add-username').val().replace(' ','') != '' && jQuery('#add-username').val().replace(' ','') != 'username'){return true;}else{ jQuery('#add-username').css({'border-color':'#CC0000','background-color':'pink'});return false; }" />
                <?php wp_nonce_field('ps-add-user'); ?>
            </div>
            <table id="grouptable" class="widefat fixed" cellspacing="0">
                <thead>
                    <tr class="thead">
                        <th class="username">Username</th>
                        <th class="name">Name</th>
                        <th class="email">Email</th>
                        <th></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="thead">
                        <th class="username">Username</th>
                        <th class="name">Name</th>
                        <th class="email">E-mail</th>
                        <th></th>
                    </tr>
                </tfoot>
                <tbody id="users" class="list:user user-list">
                    <?php
                        if(ctx_ps_count_members($_GET['groupid']) == 0){
                            echo '<td colspan="4">'.__('No users have been added to this group.').'</td>';
                        } else {
                            echo ctx_ps_display_member_list($_GET['groupid']);
                        }
                    ?>
                </tbody>
            </table>
        </form>
        <?php } //ENDS : if (empty($groupInfo->group_title)) ?>
    </div>