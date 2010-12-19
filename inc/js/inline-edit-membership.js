(function($) {
    inlineEditMembership = {
        init : function(){
            $('a.editmembership').live('click',function(){ console.log('clicked'); inlineEditMembership.edit(this); return false; });
        },
        edit : function(memberid){
            if (typeof memberid=='object') memberid = inlineEditMembership.getId(memberid);
            
            $('#user-'+memberid).hide();
        },
        save : function(){

        },
        revert : function(){
            
        },
        getId : function(obj){
            var id = obj.tagName == 'TR' ? obj.id : $(obj).parents('tr').attr('id'), parts = id.split('-');
            return parts[parts.length-1];
        }
    };
    $(document).ready(function(){ inlineEditMembership.init(); });
})(jQuery);