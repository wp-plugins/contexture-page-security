<?php
global $current_user;
?>
    <style type="text/css">
        #grouptable {

        }
        #grouptable tbody tr:hover td {
            background:#fffce0;
        }

        #grouptable .id {width:30px;}
        #grouptable .name {width:200px;}
        #grouptable .description {}
        #grouptable .user-count {width:60px;}

        #grouptable tbody .name a {font-weight:bold}

    </style>
    <script type="text/javascript">
        /**/
    </script>
    <div class="wrap">
        <h3>Group Membership</h3>
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
                        if(ctx_ps_count_groups($current_user->ID) == 0){
                            echo '<td colspan="4">This user is not attached to any groups. You can enroll users from a <a href="users.php?page=ps_groups">group\'s detail page</a>.</td>';
                        } else {
                            echo ctx_ps_display_group_list($current_user->ID);
                        }
                    }else{
                        if(ctx_ps_count_groups($_GET['user_id']) == 0){
                            echo '<td colspan="4">This user is not attached to any groups. You can enroll users from a <a href="users.php?page=ps_groups">group\'s detail page</a>.</td>';
                        } else {
                            echo ctx_ps_display_group_list($_GET['user_id']);
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>