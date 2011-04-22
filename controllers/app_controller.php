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
        add_action('admin_head', array('CTXPS_App','js_strings_init'));

        //Enable Restrict Access sidebar for ALL post types (will also automatically enable support for any custom types)
        $post_types = get_post_types();
        foreach($post_types as $type){
            add_meta_box('ctx_ps_sidebar_security', 'Restrict Access', array('CTXPS_Router','sidebar_security'), $type, 'side', 'low');
        }unset($type);
        //Enable Restrict Access options for taxonomy terms
        $tax_types = get_taxonomies();
        foreach($tax_types as $tax){
            //Add fields to the taxonomy term edit form
            add_action( $tax.'_edit_form', array('CTXPS_Router','security_tax') );
            //Add fields to the taxonomy add form
            //add_action( $tax.'_add_form',array( 'CTXPS_Components', 'render_taxonomy_protection_panel' ) );
            //Add fields to the taxonomy add form
            add_action( $tax.'_edit_form', array('CTXPS_Router','security_tax') );
        }unset($tax);

        //Add our custom admin styles
        wp_enqueue_style('psc_admin',CTXPSURL.'views/admin.css');
    }


    /**
     * Adds additional contextual help to WordPress' existing contextual help screens
     * @global array $_wp_contextual_help
     */
    public static function help_init(){
        //We bring in the global help array so we can modify it
        global $_wp_contextual_help,$pagenow;

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
            $ps_groups_edit = __('<p>This screen shows you all the details about the current group, and allows you to edit some of those details.</p><p><strong>Group Details</strong> - Change a group\'s title or description.</p><p><strong>Site Access</strong> - This option is visible if you have Site Protection enabled. Set to "Allowed" if you would like users in this group to be able to access your website. All content-specific restrictions still apply.</p><p><strong>Group Members</strong> - A list of users currently attached to the group. You also add users to a group if you know their username (users can also be added to groups from their profile pages).</p><p><strong>Associated Content</strong> - A list of all the pages and posts this group is attached to.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl;
            add_contextual_help( 'dashboard_page_ps_groups_edit', $ps_groups_edit );
            add_contextual_help( 'users_page_ps_groups_edit', $ps_groups_edit );
            $ps_groups_delete = __('<p>This screen allows you to delete the selected group. Once you click "Confirm Deletion", the group will be permanently deleted, and all users will be removed from the group.</p><p>Also note that if this is the only group attached to any "restricted" pages, those pages will not longer be accessible to anyone but administrators.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl;
            add_contextual_help( 'dashboard_page_ps_groups_delete', $ps_groups_delete );
            add_contextual_help( 'users_page_ps_groups_delete', $ps_groups_delete );
            add_contextual_help( 'settings_page_ps_manage_opts', __('<p>This screen contains general settings for Page Security.</p><p><strong>For more information:</strong></p>','contexture-page-security').$supporturl );
            //add_contextual_help( 'users_page', __('<p><strong>Page Security:</strong></p><p>To add multiple users to a group, check off the users you want to add, select the group from the "Add to group..." drop-down, and click "Add".</p><p><p><strong>For more information:</strong></p><a href="http://www.contextureintl.com/open-source-projects/contexture-page-security-for-wordpress/">Official Page Security Support</a></p>','contexture-page-security') );

        }

        //If this is the users page, use javascript to inject another bulk options box (damn you, WP core team for pulling my 3.1 bulk hooks!)
        if($pagenow==='users.php' && empty($_GET['page']) && current_user_can( 'promote_users' )){
            self::js_userbulk_init();
        }
    }

    /**
     * Adds some custom JS to the header, primarily AJAX. We can't enqueue these since we need PHP localization for the strings.
     */
    public static function js_strings_init(){
        ?>
        <script type="text/javascript">
            var ctxpsmsg = {
                NoUnprotect : '<?php _e('You cannot unprotect this page. It is protected by a parent or ancestor.','contexture-page-security') ?>',
                EraseSec : "<?php _e("This will completely erase this page's security settings and make it accessible to the public. Continue?",'contexture-page-security') ?>",
                RemoveGroup : '<?php _e('Are you sure you want to remove group "%s" from this page?','contexture-page-security') ?>',
                RemovePage : '<?php _e('Are you sure you want to remove this group from %s ?','contexture-page-security') ?>',
                RemoveUser : '<?php _e('Remove this user from the group?','contexture-page-security') ?>',
                YearRequired : '<?php _e('You must specify an expiration year.','contexture-page-security') ?>',
                GeneralError : '<?php _e('An error occurred: ','contexture-page-security') ?>',
                NoGroupSel : '<?php _e('You must select a group to add.','contexture-page-security') ?>',
                SiteProtectAdd : '<?php _e('This adds protection at a site level. Until you select site options for each group, your users will be unable to access the website.','contexture-page-security') ?>',
                SiteProtectDel : '<?php _e('This will completely erase site-level security settings and make it accessible to the public. Continue?','contexture-page-security') ?>'
            };
        </script>
        <script type="text/javascript" src="<?php echo CTXPSURL.'js/core-ajax.dev.js' ?>"></script>
        <?php
    }



    /**
     * Adds various menu items to WordPress admin
     */
    public static function admin_screens_init(){
        //Add Groups option to the WP admin menu under Users (these also return hook names, which are needed for contextual help)
        add_submenu_page('users.php', __('Group Management','contexture-page-security'), __('Groups','contexture-page-security'), 'manage_options', 'ps_groups', array('CTXPS_Router','groups_list'));
        add_submenu_page('users.php', __('Add a Group','contexture-page-security'), __('Add Group','contexture-page-security'), 'manage_options', 'ps_groups_add', array('CTXPS_Router','group_add'));
        add_submenu_page('', __('Edit Group','contexture-page-security'), __('Edit Group','contexture-page-security'), 'manage_options', 'ps_groups_edit', array('CTXPS_Router','group_edit'));
        add_submenu_page('', __('Delete Group','contexture-page-security'), __('Delete Group','contexture-page-security'), 'manage_options', 'ps_groups_delete', array('CTXPS_Router','group_delete'));

        add_options_page('Page Security by Contexture', 'Page Security', 'manage_options', 'ps_manage_opts', array('CTXPS_Router','options'));
        //add_submenu_page('options-general.php', 'Page Security', 'Page Security', 'manage_options', 'ps_manage_opts', 'ctx_ps_page_options');
    }

    /**
     * Loads localized language files, if available
     */
    public static function localize_init(){
       if (function_exists('load_plugin_textdomain')) {
          load_plugin_textdomain('contexture-page-security', false, CTXPSDIR.'/languages' );
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

    /**
     * Uses javascript to inject an AJAX-loaded bulk-add-to-group box to the Users list
     */
    public static function js_userbulk_init(){
        ?>
        <script type="text/javascript">
            jQuery(function(){
                jQuery('.tablenav.top .alignleft.actions:last').after('<?php echo CTXPS_Components::render_bulk_add_to_group(); ?>');
            });
        </script>
        <?php
    }

}
}

?>