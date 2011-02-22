<?php
if(!class_exists('CTXAjax')){
class CTXAjax{
    /**
     * Takes an associative array and outputs an XML ajax response.
     */
    public static function response($AssocArray=array()){

        //ctx_ps_ajax_response()

        if(!isset($AssocArray['code'])){
            $AssocArray['code'] = 0;
        }

        @header('Content-Type: text/xml; charset=' . get_option('blog_charset'));
        $response = "<?xml version='1.0' standalone='yes'?><ajax>";
        foreach($AssocArray as $element=>$value){
            $element = strtolower($element);
            $element = sanitize_title_with_dashes($element);
            $response .= "<{$element}>{$value}</{$element}>";
        }
        $response .= "</ajax>";
        die($response);
    }
}
}

class CTXPS_Ajax extends CTXAjax {

    /**
     * Handles ajax requests to add a group to a page. When successful, generates HTML to be used in the "Allowed Groups"
     * section of the "Restrict Page" sidebar. Spits out XML response for AJAX use.
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpsdb
     */
    public static function add_group_to_page(){
        global $wpdb, $ctxpsdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users') || !current_user_can('edit_pages')){
            //If user isn't authorized, stop and return error
            CTXAjax::response(array('code'=>'0','message'=>__('User is unauthorized to make this change.','contexture-page-security')));
        }

        //Run the query
        $result = CTXPS_Queries::add_security($_GET['postid'],$_GET['groupid']);

