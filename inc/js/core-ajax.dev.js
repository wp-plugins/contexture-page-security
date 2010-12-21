//When DOM Ready...
jQuery(function(){
    jQuery('#groups-available') //On restrict-access sidebar AND edit-users page
        .data('options',jQuery('#groups-available').html())
        .children('.detach')
            .remove();
    jQuery('#add_group_page').click(function(){ctx_ps_add_group_to_page()});
    jQuery('#ctx_ps_protectmy').click(function(){ctx_ps_togglesecurity()});
    jQuery('label[for="ctx_ps_protectmy"]').click(function(){
        //If the checkbox is disabled, it's because an ancestor is protected - let the user know
        if(jQuery('#ctx_ps_protectmy:disabled').length > 0){
            alert(msgNoUnprotect);
        }
    });
    jQuery('#btn-add-grp-2-user').click(function(){ctx_ps_add_group_to_user()});
});

//Will display a "Security Updated" message in the sidebar when successful change to security
function ctx_showSavemsg(selector){
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

//Updates the security status of the page
function ctx_ps_togglesecurity(){
    var ipostid = parseInt(jQuery('#ctx_ps_post_id').val());

    if(jQuery('#ctx_ps_protectmy:checked').length !== 0){
        //Turn security ON for this group
        jQuery.get('admin-ajax.php',
            {
                action:'ctx_ps_security_update',
                setting:'on',
                postid:ipostid
            },
            function(data){ data = jQuery(data);
                if(data.find('code').text() == '1'){
                    jQuery("#ctx_ps_pagegroupoptions").show();
                    ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle')
                }
            },'xml'
        );
    }else{
        if(confirm(msgEraseSec)){
            //Turn security OFF for this group
            jQuery.get('admin-ajax.php',
                {
                    action:'ctx_ps_security_update',
                    setting:'off',
                    postid:ipostid
                },
                function(data){
                    data = jQuery(data);
                    if(data.find('code').text() =='1'){
                        jQuery("#ctx_ps_pagegroupoptions").hide();
                        ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle')
                    }
                },'xml'
            );
        }else{
            //If user cancelled, re-check the box
            jQuery('#ctx_ps_protectmy').attr('checked','checked');
        }
    }

}

//Adds a group to a user
function ctx_ps_add_group_to_user(){
    var igroupid = parseInt(jQuery('#groups-available').val());
    var iusrid = parseInt(jQuery('#ctx-group-user-id').val());
    if(igroupid!=0){
        jQuery('#btn-add-grp-2-user').attr('disabled','disabled');
        //alert("The group you want to add is: "+$groupid);
        jQuery.get('admin-ajax.php',
            {
                action:'ctx_ps_add2user',
                groupid:igroupid,
                user_id:iusrid
            },
            function(response){
                response = jQuery(response);
                if(response.find('html').length > 0){

                    //Add group to the Allowed Groups list from our stored data
                    jQuery('#grouptable > tbody').html(response.find('html').text());

                    //Load the select drop down list
                    var grpsAvail = jQuery('#groups-available');

                    grpsAvail
                        .html(grpsAvail.data('options')) //Set the ddl content = saved data
                        .children('option[value="'+igroupid+'"]') //Select option that we just added
                            .addClass('detach') //Add detach class to it
                        .end() //Reselect ddl again
                        .data('options',grpsAvail.html()) //Re-save the options html as data
                        .children('.detach') //Select all detached options
                            .remove(); //Remove them

                    jQuery('#btn-add-grp-2-user').removeAttr('disabled');
                    ctx_showSavemsg('.ctx-ps-tablenav');
                }
            },'xml'
        );
    }else{
        alert('You must select a group to add.');
    }
}

//Removes a group from a user
function ctx_ps_remove_group_from_user(igroupid,iuserid,me,action){
    jQuery.get('admin-ajax.php',
        {
            action:'ctx_ps_removefromuser',
            groupid:igroupid,
            user_id:iuserid
        },
        function(response){
            response = jQuery(response);
            if(response.find('code').text() == '1'){

               var grpsAvail = jQuery('#groups-available');
                grpsAvail
                    .html(grpsAvail.data('options'))
                    .children('option[value="'+igroupid+'"]')
                        .removeClass('detach')
                    .end()
                    .data('options',grpsAvail.html())
                    .children('.detach')
                        .remove();

                //Take me out of the table
                me.parents('tr:first').fadeOut(500,function(){
                    //Rebuild the group
                    jQuery('#grouptable > tbody').html(response.find('html').text());
                });

                ctx_showSavemsg('.ctx-ps-tablenav');
            }
        },'xml'
    );

}


//Adds a group to a page with security
function ctx_ps_add_group_to_page(){
    var igroupid = parseInt(jQuery('#groups-available').val());
    var ipostid = parseInt(jQuery('#ctx_ps_post_id').val());
    if(igroupid!=0){
        //alert("The group you want to add is: "+$groupid);
        jQuery.get('admin-ajax.php',
            {
                action:'ctx_ps_add2page',
                groupid:igroupid,
                postid:ipostid
            },
            function(data){
                data = jQuery(data);
                if(data.find('html').length > 0){
                    //Add group to the Allowed Groups list
                    jQuery('#ctx-ps-page-group-list').html(data.find('html').text());

                    var grpsAvail = jQuery('#groups-available');

                    grpsAvail
                        .html(grpsAvail.data('options'))
                        .children('option[value="'+igroupid+'"]')
                            .addClass('detach')
                        .end()
                        .data('options',grpsAvail.html())
                        .children('.detach')
                            .remove();

                    ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle');
                }
            },'xml'
        );
    }else{
        alert('You must select a group to add.');
    }
}

//Removes a group from a page with security
function ctx_ps_remove_group_from_page(igroupid,me){
    if(confirm(msgRemoveGroup.replace(/%s/,me.parents('.ctx-ps-sidebar-group:first').children('.ctx-ps-sidebar-group-title').text()))){
        var ipostid = parseInt(jQuery('#ctx_ps_post_id').val());
        //alert("The group you want to add is: "+$groupid);
        jQuery.get('admin-ajax.php',
            {
                action:'ctx_ps_removefrompage',
                groupid:igroupid,
                postid:ipostid
            },
            function(data){
                data = jQuery(data);
                if(data.find('code').text() == '1'){

                   var grpsAvail = jQuery('#groups-available');
                    grpsAvail
                        .html(grpsAvail.data('options'))
                        .children('option[value="'+igroupid+'"]')
                            .removeClass('detach')
                        .end()
                        .data('options',grpsAvail.html())
                        .children('.detach')
                            .remove();
                    me.parent().fadeOut(500,function(){jQuery(this).remove();});

                    ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle');
                }
            },'xml'
        );
    }
}

//Removes a page from a group via the group screen
function ctx_ps_remove_page_from_group(ipostid,me){
    if(confirm( varMsgRemovePage.replace( /%s/,me.parents('td:first').children('strong:first').text() ) )){
        //Get the id of the current group
        var igroupid = parseInt(jQuery('#groupid').val());

        jQuery.get('admin-ajax.php',
            {
                action:'ctx_ps_removefrompage',
                groupid:igroupid,
                postid:ipostid
            },
            function(data){
                data = jQuery(data);
                if(data.find('code').text() == '1'){

                    me.parents('tr:first').fadeOut(500,function(){jQuery(this).remove();});

                    //ctx_showSavemsg('#ctx_ps_sidebar_security h3.hndle');
                }
            },'xml'
        );
    }
}