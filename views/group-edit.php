<?php

if ( ! current_user_can( 'manage_options' ) )
    wp_die( __( 'You do not have sufficient permissions to manage options for this site.','contexture-page-security' ) );

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


    if($_GET['page']==='ps_groups_edit')
?>
    <script type="text/javascript" src="<?php echo plugins_url('/views/js/inline-edit-membership.dev.js',dirname(__FILE__)) ?>"></script>
    <style type="text/css">
        .group-actions { text-align:right; }
        .group-actions a { color:red; font-weight:bold; }
        #grouptable tbody tr:hover td,
        #pagetable tbody tr:hover td { background:#fffce0; }
        #grouptable th.username { width:300px; }
        #grouptable th.name { width:300px; }
        #grouptable th.expires { width:100px; }
        #pagetable th.protected { width:50px; }
        #pagetable th.type { width:60px; }
        .inline-edit-row label { clear:left; }
        .inline-edit-row label,
        .inline-edit-save a,
        .inline-edit-date{ margin:0.2em 0; }
        .inline-edit-row label .title { width:100px !important; }
        .inline-edit-save { height:25px; }
        .inline-edit-col-left { width:30% !important; border-right:1px solid #DDDDDD;  }

        .inline-edit-col-right label { padding-left:10px !important; }
    </style>

    <div class="wrap">
        <div class="icon32" id="icon-users"><br/></div>
        <h2>Editing a Group</h2>
        <?php echo $actionmessage; ?>
        <?php
            if (empty($groupInfo->group_title)){ //Group doesnt exist error
                echo '<div id="message" class="error below-h2"><p>',__('A group with that id does not exist.','contexture-page-security'),' <a href="'.admin_url().'users.php?page=ps_groups">',__('View all groups','contexture-page-security'),' &gt;&gt;</a></p></div>';
            }else if(isset($groupInfo->group_system_id)){ //Group is a system group error (cannot edit)
                echo '<div id="message" class="error below-h2"><p>',__('System groups cannot be edited.','contexture-page-security'),' <a href="'.admin_url().'users.php?page=ps_groups">',__('View all groups','contexture-page-security'),' &gt;&gt;</a></p></div>';
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
                            <?php _e('Group Name <span class="description">(required)</span>','contexture-page-security'); ?>
                        </label>
                    </th>
                    <td>
                        <input id="group_name" name="group_name" type="text" aria-required="true" class="regular-text" value="<?php echo $groupInfo->group_title; ?>" maxlength="30" /> <span style="color:silver;">id: <?php echo $groupInfo->ID; ?></span>
                    </td>
                </tr>
                <tr class="form-field">
                    <th scope="row">
                        <label for="group_description">
                            <?php _e('Description','contexture-page-security'); ?>
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
            <?php _e('<h3>Group Members</h3>','contexture-page-security'); ?>
            <div class="tablenav">
                <input id="action" name="page" type="hidden" value="ps_groups_edit" />
                <input id="action" name="action" type="hidden" value="addusr" />
                <input id="groupid" name="groupid" type="hidden" value="<?php echo $_GET['groupid']; ?>" />
                <input id="add-username" name="add-username" class="regular-text" type="text" value="username" onclick="if(jQuery(this).val()=='username'){jQuery(this).val('')}" onblur="if(jQuery(this).val().replace(' ','')==''){jQuery(this).val('username')}" /> <input type="submit" class="button-secondary action" value="<?php _e('Add User','contexture-page-security'); ?>" onclick="if(jQuery('#add-username').val().replace(' ','') != '' && jQuery('#add-username').val().replace(' ','') != 'username'){return true;}else{ jQuery('#add-username').css({'border-color':'#CC0000','background-color':'pink'});return false; }" />
                <?php wp_nonce_field('ps-add-user','',false); ?>
            </div>
            <table id="grouptable" class="widefat fixed" cellspacing="0">
                <thead>
                    <tr class="thead">
                        <th class="username"><?php _e('Username','contexture-page-security') ?></th>
                        <th class="name"><?php _e('Name','contexture-page-security') ?></th>
                        <th class="email"><?php _e('Email','contexture-page-security') ?></th>
                        <th class="expires"><?php _e('Expires','contexture-page-security') ?></th>
                    </tr>
                </thead>
                <tfoot>
                    <tr class="thead">
                        <th class="username"><?php _e('Username','contexture-page-security') ?></th>
                        <th class="name"><?php _e('Name','contexture-page-security') ?></th>
                        <th class="email"><?php _e('Email','contexture-page-security') ?></th>
                        <th class="expires"><?php _e('Expires','contexture-page-security') ?></th>
                    </tr>
                </tfoot>
                <tbody id="users" class="list:user user-list">
                    <?php
                        if(ctx_ps_count_members($_GET['groupid']) < 1){
                            echo '<td colspan="4">'.__('No users have been added to this group.','contexture-page-security').'</td>';
                        } else {
                           echo '<tr id="inline-edit" class="inline-edit-row inline-options-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page" style="display: none"><td colspan="4">
                                <h4>MEMBERSHIP DETAILS</h4>
                                <fieldset class="inline-edit-col-left">
                                    <label>
                                        <span class="title">'.__('User','contexture-page-security').'</span>
                                        <span class="input-text-wrap username" style="color:silver;">
                                            username
                                        </span>
                                    </label>
                                    <label>&nbsp;</label>
                                </fieldset>
                                <fieldset class="inline-edit-col-right">
                                    <label>
                                        <span class="title">'.__('Expires','contexture-page-security').'</span>
                                        <span class="input-text-wrap">
                                            <input type="checkbox" value="" name="membership_permanent"/>
                                        </span>
                                    </label>
                                    <label>
                                        <span class="title">'.__('End Date','contexture-page-security').'</span>
                                    </label>
                                    <div class="inline-edit-date">
                                        <div class="timestamp-wrap">
                                            <select tabindex="4" name="mm" disabled="disabled">
                                                <option value="01">Jan</option>
                                                <option value="02">Feb</option>
                                                <option value="03">Mar</option>
                                                <option value="04">Apr</option>
                                                <option value="05">May</option>
                                                <option value="06">Jun</option>
                                                <option value="07">Jul</option>
                                                <option value="08">Aug</option>
                                                <option value="09">Sep</option>
                                                <option value="10">Oct</option>
                                                <option value="11">Nov</option>
                                                <option value="12">Dec</option>
                                            </select>
                                            <input type="text" autocomplete="off" tabindex="4" maxlength="2" size="2" value="" name="jj" disabled="disabled">,
                                            <input type="text" autocomplete="off" tabindex="4" maxlength="4" size="4" value="" name="aa" disabled="disabled">
                                        </div>
                                    </div>
                                </fieldset>
                                <p class="submit inline-edit-save">
                                    <a class="button-secondary cancel alignleft" title="Cancel" href="#inline-membership" accesskey="c">'.__('Cancel','contexture-page-security').'</a>
                                    <a class="button-primary save alignright" title="Update" href="#inline-membership" accesskey="s">'.__('Update','contexture-page-security').'</a>
                                    <img alt="" src="'.admin_url('/images/wpspin_light.gif').'" style="display:none;" class="waiting"/>
                                </p>
                                </td></tr>';
                                echo ctx_ps_display_member_list($_GET['groupid']);
                        }
                    ?>
                </tbody>
            </table>
        </form>
        <?php _e('<h3>Associated Content</h3>','contexture-page-security'); ?>
        <table id="pagetable" class="widefat fixed" cellspacing="0">
            <thead>
                <tr class="thead">
                    <th class="title"><?php _e('Title','contexture-page-security') ?></th>
                    <th class="protected"><?php //echo '<div class="vers"><img alt="Protected" src="'.plugins_url('images/protected.png',dirname(__FILE__)).'" /></div>'?></th>
                    <th class="type"><?php _e('Type','contexture-page-security') ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr class="thead">
                    <th class="title"><?php _e('Title','contexture-page-security') ?></th>
                    <th class="protected"><?php //echo '<div class="vers"><img alt="Protected" src="'.plugins_url('images/protected.png',dirname(__FILE__)).'" /></div>'?></th>
                    <th class="type"><?php _e('Type','contexture-page-security') ?></th>
                </tr>
            </tfoot>
            <tbody id="users" class="list:content content-list">
                <?php
                    if(ctx_ps_count_protected_pages($_GET['groupid']) < 1){
                        echo '<td colspan="3">'.__('No content is attached to this group.','contexture-page-security').'</td>';
                    } else {
                        echo ctx_ps_display_page_list($_GET['groupid']);
                    }
                ?>
            </tbody>
        </table>
        <?php } //ENDS : if (empty($groupInfo->group_title)) ?>
    </div>