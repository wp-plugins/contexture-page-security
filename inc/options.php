<?php
/**Creates the "Page Security Options" page**/

if ( !current_user_can('manage_options') )
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.' ) );

$updatesettingsMessage = "";

//Several forms post back to this page, so we catch the action and process accordingly
if(empty($_POST['action'])){
    //NOT SAVING INFO
}else if($_POST['action']=='updateopts'){
    //SAVE MY INFO

    //Set new options
    $newopts = array(
        "ad_msg_anon"=>stripslashes($_POST['ad-msg-anon']),
        "ad_msg_auth"=>stripslashes($_POST['ad-msg-auth'])
    );

    //Update the options array
    $saveStatus = ctx_ps_set_options($newopts);

    //If save was successful, show the message
    if($saveStatus){
        $updatesettingsMessage = '<div id="message" class="updated below-h2 fade"><p><strong>Page Security settings saved</strong>.</p></div>';
    }
}

//Get AD messages from options
$ADMsg = get_option('contexture_ps_options');


?>
    <style type="text/css">
        #ad-msg-auth, #ad-msg-anon { width:500px; }
        .toggle-opts-ad {display:none;} /*Hide this until "custom ad pages" feature is implemented */
        .toggle-opts-ad-page {display:none;}
    </style>
    <script type="text/javascript">
        jQuery(function(){

            /* //OFFLINE until "custom ad pages" feature is implemented
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
            */
            //jQuery('#message.fade').delay(10000).fadeOut(1000);
        });
    </script>

    <div class="wrap">
        <div class="icon32" id="icon-users"><br/></div>
        <h2>Page Security Options</h2>
        <?php echo $updatesettingsMessage; ?>
        <p></p>
        <form method="post" action="">
            <input type="hidden" name="action" id="action" value="updateopts" />
            <h3 class="title">Access Denied Messages</h3>
            <p>You are able to set what messages your users see when they try to access content they are not allowed to view.</p>
            <table class="form-table">
                <tr valign="top" class="toggle-opts-ad">
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
                        <!-- NOTE: Should show only pages marked as "Use as Access Denied" -->
                        <select name="ad-page-auth" id="ad-page-auth">
                            <option value="0">Choose AD Message Page</option>
                        </select><br/>
                        <div>The &quot;access denied&quot; page to show users who are logged in.</div>
                    </td>
                </tr>
                <tr valign="top" class="toggle-opts-ad-page">
                    <th scope="row">
                        <label for="ad-page-anon">Anonymous Users:</label>
                    </th>
                    <td>
                        <!-- NOTE: Should show only pages marked as "Use as Access Denied" -->
                        <select name="ad-page-anon" id="ad-page-auth">
                            <option value="0">Choose AD Message Page</option>
                        </select><br/>
                        <div>The &quot;access denied&quot; page to show users who are <span style="text-decoration:underline;"><em>not</em></span> logged in.</div>
                    </td>
                </tr>
                <tr valign="top" class="toggle-opts-ad-msg">
                    <th scope="row">
                        <label for="ad-msg-auth">Authenticated Users:</label>
                    </th>
                    <td>
                        <input type="text" name="ad-msg-auth" id="ad-msg-auth" value="<?php echo esc_attr($ADMsg['ad_msg_auth']); ?>" /><br/>
                        <div>The &quot;access denied&quot; message to show users who are logged in (HTML OK).</div>
                    </td>
                </tr>
                <tr valign="top" class="toggle-opts-ad-msg">
                    <th scope="row">
                        <label for="ad-msg-anon">Anonymous Users:</label>
                    </th>
                    <td>
                        <input type="text" name="ad-msg-anon" id="ad-msg-anon" value="<?php echo esc_attr($ADMsg['ad_msg_anon']); ?>" /><br/>
                        <div>The &quot;access denied&quot; message to show users who are <span style="text-decoration:underline;"><em>not</em></span> logged in (HTML OK).</div>
                    </td>
                </tr>
            </table>
            <input type="submit" name="Submit" class="button-primary" value="Save Changes" />
        </form>
    </div>
<?php

?>
