<?php
if(!class_exists('CTXPS_App')){
/**
 * Static. Application-level methods, such as init methods.
 */
class CTXPS_App{

    /**
     * Adds the security box on the right side of the 'edit page' admin section
     */
    public static function admin_init(){
        //Add our JS strings (using PHP allows us to localize JS strings)
        add_action('admin_head', 'CTXPS_App::js_strings_init');

        //Enable Restrict Access sidebar for ALL post types (will also automatically enable support for any custom types)
        $post_types = get_post_types();
        foreach($post_types as $type){
            add_meta_box('ctx_ps_sidebar_security', 'Restrict Access', 'CTXPS_Router::sidebar_security', $type, 'side', 'low');
        }

        //Add our custom admin styles
        wp_enqueue_style('psc_admin',CTXPSURL.'views/admin.css');
    }


    /**
     * Adds additional contextual help to WordPress' existing contextual help screens
     * @global array $_wp_contextual_help
     */
    public static function help_init(){
        //We bring in the global help array so we can modify it
        global $_wp_contextual_help;

        $supporturl = /*'<p><strong>'.__('For more information:','contexture-page-security').'</strong></p>'.*/'<p><a href="http://www.contextureintl.com/open-source-projects/contexture-page-security-for-wordpress/">'.__('Official Page Security Support','contexture-page-security').'</a></p>';

        //Append additional help to users page (use preg_replace to add it seamlessly before "Fore more information")
        if(isset($_wp_contextual_help['users']))
            $_wp_contextual_help['users'] .= '<div style="border-top:1px solid silver;"></div>'.__('<h4><strong>Page Security:</strong></h4><p>To add a user to a group, check the users to add, and select a group from the "Add to group..." drop down. Click "Add" to save the changes.</p>','contexture-page-security');
        if(isset($_wp_contextual_help['page']))
            $_wp_contextual_help['page'] .= '<div style="border-top:1px solid silver;"></div>'.sprintf(__('<h4><strong>Page Security:</strong></h4><p>To restrict access to this page, find the "Restrict Access" sidebar and check the box next to "Protect this page and it\'s decendants. This will reveal some additional options.</p><p>If a page is protected, but you don\'t have any groups assigned to it, only admins will be able to see or access the page. To give users access to the page, select a group from the "Available Groups" drop-down and click "Add". You may need to <a href="%s">create a group</a>, if you haven\'t already.</p><p>To remove a group, either uncheck the "Protect this page..." box (all permissions will be removed), or find the group in the "Allowed Groups" list and click "Remove".</p><p>All changes are saved immediately. There is no need to click "Update" in order to save your security settings.</p>','contexture-page-security').$supporturl,admin_url('users.php?page=ps_groups_add'));
        if(isset($_wp_contextual_help['post']))
            $_wp_contextual_help['post'] .= '<div style="border-top:1px solid silver;"></div>'.sprintf(__('<h4><strong>Page Security:</strong></h4><p>To restrict access to this post, find the "Restrict Access" sidebar and check the box next to "Protect this page and it\'s decendants. This will reveal some additional options.</p><p>If a post is protected, but you don\'t have any groups assigned to it, only admins will be able to see or access the post. To give users access to the post, select a group from the "Available Groups" drop-down and click "Add". You may need to <a href="%s">create a group</a>, if you haven\'t already.</p><p>To remove a group, either uncheck the "Protect this page..." box (all permissions will be removed), or find the group in the "Allowed Groups" list and click "Remove".</p><p>All changes are saved immediately. There is no need to click "Update" in order to save your security settings.</p>','contexture-page-security').$supporturl,admin_url('users.php?page=ps_groups_add'));
        if(isset($_wp_contextual_help['edit-page']))
            $_wp_contextual_help['edit-page'] .= '<div style="border-top:1px solid silver;"></div>'.sprintf(__('<h4><strong>Page Security:</strong></h4><p>The lock icon shows which pages currently have restrictions. The lighter icons show which pages are simply inheriting their parent\'s restrictions, while dark icons appear only on pages that have their own restrictions.</p>','contexture-page-security').$supporturl,admin_url('users.php?page=ps_groups_add'));
        if(isset($_wp_contextual_help['edit-post']))
            $_wp_contextual_help['edit-post'] .= '<div style="border-top:1px solid silver;"></div>'.sprintf(__('<h4><strong>Page Security:</strong></h4><p>The lock icon shows which posts currently have restrictions.</p>','contexture-page-security').$supporturl,admin_url('users.php?page=ps_groups_add'));


        if ( function_exists('add_contextual_help') ){
            //Add our contextual help
            add_contextual_help( 'users_page_ps_groups', __('<p>This screen shows a list of all the groups currently available. Groups are used to arbitrarily "group" users together for permissions purposes. Once you "attach" one or more groups to a page or post, only users in one of those groups will be able to access it!</p><p>To view users in a group, simply click on the group\'s name.</p><p><strong>Registered Users</strong> - This is a system group that is automatically applied to all registered users. It can\'t be edited or deleted because it\'s managed by WordPress automatically.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl );
            add_contextual_help( 'users_page_ps_groups_add', __('<p>This screen allows you to add a new group. Simply enter a new, unique name for your group, and an optional description.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl );
            $ps_groups_edit = __('<p>This screen shows you all the details about the current group, and allows you to edit some of those details.</p><p><strong>Group Details</strong> - Change a group\'s title or description.</p><p><strong>Group Members</strong> - A list of users currently attached to the group. You also add users to a group if you know their username (users can also be added to groups from their profile pages).</p><p><strong>Associated Content</strong> - A list of all the pages and posts this group is attached to.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl;
            add_contextual_help( 'dashboard_page_ps_groups_edit', $ps_groups_edit );
            add_contextual_help( 'users_page_ps_groups_edit', $ps_groups_edit );
            $ps_groups_delete = __('<p>This screen allows you to delete the selected group. Once you click "Confirm Deletion", the group will be permanently deleted, and all users will be removed from the group.</p><p>Also note that if this is the only group attached to any "restricted" pages, those pages will not longer be accessible to anyone but administrators.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl;
            add_contextual_help( 'dashboard_page_ps_groups_delete', $ps_groups_delete );
            add_contextual_help( 'users_page_ps_groups_delete', $ps_groups_delete );
            add_contextual_help( 'settings_page_ps_manage_opts', __('<p>This screen contains general settings for Page Security.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl );
            //add_contextual_help( 'users_page', __('<p><strong>Page Security:</strong></p><p>To add multiple users to a group, check off the users you want to add, select the group from the "Add to group..." drop-down, and click "Add".</p><p><p><strong>For more information:</strong></p><a href="http://www.contextureintl.com/open-source-projects/contexture-page-security-for-wordpress/">Official Page Security Support</a></p>','contexture-page-security') );

        }
    }

    /**
     * Adds some custom JS to the header, primarily AJAX
     */
    public static function js_strings_init(){
        ?>
        <script type="text/javascript">
            var msgNoUnprotect = '<?php _e('You cannot unprotect this page. It is protected by a parent or ancestor.','contexture-page-security') ?>';
            var msgEraseSec = "<?php _e("This will completely erase this page's security settings and make it accessible to the public. Continue?",'contexture-page-security') ?>";
            var msgRemoveGroup = '<?php _e('Are you sure you want to remove group "%s" from this page?','contexture-page-security') ?>';
            var msgRemovePage = '<?php _e('Are you sure you want to remove this group from %s ?','contexture-page-security') ?>';
            var msgRemoveUser = '<?php _e('Remove this user from the group?','contexture-page-security') ?>';
            var msgYearRequired = '<?php _e('You must specify an expiration year.','contexture-page-security') ?>';
            var msgGeneralError = '<?php _e('An error occurred: ','contexture-page-security') ?>';
            var msgNoGroupSel = '<?php _e('You must select a group to add.','contexture-page-security') ?>';
        </script>
        <script type="text/javascript" src="<?php echo CTXPSURL.'/js/core-ajax.dev.js' ?>"></script>
        <?php
    }



    /**
     * Adds various menu items to WordPress admin
     */
    public static function admin_screens_init(){
        //Add Groups option to the WP admin menu under Users (these also return hook names, which are needed for contextual help)
        add_submenu_page('users.php', __('Group Management','contexture-page-security'), __('Groups','contexture-page-security'), 'manage_options', 'ps_groups', 'CTXPS_Router::groups_list');
        add_submenu_page('users.php', __('Add a Group','contexture-page-security'), __('Add Group','contexture-page-security'), 'manage_options', 'ps_groups_add', 'CTXPS_Router::group_add');
        add_submenu_page('', __('Edit Group','contexture-page-security'), __('Edit Group','contexture-page-security'), 'manage_options', 'ps_groups_edit', 'CTXPS_Router::group_edit');
        add_submenu_page('', __('Delete Group','contexture-page-security'), __('Delete Group','contexture-page-security'), 'manage_options', 'ps_groups_delete', 'CTXPS_Router::group_delete');

        add_options_page('Page Security by Contexture', 'Page Security', 'manage_options', 'ps_manage_opts', 'CTXPS_Router::options');
        //add_submenu_page('options-general.php', 'Page Security', 'Page Security', 'manage_options', 'ps_manage_opts', 'ctx_ps_page_options');
    }

    /**
     * Loads localized language files, if available
     */
    public static function localize_init(){
       if (function_exists('load_plugin_textdomain')) {
          load_plugin_textdomain('contexture-page-security', false, dirname(plugin_basename(__FILE__)).'/languages' );
       }
    }

    /**
     * Creates a new group
     *
     * @global wpdb $wpdb
     * @param string $name A short, meaningful name for the group
     * @param string $description A more detailed description for the group
     * @return <type>
     */
    public static function create_group($name, $description){
        global $wpdb;

        if(!CTXPS_Queries::check_group_exists($name)){
            $current_user = wp_get_current_user();

            if(CTXPS_Queries::add_group($name, $description, $current_user->ID) !== FALSE){
                return '<div id="message" class="updated"><p>'.__('New group created','contexture-page-security').'</p></div>';
            }else{
                return '<div id="message" class="error below-h2"><p>'.__('Unable to create group. There was an unspecified system error.','contexture-page-security').'</p></div>';
            }
        } else {
            return '<div id="message" class="error below-h2"><p>'.__('Unable to create group. A group with that name already exists.','contexture-page-security').'</p></div>';
        }
    }

}
}

if(!class_exists('CTXPS_Security')){
/**
 * Handles security-related actions.
 */
class CTXPS_Security{
    /**
     * Top-level method for running security check on content, then displaying
     * Access Denied message when appropriate. Overall, this is the core
     * validation method for PSC.
     *
     * @global object $post Gets db information about this post (used to determind post_type)
     * @global object $current_user Info for the currently logged in user
     * @param string $content
     * @return string
     */
    public static function protect_content(){
        global $post,$page,$id,$current_user;
        $secureallowed = true;
        /**Get plugin options*/
        $plugin_opts = get_option('contexture_ps_options');

        //SITE-WIDE PROTECTION
        if($plugin_opts['ad_msg_protect_site']=='true'){
            /**Groups that this user is a member of*/
            $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
            foreach($useraccess as $group){
                ///////////////////////////////////////////////////////////////////////
            }
        }

        //CONTENT-SPECIFIC PROTECTION
        if(!current_user_can('edit_others_posts') && !is_home() && !is_category() && !is_tag() && !is_feed() && !is_admin() && !is_404() && !is_search()) {
            if(empty($useraccess)){
                /**Groups that this user is a member of*/
                $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
            }
            /**Groups required to access this page*/
            $pagereqs = CTXPS_Security::get_protection($post->ID);

            //wp_die(print_r($pagereqs,true));

            if(!!$pagereqs){
                //Determine if user can access this content
                $secureallowed = CTXPS_Security::check_access($useraccess,$pagereqs);

                //wp_die(print_r($secureallowed,true));

                if($secureallowed){
                    //If we're allowed to access this page (do nothing)
                }else{
                    //If we're NOT allowed to access this page
                    self::deny_access($plugin_opts);
                }
            }
        }
    }


    /**
     * Hooks to the loop and removes data for posts that are protected when the security
     * doesn't pass muster.
     *
     * @global object $current_user
     * @param array $content
     * @return <type>
     */
    public static function filter_loops($content){
        global $current_user;

            //print_r($content);
        $dbOpts = get_option('contexture_ps_options');

        if(is_feed() && $dbOpts['ad_msg_usefilter_rss']=='false'){
            //If this is a feed and it's filtering is explicitly disabled, do no filtering. Otherwise... filter as normal (below)
            return $content;
        }else{
            //Do this only if user is not an admin, or if this is the blog page, category page, tag page, or feed (and isnt an admin page)
            if( !current_user_can('edit_others_posts') && ( is_home() || is_category() || is_tag() || is_feed() || is_search() )  && !is_admin()) {
                foreach($content as $post->key => $post->value){
                    //Fun with manipulating the array
                    //$post->value->post_content = "<h2>{$post->value->ID}</h2>".$post->value->post_content;

                    /**Groups that this user is a member of*/
                    $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
                    /**Groups required to access this page*/
                    $pagereqs = CTXPS_Security::get_protection($post->value->ID);

                    if(!!$pagereqs){
                        $secureallowed = CTXPS_Security::check_access($useraccess,$pagereqs);

                        if($secureallowed){
                            //If we're allowed to access this page
                        }else{
                            //If we're NOT allowed to access this page
                            unset($content[$post->key]);
                        }
                    }
                }//End foreach
            }//End appropriate section check
        }

        //Adjust top-level array key numbers to be concurrent (since a gap between numbers can cause wp to freak out)
        $content = array_merge($content,array());

        return $content;
    }


    /**
     * When the default menu is being used, this checks restrictions for each page
     * in the menu and removes it if it's restricted for the current user.
     *
     * @global object $current_user
     * @param array $content
     * @return The array of wordpress posts used to build the default menu
     */
    public static function filter_auto_menus($content){
        global $current_user;

        //print_r($content);
        $dbOpts = get_option('contexture_ps_options');//ad_msg_usefilter_menus

        //Do this filtering only if the user isn't an admin (and isn't in admin section)... and provided the user hasn't explicitly set menu filtering to false
        if( !current_user_can('edit_others_posts')  && !is_admin() && $dbOpts['ad_msg_usefilter_menus']!='false') {

            //Loop through the content array
            foreach($content as $post->key => $post->value){

                //Get groups that this user is a member of
                $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
                //Get groups required to access this page
                $pagereqs = CTXPS_Security::get_protection($post->value->ID);

                //So long as $pagereqs is anything but false
                if(!!$pagereqs){

                    //Determine user access
                    $secureallowed = CTXPS_Security::check_access($useraccess,$pagereqs);

                    if($secureallowed){
                        //If we're allowed to access this page
                    }else{
                        //If we're NOT allowed to access this page
                        unset($content[$post->key]); //Remove content from array
                    }
                }

                //If this is an AD page, strip it too
                if($dbOpts['ad_msg_usepages']==='true'){
                    if($post->value->ID==$dbOpts['ad_page_auth_id'] || $post->value->ID==$dbOpts['ad_page_anon_id']){
                        unset($content[$post->key]);
                    }
                }
            }
        }

        return $content;
    }


    /**
     * When a WP3 custom menu is being used, this checks restrictions for each page
     * in the menu and removes it if it's restricted to the current user.
     *
     * @global object $current_user
     * @param array $content
     * @return The array of wordpress posts used to build the custom menu.
     */
    public static function filter_custom_menus($content){
        global $current_user;

        //print_r($content);
        $dbOpts = get_option('contexture_ps_options');//ad_msg_usefilter_menus

        //Do this filtering only if user isn't an admin, in admin section... and provided the user hasn't explicitly set menu filtering to false
        if( !current_user_can('edit_others_posts') && !is_admin() && $dbOpts['ad_msg_usefilter_menus']!='false' ) {

            //Get options (in case we need to strip access denied pages)
            $dbOpts = get_option('contexture_ps_options');

            foreach($content as $post->key => $post->value){

                //Get groups that this user is a member of
                $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
                //Get groups required to access this page
                $pagereqs = CTXPS_Security::get_protection($post->value->object_id);

                //So long as $pagereqs is anything but false
                if(!!$pagereqs){

                    //Determine user access
                    $secureallowed = CTXPS_Security::check_access($useraccess,$pagereqs);

                    if($secureallowed){
                        //If we're allowed to access this page
                    }else{
                        //If we're NOT allowed to access this page
                        unset($content[$post->key]);
                    }
                }
                //If this is an AD page, strip it too
                if($dbOpts['ad_msg_usepages']==='true'){
                    if($post->value->object_id==$dbOpts['ad_page_auth_id'] || $post->value->object_id==$dbOpts['ad_page_anon_id']){
                        unset($content[$post->key]);
                    }
                }
            }
        }

        return $content;
    }


    /**
     * This function takes an array of user groups and an array of page-required groups
     * and determines if the user should be allowed to access the specified content.
     *
     * @param array $UserGroupsArray The array returned by CTXPS_Queries::get_user_groups()
     * @param array $PageSecurityArray The array returned by ctx_ps_get_protection()
     * @return bool Returns true if user has necessary permissions to access the page, false if not.
     */
    public static function check_access($UserGroupsArray,$PageSecurityArray){

        //Testing...
        //wp_die(print_r($UserGroupsArray,true).' | '.print_r($PageSecurityArray,true));

        //If our page-security array is empty, automatically return false
        if(!!$PageSecurityArray && count($PageSecurityArray) == 0){return false;}

        //Used to count each page that has at least one group
        $loopswithgroups = 0;

        //Loop through each page's permissions, starting with current page and going up the heirarchy...
        foreach($PageSecurityArray as $security->page => $security->secarray){
            //If the current page has group settings attached...
            if(count($security->secarray) != 0){
                //Increment our group tracking var
                $loopswithgroups += 1;
                //If any of this user's groups do not match any of this page's groups...
                if( count(array_intersect($UserGroupsArray,$security->secarray)) == 0 ){
                    //We return false as the user does not have access
                    return false;
                }
            }
        }

        //If no pages have groups, then no-one can access the page
        if($loopswithgroups === 0){return false;}

        //If we haven't triggered a false return already, return true
        return true;

    }


    /**
     * This function will check the security for the specified page and all parent pages.
     * If security exists, a multi-dimensional array will be returned following the format
     * array( pageid=>array(groupid=>groupname) ), with the first item being the current
     * page and additional items being parents. If no security is present for any ancestor
     * then the function will return false.
     *
     * @global wpdb $wpdb
     *
     * @param int $post_id The id of the post to get permissions for.
     * @return mixed Returns an array with all the required permissions to access this page. If no security is present, returns false.
     */
    public static function get_protection($post_id){
        //If this branch isn't protected, just stop now and save all that processing power
        if (!CTXPS_Queries::check_section_protection($post_id)){
            return false;
        }

        //If we're still going, then it means something above us is protected, so lets get the list of permissions
        global $wpdb;
        $return = array();
        $group_array = array();
        /**Gets the parent id of the current page/post*/
        $parent_id = get_post($post_id);
        $parent_id = (integer)$parent_id->post_parent;
        /**Gets the ctx_ps_security data for this post (if it exists) - used to determine if this is the topmost secured page*/
        //$amisecure = get_post_meta($postid,'ctx_ps_security',true);

        //1. If I am secure, get my groups
        //if(!empty($amisecure)){
            //Get Group relationship info for this page from wp_ps_security, join wp_posts on postid
            $groups = CTXPS_Queries::get_groups_by_post($post_id, true);

            //If 0 results, dont do anything. Otherwise...
            if(!empty($groups)){
                foreach($groups as $group){
                    $group_array[$group->group_id] = $group->group_title;
                }unset($group);
            }
        //}
        //Add an item to the array. 'pageid'=>array('groupid','groupname')
        $return[(string)$post_id] = $group_array;
        unset($group_array);
        //2. If I have a parent, recurse
            //Using our earlier results, check post_parent. If it's != 0 then recurse this function, adding the return value to $array
            if($parent_id != 0){
                //$recursedArray = CTXPS_Security::get_protection($parentid);
                //$array = array_merge($array,$recursedArray);
                $parent_array = self::get_protection($parent_id);
                if(!!$parent_array){
                  $return += $parent_array;
                }
            }

        //3. Return the completed $array
        return $return;
    }

    /**
     * Alias for CTXPS_Queries::check_protection. Internally, please use CTXPS_Queries
     * instead of this. Alias is provided for developer-friendliness only.
     *
     * @return bool Whether this page has the "protected page" flag
     */
    public static function check_protection($post_id){
        return CTXPS_Queries::check_protection($post_id);
    }
    
    /**
     * When called, will determined which AD message or page to show, then show it
     * @param array $plugin_opts If db options are provided, we won't have to query this again
     */
    public static function deny_access($plugin_opts=array()){
        global $current_user;
        
        if(empty($plugin_opts)){
            $plugin_opts = get_option('contexture_ps_options');
        }
        
        //If user is NOT logged in...
        if($current_user->ID == 0 && !is_user_logged_in()){
            //Check options to determine if we're using a PAGE or a MESSAGE
            if($plugin_opts['ad_msg_usepages']==='true'){ //Have to exempt feed else it interupts feed render
                //Send user to the new page
                if(is_numeric($plugin_opts['ad_page_anon_id'])){
                    $redir_anon_link = get_permalink($plugin_opts['ad_page_anon_id']);
                    wp_safe_redirect($redir_anon_link,401);
                    exit(sprintf(__('Access Denied. Redirecting to %s','contexture-page-security'),$redir_anon_link)); //Regular die to prevent restricted content from slipping out
                }else{
                    //Just in case theres a config problem...
                    wp_die($plugin_opts['ad_msg_anon'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; '.__('Go to home page','contexture-page-security').'</a>');
                }
            }else{
                //If user is anonymous, show this message
                $blogurl = get_bloginfo('url');
                wp_die($plugin_opts['ad_msg_anon'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; '.__('Go to home page','contexture-page-security').'</a>');
            }
        }else{
            //Check options to determine if we're using a PAGE or a MESSAGE
            if($plugin_opts['ad_msg_usepages']==='true'){
                //Send user to the new page
                if(is_numeric($plugin_opts['ad_page_auth_id'])){
                    $redir_auth_link = get_permalink($plugin_opts['ad_page_auth_id']);
                    wp_safe_redirect($redir_auth_link,401);
                    exit(sprintf(__('Access Denied. Redirecting to %s','contexture-page-security'),$redir_auth_link)); //Regular die to prevent restricted content from slipping out
                }else{
                    //Just in case theres a config problem...
                    wp_die($plugin_opts['ad_msg_auth'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; '.__('Go to home page','contexture-page-security').'</a>');
                }
            }else{
                //If user is authenticated, show this message
                wp_die($plugin_opts['ad_msg_auth'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; '.__('Go to home page','contexture-page-security').'</a>');
            }
        }
    }

}
}
?>
