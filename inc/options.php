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
        #ad-msg-auth, #ad-msg-anon { width:500px; }
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
                        <input type="text" name="ad-msg-auth" id="ad-msg-auth" value="" /><br/>
                        <div>The &quot;access denied&quot; message shown to users who are logged in (HTML OK).</div>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label for="ad-msg-anon">Anonymous Users:</label>
                    </th>
                    <td>
                        <input type="text" name="ad-msg-anon" id="ad-msg-anon" value="" /><br/>
                        <div>The &quot;access denied&quot; message shown to users who are <span style="text-decoration:underline"><em>not</em></span> logged in (HTML OK).</div>
                    </td>
                </tr>
            </table>
            <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
        </form>
    </div>
<?php

?>
