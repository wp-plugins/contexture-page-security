jQuery(function(){jQuery("#groups-available").data("options",jQuery("#groups-available").html()).children(".detach").remove();jQuery("#add_group_page").click(function(){ctxps_add_group_to_page()});jQuery("#ctx_ps_protectmy").click(function(){CTXPS.toggleSecurity()});jQuery('label[for="ctx_ps_protectmy"]').click(function(){jQuery("#ctx_ps_protectmy:disabled").length>0&&alert(ctxpsmsg.NoUnprotect)});jQuery("#btn-add-grp-2-user").click(function(){ctxps_add_group_to_user()})});
function ctxps_showSavemsg(b){jQuery(b+" .ctx-ajax-status").length==0&&jQuery(b).append('<span class="ctx-ajax-status">Saved</span>').find(".ctx-ajax-status").fadeIn(500,function(){jQuery(this).delay(750).fadeOut(500,function(){jQuery(this).remove()})})}
function CTXPS.toggleSecurity(){var b=parseInt(jQuery("#ctx_ps_post_id").val());if(jQuery("#ctx_ps_protectmy:checked").length!==0)jQuery.get("admin-ajax.php",{action:"ctxps_security_update",setting:"on",postid:b},function(c){c=jQuery(c);if(c.find("code").text()=="1"){jQuery("#ctx_ps_pagegroupoptions").show();ctxps_showSavemsg("#ctx_ps_sidebar_security h3.hndle")}},"xml");else confirm(msgEraseSec)?jQuery.get("admin-ajax.php",{action:"ctxps_security_update",setting:"off",postid:b},function(c){c=jQuery(c);
if(c.find("code").text()=="1"){jQuery("#ctx_ps_pagegroupoptions").hide();ctxps_showSavemsg("#ctx_ps_sidebar_security h3.hndle")}},"xml"):jQuery("#ctx_ps_protectmy").attr("checked","checked")}
function ctxps_add_group_to_user(){var b=parseInt(jQuery("#groups-available").val()),c=parseInt(jQuery("#ctx-group-user-id").val());if(b!=0){jQuery("#btn-add-grp-2-user").attr("disabled","disabled");jQuery.get("admin-ajax.php",{action:"ctx_ps_add2user",groupid:b,user_id:c},function(a){a=jQuery(a);if(a.find("html").length>0){jQuery("#grouptable > tbody").html(a.find("html").text());a=jQuery("#groups-available");a.html(a.data("options")).children('option[value="'+b+'"]').addClass("detach").end().data("options",
a.html()).children(".detach").remove();jQuery("#btn-add-grp-2-user").removeAttr("disabled");ctxps_showSavemsg(".ctx-ps-tablenav")}},"xml")}else alert("You must select a group to add.")}
function ctxps_remove_group_from_user(b,c,a){jQuery.get("admin-ajax.php",{action:"ctxps_remove_group_from_user",groupid:b,user_id:c},function(d){d=jQuery(d);if(d.find("code").text()=="1"){var e=jQuery("#groups-available");e.html(e.data("options")).children('option[value="'+b+'"]').removeClass("detach").end().data("options",e.html()).children(".detach").remove();a.parents("tr:first").fadeOut(500,function(){jQuery("#grouptable > tbody").html(d.find("html").text())});ctxps_showSavemsg(".ctx-ps-tablenav")}},
"xml")}
function ctxps_add_group_to_page(){var b=parseInt(jQuery("#groups-available").val()),c=parseInt(jQuery("#ctx_ps_post_id").val());b!=0?jQuery.get("admin-ajax.php",{action:"ctx_ps_add2page",groupid:b,postid:c},function(a){a=jQuery(a);if(a.find("html").length>0){jQuery("#ctx-ps-page-group-list").html(a.find("html").text());a=jQuery("#groups-available");a.html(a.data("options")).children('option[value="'+b+'"]').addClass("detach").end().data("options",a.html()).children(".detach").remove();ctxps_showSavemsg("#ctx_ps_sidebar_security h3.hndle")}},"xml"):
alert("You must select a group to add.")}
function ctxps_remove_group_from_page(b,c){if(confirm(ctxpsmsg.RemoveGroup.replace(/%s/,c.parents(".ctx-ps-sidebar-group:first").children(".ctx-ps-sidebar-group-title").text()))){var a=parseInt(jQuery("#ctx_ps_post_id").val());jQuery.get("admin-ajax.php",{action:"ctx_ps_removefrompage",groupid:b,postid:a},function(d){d=jQuery(d);if(d.find("code").text()=="1"){d=jQuery("#groups-available");d.html(d.data("options")).children('option[value="'+b+'"]').removeClass("detach").end().data("options",d.html()).children(".detach").remove();
c.parent().fadeOut(500,function(){jQuery(this).remove()});ctxps_showSavemsg("#ctx_ps_sidebar_security h3.hndle")}},"xml")}}
function ctxps_remove_page_from_group(b,c){if(confirm(varMsgRemovePage.replace(/%s/,c.parents("td:first").children("strong:first").text()))){var a=parseInt(jQuery("#groupid").val());jQuery.get("admin-ajax.php",{action:"ctx_ps_removefrompage",groupid:a,postid:b},function(d){d=jQuery(d);d.find("code").text()=="1"&&c.parents("tr:first").fadeOut(500,function(){jQuery(this).remove()})},"xml")}};