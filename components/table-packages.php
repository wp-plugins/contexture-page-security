<?php
if(!class_exists('CTXPS_Table_Packages')){
/**
 * This class can be instantiated to automatically build table views. All package methods
 * should be prefixed with package_ to distinguish the packages from core methods.
 */
class CTXPS_Table_Packages extends CTX_Tables{

    /**
     * CONFIG PACKAGE for Associated Content
     */
    public function package_associated_content(){
        $this->table_conf = array(
            'form_id'=>'assoc_content_form', //id value for the form (css-friendly id)
            'form_method'=>'get',   //how to submit the form get/post
            'list_id'=>'pagetable',  //id value for the table (css-friendly id)
            'record_slug'=>'assoc_cont_rec',//css-class-friendly slug for uniquely referring to records
            'bulk'=>'false',       //set to true to include checkboxes (if false, bulk options will be disabled)
            'no_records'=>__('No content is attached to this group.','contexture-page-security') //HTML to show if no records are provided
        );
        $this->bulk_conf = array();

        // Indexed array. Each entry is an assoc array. All values required.
        $this->column_conf = array(
            /**
             * title: The visible title of the column
             * slug: The common slug to use in css classes etc
             * class: Any additional classes you want to add
             * width: Leave empty for auto. Specify a css width value to force
             */
            array(
                'title'=>__('Title','contexture-page-security'),
                'slug'=>'title',
                'class'=>'col-first',
                'width'=>''
            ),
            array(
                'title'=>'',
                'slug'=>'protected',
                'class'=>'',
                'width'=>'50px'
            ),
            array(
                'title'=>__('Type','contexture-page-security'),
                'slug'=>'type',
                'class'=>'col-last',
                'width'=>'100px'
            )
        );

        // Indexed array. Each entry is an associative array. All values required.
        $this->actions_conf = array(
            /**
             * title: The visible text for the action
             * slug: The slug to be used in css classes and querystring requests
             * color: Set to any css color value to override default color
             */
            array(
                'title'=>__('Edit','contexture-page-security'),
                'tip'=>__('Edit this content.','contexture-page-security'),
                'slug'=>'edit',
                'color'=>''
            ),
            array(
                'title'=>__('Remove','contexture-page-security'),
                'tip'=>__('Detach this group from the content.','contexture-page-security'),
                'slug'=>'trash',
                'color'=>'red'
            ),
            array(
                'title'=>__('View','contexture-page-security'),
                'tip'=>__('View this content on the website.','contexture-page-security'),
                'slug'=>'view',
                'color'=>''
                )
        );
        //Get list of pages...
        $pagelist = CTXPS_Queries::get_content_by_group($_GET['groupid']);
        foreach($pagelist as $page){
            $page_title = $page->post_title;
            $this->list_data[] = array(
                'id'=>$page->sec_protect_id,
                'columns'=>array(
                    'title'=>sprintf('<strong><a href="%s">%s</a></strong>',admin_url('post.php?post='.$page->sec_protect_id.'&action=edit'),$page_title),
                    'protected'=>'',
                    'type'=>$page->post_type
                ),
                'actions'=>array(
                    'edit'=>admin_url('post.php?post='.$page->sec_protect_id.'&action=edit'),
                    'trash'=>array('onclick'=>sprintf('CTXPS_Ajax.removePageFromGroup(%1$s,jQuery(this));return false;',$page->sec_protect_id)),
                    'view'=>get_permalink($page->ID)
                )
            );

        }

    }

    /**
     * CONFIG PACKAGE for groups attached to taxonomy terms
     */
    public function package_taxonomy_term_groups(){
        $this->table_conf = array(
            'form_id'=>     '',                 //id value for the form (css-friendly id)
            'form_method'=> '',              //how to submit the form get/post
            'list_id'=>     'pagetable',        //id value for the table (css-friendly id)
            'record_slug'=> 'term_group_rec',   //css-class-friendly slug for uniquely referring to records
            'bulk'=>        'false',            //set to true to include checkboxes (if false, bulk options will be disabled)
            'no_records'=>  __('No groups have been added yet.','contexture-page-security'), //HTML to show if no records are provided
            'actions_col'=> 'name'              //Which column do actions go in?
        );
        $this->bulk_conf = array();

        // Indexed array. Each entry is an assoc array. All values required.
        $this->column_conf = array(
            /**
             * title: The visible title of the column
             * slug: The common slug to use in css classes etc
             * class: Any additional classes you want to add
             * width: Leave empty for auto. Specify a css width value to force
             */
            array(
                'title'=>'id',
                'slug'=>'id',
                'class'=>'col-first',
                'width'=>'30px'
            ),
            array(
                'title'=>__('Name','contexture-page-security'),
                'slug'=>'name',
                'class'=>'',
                'width'=>'300px'
            ),
            array(
                'title'=>__('Description','contexture-page-security'),
                'slug'=>'description',
                'class'=>'',
                'width'=>''
            ),
            array(
                'title'=>__('Users','contexture-page-security'),
                'slug'=>'users',
                'class'=>'col-last',
                'width'=>'60px'
            )
        );

        // Indexed array. Each entry is an associative array. All values required.
        $this->actions_conf = array(
            /**
             * title: The visible text for the action
             * slug: The slug to be used in css classes and querystring requests
             * color: Set to any css color value to override default color
             */
            array(
                'title'=>__('Edit','contexture-page-security'),
                'tip'=>__('Edit this content.','contexture-page-security'),
                'slug'=>'edit',
                'color'=>''
            ),
            array(
                'title'=>__('Remove','contexture-page-security'),
                'tip'=>__('Detach this group from the content.','contexture-page-security'),
                'slug'=>'trash',
                'color'=>'red'
            )
        );

        $list = CTXPS_Queries::get_groups();
        foreach($list as $record){
            //Get edit URL
            $edit_url = admin_url("users.php?page=ps_groups_edit&groupid={$record->ID}");
            //Build records
            $this->list_data[] = array(
                //Give this row an id
                'id'=>$record->ID,
                //Define column data
                'columns'=>array(
                    'id'=>$record->ID,
                    'name'=>sprintf('<strong><a href="%s">%s</a></strong>',$edit_url,$record->group_title),
                    'description'=>$record->group_description,
                    'users'=>''//CTXPS_Queries::count_members($record->ID)
                ),
                //Define available actions
                'actions'=>array(
                    'edit'=>$edit_url,
                    'trash'=>$edit_url //array('onclick'=>'alert("test")')
                )
            );//End array add

        }//End foreach

    }

}}
?>