<?php
if(!class_exists('CTXPS_Components')){
class CTXPS_Components{
    /**
     * Adds a "Protection" column to content lists.
     *
     * @param type $columns WP column array
     * @return array The adjusted WP column array (with protected column added)
     */
    public static function add_list_protection_column($columns){

        //Peel of the date (set temp var, remove from array)
        $date = $columns['date'];
        unset($columns['date']);
        //Add new column
        $columns['protected'] = '<div class="vers"><img alt="Protected" src="'.CTXPSURL.'images/protected.png'.'" /></div>';
        //Add date back on (now at end of array);
        $columns['date'] = $date;

        return $columns;
    }

    /**
     * Generates a "lock" symbol for the "Protected" column, if the current content
     * is protected. See WP's template.php -> display_page_row() for  more.
     *
     * @param type $column_name The name of the column to affect ('protected')
     * @param type $post_id The id of the page to check.
     */
    public static function render_list_protection_column($column_name, $post_id){

        //wp_die($columnname.' GOOGLE '.$pageid);

        //Only do this if we've got the right column
        if($column_name==='protected'){
            //If page is protected, return lock icon
            if(CTXPS_Queries::check_protection($post_id)){
                CTX_Helper::img (array(
                    'alt'=>__('Protected','contexture-page-security'),
                    'title'=>__('Protected','contexture-page-security'),
                    'src'=>CTXPSURL.'images/protected-inline.png'
                ));
            }
            //If this page isnt protected, but an ancestor is, return a lighter icon
            else if(CTXPS_Queries::check_section_protection($post_id)){
                CTX_Helper::img (array(
                    'alt'=>__('Protected (inherited)','contexture-page-security'),
                    'title'=>__('Inheriting an ancestors protection','contexture-page-security'),
                    'src'=>CTXPSURL.'images/protected-inline-descendant.png'
                ));
            }
        }
    }


    /**
     * Renders a list of pages protected by the specified group. This returns only the
     * inner HTML of the <tbody> element, as tbody should be already entered on the page.
     *
     * TODO: Rebuild this using CTX_Tables.
     *
     * @global wpdb $wpdb
     *
     * @param int $group_id The id of the group we need a member list for.
     * @return string Html to go inside tbody.
     */
    public static function render_content_by_group_list($group_id){
        global $wpdb;

        $pagelist = CTXPS_Queries::get_content_by_group($group_id);

        $html = '';
        $countpages = '';
        $alternatecss = ' class="alternate" ';

        /**TODO: Must detect if this page is directly protected, or inherrited.*/

        foreach($pagelist as $page){
            $page_title = $page->post_title;
            $html .= sprintf('
            <tr id="page-%1$s" %2$s>
                <td class="post-title page-title column-title">
                    <strong><a href="%3$s">%4$s</a></strong>
                    <div class="row-actions">
                        <span class="edit"><a href="%8$spost.php?post=%1$s&action=edit" title="Edit this page">'.__('Edit','contexture-page-security').'</a> | </span>
                        <span class="trash"><a id="remcontent-%1$s" onclick="ctx_ps_remove_page_from_group(%1$s,jQuery(this))" title="Remove this group from the content">'.__('Remove','contexture-page-security').'</a> | </span>
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

            //Alternate css style for odd-numbered rows
            $alternatecss = ($alternatecss != '') ? '' : ' class="alternate" ';
        }
        return $html;//'<td colspan="2">There are pages attached, but this feature is not yet working.</td>';
    }

    /**
     * Used to generate <tbody> inner html for group lists. If a user_id is provided, the list
     * will only include groups attached to that specified user.
     *
     * @global wpdb $wpdb
     *
     * @param string $user_id If set, only shows groups that have a specific user as a member
     * @param string $view Specify which view the list is being generated for (there are some differences). Supports 'groups' (default) or 'users'
     * @param bool $show_actions If set to false, will not show the actions (default true)
     *
     * @return string Returns the html
     */
    public static function render_group_list($user_id='',$view='groups',$show_actions=true){
        global $wpdb;

        $linkBack = admin_url('users.php');

        $groups = CTXPS_Queries::get_groups($user_id);

        $html = '';
        $htmlactions = '';
        $countmembers = '';
        $alternatecss = ' class="alternate" ';
        $countusers = count_users();

        foreach($groups as $group){
            $countmembers = (!isset($group->group_system_id)) ? CTXPS_Queries::count_members($group->ID) : $countusers['total_users'];

            //Only create the actions if $showactions is true
            if($show_actions){
                switch($view){
                    case 'users':
                        //Button for "Remove" takes user out of group (ajax)
                        $htmlactions = "<div class=\"row-actions\"><span class=\"edit\"><a href=\"{$linkBack}?page=ps_groups_edit&groupid={$group->ID}\">Edit</a> | </span><span class=\"delete\"><a class=\"submitdelete\" id=\"unenroll-{$group->ID}\" onclick=\"ctx_ps_remove_group_from_user({$group->ID},{$_GET['user_id']},jQuery(this))\">Unenroll</a></span></div>";
                        break;
                    case 'groups':
                        //Button for "Delete" removes group from db (postback)
                        //If $showactions is false, we dont show the actions row at all
                        $htmlactions = "<div class=\"row-actions\"><span class=\"edit\"><a href=\"{$linkBack}?page=ps_groups_edit&groupid={$group->ID}\">Edit</a> | </span><span class=\"delete\"><a class=\"submitdelete\" href=\"?page=ps_groups_delete&groupid={$group->ID}\">Delete</a></span></div>";
                        break;
                    default:break;
                }
            }

            //If user isnt admin, we wont even link to group edit page (useful for profile pages)
            if ( current_user_can('manage_options') ){
                //User is admin - determined if link is system or not
                $grouplink = (!isset($group->group_system_id))
                    //This is a user group (editable)
                    ? "<a href=\"{$linkBack}?page=ps_groups_edit&groupid={$group->ID}\"><strong>{$group->group_title}</strong></a>{$htmlactions}"
                    //This is a system group (not editable)
                    : "<a id=\"$group->group_system_id\" class=\"ctx-ps-sysgroup\"><strong>{$group->group_title}</strong></a>";
            }else{
                //User is not admin - no links
                $grouplink = "<a id=\"$group->group_system_id\"><strong>{$group->group_title}</strong></a>";
            }

            $html .= "<tr {$alternatecss}>
                <td class=\"id\">{$group->ID}</td>
                <td class=\"name\">{$grouplink}</td>
                <td class=\"description\">{$group->group_description}</td>
                <td class=\"user-count\">$countmembers</td>
            </tr>";

            //Alternate css style for odd-numbered rows
            $alternatecss = ($alternatecss != '') ? '' : ' class="alternate" ';
        }
        return $html;
    }

    /**
     * Returns html for tbody element of group member list.
     *
     * @global wpdb $wpdb
     *
     * @param int $group_id The id of the group we need a member list for.
     * @return string Html to go inside tbody.
     */
    public static function render_member_list($group_id){
        global $wpdb;

        $members = CTXPS_Queries::get_group_members($group_id);

        $html = '';
        $countmembers = '';
        $alternatecss = ' class="alternate" ';

        foreach($members as $member){
            $fname = get_user_meta($member->ID, 'first_name', true);
            $lname = get_user_meta($member->ID, 'last_name', true);
            $rawdate = strtotime($member->grel_expires);
            $jj = (!empty($rawdate)) ? date('d',$rawdate) : ''; //Day
            $mm = (!empty($rawdate)) ? date('m',$rawdate) : ''; //Month
            $aa = (!empty($rawdate)) ? date('Y',$rawdate) : ''; //Year
            if(!empty($rawdate) && $rawdate < time()){
                $displaydate = 'Expired';
            }else{
                $displaydate = (empty($rawdate) ? 'Never' : sprintf('%s-%s-%s',$mm,$jj,$aa));
            }

            $html .= sprintf('
            <tr id="user-%1$s" %2$s>
                <td class="username column-username">
                    <a href="%8$suser-edit.php?user_id=%1$s&wp_httpd_referer=%9$s"><strong>%3$s</strong></a>
                    <div class="row-actions">
                        <span class="membership"><a href="#" class="editmembership" title="Change membership options">'.__('Membership','contexture-page-security').'</a> | </span>
                        <span class="trash"><a class="row-actions" href="%8$s?page=ps_groups_edit&groupid=%6$s&action=rmvusr&usrid=%1$s&relid=%7$s&usrname=%3$s">'.__('Unenroll','contexture-page-security').'</a> | </span>
                        <span class="view"><a href="%8$suser-edit.php?user_id=%1$s&wp_httpd_referer=%9$s" title="View User">'.__('View','contexture-page-security').'</a></span>
                    </div>
                    <div id="inline_%1$s" class="hidden">
                        <div class="username">%3$s</div>
                        <div class="jj">%11$s</div>
                        <div class="mm">%12$s</div>
                        <div class="aa">%13$s</div>
                        <div class="grel">%7$s</div>
                    </div>
                </td>
                <td class="name column-name">%4$s</td>
                <td class="email column-email"><a href="mailto:%5$s">%5$s</a></td>
                <td class="expires column-expires">%10$s</td>
            </tr>',
                /*1*/$member->ID,
                /*2*/$alternatecss,
                /*3*/$member->user_login,
                /*4*/$fname.' '.$lname,
                /*5*/$member->user_email,
                /*6*/$_GET['groupid'],
                /*7*/$member->grel_id,
                /*8*/admin_url(),
                /*9*/admin_url('users.php?page=ps_groups_edit&groupid='.$_GET['groupid']),
                /*10*/$displaydate,
                /*11*/$jj,
                /*12*/$mm,
                /*13*/$aa
                );

            //Alternate css style for odd-numbered rows
            $alternatecss = ($alternatecss != '') ? '' : ' class="alternate" ';
        }
        return $html;
    }


}}

if(!class_exists('CTXPS_Tags')){
/**
 * Put any code that generated
 */
class CTXPS_Shortcodes{

    /**
     * This tag will output a list of groups attached to the current page.
     *
     * @global wpdb $wpdb
     * @global array $post
     */
    public static function groups_attached($atts){
        global $wpdb, $post;

        //Attribute defaults
        $output = shortcode_atts(
        array(
            'public' => 'false',
            'label' => __('Groups attached to this page:','contexture-page-security'),
        ), $atts);

        //Create an array of groups that are already attached to the page
        $currentGroups = '';
        foreach(CTXPS_Queries::get_groups_by_post($post->ID) as $curGrp){
            $currentGroups .= "<li>".$curGrp->group_title." (id:{$curGrp->sec_access_id})</li>";
        }
        $currentGroups = (empty($currentGroups)) ? '<li><em>'.__('No groups attached.','contexture-page-security').'</em></li>' : $currentGroups;
        $return = "<div class=\"ctx-ps-groupvis\"><h3>{$output['label']}</h3><ol>{$currentGroups}</ol></div>";
        if($output['public']==='true'){
            return $return;
        }else{
            return (current_user_can('manage_options')) ? $return : '';
        }
    }

    /**
     * This tag will output a list of groups required to access the current page
     *
     * @global wpdb $wpdb
     * @global array $post
     * @attr public
     * @attr label
     */
    public static function groups_required($atts){
        global $wpdb, $post;

        //Attribute defaults
        $output = shortcode_atts(
        array(
            'public' => 'false',
            'label' => __('Groups Required:','contexture-page-security'),
            'description' => __('To access this page, users must be a member of at least one group from each set of groups.','contexture-page-security'),
            'showempty' => 'true',
        ), $atts);

        $requiredGroups = CTXPS_Security::get_protection( $post->ID );

        //Set this var to count groups for current page
        $groupcount = 0;

        $return = "<div class=\"ctx-ps-groupvis\"><h3>{$output['label']}</h3><p>{$output['description']}</p><ul>";

        foreach($requiredGroups as $pageGroup->ID => $pageGroups->groups){

            //List the page title
            $return .= "<li><strong>".get_the_title($pageGroup->ID)." (id:{$pageGroup->ID})</strong><ul>";

            foreach($pageGroups->groups as $curGrp->ID => $curGrp->title){
                ++$groupcount;
                $return .= "<li>".$curGrp->title." (id:{$curGrp->ID})</li>";
            }

            //If there were no groups attached, show that there's no access at that level
            if(empty($groupcount) && $output['showempty']==='true'){
                $return .= "<li><em>".__('No groups attached','contexture-page-security')."</em></li>";
            }

            //Reset groupcount
            $groupcount = 0;

            $return .= '</ul></li>';
        }

        $return .= '</ul></div>';

        if($output['public']==='true'){
            return $return;
        }else{
            return (current_user_can('manage_options')) ? $return : '';
        }
    }

}}


if(!class_exists('CTX_Tables')){
/**
 * STATIC. Contains methods for generating WP-style table lists.
 * TODO: Add pagination support.
 */
class CTX_Tables {

    /** Assoc array.
     * Default list-table configuration settings.
     * @var array
     * @var 'form_id' : The id to be used in <form>
     * @var 'form_method' : Whether to use 'get' or 'post' in the form
     * @var 'list_id' : The id to use in the table's <tbody>
     * @var 'record_slug' : Used for generating table row classes
     * @var 'bulk' : true will show bulk options (if any are set). False will hide them.
     * @var 'no_records' : The text/html to render out if the query returns no records
     */
    public $table_conf      = array();
    /** Index array => Assoc arrays.
     * An array of column options. Each indexed array represents another column to be displayed.
     * Each associative array must have the following values...
     * @var array
     * @var 'title' : The title to display in the thead and tfoot
     * @var 'slug' : Slug to use for css class of columns
     * @var 'class' : Additional css classes to add to columns (ie: col-first or col-last)
     * @var 'width' : Optional css value for width. Leave blank for auto.
     */
    public $column_conf     = array();
    /** Index array => Assoc arrays.
     * An array of action options. Each indexed array represents another row-action to be displayed.
     * Each associative array must have the following values...
     * @var array
     * @var 'title' : The text to display for the action
     * @var 'tip' : Tooltip renders into the link's title attribute
     * @var 'slug' : Slug to render into css
     * @var 'color' : Optional css value for text color. Leave blank for auto.
     */
    public $actions_conf    = array();
    /**
     * Assoc array. Allows configuration of bulk actions. If empty, bulk actions will be
     * hidden and no checkboxes will be rendered.
     * @var array
     * @var KEY : Visible bulk action text. ie: "Delete"
     * @var VALUE : Slug-value to be passed in querystring. Use $_GET['action'] to get value. ie: "delete"
     */
    public $bulk_conf       = array();
    /**
     * An array of configuration packages we've included in this class.
     * @var array
     */
    private $lists          = array('demo','forms');
    /**
     * Store final, processed query data here. This is passed directly to $this->create
     * @var array
     */
    public $list_data      = array();

    /**
     * Routes request to correct configuration package then runs the create function
     * @param string $list The name of the list to generate (if using packaged ;ist table)
     */
    public function __construct($list,$echo=true){
        if( in_array(strtolower($list),$this->lists) ){
            //Run configuration package for specified list (if list exists)
            eval(sprintf('$this->package_%s();',$list));
        }else{
            //If list isn't a package, try using hooked package
            do_action('ctx_list_table_prepare-'.$list,$this);
        }
        if($echo){
            //Spit the created table onto the page
            $this->render();
        }
    }

    /** Immediately renders/echos the table in it's current state. */
    public function render(){
        echo $this->create($this->table_conf, $this->column_conf, $this->actions_conf, $this->bulk_conf, $this->list_data);
    }

    /** Procedural setter for $this->table_conf */
    public function set_table_conf($array){
        $this->table_conf = $array;
    }
    /** Procedural setter for $this->column_conf */
    public function set_column_conf($array){
        $this->column_conf = $array;
    }
    /** Procedural setter for $this->actions_conf */
    public function set_actions_conf($array){
        $this->actions_conf = $array;
    }
    /** Procedural setter for $this->bulk_conf */
    public function set_bulk_conf($array){
        $this->bulk_conf = $array;
    }
    /** Procedural setter for $this->list_data */
    public function set_list_data($array){
        $this->list_data = $array;
    }

    /**
     * Returns an associative array compatible with a single entry in the self::create()'s $records array.
     * Use $records[] = prepare_row(); to append returned record to your $records array for use in list_table.
     *
     * Note: Be sure that the column and action slugs correspond to the configs you will use in self::create()
     *
     * @param string $id A unique id that corresponds to the db id.
     * @param array $columns An associative array of $col['slug']=>$display_html
     * @param array $actions An associative array of $acts['slug']=>$link_url
     * @return array Returns a self::create($records) compatible array entry.
     */
    static function prepare_row($id,$columns,$actions=null){
        //Check for errors
        if(empty($id)) trigger_error (__('Parameter $id must be set to a unique record id or guid.','contexture-page-security'), E_USER_WARNING);
        if(!is_array($columns)) trigger_error (__('Parameter $columns is not an array.','contexture-page-security'), E_USER_WARNING);
        if(!is_array($columns) && !empty($columns)) trigger_error (__('Parameter $actions is not an array or empty value.','contexture-page-security'), E_USER_WARNING);
        //Build the array
        $row_array = array();
        $row_array['id'] = $id;
        $row_array['columns'] = $columns;
        $row_array['actions'] = $actions;
        return $row_array;
    }

    /**
     * Can be used to generate WP-style list tables
     *
     * @param type $tableinfo
     * @param type $columninfo
     * @param type $rowinfo
     * @param type $demo If true, a demo table will be generated, regardless of settings
     */
    static function create($table_conf=array(),$column_conf=array(),$action_conf=array(),$bulk_conf=array(),$records=array()){

        /** Stores output HTML */
        $html ='';
        /** Stores html for the table */
        $table='';
        /** Whether or not to use bulk actions and checkboxes */
        $usebulk=true;
        /** Stores count information for columns */
        $column_count=0;
        /** Stores count information for records */
        $record_count=0;
        /** Stores count info for actions */
        $action_count=0;
        /** The page this form is currently on */
        $current_page=(!empty($_GET['page'])) ? $_GET['page'] : '';


        /****** USE DEFAULT ARRAYS ********************************************
        if($demo===true){
            $table_conf = $demo_tableopts;
            $column_conf = $demo_columns;
            $action_conf = $demo_actions;
            $bulk_conf = $demo_bulk;
            $records = $demo_records;

        /****** USE CUSTOM ARRAYS **********************************************
        }else{
            $table_conf = array_merge($demo_tableopts, $table_conf);
        }
        ***/

        /****** SHOW ERROR OUTPUT IF CONFIG IS WRONG **************************/
        //Check whether or not we can show valid bulk options
        $usebulk = (count($bulk_conf)>0 && strtolower($table_conf['bulk'])==='true');
        //Lets get a count of columns and records
        $column_count = ($usebulk) ? count($column_conf)+1 : count($column_conf);
        $record_count = count($records);
        $action_count = count($action_conf);

        //TODO: Run checks to ensure array formatting is correct and required data is available. Output error message if any test fails.

        if ($column_count===0) return __('<h3>No columns were specified!</h3><p>See demo arrays for correct configurations.</p>','contexture-page-security');

        /****** BULK OPTIONS **************************************************/

        if($usebulk){
            //Must have items in bulk array AND must have use checkboxes option set to true

            //Build start of bulk settings at top of $html
            $html .= '
    <div class="tablenav top">
        <div class="alignleft actions">
            <input type="hidden" name="page" value="'.$current_page.'" />
            <select name="action">
                <option value="-1" selected="selected">'.__('Bulk Actions','contexture-page-security').'</option>';

            //Build custom bulk options
            foreach($bulk_conf as $bulk_key=>$bulk_value){
                $html .= sprintf('<option value="%1$s">%2$s</option>',$bulk_value,$bulk_key);
            }
            //Toss these variables, we don't need them any more
            unset ($bulk_key,$bulk_value);

            //Build end of bulk settings html
            $html .= '
            </select>
            <input type="submit" id="'.$table_conf['form_id'].'-doaction" class="button-secondary action" value="Apply" />
        </div>
        <br class="clear"/>
    </div>';
        }


        /****** BUILD RECORDS *************************************************/
        if($record_count===0){ //If there are no records to show, use no_records message

            $table = sprintf('<tr><td colspan="%s">'.$table_conf['no_records'].'</td></tr>',$column_count);

        } else { //We got some records, so lets build those rows!

            $alt=true;
            //***************** ROWS ********************
            foreach($records as $record){
                //SET $ROWHTML. Do we need to show a checkbox for this row?
                $rowhtml = ($usebulk) ? sprintf('<th scope="row" class="check-column"><input type="checkbox" name="%1$s[]" value="%2$s" /></th>',$table_conf['record_slug'],$record['id']) : '';
                //We need to count iterations
                $count_cols = 0;
                //***************** COLUMNS ********************
                foreach($column_conf as $column){
                    //Main column content
                    $colhtml = '<div>'.$record['columns'][$column['slug']].'</div>';
                    //If this is the first loop, load actions
                    if($count_cols===0){
                        if(count($action_conf)>0){
                            //***************** ACTIONS ********************
                            $colhtml .= '<div class="row-actions" style="white-space:nowrap">';
                            $count_acts = 0;
                            foreach($action_conf as $action){
                                ++$count_acts;
                                $colhtml .= sprintf('<span class="il-action %1$s"><a href="%3$s" title="%4$s">%2$s</a></span>',
                                        /*1*/$action['slug'],
                                        /*2*/$action['title'],
                                        /*3*/$record['actions'][$action['slug']],
                                        /*4*/$action['tip']
                                );
                                if($count_acts!==$action_count){
                                    $colhtml .= ' | ';
                                }
                            }
                            $colhtml .= '</div>';
                        }
                    }
                    ++$count_cols;

                    //Wrap cell content in a <td>
                    $rowhtml .= sprintf('<td class="%1$s column-%1$s">%2$s</td>',$column['slug'],$colhtml);
                }

                //Wrap the $rowhtml content in a <tr>
                $table .= sprintf(' <tr id="%1$s-%2$s" class="%4$s">%3$s</tr>',
                    /*1*/$table_conf['record_slug'],
                    /*2*/$record['id'],
                    /*3*/$rowhtml,
                    /*4*/($alt) ? 'alternate' : '');

                //Reverse the alt value
                !$alt;

            }
            //Lets toss all these throw-away loop variables
            unset($record,$alt,$column,$count_cols,$count_acts,$action,$rowhtml,$colhtml);
        }

        //Wrap the rows in tbody
        $table = sprintf('<tbody id="the-list-%1$s" class="list:%1$s">%2$s</tbody>',$table_conf['list_id'],$table);


        /****** BUILD THEAD & TFOOT *******************************************/
        $column_heads='';
        foreach($column_conf as $th_column){
            $column_heads .= sprintf('<th scope="col" id="%1$s" class="manage-column column-%1$s %4$s" %2$s>%3$s</th>',
                    /*1*/$th_column['slug'],
                    /*2*/(!empty($th_column['width'])) ? sprintf(' style="width:%s"',$th_column['width']) : '',
                    /*3*/$th_column['title'],
                    /*4*/$th_column['class']
            );
        }
        $table .= '<thead><tr>';
        $table .= ($usebulk) ? '<th scope="col" id="cb" class="manage-column column-cb check-column"><input type="checkbox"/></th>' : '';
        $table .= $column_heads;
        $table .= '</tr></thead>';
        $table .= '<tfoot><tr>';
        $table .= ($usebulk) ? '<th scope="col" class="manage-column column-cb check-column"><input type="checkbox"/></th>' : '';
        $table .= $column_heads;
        $table .= '</tr></tfoot>';
        unset($column_heads,$th_column);


        /****** MERGE TABLE WITH HTML *****************************************/
        $html = sprintf('%1$s<table class="wp-list-table widefat fixed %2$s" cellspacing="0">%3$s</table>',
                /*1*/$html,
                /*2*/$table_conf['list_id'],
                /*3*/$table);


        /****** RETURN FORM & TABLE *******************************************/
        return sprintf('<form id="%1$s" action="" method="%2$s">%3$s</form>',
                /*1*/$table_conf['form_id'],
                /*2*/$table_conf['form_method'],
                /*3*/$html);
    }

}}


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
                        <span class="trash"><a href="#" onclick="ctx_ps_remove_page_from_group(%1$s,jQuery(this))" title="Remove current group from this page\'s security">'.__('Remove','contexture-page-security').'</a> | </span>
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