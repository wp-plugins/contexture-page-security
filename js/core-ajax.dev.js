//When DOM Ready...
jQuery(function(){
    jQuery('#groups-available') //On restrict-access sidebar AND edit-users page
        .data('options',jQuery('#groups-available').html())
        .children('.detach')
            .remove();
    jQuery('#add_group_page').click(function(){
        CTXPS_Ajax.addGroupToPage()
    });
    jQuery('#ctx_ps_protectmy').click(function(){
        CTXPS_Ajax.toggleSecurity()
    });
    jQuery('label[for="ctx_ps_protectmy"]').click(function(){
        //If the checkbox is disabled, it's because an ancestor is protected - let the user know
        if(jQuery('#ctx_ps_protectmy:disabled').length > 0){
            alert(ctxpsmsg.NoUnprotect);
        }
    });
    jQuery('#btn-add-grp-2-user').click(function(){
        CTXPS_Ajax.addGroupToUser()
    });
    //Notify users if they are trying to remove site security (options.php)
    jQuery('#ad-protect-site:checked').click(function(){
        if(jQuery(this).filter(':checked').length===0){
            return confirm(ctxpsmsg.SiteProtectDel);
        }
        return true;
    });
    //Toggle visibility of page options (options.php)
    jQuery('#ad-msg-enable, label[for="ad-msg-enable"]').click(function(){

        var optmsg = jQuery('.toggle-opts-ad-msg'),
            optpg = jQuery('.toggle-opts-ad-page'),
            forcelog = jQuery('#ad-msg-forcelogin:checked').length;

        //If checking...
        if(jQuery(this).filter(':checked').length){

            //If force login is enabled...
            if(forcelog){
                //Exclude anon opts
                optmsg.not('.ad-opt-anon').fadeOut(250,function(){
                    optpg.not('.ad-opt-anon').fadeIn(250);
                });
            //If force login is NOT enabled
            }else{
                optmsg.fadeOut(250,function(){
                    jQuery('.toggle-opts-ad-page').fadeIn(250);
                });
            }

        //If UNchecking
        }else{
            //If force login is enabled...
            if(forcelog){
               optpg.not('.ad-opt-anon').fadeOut(250,function(){
                    optmsg.not('.ad-opt-anon').fadeIn(250);
                });
            //If force login is NOT enabled
            }else{
                optpg.fadeOut(250,function(){
                    optmsg.fadeIn(250);
                });
            }
        }
    });

    //Toggle visibility of anon boxes with force redirect
    jQuery('#ad-msg-forcelogin, label[for="ad-msg-forcelogin"]').click(function(){
        var anon = jQuery('.ad-opt-anon'),
            pages = jQuery('#ad-msg-enable:checked').length;
        //This is being checked
        if(jQuery(this).filter(':checked').length){
            anon.filter(':visible').fadeOut(250);
        //This is being UNchecked
        }else{
            //Check if we need to show pages or messages
            if(pages){
                jQuery('.toggle-opts-ad-page').fadeIn(250);
            }else{
                jQuery('.toggle-opts-ad-msg').fadeIn(250);
            }
        }
    });

    jQuery('#enrollit').click(function(){
        CTXPS_Ajax.addBulkUsersToGroup();
    });

});

/**
 * Let's define the custom static class
 */
function CTXPS_Ajax(){
    //Constructor
}

/**
 * USERS.PHP. Will bulk-add users to groups.
 */
CTXPS_Ajax.addBulkUsersToGroup = function(){
    var checkedArray = jQuery('#the-list input:checkbox:checked');
    jQuery.get(
        'admin-ajax.php',
        {
            action: 'ctxps_user_bulk_add',
            users:  checkedArray.serializeArray(),
            group_id:jQuery('#psc_group_add').val()
        },
        function(response){
            response = jQuery(response);
            var cmsg = jQuery('#message'),
                emsg = response.find('supplemental html').text();

            //Put a new bulk message on the page (replace current or add new)
            if(cmsg.length){
                cmsg.replaceWith(emsg);
            }else{
                jQuery('#wpbody-content h2:first').after(emsg);
            }

            //If this was a success, uncheck all selected users
            if(response.find('bulk_enroll').attr('id') == '1'){
                checkedArray.removeAttr('checked');
            }
        },
        'xml'
    );
}

/**
 * GENERAL. Will display a "Security Updated" message in the sidebar when successful change to security
 */
