<?php
    global $wpdb, $post;

    //We MUST have a post id in the querystring in order for this to work (ie: this wont appear for the "create new" pages, as the page doesnt exist yet)
    if(!empty($_GET['post']) && intval($_GET['post']) == $_GET['post']){

        //Create an array of groups that are already attached to the page
        $currentGroups = array();
        foreach(CTXPS_Queries::get_groups_by_post($_GET['post']) as $curGrp){
            $currentGroups[$curGrp->sec_access_id] = $curGrp->group_title;
        }

        //Get array with security requirements for this page
        $securityStatus = CTXPS_Security::get_protection( $_GET['post'] );

        //Get options
        $dbOpts = get_option('contexture_ps_options');
        //ad_page_auth_id     ad_page_anon_id

        /***START BUILDING HTML****************************/
        echo '<div class="new-admin-wp25">';

        //Only print restriction options if this ISN'T set as an access denied page
        if($dbOpts['ad_page_anon_id']!=$_GET['post'] && $dbOpts['ad_page_auth_id']!=$_GET['post']){
            echo '  <input type="hidden" id="ctx_ps_post_id" name="ctx_ps_post_id" value="'.$_GET['post'].'" />';
            //Build "Protect this page" label
            echo '  <label for="ctx_ps_protectmy">';
            echo '      <input type="checkbox" id="ctx_ps_protectmy" name="ctx_ps_protectmy"';
            if ( !!$securityStatus )
                echo ' checked="checked" ';
            if ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') ){
                echo ' disabled="disabled" ';
            }
            echo '/>';
            echo __(' Protect this page and it\'s descendants','contexture-page-security');
            echo '  </label>';
            /** TODO: Add "Use as Access Denied page" option */

            //If the checkbox is disabled, give admin the option to go straight to the parent
            if ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') ){
                echo '<a href="'.admin_url().'post.php?post='.$post->post_parent.'&action=edit" style="display:block;font-size:0.75em;text-align:left;padding-left:20px;">',__('Edit Parent','contexture-page-security'),'</a>';
            }
            //Start on "Available Groups" select box
            echo '  <div id="ctx_ps_pagegroupoptions" style="border-top:#EEEEEE 1px solid;margin-top:0.5em;';
            if ( !!$securityStatus )
                echo ' display:block ';
            echo '">';
            echo    sprintf('<h5>%1$s <a href="%3$s" title="%2$s" style="text-decoration:none;">+</a></h5>',__('Available Groups','contexture-page-security'),__('New Group','contexture-page-security'),admin_url('users.php?page=ps_groups_add'));
            echo '      <select id="groups-available" name="groups-available">';
            echo '<option value="0">-- '.__('Select','contexture-page-security').' -- </option>';
            //Loop through all groups in the db to populate the drop-down list
            foreach(CTXPS_Queries::get_groups() as $group){
                //Generate the option HTML, hiding it if it's already in our $currentGroups array
                echo        '<option '.((!empty($currentGroups[$group->ID]))?'class="detach"':'').' value="'.$group->ID.'">'.$group->group_title.'</option>';
            }
            echo       '</select>';
            echo       '<input type="button" id="add_group_page" class="button-secondary action" value="',__('Add','contexture-page-security'),'" />';
            echo       sprintf('<h5>%s</h5>',__('Allowed Groups','contexture-page-security'));
            echo       '<div id="ctx-ps-page-group-list">';
            //Set this to 0, we are going to count the number of groups attached to this page next...
            $groupcount = 0;
            //Count the number of groups attached to this page (including inherited groups)
            if(!!$securityStatus)
                foreach($securityStatus as $securityGroups){ $groupcount = $groupcount+count($securityGroups); }
            //Show groups that are already added to this page
            if($groupcount===0){
                //Display this if we have no groups (inherited or otherwise)
                echo '<div><em>'.__('No groups have been added yet.','contexture-page-security').'</em></div>';
            }else{
                foreach($securityStatus as $securityArray->pageid => $securityArray->grouparray){
                    if($securityArray->pageid == $_GET['post']){
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            echo '<div class="ctx-ps-sidebar-group">&bull; <span class="ctx-ps-sidebar-group-title"><a href="'.admin_url('/users.php?page=ps_groups_edit&groupid='.$currentGroup->id).'">'.$currentGroup->name.'</a></span><span class="removegrp" onclick="ctx_ps_remove_group_from_page('.$currentGroup->id.',jQuery(this))">'.__('remove','contexture-page-security').'</span></div>';
                        }
                    }else{
                        foreach($securityArray->grouparray as $currentGroup->id => $currentGroup->name){
                            echo '<div class="ctx-ps-sidebar-group inherited">&bull; <span class="ctx-ps-sidebar-group-title"><a href="'.admin_url('/users.php?page=ps_groups_edit&groupid='.$currentGroup->id).'">'.$currentGroup->name.'</a></span><a class="viewgrp" target="_blank" href="'.admin_url('post.php?post='.$securityArray->pageid.'&action=edit').'" >'.__('ancestor','contexture-page-security').'</a></div>';
                        }
                    }
                }
            }
            echo '      </div>';
            echo '  </div>';
        }else{
            echo sprintf(__('<p>This is currently an Access Denied page. You cannot restrict it.</p><p><a href="%s">View Security Settings</a></p>','contexture-page-security'),admin_url('options-general.php?page=ps_manage_opts'));
        }
        echo '</div>';
        /***END BUILDING HTML****************************/
    }else{
        echo '<div class="new-admin-wp25">';
        echo __('<p>You need to publish before you can update security settings.</p>','contexture-page-security');
        echo '</div>';
    }
?>