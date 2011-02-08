<?php
/**Creates the "Page Security Options" page**/
global $wpdb;

if ( !current_user_can('manage_options') )
	wp_die( __( 'You do not have sufficient permissions to manage options for this site.','contexture-page-security' ) );

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

    //Add page selections if the checkbox is checked
    if(isset($_POST['ad-msg-enable'])){
        //So long as valid pages are set for both AD types, successfully set usepages to true
        if(is_numeric($_POST['ad-page-auth']) && is_numeric($_POST['ad-page-anon'])){
            $newopts['ad_msg_usepages'] = 'true';
        }else{
            //User didn't select both AD pages, disable usepages
            $newopts['ad_msg_usepages'] = 'false';
            $InvADPagesMsg = '<div class="updated" style="clear:both;"><p><strong>'.__('Custom pages were deactivated. You must select a valid page from each list.','contexture-page-security').'</strong></p></div>';
        }

        //Set new AD page ids
        $newopts['ad_page_auth_id'] = (is_numeric($_POST['ad-page-auth'])) ? $_POST['ad-page-auth'] : ''; //If not numeric, use blank
        $newopts['ad_page_anon_id'] = (is_numeric($_POST['ad-page-anon'])) ? $_POST['ad-page-anon'] : ''; //If not numeric, use blank

        //Disable comments and trackbacks for currently set AD pages
        if(!empty($_POST['ad-page-auth']) && is_numeric($_POST['ad-page-auth']))
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET comment_status='closed', ping_status='closed' WHERE `ID`='%s'",$_POST['ad-page-auth']));
        if(!empty($_POST['ad-page-anon']) && is_numeric($_POST['ad-page-anon']))
            $wpdb->query($wpdb->prepare("UPDATE {$wpdb->posts} SET comment_status='closed', ping_status='closed' WHERE `ID`='%s'",$_POST['ad-page-anon']));
    }else{
        //Checkbox is not set, so we're not using pages
        $newopts['ad_msg_usepages'] = 'false';
    }

    //Update filtering options
    $newopts['ad_msg_usefilter_menus'] = (isset($_POST['filter-menus'])) ? 'true' : 'false';
    $newopts['ad_msg_usefilter_rss'] = (isset($_POST['filter-rss'])) ? 'true' : 'false';

    //Update the options array
    $saveStatus = CTXPSC_Queries::set_options($newopts);

    //If save was successful, show the message
    if(isset($saveStatus)){
        $updatesettingsMessage = '<div id="message" class="updated below-h2 fade"><p><strong>'.__('Page Security settings saved.','contexture-page-security').'</strong></p></div>';
    }
}

//Get AD messages from options
$ADMsg = get_option('contexture_ps_options');
$ProtPages = ctx_ps_get_protected_pages('string');

//Generate ddls with page heirarchy
$pageDDLAuth = wp_dropdown_pages(array('name' => 'ad-page-auth', 'show_option_none' => __('-- Choose AD Message Page --','contexture-page-security'), 'show_option_none_value' => 0, 'selected'=>$ADMsg['ad_page_auth_id'], 'echo' => 0, 'exclude'=>$ProtPages));
$pageDDLAnon = wp_dropdown_pages(array('name' => 'ad-page-anon', 'show_option_none' => __('-- Choose AD Message Page --','contexture-page-security'), 'show_option_none_value' => 0, 'selected'=>$ADMsg['ad_page_anon_id'], 'echo' => 0, 'exclude'=>$ProtPages));