CTXPS_Ajax.showSaveMsg = function(selector){
    if(jQuery(selector+' .ctx-ajax-status').length==0){
        jQuery(selector)
            .append('<span class="ctx-ajax-status">Saved</span>')
            .find('.ctx-ajax-status')
            .fadeIn(500,function(){
                jQuery(this)
                    .delay(750).fadeOut(500,function(){
                        jQuery(this).remove();
                    });
            });
    }
}

/**
 * SIDEBAR. Updates the security status of the page
 */
CTXPS_Ajax.toggleSecurity = function(){
    var post_id = parseInt(jQuery('#ctx_ps_post_id').val());

    if(jQuery('#ctx_ps_protectmy:checked').length !== 0){
        //Turn security ON for this group
        jQuery.get('admin-ajax.php',
            {
                action:     'ctxps_security_update',
                setting:    'on',
                postid:     post_id
            },
            function(response){ response = jQuery(response);
                if(response.find('update_sec').attr('id') == '1'){
                    jQuery("#ctx_ps_pagegroupoptions").show();
                    CTXPS_Ajax.showSaveMsg('#ctx_ps_sidebar_security h3.hndle')
                }else{
                    alert(ctxpsmsg.GeneralError+response.find('wp_error').text());
                }
            },'xml'
        );
    }else{
        if(confirm(ctxpsmsg.EraseSec)){
            //Turn security OFF for this group
            jQuery.get('admin-ajax.php',
                {
                    action:     'ctxps_security_update',
                    setting:    'off',
                    postid:     post_id
                },
                function(response){
                    response = jQuery(response);
                    if(response.find('update_sec').attr('id') == '1'){
                        jQuery("#ctx_ps_pagegroupoptions").hide();
                        CTXPS_Ajax.showSaveMsg('#ctx_ps_sidebar_security h3.hndle')
                    }else{
                        alert(ctxpsmsg.GeneralError+response.find('wp_error').text());
                    }
                },'xml'
            );
        }else{
            //If user cancelled, re-check the box
            jQuery('#ctx_ps_protectmy').attr('checked','checked');
        }
    }
}

/**
 * USER PROFILE MEMBERSHIP TABLE. Adds a group to a user
 */
CTXPS_Ajax.addGroupToUser = function(){
    var group_id = parseInt(jQuery('#groups-available').val());
    var user_id = parseInt(jQuery('#ctx-group-user-id').val());
    if(group_id!=0){
        jQuery('#btn-add-grp-2-user').attr('disabled','disabled');
        //alert("The group you want to add is: "+$groupid);
        jQuery.get('admin-ajax.php',
            {
                action:     'ctxps_add_group_to_user',
                groupid:    group_id,
                user_id:    user_id
            },
            function(response){
                response = jQuery(response);
                if(response.find('enroll').attr('id') == '1'){

                    //Add group to the Allowed Groups list from our stored response
                    jQuery('#grouptable > tbody').html(response.find('supplemental html').text());

                    //Load the select drop down list
                    var grpsAvail = jQuery('#groups-available');
                    grpsAvail
                        .html(grpsAvail.data('options')) //Set the ddl content = saved response
                        .children('option[value="'+group_id+'"]') //Select option that we just added
                            .addClass('detach') //Add detach class to it
                        .end() //Reselect ddl again
                        .data('options',grpsAvail.html()) //Re-save the options html as response
                        .children('.detach') //Select all detached options
                            .remove(); //Remove them

                    jQuery('#btn-add-grp-2-user').removeAttr('disabled');
                    CTXPS_Ajax.showSaveMsg('.ctx-ps-tablenav');
                }else{
                    alert(ctxpsmsg.GeneralError+data.find('wp_error').text());
                }
            },'xml'
        );
    }else{
        alert(ctxpsmsg.NoGroupSel);
    }
}

/**
 * USER PROFILE MEMBERSHIP TABLE. Removes a group from a user
 */
CTXPS_Ajax.removeGroupFromUser = function(group_id,user_id,me,action){
    jQuery.get('admin-ajax.php',
        {
            action:     'ctxps_remove_group_from_user',
            groupid:    group_id,
            user_id:    user_id
        },
        function(response){
            response = jQuery(response);
            if(response.find('unenroll').attr('id') == '1'){

                //Use a cool fade effect to remove item from the list
                var grpsAvail = jQuery('#groups-available');
                grpsAvail
                    .html(grpsAvail.data('options'))
                    .children('option[value="'+group_id+'"]')
                        .removeClass('detach')
                    .end()
                    .data('options',grpsAvail.html())
                    .children('.detach')
                        .remove();

                //Take me out of the table
                me.parents('tr:first').fadeOut(500,function(){
                    //Rebuild the group
                    me.parents('tbody:first').html(response.find('supplemental html').text());
                });

                CTXPS_Ajax.showSaveMsg('.ctx-ps-tablenav');
            }else{
                alert(ctxpsmsg.GeneralError+data.find('wp_error').text());
            }
        },'xml'
    );
}

