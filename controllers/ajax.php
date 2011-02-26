<?php
if(!class_exists('CTXPS_Ajax')){
class CTXPS_Ajax {

    /**
     * SIDEBAR. Handles ajax requests to add a group to a page. When successful, generates HTML to be used in the "Allowed Groups"
     * section of the "Restrict Page" sidebar. Spits out XML response for AJAX use.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpsdb
     */
    public static function add_group_to_page(){
        global $wpdb, $ctxpsdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users') || !current_user_can('edit_pages')){
            //ERROR! If user isn't authorized, stop and return error
            $response = new WP_Ajax_Response(array(
                'what'=>    'add_group',
                'action'=>  'add_group_to_page',
                'id'=>      new WP_Error('error',__('User is not authorized.','contexture-page-security'))
            ));
            $response->send();
        }

        //Run the query
        $result = CTXPS_Queries::add_security($_GET['postid'],$_GET['groupid']);

        if($result!==false){

            //Get security info for the specified page and it's parents
            $security = CTXPS_Security::get_protection( $_GET['postid'] );

            //SUCCESS!
            $response = new WP_Ajax_Response(array(
                'what'=>    'add_group',
                'action'=>  'add_group_to_page',
                'id'=>      1,
                'data'=>    __('Group added to content','contexture-page-security'),
                'supplemental'=>array('html'=>CTXPS_Components::render_sidebar_attached_groups($security,$_GET['postid']))
            ));
            $response->send();
        }
    }

    /**
     * Handles ajax requests to remove a group from a specified page
     */
    public static function remove_group_from_page(){
        global $wpdb;

        $response='';
        $supplemental=array();

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users') || !current_user_can('edit_pages')){
            //ERROR! If user isn't authorized
            $response = array(
                'what'=>    'remove_group',
                'action'=>  'remove_group_from_page',
                'id'=>      new WP_Error('error',__('User is not authorized.','contexture-page-security'))
            );
            $response = new WP_Ajax_Response($response);
            $response->send();
        }

        if(CTXPS_Queries::delete_security($_GET['postid'],$_GET['groupid']) !== false){
            //Which content do we need to render?
            if(isset($_GET['requester']) && $_GET['requester']=='sidebar'){
                $supplemental = array('html'=>CTXPS_Components::render_sidebar_attached_groups($_GET['postid']));//We need to regenerate sidebar content
            }else{
                $supplemental = array('html'=>new CTXPS_Table_Packages('associated_content',false,true)/*CTXPS_Components::render_content_by_group_list($_GET['groupid'])*/);//We need to regenerate list-table content
            }

            //SUCCESS!
            $response = array(
                'what'=>    'remove_group',
                'action'=>  'remove_group_from_page',
                'id'=>      1,
                'data'=>    __('Group removed from content','contexture-page-security'),
                'supplemental'=>$supplemental
            );
        }
        else{
            //ERROR!
            $response = array(
                'what'=>    'remove_group',
                'action'=>  'remove_group_from_page',
                'id'=>      new WP_Error('error',__('Query failed or content not in group.','contexture-page-security'))
            );
        }
        $response = new WP_Ajax_Response($response);
        $response->send();
    }

    /**
     * GROUP EDIT > USERS. USER PROFILES. Handles ajax requests to add a user to a group
     *
     * @global wpdb $wpdb
     */
    public static function add_group_to_user(){
        global $wpdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users')){
            //ERROR! If user isn't authorized
            $response = new WP_Ajax_Response(array(
                'what'=>    'enroll',
                'action'=>  'add_group_to_user',
                'id'=>      new WP_Error('error',__('User is not authorized.','contexture-page-security'))
            ));
            $response->send();
        }

        //If this user doesn't exist
        if(!CTXPS_Queries::check_user_exists($_GET['user_id'])){
            //ERROR!
            $response = new WP_Ajax_Response(array(
                'what'=>    'enroll',
                'action'=>  'add_group_to_user',
                'id'=>      new WP_Error('error',__('User not found','contexture-page-security'))
            ));
            $response->send();
        } else {

            //Make sure user isnt already in the group
            if(CTXPS_Queries::check_membership($_GET['user_id'], $_GET['groupid'])){
                //ERROR!
                $response = new WP_Ajax_Response(array(
                    'what'=>    'enroll',
                    'action'=>  'add_group_to_user',
                    'id'=>      new WP_Error('error',__('User already in group','contexture-page-security'))
                ));
                $response->send();
            }

            //Add the user to the group
            if(CTXPS_Queries::add_membership($_GET['user_id'], $_GET['groupid']) === false){
                //ERROR!
                $response = new WP_Ajax_Response(array(
                    'what'=>    'enroll',
                    'action'=>  'add_group_to_user',
                    'id'=>      new WP_Error('error',__('Query failed','contexture-page-security'))
                ));
                $response->send();
            } else {
                //SUCCESS!!!!
                $response = new WP_Ajax_Response(array(
                    'what'=>    'enroll',
                    'action'=>  'add_group_to_user',
                    'id'=>      1,
                    'data'=>    __('User enrolled in group','contexture-page-security'),
                    'supplemental'=>array('html'=>CTXPS_Components::render_group_list($_GET['user_id'],'users'))//We need to regenerate table content
                ));
                $response->send();
            }
        }

    }


    /**
     * GROUP MEMBER TABLE. Handles ajax requests to update a users membership info
     *
     * @global wpdb $wpdb
     */
    public static function update_membership(){
        global $wpdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users')){
            //ERROR! If user isn't authorized, stop and return error
            $response = new WP_Ajax_Response(array(
                'what'=>    'update',
                'action'=>  'update_membership',
                'id'=>      new WP_Error('error',__('User is not authorized.','contexture-page-security'))
            ));
            $response->send();
        }

        //Determine if we need to pass NULL or a DateTime value...
        $db_expires = ($_POST['expires']=='1') ? $_POST['date'] : 'NULL';

        //Determine response
        if(CTXPS_Queries::grel_enrollment_update($_POST['grel'], $db_expires) === false){
            $response = array(
                'what'=>    'update',
                'action'=>  'update_membership',
                'id'=>      new WP_Error('error',__('Query failed.','contexture-page-security'))
            );
        } else {
            $response = array(
                'what'=>    'update',
                'action'=>  'update_membership',
                'id'=>      1,
                'data'=>    __('User membership updated','contexture-page-security')
            );
        }
        $response = new WP_Ajax_Response($response);
        $response->send();
    }

    /**
     * GROUP EDIT & USER PROFILE. Handles ajax requests to remove a group from a users account
     *
     * @global wpdb $wpdb
     */
    public static function remove_group_from_user(){
        global $wpdb, $current_user;
        $response = array();

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users')){
            //ERROR! - membership not found.
            $response = new WP_Ajax_Response(array(
                'what'=>    'unenroll',
                'action'=>  'remove_group_from_user',
                'id'=>      new WP_Error('error',__('User is not authorized.','contexture-page-security'))
            ));
            $response->send();
        }

        if(!CTXPS_Queries::delete_membership($_GET['user_id'], $_GET['groupid'])){
            //Error - membership not found.
            $response = array(
                'what'=>    'unenroll',
                'action'=>  'remove_group_from_user',
                'id'=>      new WP_Error('error',__('User not found in group.','contexture-page-security'))
            );
        } else {
            //SUCCESS!
            $response = array(
                'what'=>    'unenroll',
                'action'=>  'remove_group_from_user',
                'id'=>      1,
                'data'=>    __('User removed from group.','contexture-page-security'),
                'supplemental'=>array('html'=>CTXPS_Components::render_group_list($_GET['user_id'],'users',current_user_can('edit_users'),($_GET['user_id']==$current_user->ID)))//We need to regenerate table content
            );
        }
        $response = new WP_Ajax_Response($response);
        $response->send();
    }

    /**
     * Toggles page security on or off - removes all groups from page if toggled off
     *
     * @global wpdb $wpdb
     */
    public static function update_security(){
        global $wpdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_pages')){
            //ERROR! - membership not found.
            $response = new WP_Ajax_Response(array(
                'what'=>    'update_sec',
                'action'=>  'update_security',
                'id'=>      new WP_Error('error',__('User is not authorized.','contexture-page-security'))
            ));
            $response->send();
        }

        $response = array();
        switch($_GET['setting']){
            case 'on':
                $response = array(
                    'what'=>    'update_sec',
                    'action'=>  'update_security',
                    'id'=>      add_post_meta($_GET['postid'],'ctx_ps_security','1'),
                    'data'=>    __('Security enabled.','contexture-page-security')
                );
                break;
            case 'off':
                if(CTXPS_Queries::delete_security($_GET['postid']) !== false){
                    $response = array(
                        'what'=>    'update_sec',
                        'action'=>  'update_security',
                        'id'=>      delete_post_meta($_GET['postid'],'ctx_ps_security'),
                        'data'=>    __('Security disabled.','contexture-page-security')
                    );
                }else{
                    $response = new WP_Ajax_Response(array(
                        'what'=>    'update_sec',
                        'action'=>  'update_security',
                        'id'=>      new WP_Error('error',__('Query failed.','contexture-page-security'))
                    ));
                }
                break;
            default:
                $response = new WP_Ajax_Response(array(
                    'what'=>    'update_sec',
                    'action'=>  'update_security',
                    'id'=>      new WP_Error('error',__('Unrecognized request.','contexture-page-security'))
                ));
                break;
        }
        $response = new WP_Ajax_Response($response);
        $response->send();
    }
    
    public static function add_bulk_users_to_group(){
        $added_users = 0;
        
        //ERROR - No users selected!
        if(empty($_GET['users'])){
            $response = new WP_Ajax_Response(array(
                'what'=>    'bulk_enroll',
                'action'=>  'add_bulk_users_to_group',
                'id'=>      new WP_Error('error',__('No users were selected.','contexture-page-security')),
                'supplemental'=>array('html'=>  CTXPS_Components::render_wp_message(__('No users were selected.','contexture-page-security'), 'error'))
            ));
            $response->send();
        }
        
        //ERROR - No group selected
        if(empty($_GET['group_id'])){
            $response = new WP_Ajax_Response(array(
                'what'=>    'bulk_enroll',
                'action'=>  'add_bulk_users_to_group',
                'id'=>      new WP_Error('error',__('No group was selected.','contexture-page-security')),
                'supplemental'=>array('html'=>  CTXPS_Components::render_wp_message(__('No group was selected.','contexture-page-security'), 'error'))
            ));
            $response->send();
        }
        
        //Loop through all selected users...
        foreach($_GET['users'] as $user){
            //Ensure users exists and is isnt already in group
            if(CTXPS_Queries::check_user_exists($user['value']) && !CTXPS_Queries::check_membership($user['value'], $_GET['group_id'])){
                //Try to add user
                if(CTXPS_Queries::add_membership($user['value'], $_GET['group_id'])){
                    //increment for added users
                    $added_users++;
                }
            }
        }
        
        $response = new WP_Ajax_Response(array(
            'what'=>    'bulk_enroll',
            'action'=>  'add_bulk_users_to_group',
            'id'=>      1,
            'data'=>    '',
            'supplemental'=>array( 'html'=>CTXPS_Components::render_wp_message(sprintf(__('%d users were enrolled.','contexture-page-security'),$added_users), 'updated fade') )
        ));
        $response->send();
    }
}}
?>