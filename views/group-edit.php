    <script type="text/javascript" src="<?php echo CTXPSURL.'/views/js/inline-edit-membership.dev.js' ?>"></script>
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
                    <?php echo CTXPS_Components::render_member_list($_GET['groupid']); ?>
                </tbody>
            </table>
        </form>
        <?php _e('<h3>Associated Content</h3>','contexture-page-security'); ?>
        <?php //new CTXPS_Table_Packages('associated_content'); ?>
        <table id="pagetable" class="widefat fixed" cellspacing="0">
            <thead>
                <tr class="thead">
                    <th class="title"><?php _e('Title','contexture-page-security') ?></th>
                    <th class="protected"><?php //echo '<div class="vers"><img alt="Protected" src="'.CTXPSURL.'images/protected.png" /></div>'?></th>
                    <th class="type"><?php _e('Type','contexture-page-security') ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr class="thead">
                    <th class="title"><?php _e('Title','contexture-page-security') ?></th>
                    <th class="protected"><?php //echo '<div class="vers"><img alt="Protected" src="'.CTXPSURL.'images/protected.png" /></div>'?></th>
                    <th class="type"><?php _e('Type','contexture-page-security') ?></th>
                </tr>
            </tfoot>
            <tbody id="users" class="list:content content-list">
                <?php echo CTXPS_Components::render_content_by_group_list($_GET['groupid']); ?>
            </tbody>
        </table>
        <?php } //ENDS : if (empty($groupInfo->group_title)) ?>
    </div>