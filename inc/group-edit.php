<?php

$sqlGetGroupInfo = "SELECT * FROM `{$wpdb->prefix}ps_groups` WHERE `ID` = '{$wpdb->escape($_GET['groupid'])}'";
$actionmessage = '';

if(!empty($_GET['action'])){
    switch($_GET['action']){
        case 'updtgrp':
            $sqlUpdateGroup = "UPDATE `{$wpdb->prefix}ps_groups` SET group_title = '{$wpdb->escape($_GET['group_name'])}', group_description = '{$wpdb->escape($_GET['group_description'])}' WHERE ID = '{$wpdb->escape($_GET['groupid'])}';";
            if($wpdb->query($sqlUpdateGroup) === false){
                $actionmessage = '<div class="error below-h2"><p>An error occurred. Group Details could not be updated.</p></div>';
            } else {
                $linkBack = admin_url();
                $actionmessage = '<div id="message" class="updated below-h2"><p>Group details have been saved. <a href="'.$linkBack.'users.php?page=ps_groups">Return to group list &gt;&gt;</a></p></div>';
            }
            break;
        case 'addusr':
            //Make sure user exists in db
            $sqlCheckUserExists = $wpdb->prepare("SELECT * FROM {$wpdb->users} WHERE user_login = '{$wpdb->escape($_GET['add-username'])}'");
            $UserInfo = $wpdb->query($sqlCheckUserExists);
            if($UserInfo == 0){
                $actionmessage = '<div class="error below-h2"><p>User &quot;'.$_GET['add-username'].'&quot; does not exist.</p></div>';
            } else {
                //Add user to group
                $sqlUpdateGroup = "INSERT INTO `{$wpdb->prefix}ps_group_relationships` (grel_group_id, grel_user_id) VALUES ('{$wpdb->escape($_GET['groupid'])}','{$wpdb->get_var($sqlCheckUserExists,0,0)}');";
                if($wpdb->query($sqlUpdateGroup) === false){
                    $actionmessage = '<div class="error below-h2"><p>An error occurred. User could not be added to the group.</p></div>';
                } else {
                    $actionmessage = '<div id="message" class="updated below-h2"><p>User &quot;'.$_GET['add-username'].'&quot; has been added to the group.</p></div>';
                }
            }
            break;
        case 'rmvusr':
            $sqlRemoveUserRel = "DELETE FROM `{$wpdb->prefix}ps_group_relationships` WHERE ID = '{$wpdb->escape($_GET['relid'])}' AND grel_group_id = '{$wpdb->escape($_GET['groupid'])}' AND grel_user_id = '{$wpdb->escape($_GET['usrid'])}';";
            if($wpdb->query($sqlRemoveUserRel) == 0){
                $actionmessage = '<div class="error below-h2"><p>An error occurred. User could not be removed from group.</p></div>';
            } else {
                $actionmessage = '<div id="message" class="updated below-h2"><p>User &quot;'.$_GET['usrname'].'&quot; was removed from the group.</p></div>';
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
                echo '<div id="message" class="error below-h2"><p>A group with that id does not exist. <a href="users.php?page=ps_groups">View all groups &gt;&gt;</a></p></div>';
            }else if(isset($groupInfo->group_system_id)){ //Group is a system group error (cannot edit)
                echo '<div id="message" class="error below-h2"><p>System groups cannot be edited. <a href="users.php?page=ps_groups">View all groups &gt;&gt;</a></p></div>';
            }else{ 
        ?>

        <form id="editgroup" name="editgroup" class="validate" method="get" action="">
            <h3>Group Details</h3>
            <input id="action" name="page" type="hidden" value="ps_groups_edit" />
            <input id="action" name="action" type="hidden" value="updtgrp" />
            <input id="groupid" name="groupid" type="hidden" value="<?php echo $wpdb->escape($_GET['groupid']); ?>" />
            <?php wp_nonce_field('edit-group'); ?>
            <table class="form-table">
                <tr class="form-field form-required">
                    <th scope="row">
                        <label for="group_name">
                            Group Name <span class="description">(required)</span>
                        </label>
                    </th>
                    <td>
                        <input id="group_name" name="group_name" type="text" aria-required="true" class="regular-text" value="<?php echo $groupInfo->group_title; ?>" maxlength="30" />
                    </td>
                </tr>
                <tr class="form-field">
                    <th scope="row">
                        <label for="group_description">
                            Description
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
            <h3>Group Members</h3>
            <div class="tablenav">
                <input id="action" name="page" type="hidden" value="ps_groups_edit" />
                <input id="action" name="action" type="hidden" value="addusr" />
                <input id="groupid" name="groupid" type="hidden" value="<?php echo $wpdb->escape($_GET['groupid']); ?>" />
                <input id="add-username" name="add-username" class="regular-text" type="text" value="username" onclick="if(jQuery(this).val()=='username'){jQuery(this).val('')}" onblur="if(jQuery(this).val().replace(' ','')==''){jQuery(this).val('username')}" /> <input type="submit" class="button-secondary action" value="Add User" onclick="if(jQuery('#add-username').val().replace(' ','') != '' && jQuery('#add-username').val().replace(' ','') != 'username'){return true;}else{ jQuery('#add-username').css({'border-color':'#CC0000','background-color':'pink'});return false; }" />
                <?php wp_nonce_field('add-user'); ?>
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
                            echo '<td colspan="4">No users have been added to this group.</td>';
                        } else {
                            echo ctx_ps_display_member_list($_GET['groupid']);
                        }
                    ?>
                </tbody>
            </table>
        </form>
        <?php } //ENDS : if (empty($groupInfo->group_title)) ?>
    </div>