/**
 * SIDEBAR. Adds a group to a page with security
 */
CTXPS_Ajax.addGroupToPage = function(){
    var group_id = parseInt(jQuery('#groups-available').val());
    var post_id = parseInt(jQuery('#ctx_ps_post_id').val());
    if(group_id!=0){
        //alert("The group you want to add is: "+$groupid);
        jQuery.get('admin-ajax.php',
            {
                action:     'ctxps_add_group_to_page',
                groupid:    group_id,
                postid:     post_id
            },
            function(data){
                data = jQuery(data);
                if(data.find('add_group').attr('id')=='1'){
                    //Add group to the Allowed Groups list
                    jQuery('#ctx-ps-page-group-list').html(data.find('supplemental html').text());

                    //Update the select box and the attached group list
                    var grpsAvail = jQuery('#groups-available');
                    grpsAvail
                        .html(grpsAvail.data('options'))
                        .children('option[value="'+group_id+'"]')
                            .addClass('detach')
                        .end()
                        .data('options',grpsAvail.html())
                        .children('.detach')
                            .remove();

                    CTXPS_Ajax.showSaveMsg('#ctx_ps_sidebar_security h3.hndle');
                }
            },'xml'
        );
    }else{
        alert(ctxpsmsg.NoGroupSel);
    }
}

/**
 * SIDEBAR. Removes a group from a page with security
 */
CTXPS_Ajax.removeGroupFromPage = function(group_id,me){
    if(confirm(ctxpsmsg.RemoveGroup.replace(/%s/,me.parents('.ctx-ps-sidebar-group:first').children('.ctx-ps-sidebar-group-title').text()))){
        //Get the post id from the form field
        var post_id = parseInt(jQuery('#ctx_ps_post_id').val());
        //Submit the ajax request
        jQuery.get('admin-ajax.php',
            {
                action:     'ctxps_remove_group_from_page',
                groupid:    group_id,
                postid:     post_id,
                requester:  'sidebar'
            },
            function(response){
                response = jQuery(response);
                //If request was successful
                if(response.find('remove_group').attr('id') == '1'){
                    //Remove the row from the sidebar with a nifty fade effect, and add it back to the select box
                    var grpsAvail = jQuery('#groups-available');
                    grpsAvail
                        .html(grpsAvail.data('options'))
                        .children('option[value="'+group_id+'"]')
                            .removeClass('detach')
                        .end()
                        .data('options',grpsAvail.html())
                        .children('.detach')
                            .remove();
                    me.parent().fadeOut(500,function(){
                        console.log('Removed');
                        jQuery('#ctx-ps-page-group-list').html(response.find('supplemental html').text());
                    });

                    CTXPS_Ajax.showSaveMsg('#ctx_ps_sidebar_security h3.hndle');
                }else{
                    alert(ctxpsmsg.GeneralError+response.find('wp_error').text());
                }
            },'xml'
        );
    }
}

/**
 * GROUP EDIT > ASSOCIATED CONTENT TABLE. Removes a page from a group via the group screen
 */
CTXPS_Ajax.removePageFromGroup = function(post_id,me){
    if(confirm( ctxpsmsg.RemovePage.replace( /%s/,me.parents('td:first').find('strong>a:first').text() ) )){
        //Get the id of the current group
        var group_id = parseInt(jQuery('#groupid').val());

        jQuery.get('admin-ajax.php',
            {
                action:     'ctxps_remove_group_from_page',
                groupid:    group_id,
                postid:     post_id
            },
            function(data){
                data = jQuery(data);
                if(data.find('remove_group').attr('id') == '1'){
                    me.parents('tr:first').fadeOut(500,function(){
                        jQuery(this).parents('tbody:first')
                            .html(data.find('supplemental html').text());
                    });
                    //CTXPS.showSaveMsg('#ctx_ps_sidebar_security h3.hndle');
                }
                else{
                    alert(ctxpsmsg.GeneralError+data.find('wp_error').text());
                }
            },'xml'
        );
    }
}