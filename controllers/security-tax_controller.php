<?php
global $taxonomy,$tag,$wpdb;

/**
 * TRANSLATABLE OUTPUT STRINGS
 ******************************************************************************/
$txt_h3_restrict = __('Restrict Access','contexture-page-security');
$txt_label_protect = __('Protect Term','contexture-page-security');
$txt_prottext =  __('Protect this term and any content associated with it.','contexture-page-security');
$txt_addgroup = __('Add group...','contexture-page-security');
$txt_subtitle_table = __('Groups With Access','contexture-page-security');


/**
 * LOGIC
 ******************************************************************************/

//Determined if this term is protected
$protected_status = CTXPS_Queries::get_term_protection( $_REQUEST['tag_ID'] );

//Determine how protected status alters display
$echo_protcheck = ($protected_status) ? 'checked="checked"' : '';
$echo_tlist_style = ($protected_status) ? 'display:block;' : '';

//Get list of all groups
$all_groups = CTXPS_Queries::get_groups();

//Get list of attached groups
$term_groups = CTXPS_Queries::get_groups_by_object('term', $_REQUEST['tag_ID']);

//Build list of unused groups by filtering attached groups out of all groups
$avail_groups = $all_groups;

$ddl_group_opts = sprintf( '<option value="0">%s</option>', $txt_addgroup );

//Count the loop position
$loop = 0;

//Loop through all groups in the db to populate the drop-down list
foreach($all_groups as $group){
    //Generate the option HTML, hiding it if it's already in our $currentGroups array
    $ddl_group_opts .= CTX_Helper::gen('option',
        array(
            'class'=>( isset($term_groups[$loop]->ID) && $term_groups[$loop]->ID==$group->ID )?'detach':'',
            'value'=>$group->ID
        ),$group->group_title
    );
    if(isset($avail_groups[$group->ID])){
        unset($avail_groups[$group->ID]);
    }
    ++$loop;
}

//Put all those options into the select box
$selectbox = CTX_Helper::gen('select', array('id'=>'ctxps-add-group','name'=>'ctxps-add-group'), $ddl_group_opts);

/*
echo '<pre>';
print_r($avail_groups);
echo '

';
print_r($term_groups);
echo '

';
print_r($all_groups);
echo '</pre>';
*/
?>