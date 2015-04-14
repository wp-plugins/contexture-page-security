function CTXPS_Ajax(){}jQuery(function(){var e=jQuery("#ctxps-grouplist-ddl");e.data("options",e.html()).children(".detach").remove(),jQuery("#ctxps-grouplist-ddl").data("options",jQuery("#ctxps-grouplist-ddl").html()).children(".detach").remove(),jQuery("#ctxps-grouplist-box #ctxps-cb-protect").click(function(){CTXPS_Ajax.toggleContentSecurity("post",parseInt(jQuery("#ctx_ps_post_id").val()),"#ctxps-grouplist-box h3.hndle")}),jQuery('#ctxps-grouplist-box label[for="ctx_ps_protectmy"]').click(function(){jQuery("#ctx_ps_protectmy:disabled").length>0&&alert(ctxpsmsg.NoUnprotect)}),jQuery("#add_group_page").click(function(){CTXPS_Ajax.addGroupToPage()}),jQuery("#edittag #ctxps-cb-protect").click(function(){CTXPS_Ajax.toggleContentSecurity("term",parseInt(jQuery('#edittag input[name="tag_ID"]').val()))}),jQuery('#edittag label[for="ctxps-cb-protect"]').click(function(){jQuery("#ctxps-cb-protect:disabled").length>0&&alert(ctxpsmsg.NoUnprotect)}),jQuery("#ctxps-grouplist-ddl-btn").click(function(){CTXPS_Ajax.addGroupToTerm()}),jQuery("#btn-add-grp-2-user").click(function(){CTXPS_Ajax.addGroupToUser()}),jQuery("#enrollit").click(function(){CTXPS_Ajax.addBulkUsersToGroup()}),jQuery("#ad-protect-site:checked").click(function(){return 0===jQuery(this).filter(":checked").length?confirm(ctxpsmsg.SiteProtectDel):!0}),jQuery('#ad-msg-enable, label[for="ad-msg-enable"]').click(function(){var e=jQuery(".toggle-opts-ad-msg"),t=jQuery(".toggle-opts-ad-page"),r=jQuery("#ad-msg-forcelogin:checked").length;jQuery(this).filter(":checked").length?r?e.not(".ad-opt-anon").fadeOut(250,function(){t.not(".ad-opt-anon").fadeIn(250)}):e.fadeOut(250,function(){jQuery(".toggle-opts-ad-page").fadeIn(250)}):r?t.not(".ad-opt-anon").fadeOut(250,function(){e.not(".ad-opt-anon").fadeIn(250)}):t.fadeOut(250,function(){e.fadeIn(250)})}),jQuery('#ad-msg-forcelogin, label[for="ad-msg-forcelogin"]').click(function(){var e=jQuery(".ad-opt-anon"),t=jQuery("#ad-msg-enable:checked").length;jQuery(this).filter(":checked").length?e.filter(":visible").fadeOut(250):t?jQuery(".toggle-opts-ad-page").fadeIn(250):jQuery(".toggle-opts-ad-msg").fadeIn(250)})}),CTXPS_Ajax.addBulkUsersToGroup=function(){var e=jQuery("#the-list input:checkbox:checked");jQuery.get("admin-ajax.php",{action:"ctxps_user_bulk_add",users:e.serializeArray(),group_id:jQuery("#psc_group_add").val()},function(t){t=jQuery(t);var r=jQuery("#message"),a=t.find("supplemental html").text();r.length?r.replaceWith(a):jQuery("#wpbody-content h2:first").after(a),"1"==t.find("bulk_enroll").attr("id")&&(e.removeAttr("checked").prop("checked",!1),jQuery("#cb-select-all-1,#cb-select-all-2").removeAttr("checked").prop("checked",!1))},"xml")},CTXPS_Ajax.showSaveMsg=function(e){0==jQuery(e+" .ctx-ajax-status").length&&jQuery(e).append('<span class="ctx-ajax-status">Saved</span>').find(".ctx-ajax-status").fadeIn(500,function(){jQuery(this).delay(750).fadeOut(500,function(){jQuery(this).remove()})})},CTXPS_Ajax.toggleSecurity=function(){var e=parseInt(jQuery("#ctx_ps_post_id").val());0!==jQuery("#ctx_ps_protectmy:checked").length?jQuery.get("admin-ajax.php",{action:"ctxps_security_update",setting:"on",object_type:"post",object_id:e},function(e){e=jQuery(e),"1"==e.find("update_sec").attr("id")?(jQuery("#ctx_ps_pagegroupoptions").show(),CTXPS_Ajax.showSaveMsg("#ctxps-grouplist-box h3.hndle")):alert(ctxpsmsg.GeneralError+e.find("wp_error").text())},"xml"):confirm(ctxpsmsg.EraseSec)?jQuery.get("admin-ajax.php",{action:"ctxps_security_update",setting:"off",object_type:"post",object_id:e},function(e){e=jQuery(e),"1"==e.find("update_sec").attr("id")?(jQuery("#ctx_ps_pagegroupoptions").hide(),CTXPS_Ajax.showSaveMsg("#ctxps-grouplist-box h3.hndle")):alert(ctxpsmsg.GeneralError+e.find("wp_error").text())},"xml"):jQuery("#ctx_ps_protectmy").attr("checked","checked").prop("checked","checked")},CTXPS_Ajax.toggleContentSecurity=function(e,t,r){var a=jQuery("#ctxps-grouplist-ddl");"undefined"==typeof e&&alert("Programming Error: Type was undefined. Changes not saved."),"undefined"==typeof t&&(t=parseInt(jQuery("#ctxps-object-id").val())),0!==jQuery("#ctxps-cb-protect:checked").length?jQuery.get("admin-ajax.php",{action:"ctxps_security_update",setting:"on",object_type:e,object_id:t},function(t){if(t=jQuery(t),0!=t.find("update_sec").attr("id")){if(0!=t.find("supplemental html").length)switch(e){case"term":jQuery("#the-list-ctxps-relationships").replaceWith(t.find("supplemental html").text())}jQuery("#ctxps-relationships-list").show(),"undefined"!=typeof r&&CTXPS_Ajax.showSaveMsg(r)}else alert(ctxpsmsg.GeneralError+t.find("wp_error").text())},"xml"):confirm(ctxpsmsg.EraseSec)?jQuery.get("admin-ajax.php",{action:"ctxps_security_update",setting:"off",object_type:e,object_id:t},function(t){if(t=jQuery(t),"1"==t.find("update_sec").attr("id")){if(jQuery("#ctxps-relationships-list").hide(),t.find("supplemental html").length>0)switch(e){case"term":jQuery("#the-list-ctxps-relationships").replaceWith(t.find("supplemental html").text())}0!=a.length,"undefined"!=typeof save_loc&&CTXPS_Ajax.showSaveMsg(r),jQuery("#ctxps-grouplist-box #ctx-parentmsg").length>0&&jQuery("#publish").click()}else alert(ctxpsmsg.GeneralError+t.find("wp_error").text())},"xml"):jQuery("#ctxps-cb-protect").attr("checked","checked").prop("checked","checked")},CTXPS_Ajax.addGroupToUser=function(){var e=parseInt(jQuery("#ctxps-grouplist-ddl").val()),t=parseInt(jQuery("#ctx-group-user-id").val());0!=e?(jQuery("#btn-add-grp-2-user").attr("disabled","disabled").prop("disabled","disabled"),jQuery.get("admin-ajax.php",{action:"ctxps_add_group_to_user",group_id:e,user_id:t},function(t){if(t=jQuery(t),"1"==t.find("enroll").attr("id")){jQuery("#grouptable > tbody").html(t.find("supplemental html").text());var r=jQuery("#ctxps-grouplist-ddl");r.html(r.data("options")).children('option[value="'+e+'"]').addClass("detach").end().data("options",r.html()).children(".detach").remove(),jQuery("#btn-add-grp-2-user").removeAttr("disabled").prop("disabled",!1),CTXPS_Ajax.showSaveMsg(".ctx-ps-tablenav")}else alert(ctxpsmsg.GeneralError+data.find("wp_error").text())},"xml")):alert(ctxpsmsg.NoGroupSel)},CTXPS_Ajax.removeGroupFromUser=function(e,t,r){jQuery.get("admin-ajax.php",{action:"ctxps_remove_group_from_user",group_id:e,user_id:t},function(t){if(t=jQuery(t),"1"==t.find("unenroll").attr("id")){var a=jQuery("#ctxps-grouplist-ddl");a.html(a.data("options")).children('option[value="'+e+'"]').removeClass("detach").end().data("options",a.html()).children(".detach").remove(),r.parents("tr:first").fadeOut(500,function(){r.parents("tbody:first").html(t.find("supplemental html").text())}),CTXPS_Ajax.showSaveMsg(".ctx-ps-tablenav")}else alert(ctxpsmsg.GeneralError+t.find("wp_error").text())},"xml")},CTXPS_Ajax.addGroupToPage=function(){var e=parseInt(jQuery("#ctxps-grouplist-ddl").val()),t=parseInt(jQuery("#ctx_ps_post_id").val());0!=e?jQuery.get("admin-ajax.php",{action:"ctxps_add_group_to_post",group_id:e,post_id:t},function(t){if(t=jQuery(t),"1"==t.find("add_group").attr("id")){jQuery("#ctx-ps-page-group-list").html(t.find("supplemental html").text());var r=jQuery("#ctxps-grouplist-ddl");r.html(r.data("options")).children('option[value="'+e+'"]').addClass("detach").end().data("options",r.html()).children(".detach").remove(),CTXPS_Ajax.showSaveMsg("#ctxps-grouplist-box h3.hndle")}},"xml"):alert(ctxpsmsg.NoGroupSel)},CTXPS_Ajax.addGroupToTerm=function(){var e=jQuery("#ctxps-grouplist-ddl").val(),t=jQuery('#edittag input[name="tag_ID"]').val(),r=jQuery('#edittag input[name="taxonomy"]').val(),a="#the-list-ctxps-relationships",o="#ctxps-grouplist-ddl";"undefined"!=typeof e&&0!=e?jQuery.get("admin-ajax.php",{action:"ctxps_add_group_to_term",group_id:e,content_id:t,taxonomy:r},function(t){if(t=jQuery(t),"1"==t.find("add_group").attr("id")){"undefined"!=typeof a&&jQuery(a).replaceWith(t.find("supplemental html").text());var r=jQuery(o);r.html(r.data("options")).children('option[value="'+e+'"]').addClass("detach").end().data("options",r.html()).children(".detach").remove(),"undefined"!=typeof savemsg_selector&&CTXPS_Ajax.showSaveMsg(savemsg_selector)}},"xml"):alert(ctxpsmsg.NoGroupSel)},CTXPS_Ajax.addGroupToContent=function(e,t,r,a,o,n){"undefined"!=typeof e&&0!=e?jQuery.get("admin-ajax.php",{action:"ctxps_add_group_to_term",group_id:e,content_type:t,content_id:r},function(t){if(t=jQuery(t),"1"==t.find("add_group").attr("id")){"undefined"!=typeof a&&jQuery(a).html(t.find("supplemental html").text());var r=jQuery(o);r.html(r.data("options")).children('option[value="'+e+'"]').addClass("detach").end().data("options",r.html()).children(".detach").remove(),"undefined"!=typeof n&&CTXPS_Ajax.showSaveMsg(n)}},"xml"):alert(ctxpsmsg.NoGroupSel)},CTXPS_Ajax.removeGroupFromPage=function(e,t){if(confirm(ctxpsmsg.RemoveGroup.replace(/%s/,t.parents(".ctx-ps-sidebar-group:first").children(".ctx-ps-sidebar-group-title").text()))){var r=parseInt(jQuery("#ctx_ps_post_id").val());jQuery.get("admin-ajax.php",{action:"ctxps_remove_group_from_page",group_id:e,post_id:r,requester:"sidebar"},function(r){if(r=jQuery(r),"1"==r.find("remove_group").attr("id")){var a=jQuery("#ctxps-grouplist-ddl");a.html(a.data("options")).children('option[value="'+e+'"]').removeClass("detach").end().data("options",a.html()).children(".detach").remove(),t.parent().fadeOut(500,function(){console.log("Removed"),jQuery("#ctx-ps-page-group-list").html(r.find("supplemental html").text())}),CTXPS_Ajax.showSaveMsg("#ctxps-grouplist-box h3.hndle")}else alert(ctxpsmsg.GeneralError+r.find("wp_error").text())},"xml")}},CTXPS_Ajax.removeGroupFromTerm=function(e,t){var r=jQuery('#edittag input[name="tag_ID"]').val(),a=jQuery('#edittag input[name="taxonomy"]').val(),o=jQuery("#the-list-ctxps-relationships");confirm(ctxpsmsg.RemoveGroup.replace(/%s/,t.parents("tr:first").children("td.column-name a:first").text()))&&jQuery.get("admin-ajax.php",{action:"ctxps_remove_group_from_term",group_id:e,content_id:r,taxonomy:a},function(r){if(r=jQuery(r),"1"==r.find("remove_group").attr("id")){var a=jQuery("#ctxps-grouplist-ddl");a.html(a.data("options")).children('option[value="'+e+'"]').removeClass("detach").end().data("options",a.html()).children(".detach").remove(),t.parents("tr:first").fadeOut(500,function(){o.replaceWith(r.find("supplemental html").text())})}else alert(ctxpsmsg.GeneralError+r.find("wp_error").text())},"xml")},CTXPS_Ajax.removePageFromGroup=function(e,t){if(confirm(ctxpsmsg.RemovePage.replace(/%s/,t.parents("td:first").find("strong>a:first").text()))){var r=parseInt(jQuery("#groupid").val());jQuery.get("admin-ajax.php",{action:"ctxps_remove_group_from_page",group_id:r,post_id:e},function(e){e=jQuery(e),"1"==e.find("remove_group").attr("id")?t.parents("tr:first").fadeOut(500,function(){jQuery(this).parents("tbody:first").html(e.find("supplemental html").text())}):alert(ctxpsmsg.GeneralError+e.find("wp_error").text())},"xml")}};