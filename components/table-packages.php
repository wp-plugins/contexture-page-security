<?php
if(!class_exists('CTXPS_Table_Packages')){
/**
 * This class can be instantiated to automatically build table views.
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
                'title'=>'Title',
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
                'title'=>'Type',
                'slug'=>'type',
                'class'=>'col-last',
                'width'=>'80px'
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
                'title'=>'Edit',
                'tip'=>'Edit this content.',
                'slug'=>'edit',
                'color'=>''
            ),
            array(
                'title'=>'Remove',
                'tip'=>'Detach this group from the content.',
                'slug'=>'trash',
                'color'=>'red'
            ),
            array(
                'title'=>'View',
                'tip'=>'View this content on the website.',
                'slug'=>'view',
                'color'=>'red'
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

}}
?>