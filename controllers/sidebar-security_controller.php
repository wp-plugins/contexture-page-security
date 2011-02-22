<?php
    /**
     * This is still a disgraceful mess, but at least it's marginally more readable. Let's put this on hold till 1.5
     */
    global $wpdb, $post;
    $outputHtml = '';

    //We MUST have a post id in the querystring in order for this to work (ie: this wont appear for the "create new" pages, as the page doesnt exist yet)
    if(!empty($_GET['post']) && is_numeric($_GET['post'])){

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

        //Only print restriction options if this ISN'T set as an access denied page
        if($dbOpts['ad_page_anon_id']!=$_GET['post'] && $dbOpts['ad_page_auth_id']!=$_GET['post']){

            $outputHtml .= sprintf('<input type="hidden" id="ctx_ps_post_id" name="ctx_ps_post_id" value="%s" />',$_GET['post']);

            //Build "Protect this page" label
            $outputHtml .= CTX_Helper::wrap('<label for="ctx_ps_protectmy">',
                sprintf('<input type="checkbox" id="ctx_ps_protectmy" name="ctx_ps_protectmy" %s %s />',
                    (!!$securityStatus)? 'checked="checked"' : '',
                    ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') ) ? 'disabled="disabled"' : '')
                .__(" Protect this page and it's descendants",'contexture-page-security')
            );

            /** TODO: Add "Use as Access Denied page" option */

            //If the checkbox is disabled, give admin the option to go straight to the parent
            if ( !!$securityStatus && !get_post_meta($_GET['post'],'ctx_ps_security') ){
                $outputHtml .= sprintf('<a href="%s" style="display:block;font-size:0.75em;text-align:left;padding-left:20px;">%s</a>',
                    admin_url('post.php?post='.$post->post_parent.'&action=edit'),
                    __('Edit Parent','contexture-page-security')
                );
            }

            //Start on "Available Groups" select box
            $outputHtml .= sprintf('<div id="ctx_ps_pagegroupoptions" style="border-top:#EEEEEE 1px solid;margin-top:0.5em;%s">',
                ( !!$securityStatus )?'display:block;':''
            );

            $outputHtml .=    sprintf('<h5>%1$s <a href="%3$s" title="%2$s" style="text-decoration:none;">+</a></h5>',__('Available Groups','contexture-page-security'),__('New Group','contexture-page-security'),admin_url('users.php?page=ps_groups_add'));

            $group_avail_opts .= sprintf( '<option value="0">-- %s -- </option>',__('Select','contexture-page-security') );

            //Loop through all groups in the db to populate the drop-down list
            foreach(CTXPS_Queries::get_groups() as $group){
                //Generate the option HTML, hiding it if it's already in our $currentGroups array
                $group_avail_opts .= CTX_Helper::gen('option',
                    array(
                        'class'=>(!empty($currentGroups[$group->ID]))?'detach':'',
                        'value'=>$group->ID
                    ),$group->group_title
                );
            }

            //Put all those options into the select box
            $outputHtml .= CTX_Helper::gen('select',array('id'=>'groups-available','name'=>'groups-available'),$group_avail_opts);

            $outputHtml .= sprintf('<input type="button" id="add_group_page" class="button-secondary action" value="%s" />',__('Add','contexture-page-security'));
            $outputHtml .= sprintf('<h5>%s</h5>',__('Allowed Groups','contexture-page-security'));
            $outputHtml .= '<div id="ctx-ps-page-group-list">';


            $outputHtml .= CTXPS_Components::render_sidebar_attached_groups($securityStatus,$_GET['post']);


            $outputHtml .= '      </div>'; //ctx-ps-page-group-list
            $outputHtml .= '  </div>'; //ctx_ps_pagegroupoptions


        }else{
            $outputHtml .= sprintf(__('<p>This is currently an Access Denied page. You cannot restrict it.</p><p><a href="%s">View Security Settings</a></p>','contexture-page-security'),admin_url('options-general.php?page=ps_manage_opts'));
        }
    }else{
        $outputHtml = __('<p>You need to publish before you can update security settings.</p>','contexture-page-security');
    }
    CTX_Helper::div(array('class'=>'new-admin-wp25'), $outputHtml);
    /***END BUILDING HTML****************************/
?>