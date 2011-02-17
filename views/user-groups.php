    <style type="text/css">
        #grouptable { }
        #grouptable tbody tr:hover td { background:#fffce0; }

        #grouptable .id {width:30px;}
        #grouptable .name {width:200px;}
        #grouptable .description {}
        #grouptable .user-count {width:60px;}

        #grouptable tbody .name a {}
        #grouptable .delete { cursor:pointer; }

        .profile-php #grouptable a:hover {color:#21759B !important;}
        .ctx-ps-tablenav .ctx-ajax-status {float:right;padding-top:20px;}
    </style>
    <script type="text/javascript">
        /**/
    </script>
    <div class="wrap">
        <h3>Group Membership</h3>
        <?php if ( current_user_can('add_users') ) { ?>
        <div class="tablenav ctx-ps-tablenav">
            <select id="groups-available">
                <option value="0">-- Select -- </option>
                <?php
                //Loop through all groups in the db to populate the drop-down list
                foreach($wpdb->get_results("SELECT * FROM {$wpdb->prefix}ps_groups WHERE {$wpdb->prefix}ps_groups.group_system_id IS NULL ORDER BY `group_system_id` DESC, `group_title` ASC") as $group){
                    //Generate the option HTML, hiding it if it's already in our $currentGroups array
                    echo '<option '.((!empty($currentGroups[$group->ID]))?'class="detach"':'').' value="'.$group->ID.'">'.$group->group_title.'</option>';
                }
                ?>
            </select>
            <input type="hidden" id="ctx-group-user-id" value="<?php echo $display_user;  ?>" />
            <input type="button" class="button-secondary action" id="btn-add-grp-2-user" value="Add to Group" />
        </div>
        <?php
        }else{ //end check for admin priviledges
            //echo "<p>Group membership is managed by site administrators. To be added or removed from a group, please contact an administrator.</p>";
        } //end else
        ?>
        <table id="grouptable" class="widefat fixed" cellspacing="0">
            <thead>
                <tr class="thead">
                    <th class="id">id</th>
                    <th class="name"><?php _e('Name','contexture-page-security') ?></th>
                    <th class="description"><?php _e('Description','contexture-page-security') ?></th>
                    <th class="user-count"><?php _e('Users','contexture-page-security') ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr class="thead">
                    <th class="id">id</th>
                    <th class="name"><?php _e('Name','contexture-page-security') ?></th>
                    <th class="description"><?php _e('Description','contexture-page-security') ?></th>
                    <th class="user-count"><?php _e('Users','contexture-page-security') ?></th>
                </tr>
            </tfoot>
            <tbody>
                <?php
                    if ( IS_PROFILE_PAGE ) {
                        //IF THIS IS A PROFILE PAGE (non-admin)
                        if(CTXPS_Queries::count_groups($display_user) == '0'){
                            echo '<td colspan="4">',__('You are not currently a member of any groups.','contexture-page-security'),'</td>';
                        } else {
                            echo CTXPS_Components::render_group_list($display_user,'users',false);
                        }
                    }else{
                        //IF THIS IS A USER-EDIT PAGE (admin version)
                        if(CTXPS_Queries::count_groups($display_user) == '0'){
                            echo '<td colspan="4">',sprintf(__('This user has not been added to any custom groups. Select a group above or visit any <a href="%s">group detail page</a>.','contexture-page-security'),admin_url('users.php?page=ps_groups')),'</td>';
                        } else {
                            echo CTXPS_Components::render_group_list($display_user,'users',true);
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>