?>
    <style type="text/css">
        #ad-msg-auth, #ad-msg-anon { width:500px; }
        .toggle-opts-ad { } /*Hide this until "custom ad pages" feature is implemented */
        .toggle-opts-ad-page {display:none; }
        #ctx-about {width:326px;float:right;border:1px solid #e5e5e5;border-radius:5px;-moz-border-radius:5px;-webkit-border-radius:5px;padding:10px;margin-top:25px;margin-right:20px;margin-left:10px;}
        #ctx-about a.img-block {display:block;text-align:center;}
        #ctx-about p, #ctx-about div {padding-left:10px;color:#9c9c9c;}
        #ctx-about p a { color:gray; }
        #ctx-ps-opts-form {float:left;width:765px;padding-top:20px;}
        .ctx-footnote { color:#9C9C9C; font-style:italic; }
    </style>
    <script type="text/javascript">
        jQuery(function(){

            //If window < 1400px, hide #ctx-ps-opts-form
            /*
            if(jQuery(window).width()<1400){
                jQuery('#ctx-about').hide();
            }
            jQuery(window).resize(function(){
                if(jQuery(window).width()<1380){
                    jQuery('#ctx-about').hide();
                }else{
                    jQuery('#ctx-about').fadeIn(250);
                }
            });*/

            //OFFLINE until "custom ad pages" feature is implemented
            jQuery('#ad-msg-enable, label[for="ad-msg-enable"]').click(function(){
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
            //jQuery('#message.fade').delay(10000).fadeOut(1000);
        });
    </script>

<table cellpadding="0" cellspacing="0" style="border:none;width:100%;margin-top:-20px;">
    <tr>
        <td id="ctx-ps-opts-form">
            <div class="wrap">
                <div class="icon32" id="icon-users"><br/></div>
                <h2>Page Security Options</h2>
                <?php echo $updatesettingsMessage,$InvADPagesMsg;/*print_r($ADMsg)*/ ?>
                <p></p>
                <form method="post" action="">
                    <input type="hidden" name="action" id="action" value="updateopts" />
                    <h3 class="title"><?php _e('Access Denied Messages','contexture-page-security') ?></h3>
                    <p><?php _e('Use these settings to determine what your users will see when accessing content they are not allowed to view.','contexture-page-security') ?></p>
                    <table class="form-table">
                        <tr valign="top" class="toggle-opts-ad">
                            <th scope="row">
                                <label for="ad-msg-enable"> <?php _e('Use Custom Pages:','contexture-page-security') ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="ad-msg-enable" id="ad-msg-enable" <?php echo ($ADMsg['ad_msg_usepages']=='true') ? 'checked="checked"' : ''; ?> /> <?php _e('Use <strong>pages</strong> for default access denied screens','contexture-page-security') ?>
                                </label>
                            </td>
                        </tr>
                        <tr valign="top" class="toggle-opts-ad-page" style="<?php echo ($ADMsg['ad_msg_usepages']==='true') ? 'display:table-row;' : ''; ?>">
                            <th scope="row">
                                <label for="ad-page-auth"><?php _e('Authenticated Users:','contexture-page-security') ?></label>
                            </th>
                            <td>
                                <!-- NOTE: Should show only pages marked as "Use as Access Denied" -->
                                <?php echo $pageDDLAuth; ?> <?php echo '<a href="'.admin_url("post.php?post={$ADMsg['ad_page_auth_id']}&action=edit").'">Edit Page</a>' ?><br/>
                                <div class="ctx-footnote"><?php _e('The "access denied" page to show users who <strong><em>are logged in</em></strong>.','contexture-page-security') ?></div>
                            </td>
                        </tr>
                        <tr valign="top" class="toggle-opts-ad-page" style="<?php echo ($ADMsg['ad_msg_usepages']==='true') ? 'display:table-row;' : ''; ?>">
                            <th scope="row">
                                <label for="ad-page-anon"><?php _e('Anonymous Users:','contexture-page-security') ?></label>
                            </th>
                            <td>
                                <!-- NOTE: Should show only pages marked as "Use as Access Denied" -->
                                <?php echo $pageDDLAnon; ?> <?php echo '<a href="'.admin_url("post.php?post={$ADMsg['ad_page_anon_id']}&action=edit").'">Edit Page</a>' ?><br/>
                                <div class="ctx-footnote"><?php _e('The "access denied" page to show users who are <strong><em>not</em></strong> logged in.','contexture-page-security') ?></div>
                            </td>
                        </tr>
                        <tr valign="top" class="toggle-opts-ad-msg" style="<?php echo ($ADMsg['ad_msg_usepages']==='true') ? 'display:none;' : ''; ?>">
                            <th scope="row">
                                <label for="ad-msg-auth"><?php _e('Authenticated Users:','contexture-page-security') ?></label>
                            </th>
                            <td>
                                <input type="text" name="ad-msg-auth" id="ad-msg-auth" value="<?php echo esc_attr($ADMsg['ad_msg_auth']); ?>" /><br/>
                                <div class="ctx-footnote"><?php _e('The "access denied" message to show users who are logged in (HTML OK).','contexture-page-security') ?></div>
                            </td>
                        </tr>
                        <tr valign="top" class="toggle-opts-ad-msg" style="<?php echo ($ADMsg['ad_msg_usepages']==='true') ? 'display:none;' : ''; ?>">
                            <th scope="row">
                                <label for="ad-msg-anon"><?php _e('Anonymous Users:','contexture-page-security') ?></label>
                            </th>
                            <td>
                                <input type="text" name="ad-msg-anon" id="ad-msg-anon" value="<?php echo esc_attr($ADMsg['ad_msg_anon']); ?>" /><br/>
                                <div class="ctx-footnote"><?php _e('The "access denied" message to show users who are <strong><em>not</em></strong> logged in (HTML OK).','contexture-page-security') ?></div>
                            </td>
                        </tr>
                    </table>
                    <h3 class="title"><?php _e('Global Security Features','contexture-page-security') ?></h3>
                    <p><?php _e('These options selectively enable/disable Page Security features.','contexture-page-security') ?></p>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <label for="filter-menu"><?php _e('Enable Menu Filtering:','contexture-page-security') ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="filter-menus" id="filter-menus" <?php echo ($ADMsg['ad_msg_usefilter_menus']!='false') ? 'checked="checked"' : ''; ?> /> <?php _e('Use permissions to filter menu items*','contexture-page-security') ?><br/>
                                    <div class="ctx-footnote"><?php _e('*Restricted content will be removed from menus unless user is authenticated','contexture-page-security') ?></div>
                                </label>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row">
                                <label for="filter-rss"><?php _e('Enable RSS Filtering:','contexture-page-security') ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="filter-rss" id="filter-rss" <?php echo ($ADMsg['ad_msg_usefilter_rss']!='false') ? 'checked="checked"' : ''; ?> /> <?php _e('Use permissions to filter RSS content*','contexture-page-security') ?><br/>
                                    <div class="ctx-footnote"><?php _e('*Feed content for restricted posts will be removed unless user is authenticated<br/> Warning: This will hide protected content from most RSS readers.','contexture-page-security') ?></div>
                                </label>
                            </td>
                        </tr>
                    </table>
                    <input type="submit" name="Submit" class="button-primary" value="<?php _e('Save Changes','contexture-page-security') ?>" />
                </form>
            </div>
        </td>
        <td style="vertical-align:top;">
            <div id="ctx-about">
                <a class="img-block" href="http://www.contextureintl.com"><img src="<?php echo plugins_url('images/ctx-logo.gif',dirname(__FILE__)); ?>" alt="Contexture International" /></a>
                <p>Contexture International is an all-in-one agency specializing in <a href="http://www.contextureintl.com/portfolio/graphic-design/">graphic design</a>, <a href="http://www.contextureintl.com/portfolio/web-interactive/">web design</a>, and <a href="http://www.contextureintl.com/portfolio/broadcast-video-production/">broadcast and video production</a>, with an unparalleled ability to connect with the heart of your audience.</p>
                <p>Contexture's staff has successfully promoted organizations and visionaries for more than 2 decades through exceptional storytelling, in just the right contexts for their respective audiences, with overwhelming returns on investment.  See the proof in our <a href="http://www.contextureintl.com/portfolio/">portfolio </a>or learn more <a href="http://www.contextureintl.com/about-us/">about us</a>.</p>
                <div><a href="http://www.contextureintl.com/offers/">Need a custom web or video project?</a></div>
                <div><a href="http://www.contextureintl.com/open-source-projects/contexture-page-security-for-wordpress/">Need help with Page Security?</a></div>
            </div>
        </td>
    </tr>
</table>

<?php

?>