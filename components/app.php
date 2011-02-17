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
?>