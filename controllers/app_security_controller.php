<?php

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
        global $post,$page,$id,$current_user,$is_IIS;
        $secureallowed = true;
        $plugin_opts = get_option('contexture_ps_options');

        //SET 401 CODE IF ON AD PAGE (AND NEVER BLOCK)
        if(!is_admin() && CTXPS_Queries::check_ad_status()){
            if ( !$is_IIS && php_sapi_name() != 'cgi-fcgi' ){
                status_header(401); // This causes problems on IIS and some FastCGI setups
            }
            return;//Exit the function, no further checks are needed
        }

        //CONDITIONS WHERE USER SHOULD BE LET THROUGH
        if(current_user_can('edit_others_posts')){
            return;//Exit the function, no further checks are needed
        }

        //SITE-WIDE PROTECTION
        if($plugin_opts['ad_opt_protect_site']==='true'){

            /**Groups that this user is a member of*/
            $siteaccess = CTXPS_Queries::get_user_groups($current_user->ID,true);
            //User isnt in any groups, no more checking necessary
            if(empty($siteaccess)){
                self::deny_access($plugin_opts);
            }
            //If $siteaccess returned anything, we can safely assume user has "Limited"
            //Since "Full" isn't implemented yet, we don't have to make that check or change the way get_user_groups works
        }

        //CONTENT-SPECIFIC PROTECTION
        if(!is_home() && !is_category() && !is_tag() && !is_feed() && !is_tax() && !is_admin() && !is_404() && !is_search()) {
            if(empty($useraccess)){
                /**Groups that this user is a member of*/
                $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
            }
            /**Groups required to access this page*/
            $pagereqs = CTXPS_Security::get_post_protection($post->ID);

            //wp_die(print_r($pagereqs,true));

            if(!!$pagereqs){
                //Determine if user can access this content
                $secureallowed = CTXPS_Security::check_access($useraccess,$pagereqs);

                //wp_die(print_r($secureallowed,true));

                //NOT ALLOWED TO ACCESS!
                if(!$secureallowed){
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
            if( !current_user_can('edit_others_posts') && ( is_home() || is_category() || is_tag() || is_tax() || is_feed() || is_author() || is_search() )  && !is_admin()) {
                foreach($content as $post->key => $post->value){

                    /**Groups that this user is a member of*/
                    $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
                    /**Groups required to access this page*/
                    $pagereqs = CTXPS_Security::get_post_protection($post->value->ID);

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

        $dbOpts = get_option('contexture_ps_options');//ad_msg_usefilter_menus

        //Do this filtering only if the user isn't an admin (and isn't in admin section)... and provided the user hasn't explicitly set menu filtering to false
        if( !current_user_can('edit_others_posts')  && !is_admin() && $dbOpts['ad_msg_usefilter_menus']!='false') {

            //NO MENU!!! If site protect is on, menu filtering is on, and user is anon, remove EVERYTHING
            if($dbOpts['ad_opt_protect_site']==='true' &&
               (!is_user_logged_in() || $current_user->ID==0)){
                return array();
            }

            //Loop through the content array
            foreach($content as $post->key => $post->value){

                //Get groups that this user is a member of
                $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
                //Get groups required to access this page
                $pagereqs = CTXPS_Security::get_post_protection($post->value->ID);

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

        $dbOpts = get_option('contexture_ps_options');//ad_msg_usefilter_menus


        //Do this filtering only if user isn't an admin, in admin section... and provided the user hasn't explicitly set menu filtering to false
        if( !current_user_can('edit_others_posts') && !is_admin() && $dbOpts['ad_msg_usefilter_menus']!='false' ) {

            //NO MENU!!! If site protect is on, menu filtering is on, and user is anon, remove EVERYTHING
            if($dbOpts['ad_opt_protect_site']==='true' &&
               (!is_user_logged_in() || $current_user->ID==0)){
                return array();
            }

            //Get options (in case we need to strip access denied pages)
            $dbOpts = get_option('contexture_ps_options');

            foreach($content as $post->key => $post->value){

                //Get groups that this user is a member of
                $useraccess = CTXPS_Queries::get_user_groups($current_user->ID);
                //Get groups required to access this page
                $pagereqs = CTXPS_Security::get_post_protection($post->value->object_id);

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

        //Loop through each page's permissions, starting with current page and travelling UP the heirarchy...
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
                /**TODO: Ensure user isnt expired?*/
            }
        }

        //If no pages have groups, then no-one can access the page
        if($loopswithgroups === 0){return false;}

        //If we haven't triggered a false return already, return true
        return true;

    }


    /**
     * Alias for self::get_{$type}_protection() to maintain backwards compatibility.
     *
     *
     * @param int $content_id The id of the content to check
     * @param string $content_type The type of content to check
     * @return type
     */
    public static function get_protection($content_id,$content_type='post'){
        switch ($content_type){
            case 'post':
                return self::get_post_protection($content_id);
                break;
            case 'term':
                return self::get_term_protection($content_id);
                break;
            default:
                return false;
                break;
        }
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
     * @param int $content_id The id of the post to get permissions for.
     * @param string $content_type What type of content are we checking? Defaults to "all"
     * @return mixed Returns an array with all the required permissions to access this page. If no security is present, returns false.
     */
    public static function get_post_protection($content_id){

        //If this branch isn't protected, just stop now and save all that processing power
        if (!CTXPS_Queries::check_section_protection($content_id)){
            return false;
        }

        //If we're still going, then it means something above us is protected, so lets get the list of permissions
        global $wpdb;
        $return = array();
        $group_array = array();
        /**Gets the parent id of the current page/post*/
        $parent_id = get_post($content_id);
        $parent_id = (integer)$parent_id->post_parent;
        /**Gets the ctx_ps_security data for this post (if it exists) - used to determine if this is the topmost secured page*/
        //$amisecure = get_post_meta($postid,'ctx_ps_security',true);

        //1. If I am secure, get my groups
        //if(!empty($amisecure)){
            //Get Group relationship info for this page from wp_ps_security, join wp_posts on postid
            $groups = CTXPS_Queries::get_groups_by_object('post',$content_id, true);

            //If 0 results, dont do anything. Otherwise...
            if(!empty($groups)){
                foreach($groups as $group){
                    $group_array[$group->group_id] = $group->group_title;
                }unset($group);
            }
        //}
        //Add an item to the array. 'pageid'=>array('groupid','groupname')
        $return[(string)$content_id] = $group_array;
        unset($group_array);
        //2. If I have a parent, recurse
            //Using our earlier results, check post_parent. If it's != 0 then recurse this function, adding the return value to $array
            if($parent_id != 0){
                //$recursedArray = CTXPS_Security::get_protection($parentid);
                //$array = array_merge($array,$recursedArray);
                $parent_array = self::get_post_protection($parent_id);
                if(!!$parent_array){
                  $return += $parent_array;
                }
            }

        //3. Return the completed $array
        return $return;
    }


    /**
     * This function will check the security for the specified term and all parent terms.
     * If security exists, a multi-dimensional array will be returned following the format
     * array( term_id=>array(group_id=>group_name) ), with the first item being the current
     * term and additional items being parents. If no security is present for any ancestor
     * then the function will return false.
     *
     * @global wpdb $wpdb
     *
     * @param int $term_id The id of the post to get permissions for.
     * @param string $taxonomy The name of the taxonomy that needs to be checked
     * @return mixed Returns an array with all the required permissions to access this page. If no security is present, returns false.
     */
    public static function get_term_protection($term_id,$taxonomy){

        //If this branch isn't protected, just stop now and save all that processing power
        if (!CTXPS_Queries::check_term_protection($term_id,$taxonomy)){
            return false;
        }

        //If we're still going, then it means something above us is protected, so lets get the list of permissions
        global $wpdb;
        $return = array();
        $group_array = array();
        /**Gets the parent id of the current page/post*/
        $parent_id = get_term($term_id,$taxonomy);
        $parent_id = (integer)$parent_id->parent;
        /**Gets the ctx_ps_security data for this post (if it exists) - used to determine if this is the topmost secured page*/
        //$amisecure = get_post_meta($postid,'ctx_ps_security',true);

        //1. If I am secure, get my groups
        //if(!empty($amisecure)){
            //Get Group relationship info for this page from wp_ps_security, join wp_posts on postid
            $groups = CTXPS_Queries::get_groups_by_object('term',$term_id, true);

            //If 0 results, dont do anything. Otherwise...
            if(!empty($groups)){
                foreach($groups as $group){
                    $group_array[$group->group_id] = $group->group_title;
                }unset($group);
            }
        //}
        //Add an item to the array. 'pageid'=>array('groupid','groupname')
        $return[(string)$term_id] = $group_array;
        unset($group_array);
        //2. If I have a parent, recurse
            //Using our earlier results, check post_parent. If it's != 0 then recurse this function, adding the return value to $array
            if($parent_id != 0){
                //$recursedArray = CTXPS_Security::get_protection($parentid);
                //$array = array_merge($array,$recursedArray);
                $parent_array = self::get_term_protection($parent_id);
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
     *
     * @param array $plugin_opts If db options are provided, we won't have to query this again
     */
    public static function deny_access($plugin_opts=array()){
        global $current_user,$post,$is_IIS;

        if(empty($plugin_opts)){
            $plugin_opts = get_option('contexture_ps_options');
        }
        $blogurl = get_bloginfo('url');

        //HANDLE UNAUTHENTICATED USERS.....
        if($current_user->ID == 0 || !is_user_logged_in()){

            //IF FORCE LOGIN....
            if($plugin_opts['ad_opt_login_anon']==='true'){
                wp_safe_redirect(wp_login_url((empty($_SERVER['HTTPS'])?'http://':'https://').$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']));
                die();
            }

            //If site restriction is enabled and user isnt logged in, don't bother linking to the homepage
            $homepage_link = '';
            if( $plugin_opts['ad_opt_protect_site']!=='true' ){
                $homepage_link = '<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; '.__('Go to home page','contexture-page-security').'</a>';
            }

            //SHOW AD *PAGE*
            if($plugin_opts['ad_msg_usepages']==='true'){ //Have to exempt feed else it interupts feed render

                //IF USING PAGE...
                if(is_numeric($plugin_opts['ad_page_anon_id'])){

                    //IF USING REPLACEMENT...
                    if($plugin_opts['ad_opt_page_replace']==='true'){
                        $new_content = get_post($plugin_opts['ad_page_anon_id']);
                        $post->post_title = $new_content->post_title;
                        $post->post_content = $new_content->post_content;
                        if ( !$is_IIS && php_sapi_name() != 'cgi-fcgi' ){
                            status_header(401); // This causes problems on IIS and some FastCGI setups
                        }
                        return;
                    //ELSE USE REDIRECT...
                    }else{
                        $redir_anon_link = get_permalink($plugin_opts['ad_page_anon_id']);
                        wp_redirect($redir_anon_link);
                        exit(sprintf(__('Access Denied. Redirecting to %s','contexture-page-security'),$redir_anon_link)); //Regular die to prevent restricted content from slipping out
                    }

                //INVALID PAGE, USE MSG
                }else{
                    wp_die($plugin_opts['ad_msg_anon'].$homepage_link);
                }

            //SHOW AD *MSG*
            }else{
                //If user is anonymous, show this message
                wp_die($plugin_opts['ad_msg_anon'].$homepage_link);
            }


        //HANDLE AUTHENTICATED USERS....
        }else{

            //SHOW AD *PAGE*
            if($plugin_opts['ad_msg_usepages']==='true'){

                //IF USING PAGE...
                if(is_numeric($plugin_opts['ad_page_auth_id'])){

                    //IF USING REPLACEMENT...
                    if($plugin_opts['ad_opt_page_replace']==='true'){
                        $new_content = get_post($plugin_opts['ad_page_auth_id']);
                        $post->post_title = $new_content->post_title;
                        $post->post_content = $new_content->post_content;
                        if ( !$is_IIS && php_sapi_name() != 'cgi-fcgi' ){
                            status_header(401); // This causes problems on IIS and some FastCGI setups
                        }
                        return;

                    //ELSE USE REDIRECT...
                    }else{
                        $redir_auth_link = get_permalink($plugin_opts['ad_page_auth_id']);
                        wp_redirect($redir_auth_link);
                        exit(sprintf(__('Access Denied. Redirecting to %s','contexture-page-security'),$redir_auth_link)); //Regular die to prevent restricted content from slipping out
                    }

                //INVALID PAGE, USE MSG
                }else{
                    wp_die($plugin_opts['ad_msg_auth'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; '.__('Go to home page','contexture-page-security').'</a>');
                }

            //SHOW AD *MSG*
            }else{
                //If user is authenticated, show this message
                wp_die($plugin_opts['ad_msg_auth'].'<a style="display:block;font-size:0.7em;" href="'.$blogurl.'">&lt;&lt; '.__('Go to home page','contexture-page-security').'</a>');
            }
        }
        exit(); //Useless
    }

}
}
?>