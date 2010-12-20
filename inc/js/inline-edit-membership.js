(function($) {
    inlineEditMembership = {
        init : function(){
            //Open membership expiration editor
            $('a.editmembership').live('click',function(){ inlineEditMembership.edit(this); return false; });
            
            //Edit window cancel
            $('.submit a.cancel').live('click',function(){
                var myRow = $(this).parents('tr:first');
                var myId = inlineEditMembership.getId(myRow[0]);
                myRow.remove();
                $('#user-'+myId).css('display','table-row');
            });
            
            //Toggle editor window date enabled
            $('input[name="membership_permanent"]').live('click',function(){
                if( $(this).filter(':checked').length>0 ){
                    $(this).parents('fieldset:first').find('.inline-edit-date').find('input, select').removeAttr('disabled');
                }else{
                    $(this).parents('fieldset:first').find('.inline-edit-date').find('input, select').attr('disabled','disabled');
                }
            });
            
        },
        edit : function(memberid){
            var rowData, editForm;
            
            //Close other open edit windows
            inlineEditMembership.revert();
            
            //Set memberid to the memberid int, if its an object
            if (typeof memberid=='object') memberid = inlineEditMembership.getId(memberid);
            //Get data
            rowData = $('#inline_'+memberid);
            //Move editor to new position in table
            editForm = $('#inline-edit').clone().attr('id','edit-'+memberid).insertAfter('#user-'+memberid).css('display','table-row');
            //Update username
            $('.username',editForm).text($('.username',rowData).text());
            //Update expires checkbox
            if($('.jj',rowData).text().length!=0){ $('input[name="membership_permanent"]',editForm).attr('checked','checked'); }
            //Update dates
            
            //Hide original tr
            $('#user-'+memberid).hide();
            
        },
        save : function(){
            //Submit ajax data to server
            //If successful, update tr data
            //Hide editor
            //Show tr
            //Flash tr green
        },
        revert : function(){
            //Close any open edit and restore original row
            $('tr.inline-edit-row:visible').each(function(){
                var myId = inlineEditMembership.getId( $(this)[0] );
                $(this).remove();
                $('#user-'+myId).css('display','table-row');
            });
            //Clear out edit data
            //Hide edit tr
            //Restore original tr
        },
        getId : function(obj){
            var id = (obj.tagName == 'TR') ? obj.id : $(obj).parents('tr:first').attr('id'), 
                parts = id.split('-');
            return parts[parts.length-1];
        }
    };
    $(document).ready(function(){ inlineEditMembership.init(); });
})(jQuery);