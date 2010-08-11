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

//Get AD messages from options
$ADMsg = get_option('contexture_ps_options');


?>
    <style type="text/css">
        #ad-msg-auth, #ad-msg-anon { width:500px; }
        .toggle-opts-ad-page {display:none;}
    </style>
    <script type="text/javascript">
        jQuery(function(){
            jQuery('#ad-msg-enable').click(function(){
                if(jQuery(this).filter(':checked').length>0){
                    jQuery('.toggle-opts-ad-msg').fadeOut(250,function(){
                        jQuery('.toggle-opts-ad-page').fadeIn(250);
                    });
                }else{
                    jQuery('.toggle-opts-ad-page').fadeOut(250,function(){
                        jQuery('.toggle-opts-ad-msg').fadeIn(250);
                    });
                }
            });
        });
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
                        Enable Pages:
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="ad-msg-enable" id="ad-msg-enable" /> Use specific <em>pages</em> as default access denied screens
                        </label>
                    </td>
                </tr>
                <tr valign="top" class="toggle-opts-ad-page">
                    <th scope="row">
                        <label for="ad-page-auth">Authenticated Users:</label>
                    </th>
                    <td>
                        <select name="ad-page-auth" id="ad-page-auth">
                            <option value="0">Choose AD Message Page</option>
                        </select><br/>
                        <div>The &quot;access denied&quot; page to show to users who are logged in.</div>
                    </td>
                </tr>
                <tr valign="top" class="toggle-opts-ad-page">
                    <th scope="row">
                        <label for="ad-page-anon">Anonymous Users:</label>
                    </th>
                    <td>
                        <select name="ad-page-anon" id="ad-page-auth">
                            <option value="0">Choose AD Message Page</option>
                        </select><br/>
                        <div>The &quot;access denied&quot; page to show to users who are <span style="text-decoration:underline"><em>not</em></span> logged in.</div>
                    </td>
                </tr>
                <tr valign="top" class="toggle-opts-ad-msg">
                    <th scope="row">
                        <label for="ad-msg-auth">Authenticated Users:</label>
                    </th>
                    <td>
                        <input type="text" name="ad-msg-auth" id="ad-msg-auth" value="<?php echo $ADMsg['ad_msg_auth']; ?>" /><br/>
                        <div>The &quot;access denied&quot; message to show to users who are logged in (HTML OK).</div>
                    </td>
                </tr>
                <tr valign="top" class="toggle-opts-ad-msg">
                    <th scope="row">
                        <label for="ad-msg-anon">Anonymous Users:</label>
                    </th>
                    <td>
                        <input type="text" name="ad-msg-anon" id="ad-msg-anon" value="<?php echo $ADMsg['ad_msg_anon']; ?>" /><br/>
                        <div>The &quot;access denied&quot; message to show to users who are <span style="text-decoration:underline"><em>not</em></span> logged in (HTML OK).</div>
                    </td>
                </tr>
            </table>
            <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
        </form>
    </div>
<?php

?>
