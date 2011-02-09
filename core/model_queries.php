<?php
if(!class_exists('CTXPSC_Queries')){
/**
 * Lets put as much SQL in here as possible to simplify our code
 */
class CTXPSC_Queries{
    /**
     * Adds the important tables to the wordpress database
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     */
    public static function plugin_install(){
        global $wpdb, $ctxpscdb;

        $linkBack = admin_url();

        //Ensure that we're using PHP5 (plugin has reported problems with PHP4)
        if (version_compare(PHP_VERSION, '5', '<')) {
            deactivate_plugins($ctxpscdb->pluginbase);
            wp_die(
                "<span style=\"color:red;font-weight:bold;\">".__('Missing Requirement:','contexture-page-security')."</span> "
                .sprintf(__('Page Security requires PHP 5 or higher. Your server is running %s. Please contact your hosting service about enabling PHP 5 support.','contexture-page-security'),PHP_VERSION)
                ."<a href=\"{$linkBack}plugins.php\"> ".__('Return to plugin page','contexture-page-security')." &gt;&gt;</a>"
            );
        }

        //Build our SQL scripts to create the new db tables
        $sql_create_groups = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `group_title` varchar(40) NOT NULL COMMENT 'The name of the group',
            `group_description` text COMMENT 'A description of or notes about the group',
            `group_creator` bigint(20) UNSIGNED default NULL COMMENT 'The id of the user who created the group',
            `group_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'The datetime the group was created',
            `group_system_id` varchar(5) UNIQUE NULL COMMENT 'A unique system id for system groups',
            PRIMARY KEY (`ID`)
        )",$ctxpscdb->groups);

        $sql_create_group_relationships = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `grel_group_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The group id that the user is attached to',
            `grel_user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The user id to attach to the group',
            `grel_expires` datetime COMMENT 'If set, user cannot access content after this date',
            PRIMARY KEY (`ID`)
        )",$ctxpscdb->group_rels);

        $sql_create_security = sprintf("CREATE TABLE IF NOT EXISTS `%s` (
            `ID` bigint(20) UNSIGNED NOT NULL auto_increment,
            `sec_protect_type` varchar(10) NOT NULL default 'page' COMMENT 'What type of item is being protected? (page, post, category, etc)',
            `sec_protect_id` bigint(20) unsigned NOT NULL COMMENT 'The id of the item (post, page, etc)',
            `sec_access_type` varchar(10) NOT NULL default 'group' COMMENT 'Specifies whether this security entry pertains to a user, group, or role.',
            `sec_access_id` bigint(20) NOT NULL COMMENT 'The id of the user, group, or role this pertains to.',
            `sec_setting` varchar(10) NOT NULL default 'allow' COMMENT 'Set to either allow or restrict',
            `sec_cascades` tinyint(1) NOT NULL default '1' COMMENT 'If true, these settings inherit down through the pages ancestors. If false (default), settings affect this page only.',
            PRIMARY KEY (`ID`)
        )",$ctxpscdb->security);

        //deactivate_plugins($ctxpscdb->pluginbase);
        //wp_die('<pre>'.print_r($ctxpscdb,true).'</pre>');
        //wp_die($ctxpscdb->security);

        //Create the tables
        $wpdb->show_errors();
        $wpdb->query($sql_create_groups);
        $wpdb->query($sql_create_group_relationships);
        $wpdb->query($sql_create_security);

        //Record what version of the db we're using (only works if option not already set)
        add_option("contexture_ps_db_version", "1.2");

        //Set plugin options (not db version)
        CTXPSC_Queries::set_options();

        /********* START UPGRADE PATH < 1.1 ***********/
        $dbver = get_option("contexture_ps_db_version");
        if($dbver == "" || (float)$dbver < 1.1){
            $wpdb->query("ALTER TABLE `".$ctxpscdb->groups."` ADD COLUMN `group_system_id` varchar(5) UNIQUE NULL COMMENT 'A unique system id for system groups' AFTER `group_date`");
            update_option("contexture_ps_db_version", "1.1");
        }
        /******** END UPGRADE PATH < 1.1 **************/

        /********* START UPGRADE PATH < 1.2 ***********/
        $dbver = get_option("contexture_ps_db_version");
        if($dbver == "" || (float)$dbver < 1.2){
            $wpdb->query("ALTER TABLE `".$ctxpscdb->group_rels."` ADD COLUMN `grel_expires` datetime COMMENT 'If set, user cannot access content after this date' AFTER `grel_user_id`");
            update_option("contexture_ps_db_version", "1.2");
        }
        /******** END UPGRADE PATH < 1.2 **************/

        //Check if our "Registered Users" group already exists
        $CntRegSmrtGrp = (bool)$wpdb->get_var("SELECT COUNT(*) FROM `".$ctxpscdb->groups."` WHERE `group_system_id` = 'CPS01' LIMIT 1");
        if(!$CntRegSmrtGrp){
            //Adds the Registered Users system group (if it doesnt exist)
            $wpdb->insert($ctxpscdb->groups, array(
                    'group_title'=>__('Registered Users','contexture-page-security'),
                    'group_description'=>__('This group automatically applies to all authenticated users.','contexture-page-security'),
                    'group_creator'=>'0',
                    'group_system_id'=>'CPS01'
            ));
        }
    }

     /**
     * Removes custom tables and options from the WP database.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     */
    public static function plugin_delete(){
        global $wpdb, $ctxpscdb;

        //Build our SQL scripts to delete the old db tables
        $sql_drop_groups = "DROP TABLE IF EXISTS `" . $ctxpscdb->groups . "`";
        $sql_drop_group_relationships = "DROP TABLE IF EXISTS `" . $ctxpscdb->group_rels . "`";
        $sql_drop_security = "DROP TABLE IF EXISTS `" . $ctxpscdb->security . "`";

        //Run our cleanup queries
        $wpdb->show_errors();
        $wpdb->query($sql_drop_groups);
        $wpdb->query($sql_drop_group_relationships);
        $wpdb->query($sql_drop_security);

        //Remove our db version reference from options
        delete_option("contexture_ps_db_version");
        delete_option("contexture_ps_options");
    }



    /**
     * Inserts a new security setting into the db.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param string $content_id The id of the page/post/etc to be protected
     * @param string $protection_id The id of the group/user/etc being given access
     * @param string $content_type The type of content being protected (page/post/category/etc)
     * @param string $protection_type The type of protection being applied (group/user/role/etc)
     * @return mixed Either a boolean (false) if failed, or an int if succeeded (no rows affected)
     */
    public static function add_security($content_id,$protection_id,$content_type='page',$protection_type='group'){
        global $wpdb, $ctxpscdb;

        return $wpdb->insert($ctxpscdb->security,
                array(
                    'sec_protect_type'  =>$content_type,
                    'sec_protect_id'    =>$content_id,
                    'sec_access_type'   =>$protection_type,
                    'sec_access_id'     =>$protection_id
                    )
                );

    }

    /**
     * Deletes one or more security records from the db.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Props $ctxpscdb
     * @param string $content_id The id of the page or post to protect
     * @param string $protection_id The id of the protector to be revoked. If empty, ALL groups will be removed from the content.
     * @param string $content_type Unused. Will tell PSC what type of content to protect.
     * @return mixed Either a boolean (false) if failed, or an int if succeeded (no rows affected)
     */
    public static function delete_security($content_id,$protection_id='',$content_type='page',$protection_type='group'){
        global $wpdb, $ctxpscdb;
        $sql=false;

        if(!empty($protection_id)){
            //To be used for removing specific access from content
            $sql = $wpdb->prepare('
            DELETE FROM `'.$ctxpscdb->security.'`
            WHERE   sec_protect_id      = %s
            AND     sec_protect_type    = %s
            AND     sec_access_id       = %s
            AND     sec_access_type     = %s',
                /*1*/$content_id,
                /*2*/$content_type,
                /*3*/$protection_id,
                /*4*/$protection_type
            );
        }else{
            //To be used for removing ALL access from content
            $sql = $wpdb->prepare('
            DELETE FROM `'.$ctxpscdb->security.'`
            WHERE   sec_protect_id      = %s
            AND     sec_protect_type    = %s',
                /*1*/$content_id,
                /*2*/$content_type
            );
        }

        return $wpdb->query($sql);
    }

    /**
     * Checks if a user is enrolled in a group.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param int $user_id
     * @param int $group_id
     * @return boolean Returns true if user is in group, false if not.
     */
    public static function check_membership($user_id,$group_id){
        global $wpdb, $ctxpscdb;
        $check = $wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM `'.$ctxpscdb->group_rels.'` WHERE grel_group_id=%s AND grel_user_id=%s',
                $group_id,
                $user_id
        ));
        return ($check>0);
    }

    /**
     * Checks if user exists in WP db. Returns true if user exists, false if not.
     *
     * @global wpdb $wpdb
     * @param integer $user_id
     * @return boolean True if user exists in db. False if not.
     */
    public static function check_user_exists($user_id){
        global $wpdb;
        $check = (integer)$wpdb->get_var($wpdb->prepare(
            'SELECT COUNT(*) FROM '.$wpdb->users.' WHERE '.$wpdb->users.'.ID = \'%s\'',
                $user_id
        ));
        return ($check>0);
    }

    /**
     * Add a user to a group.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param int $user_id
     * @param int $group_id
     * @return bool Returns 1 if success, false if failed.
     */
    public static function enroll($user_id,$group_id){
        global $wpdb,$ctxpscdb;
        return $wpdb->insert($ctxpscdb->group_rels,
                array(
                    'grel_group_id'=>$group_id,
                    'grel_user_id'=>$user_id
                )
        );

    }

    /**
     * Updates a user's enrollment information, by grel_id. Use get_grel(), if needed,
     * to find the grel_id.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param int $grel_id
     * @param string $expiration_date A MySQL-friendly, string-formatted DateTime. yyyy-mm-dd hh:mm:ss
     * @return type
     */
    public static function grel_enrollment_update($grel_id,$expiration_date){
        global $wpdb,$ctxpscdb;

        //Return false if this is empty
        if(empty($expiration_date)){
            return false;
        }

        //If we need to set this to null...
        if(trim(strtolower($expiration_date))==='null'){
            return $wpdb->query($wpdb->prepare('UPDATE `'.$ctxpscdb->group_rels.'` SET grel_expires=NULL WHERE id=%s',$grel_id));
        }

        if(preg_match('/^\d{4}-\d{1,2}-\d{1,2}/', trim($expiration_date))>=1){
            //Try to format the date (extra layer of validation)
            $expiration_date = strtotime((string)$expiration_date);
            //Let's convert our unix timestamp back to a MySQL-friendly date
            $expiration_date = date('Y-m-d H:i:s');
        }else{
            return false;
        }

        //Run the query and return
        return $wpdb->update($ctxpscdb->group_rels, array('grel_expires'=>$expiration_date), array('ID'=>$grel_id));;
    }

    /**
     * Gets the group-relationship id for a user's group membership.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @return mixed Returns the grel_id (int) if found, false otherwise.
     */
    public static function get_grel($user_id,$group_id){
        global $wpdb,$ctxpscdb;

        $return = $wpdb->get_var($wpdb->prepare(
                'SELECT `ID` FROM `'.$ctxpscdb->group_rels.'`
                    WHERE grel_user_id=%s
                    AND grel_group_id=%s LIMIT 1',
                $user_id,
                $group_id),
            0,0
        );

        //Return false if above is empty (0 is not a valid starting id in MySQL), else return $result
        return (empty($return)) ? false : $return;
    }

    /**
     * Removes a user from a group.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param int $user_id
     * @param int $group_id
     * @return boolean Returns true if delete was successful, false if it failed for any reason.
     */
    public static function unenroll($user_id,$group_id){
        global $wpdb,$ctxpscdb;

        $count = $wpdb->query($wpdb->prepare('DELETE FROM `'.$ctxpscdb->group_rels.'` WHERE grel_group_id = %s AND grel_user_id = %s',
                $group_id,
                $user_id
        ));
        return ($count>0);
    }


    /**
     * Handles creating or updating the options array
     *
     * @param array $array_overrides An associative array containing key=>value pairs to override originals
     * @return string
     */
    public static function set_options($arrayOverrides=false){

        //Set defaults
        $defaultOpts = array(
            "ad_msg_usepages"=>"false",
            "ad_msg_anon"=>sprintf(__('You do not have the appropriate group permissions to access this page. Please try <a href="%s">logging in</a> or contact an administrator for assistance.','contexture-page-security'),wp_login_url( get_permalink() )),
            "ad_msg_auth"=>__('You do not have the appropriate group permissions to access this page. If you believe you <em>should</em> have access to this page, please contact an administrator for assistance.','contexture-page-security'),
            "ad_page_anon_id"=>"",
            "ad_page_auth_id"=>"",
            "ad_msg_usefilter_menus"=>"true",
            "ad_msg_usefilter_rss"=>"true"
        );

        //Let's see if the options already exist...
        $dbOpts = get_option('contexture_ps_options');

        if(!$dbOpts){
            //There's no options! Let's build them...
            if($arrayOverrides!=false && is_array($arrayOverrides)){
                //If we have some custom settings, use those
                $defaultOpts = array_merge($defaultOpts, $arrayOverrides);
            }
            //Now add them to the db
            return add_option('contexture_ps_options',$defaultOpts);
        }else{
            //db options exist, so let's merge it with the defaults (just to be sure we have all the latest options
            $defaultOpts = array_merge($defaultOpts, $dbOpts);
            //Now let's add our custom settings (if appropriate)
            if($arrayOverrides!=false && is_array($arrayOverrides)){
                //If we have some custom settings, use those
                $defaultOpts = array_merge($defaultOpts, $arrayOverrides);
            }
            return update_option('contexture_ps_options',$defaultOpts);
        }

    }

    /**
     * Gets a count of the number of groups currently in the db
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param int $user_id Optional. If provided, will return the number of groups a specified user is a member of.
     * @return int The number of groups in the db
     */
    public static function count_groups($user_id=null){
        global $wpdb,$ctxpscdb;
        if(is_numeric($user_id) && !empty($user_id)){
            return $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM `'.$ctxpscdb->group_rels.'` WHERE grel_user_id = %s',$user_id));
        }
        return $wpdb->get_var('SELECT COUNT(*) FROM `'.$ctxpscdb->groups.'` WHERE group_system_id IS NULL');
    }


    /**
     * Count the number of pages that use this group for permissions
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param int $group_id The id of the group to count for pages.
     * @return int The number of groups attached to this page.
     */
    public static function count_protected($group_id=null){
        global $wpdb,$ctxpscdb;
        if(is_numeric($group_id) && !empty($group_id)){
            return $wpdb->get_var($wpdb->prepare('SELECT COUNT(DISTINCT(sec_protect_id)) FROM `'.$ctxpscdb->security.'` WHERE sec_access_id=%s',$group_id));
        }
        return $wpdb->get_var('SELECT COUNT(DISTINCT(sec_protect_id)) FROM `'.$ctxpscdb->security.'`');
    }


    /**
     * Gets a count of the number of users currently in a group, or the total number of users
     * currently
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param int $group_id The group id to count users for
     * @return int The number of users attached to the group
     */
    public static function count_members($group_id=null){
        global $wpdb,$ctxpscdb;
        if(is_numeric($group_id) && !empty($group_id)){
            return $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM `'.$ctxpscdb->group_rels.'` WHERE grel_group_id = %s',$group_id));
        }
        return $wpdb->get_var('SELECT COUNT(DISTINCT(grel_user_id)) FROM `'.$ctxpscdb->group_rels.'`');
    }

    /**
     * Returns an array containing the groups attached to the specified page.
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param integer $post_id The post_id of the content to get groups for (can be any content type that uses posts table)
     * @return integer
     */
    public static function get_page_groups($post_id){
        global $wpdb,$ctxpscdb;
        if(is_numeric($post_id) && !empty($post_id)){
            return $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM `'.$ctxpscdb->security.'`
                    JOIN `'.$ctxpscdb->groups.'`
                        ON '.$ctxpscdb->security.'.sec_access_id = '.$ctxpscdb->groups.'.ID
                    WHERE sec_protect_id = %s',
                $post_id
            ));
        }
        //If $post_id is improper, return false
        return false;
    }

    /**
     * Returns a list of pages that are attached to the specified group.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param type $group_id
     * @return type
     */
    public static function get_group_pages($group_id){
        global $wpdb,$ctxpscdb;
        if(is_numeric($group_id) && !empty($group_id)){
            return $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM `'.$ctxpscdb->security.'`
                    JOIN `'.$wpdb->posts.'`
                        ON `sec_protect_id` = `'.$wpdb->posts.'`.ID
                    WHERE sec_access_id=%s',
                $group_id
            ));
        }
        //If $group_id is improper, return false
        return false;
    }

    /**
     * Adds a group to the database.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param string $group_title A short title for the group.
     * @param string $group_description A description of the group.
     * @param int $creator_id The user_id for the person creating this group (can use 0 for none)
     * @return mixed Returns the number of rows inserted (int) or false (bool) on error.
     */
    public static function add_group($group_title,$group_description,$creator_id='0'){
        global $wpdb,$ctxpscdb;
        //Get rid of extra whitespace
        $group_title = trim($group_title);
        //DB column requires names < 40 char
        $group_title = str_truncate($group_title, 40);

        //Only insert the group if the name isn't taken
        if(!self::check_group_exists($group_title)){
            return $wpdb->insert($ctxpscdb->groups, array(
                'group_title'=>$group_title,
                'group_description'=>$group_description,
                'group_creator'=>$creator_id
            ));
        }
        return 0;
    }

    /**
     * Checks if a group with the provided name already exists. This is used to validate
     * in self::create_group() to ensure duplicate group names don't crop up.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @param string $group_name
     * @return boolean Returns true if the group already exists, false if not.
     */
    public static function check_group_exists($group_name){
        global $wpdb,$ctxpscdb;

        //Get rid of extra whitespace
        $group_title = trim($group_title);
        //DB column requires names < 40 char
        $group_name = str_truncate($group_name, 40);

        //Check for a match
        $check = $wpdb->get_var($wpdb->prepare(
                'SELECT COUNT(*) FROM `'.$ctxpscdb->groups.'`
                    WHERE group_title = %s',
                $group_name
        ));

        return ($check>0);
    }

    /**
     * Finds the groups attached to the specified post/page and returns them as
     * an array.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     */
    public static function get_groups($post_id=null){
        global $wpdb,$ctxpscdb;

        //Return an object/array of groups attached to the current page
        if(is_numeric($post_id) && !empty($post_id)){
            return $wpdb->get_results($wpdb->prepare(
                'SELECT * FROM `'.$ctxpscdb->security.'`
                    JOIN `'.$ctxpscdb->groups.'`
                        ON `'.$ctxpscdb->security.'`.sec_access_id = `'.$ctxpscdb->groups.'`.ID
                    WHERE sec_protect_id = %s',
                $post_id
            ));
        //Return ALL groups, orderedby system first, then title (alphabetically)
        }else{
            return $wpdb->get_results('SELECT * FROM `'.$ctxpscdb->groups.'` ORDER BY `group_system_id` DESC, `group_title` ASC');
        }
    }

    /**
     * Gets the id of the specified posts parent.
     *
     * @global wpdb $wpdb
     * @param type $post_id
     * @return integer The id of the specified post's parent
     */
    public static function get_parent_id($post_id){
        global $wpdb,$ctxpscdb;
        return $wpdb->get_var($wpdb->prepare('SELECT post_parent FROM `'.$wpdb->posts.'` WHERE `ID` = %s',$post_id));
    }

    /**
     * Returns an array containing the ids of all explicitly protected pages
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     * @return mixed Returns an array containing post ids, or a CSV if $return_type is set to 'csv'
     */
    public static function get_protected_posts($return_type='array'){
        global $wpdb,$ctxpscdb;
        $results =  $wpdb->get_results('SELECT DISTINCT(post_id) FROM `'.$wpdb->postmeta.'` WHERE `meta_key` = "ctx_ps_security"',ARRAY_N);

        //IF WE NEED A STRING (CSV) DO THIS....
        if($return_type==='string'){
            $string = '';
            foreach($results as $page){
                $string .= $page[0].',';
            }
            //get rid of the last comma before returning
            return preg_replace('/,$/','',$string);
        //HANDLE DEFAULT (ARRAY)
        }

        //We get back an unnecessary multidimensional array, so we will collapse this into a simple array
        $array = array();
        foreach($results as $page){
            $array[] = $page[0];
        }
        return $array;
    }

    /**
     * Gets an array with all the groups that a user belongs to.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Tables $ctxpscdb
     *
     * @param int $user_id The user id of the user to check
     * @return array Returns an array with all the groups that the specified user belongs to.
     */
    public static function get_user_groups($user_id){
        global $wpdb, $ctxpscdb;

        /**Empty array to be used for building output*/
        $array = array();
        /**Todays date for MySQL comparison*/
        $today = date('Y-m-d');
        /**Assume user is multi-site user*/
        $multisitemember = true;

        $groups = $wpdb->get_results($wpdb->prepare(
            'SELECT * FROM `'.$ctxpscdb->group_rels.'`
            JOIN `'.$ctxpscdb->groups.'`
                ON `'.$ctxpscdb->group_rels.'`.grel_group_id = `'.$ctxpscdb->groups.'`.ID
            WHERE `'.$ctxpscdb->group_rels.'`.grel_user_id = %s
            AND grel_expires IS NULL OR grel_expires > %s',
                $user_id,
                $today
        ));

        //We only need an ID and a name as a key/value...
        foreach($groups as $group){
            $array += array($group->ID => $group->group_title);
        }


        //If multisite is enabled we can better support it...
        if(function_exists('is_user_member_of_blog')){
            $multisitemember = is_user_member_of_blog($user_id);
        }

        /*** ADD SMART GROUPS (AKA SYSTEM GROUPS ***/
        //Registered Users Smart Group
        if($user_id != 0 && $multisitemember){
            //Get the ID for CPS01
            $newArray = ctx_ps_get_sysgroup('CPS01');
            //Add CPS01 to the current users permissions array
            $array += array($newArray->ID => $newArray->group_title);
        }

        return $array;
    }

}
}
?>