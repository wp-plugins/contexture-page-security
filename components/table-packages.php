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
            'list_id'=>'assoc_content_list',  //id value for the table (css-friendly id)
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
                'title'=>'Protection',
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
                'slug'=>'remove',
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
                    'remove'=>'',
                    'view'=>''
                )
            );
            $html .= sprintf('
            <tr id="page-%1$s" %2$s>
                <td class="post-title page-title column-title">
                    <strong><a href="%3$s">%4$s</a></strong>
                    <div class="row-actions">
                        <span class="edit"><a href="%8$spost.php?post=%1$s&action=edit" title="Edit this page">'.__('Edit','contexture-page-security').'</a> | </span>
                        <span class="trash"><a href="#" onclick="CTXPS.removePageFromGroup(%1$s,jQuery(this))" title="Remove current group from this page\'s security">'.__('Remove','contexture-page-security').'</a> | </span>
                        <span class="view"><a href="%7$s" title="View the page">'.__('View','contexture-page-security').'</a></span>
                    </div>
                </td>
                <td class="protected column-protected">%5$s</td>
                <td class="type column-type">%6$s</td>
            </tr>',
                /*1*/$page->sec_protect_id,
                /*2*/$alternatecss,
                /*3*/admin_url('post.php?post='.$page->sec_protect_id.'&action=edit'),
                /*4*/$page_title,
                /*5*/'',
                /*6*/$page->post_type,
                /*7*/get_permalink($page->sec_protect_id),
                /*8*/admin_url()
            );
        }

        // Indexed array. Each entry is an associative array. All values required.
        $this->list_data = array(
            array(
                /** Association, must assign record a unique id (usually from db) */
                'id'=>'1',
                /**
                 * Associate array. Each key MUST correspond to a column slug. Value can be any HTML.
                 */
                'columns'=>array(
                    'col1'=>'<strong><a href="#a">Hello world!</a></strong>',
                    'col2'=>'testing',
                    'col3'=>'first row'
                ),
                /**
                 * Associative array. Each key MUST correspond to an action slug. Value is a link href.
                 */
                'actions'=>array(
                    'view'=>'/wp-admin/post.php?post=1&action=edit',
                    'edit'=>'/wp-admin/edit.php',
                    'delete'=>'/wp-admin/edit.php?action=delete'
                )
            ),
            array(
                'id'=>'2',
                'columns'=>array(
                    'col1'=>'Hello world 2!',
                    'col2'=>'testing 2',
                    'col3'=>'middle row'
                ),
                'actions'=>array(
                    'view'=>'/wp-admin/post.php?post=1&action=edit',
                    'edit'=>'/wp-admin/edit.php',
                    'delete'=>'/wp-admin/edit.php?action=delete'
                )
            ),
            array(
                'id'=>'3',
                'columns'=>array(
                    'col1'=>'<a href="#a">Hello world 3!</a>',
                    'col2'=>'testing 3',
                    'col3'=>'last row'
                ),
                'actions'=>array(
                    'view'=>'/wp-admin/post.php?post=1&action=edit',
                    'edit'=>'/wp-admin/edit.php',
                    'delete'=>'/wp-admin/edit.php?action=delete'
                )
            )
        );
    }

}}
?>