<?php
global $current_user, $wpdb;



//Create an array of groups that are already attached to this user
$currGroups = array();
$sqlCurrGroups = "
    SELECT 
        {$wpdb->prefix}ps_groups.ID,
        {$wpdb->prefix}ps_groups.group_title
    FROM {$wpdb->prefix}ps_groups
    JOIN {$wpdb->prefix}ps_group_relationships
        ON {$wpdb->prefix}ps_group_relationships.grel_group_id = {$wpdb->prefix}ps_groups.ID
    WHERE {$wpdb->prefix}ps_group_relationships.grel_user_id = '{$wpdb->escape($_GET['user_id'])}'
";
foreach($wpdb->get_results($sqlCurrGroups) as $curGrp){
    $currentGroups[$curGrp->ID] = $curGrp->group_title;
}


?>
    <style type="text/css">
        #grouptable { }
        #grouptable tbody tr:hover td { background:#fffce0; }

        #grouptable .id {width:30px;}
        #grouptable .name {width:200px;}
        #grouptable .description {}
        #grouptable .user-count {width:60px;}

        #grouptable tbody .name a {font-weight:bold}

        .profile-php #grouptable a:hover {color:#21759B !important;}
    </style>
    <script type="text/javascript">
        /**/
    </script>
    <div class="wrap">
        <h3>Group Membership</h3>
        <?php if ( current_user_can('manage_options') ) { ?>
        <div class="tablenav">
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
            <input type="hidden" id="ctx-group-user-id" value="<?php echo $_GET['user_id'];  ?>" />
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
                    <th class="name">Name</th>
                    <th class="description">Description</th>
                    <th class="user-count">Users</th>
                </tr>
            </thead>
            <tfoot>
                <tr class="thead">
                    <th class="id">id</th>
                    <th class="name">Name</th>
                    <th class="description">Description</th>
                    <th class="user-count">Users</th>
                </tr>
            </tfoot>
            <tbody>
                <?php
                    if ( IS_PROFILE_PAGE ) {
                        //IF THIS IS A PROFILE PAGE (non-admin)
                        if(ctx_ps_count_groups($current_user->ID) == '0'){
                            echo '<td colspan="4">You are not currently a member of any groups. '.$current_user->ID.'</td>';
                        } else {
                            echo ctx_ps_display_group_list($current_user->ID,'users',false);
                        }
                    }else{
                        //IF THIS IS A USER-EDIT PAGE (admin version)
                        if(ctx_ps_count_groups($_GET['user_id']) == '0'){
                            echo '<td colspan="4">This user has not been added to any static groups. Select a group above or visit any <a href="users.php?page=ps_groups">group detail page</a>.</td>';
                        } else {
                            echo ctx_ps_display_group_list($_GET['user_id'],'users',true);
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>