        if($result!==false){
            //Start with blank HTML output
            $OutputHTML = '';

            //Get security info for the specified page and it's parents
            $securityStatus = CTXPS_Security::get_protection( $_GET['postid'] );

            //Set $groupcount to 0, because we are about to count the number of groups attached to THIS page...
            $groupcount = 0;
            //If there's any security, count the number of groups attached to this page (including inherited groups from ancestors)
            if(!!$securityStatus) {
                foreach($securityStatus as $securityGroups){
                    //Increment $groupcount by the number of groups
                    $groupcount = $groupcount+count($securityGroups);
                }
            }
            //Show groups that are already added to this page
            if($groupcount===0){
                //Display this if we have no groups (inherited or otherwise)
                $OutputHTML = '<div><em>'.__('No groups have been added yet.','contexture-page-security').'</em></div>';
            }else{
                //Loop through each PAGE (starting with this one and working our way up)
                foreach($securityStatus as $securityArray->pageid => $securityArray->grouparray){
                    //If the pageid in the array is this page (ie: current page)
                    if($securityArray->pageid == $_GET['postid']){
                        //Loop through all groups for the CURRENT page
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            $OutputHTML .= '<div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><span class="removegrp" onclick="CTXPS.removeGroupFromPage('.$currentGroup->id.',jQuery(this))">'.__('remove','contexture-page-security').'</span></div>';
                        }
                    }else{
                        //Loop through all groups for the ANCESTOR page
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            $OutputHTML .= '<div class="ctx-ps-sidebar-group inherited">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><a class="viewgrp" target="_blank" href="'.admin_url().'post.php?post='.$securityArray->pageid.'&action=edit" >'.__('ancestor','contexture-page-security').'</a></div>';
                        }
                    }
                }
            }

            CTXAjax::response(array('code'=>0,'html'=>'<![CDATA['.$OutputHTML.']]>'));
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
            //If user isn't authorized
            $response = array(
                'what'=>'remove_group',
                'action'=>'remove',
                'id'=>new WP_Error('error',__('Admin user is not authorized.','contexture-page-security'))
            );
        }

        if(CTXPS_Queries::delete_security($_GET['postid'],$_GET['groupid']) !== false){
            if($_GET['requester']=='sidebar'){
                $supplemental = array('html'=>CTXPS_Components::render_sidebar_attached_groups($_GET['postid']));//We need to regenerate sidebar content
            }else{
                $supplemental = array('html'=>CTXPS_Components::render_content_by_group_list($_GET['groupid']));//We need to regenerate list-table content
            }

            //SUCCESS!
            $response = array(
                'what'=>'remove_group',
                'action'=>'remove',
                'id'=>1,
                'data'=>__('Group removed from content','contexture-page-security'),
                'supplemental'=>$supplemental
            );
        }
        else{
            //Some other problem
            $response = array(
                'what'=>'remove_group',
                'action'=>'remove',
                'id'=>new WP_Error('error',__('Query failed or content not in group.','contexture-page-security'))
            );
        }
        $response = new WP_Ajax_Response($response);
        $response->send();
    }

    /**
     * Handles ajax requests to add a user to a group
     *
     * @global wpdb $wpdb
     */
    public static function add_group_to_user(){
        global $wpdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users')){
            //If user isn't authorized
            CTXAjax::response(array('code'=>'0','message'=>__('Admin user is unauthorized.','contexture-page-security')));
        }

        //If this user doesn't exist
        if(!CTXPS_Queries::check_user_exists($_GET['user_id'])){
            CTXAjax::response(array('code'=>'0','message'=>__('User not found','contexture-page-security')));
        } else {

            //Make sure user isnt already in the group
            if(CTXPS_Queries::check_membership($_GET['user_id'], $_GET['groupid'])){
                CTXAjax::response( array('code'=>'0','message'=>__('Already in group','contexture-page-security')) );
            }

            //Add the user to the group
            if(CTXPS_Queries::add_membership($_GET['user_id'], $_GET['groupid']) === false){
                CTXAjax::response( array('code'=>'0','message'=>__('Query failed','contexture-page-security')) );
            } else {
                CTXAjax::response( array('code'=>'1','message'=>__('User enrolled in group','contexture-page-security'),'html'=>'<![CDATA['.CTXPS_Components::render_group_list($_GET['user_id'],'users').']]>') );
            }
        }

    }


    /**
     * Handles ajax requests to update a users membership info
     *
     * @global wpdb $wpdb
     */
    public static function update_membership(){
        global $wpdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users')){
            //If user isn't authorized
            CTXAjax::response( array('code'=>'0','message'=>__('Admin user is unauthorized.','contexture-page-security')) );
        }

        //Determine if we need to pass NULL or a DateTime value...
        $db_expires = ($_POST['expires']=='1') ? $_POST['date'] : 'NULL';

        //Determine response
        if(CTXPS_Queries::grel_enrollment_update($_POST['grel'], $db_expires) === false){
            CTXAjax::response( array('code'=>'0','message'=>__('Query failed!','contexture-page-security')) );
        } else {
            CTXAjax::response( array('code'=>'1','message'=>__('User membership updated','contexture-page-security')) );
        }

    }

    /**
     * Handles ajax requests to remove a group from a users account
     *
     * @global wpdb $wpdb
     */
    public static function remove_group_from_user(){
        global $wpdb;
        $response = array();

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_users')){
            //Error - membership not found.
            $response = array(
                'what'=>'unenroll',
                'action'=>'unenroll',
                'id'=>new WP_Error('error',__('User is not authorized.','contexture-page-security'))
            );
        }

        if(!CTXPS_Queries::delete_membership($_GET['user_id'], $_GET['groupid'])){
            //Error - membership not found.
            $response = array(
                'what'=>'unenroll',
                'action'=>'unenroll',
                'id'=>new WP_Error('error',__('User not found in group.','contexture-page-security'))
            );
        } else {
            //SUCCESS!
            $response = array(
                'what'=>'unenroll',
                'action'=>'unenroll',
                'id'=>1,
                'data'=>__('User removed from group.','contexture-page-security'),
                'supplemental'=>array('html'=>CTXPS_Components::render_group_list($_GET['user_id'],'users',true))//We need to regenerate table content
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
            //If user isn't authorized
            CTXAjax::response(array('code'=>'0','message'=>__('Admin user is unauthorized.','contexture-page-security')));
        }

        $response = array();
        switch($_GET['setting']){
            case 'on':
                $response['code'] = add_post_meta($_GET['postid'],'ctx_ps_security','1');
                $response['message'] = 'Security enabled';
                break;
            case 'off':
                if(CTXPS_Queries::delete_security($_GET['postid']) !== false){
                    $response['code'] = delete_post_meta($_GET['postid'],'ctx_ps_security');
                    $response['message'] = 'Security disabled';
                }else{
                    $response['code'] = '0';
                    $response['message'] = 'Query failed';
                }
                break;
            default:
                $response['code'] = '0';
                $response['message'] = 'Data does not validate';
                break;
        }
        CTXAjax::response($response);
    }
}
?>