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

class CTXPSAjax extends CTXAjax {

    /**
     * Handles ajax requests to add a group to a page. When successful, generates HTML to be used in the "Allowed Groups"
     * section of the "Restrict Page" sidebar. Spits out XML response for AJAX use.
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     */
    public static function add_group_to_page(){
        global $wpdb, $ctxpscdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('edit_pages')){
            //If user isn't authorized, stop and return error
            CTXAjax::response(array('code'=>'0','message'=>__('User is unauthorized to make this change.','contexture-page-security')));
        }

        //Run the query
        $result = CTXPSC_Queries::add_security($_GET['postid'],$_GET['groupid']);

        if($result!==false){
            //Start with blank HTML output
            $OutputHTML = '';

            //Get security info for the specified page and it's parents
            $securityStatus = ctx_ps_getprotection( $_GET['postid'] );

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
                            $OutputHTML .= '<div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title">'.$currentGroup->name.'</span><span class="removegrp" onclick="ctx_ps_remove_group_from_page('.$currentGroup->id.',jQuery(this))">'.__('remove','contexture-page-security').'</span></div>';
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
    function remove_group_from_page(){
        global $wpdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('manage_options')){
            //If user isn't authorized
            CTXAjax::response(array('code'=>'0','message'=>__('Admin user is unauthorized.','contexture-page-security')));
        }

        if(CTXPSC_Queries::delete_security($_GET['postid'],$_GET['groupid']) !== false){
            CTXAjax::response(array('code'=>'1','message'=>__('Group removed','contexture-page-security')));
        }else{
            CTXAjax::response(array('code'=>'0','message'=>__('Query failed','contexture-page-security')));
        }
        CTXAjax::response($response);
    }

    /**
     * Handles ajax requests to add a user to a group
     *
     * @global wpdb $wpdb
     */
    function add_group_to_user(){
        global $wpdb;
        /** Determines if user exists*/
        $UserInfo = 0;
        /** Determines if user is already in the group */
        $UserInGroup = 0;
        /** SQL for updating the group */
        $sqlUpdateGroup = 0;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('manage_options')){
            //If user isn't authorized
            CTXAjax::response(array('code'=>'0','message'=>__('Admin user is unauthorized.','contexture-page-security')));
        }

        //Make sure user exists in db
        $UserInfo = (int)$wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->users} WHERE {$wpdb->users}.ID = '%s'",
                        $_GET['user_id']));

        //If this user doesn't exist
        if($UserInfo === 0){
            CTXAjax::response(array('code'=>'0','message'=>'User not found'));
        } else {

            //Make sure user isnt already in the group
            $UserInGroup = $wpdb->prepare('SELECT COUNT(*) FROM `'.$wpdb->prefix.'ps_group_relationships` WHERE grel_group_id=%s AND grel_user_id=%s',
                    $_GET['groupid'],
                    $_GET['user_id']);
            if($wpdb->get_var($UserInGroup)>0){
                CTXAjax::response( array('code'=>'0','message'=>__('Already in group','contexture-page-security')) );
            }

            //Add user to group
            $sqlUpdateGroup = $wpdb->prepare("INSERT INTO `{$wpdb->prefix}ps_group_relationships` (grel_group_id, grel_user_id) VALUES ('%s','%s');",
                    $_GET['groupid'],
                    $_GET['user_id']);
            if($wpdb->query($sqlUpdateGroup) === false){
                CTXAjax::response( array('code'=>'0','message'=>__('Query failed','contexture-page-security')) );
            } else {
                CTXAjax::response( array('code'=>'1','message'=>__('User enrolled in group','contexture-page-security'),'html'=>'<![CDATA['.ctx_ps_display_group_list($_GET['user_id'],'users').']]>') );
            }
        }

    }


    /**
     * Handles ajax requests to update a users membership info
     *
     * @global wpdb $wpdb
     */
    function update_membership(){
        global $wpdb;

        //Added in 1.1 - ensures current user is an admin before processing, else returns an error (probably not necessary - but just in case...)
        if(!current_user_can('manage_options')){
            //If user isn't authorized
            CTXAjax::response( array('code'=>'0','message'=>__('Admin user is unauthorized.','contexture-page-security')) );
        }

        //Determine null or value
        $db_expires = ($_POST['expires']=='1') ? "'".$_POST['date']."'" : 'NULL';

        //Build query
        $sqlUpdateMember = sprintf('UPDATE `%sps_group_relationships` SET grel_expires=%s WHERE ID=\'%s\';',$wpdb->prefix,$db_expires,$_POST['grel']);

        //Determine response
        if($wpdb->query($sqlUpdateMember) === false){
            CTXAjax::response( array('code'=>'0','message'=>__('Query failed!','contexture-page-security')) );
        } else {
            CTXAjax::response( array('code'=>'1','message'=>__('User membership updated','contexture-page-security')) );
        }

    }

}
?>