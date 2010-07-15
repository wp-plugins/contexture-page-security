<?php
/**Creates the "Add/View Groups" page**/

//Several forms post back to this page, so we catch the action and process accordingly
if(!empty($_POST['action'])){
    //Launch code based on action
    switch($_POST['action']){
        case 'addgroup':
            $creategroup_message = ctx_ps_create_group($_POST['group_name'], $_POST['group_description']);
            break;
        default: break;
    }
    
}

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
        <div class="icon32" id="icon-users"><br/></div>
        <h2>Groups <?php if (current_user_can('create_users')){ ?><a href="users.php?page=ps_groups_add" class="button add-new-h2">Add New</a><?php } ?></h2>
        <?php echo $creategroup_message; ?>
        <p></p>
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
                    if(ctx_ps_count_groups() == 0){
                        echo '<td colspan="4">No groups have been created yet. Please <a href="?page=ps_groups_add">add a group</a>.</td>';
                    } else {
                        echo ctx_ps_display_group_list();
                }
                ?>
            </tbody>
        </table>
    </div>
<?php

?>
