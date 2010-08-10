<?php
/**Creates the "Page Security Options" page**/

$creategroup_message = "";

//Several forms post back to this page, so we catch the action and process accordingly
if(empty($_POST['action'])){
    //Not saving info
}else{
    //Launch code based on action
    switch($_POST['action']){
        case 'addgroup':
            break;
        default: break;
    }
}



?>
    <style type="text/css">
        /*Styles*/
    </style>
    <script type="text/javascript">
        /*Scripts*/
    </script>

    <div class="wrap">
        <div class="icon32" id="icon-users"><br/></div>
        <h2>Page Security Options</h2>
        <?php echo $creategroup_message; ?>
        <p></p>
        <form method="post" action="">
            <h3 class="title">Access Denied Messages</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">
                        <label for="ad-msg-auth">Authenticated Users:</label>
                    </th>
                    <td>
                        <input type="text" name="ad-msg-auth" id="ad-msg-auth" value="" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="ad-msg-anon">Anonymous Users:</label>
                    </th>
                    <td>
                        <input type="text" name="ad-msg-anon" id="ad-msg-anon" value="" />
                    </td>
                </tr>
            </table>
            <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
        </form>
    </div>
<?php

?>
