<?php
if(!class_exists('CTXPSC_Queries')){
/**
 * Lets put as much SQL in here as possible to simplify our code
 */
class CTXPSC_Queries{
    /**
     * Adds the important tables to the wordpress database
     * @global wpdb $wpdb
     * @global CTXPSC_Props $ctxpscdb
     */
    public static function plugin_install(){
        global $wpdb, $ctxpscdb;

        $linkBack = admin_url();

        //Ensure that we're using PHP5 (plugin has reported problems with PHP4)
        if (version_compare(PHP_VERSION, '5', '<')) {
            deactivate_plugins($ctxpscdb->pluginbase);
            wp_die(
                "<span style=\"color:red;font-weight:bold;\">".__('Missing Requirement:','contexture-page-security')."</span> "
                .sprintf(__('Page Security requires PHP5 or higher. Your server is running %s. Please contact your hosting service about enabling PHP5 support.','contexture-page-security'),PHP_VERSION)
                ."<a href=\"{$linkBack}plugins.php\"> ".__('Return to plugin page','contexture-page-security')." &gt;&gt;</a>"
            );
        }

        //Build our SQL scripts to create the new db tables
        $sql_create_groups = "CREATE TABLE IF NOT EXISTS `{$ctxpscdb->groups}` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `group_title` varchar(40) NOT NULL COMMENT 'The name of the group',
            `group_description` text COMMENT 'A description of or notes about the group',
            `group_creator` bigint(20) UNSIGNED default NULL COMMENT 'The id of the user who created the group',
            `group_date` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP COMMENT 'The datetime the group was created',
            `group_system_id` varchar(5) UNIQUE NULL COMMENT 'A unique system id for system groups',
            PRIMARY KEY (`ID`)
        )";

        $sql_create_group_relationships = "CREATE TABLE IF NOT EXISTS `{$ctxpscdb->group_rels}` (
            `ID` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `grel_group_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The group id that the user is attached to',
            `grel_user_id` bigint(20) UNSIGNED NOT NULL COMMENT 'The user id to attach to the group',
            `grel_expires` datetime COMMENT 'If set, user cannot access content after this date',
            PRIMARY KEY (`ID`)
        )";

        $sql_create_security = "CREATE TABLE IF NOT EXISTS `{$ctxpscdb->security}` (
            `ID` bigint(20) UNSIGNED NOT NULL auto_increment,
            `sec_protect_type` varchar(10) NOT NULL default 'page' COMMENT 'What type of item is being protected? (page, post, category, etc)',
            `sec_protect_id` bigint(20) unsigned NOT NULL COMMENT 'The id of the item (post, page, etc)',
            `sec_access_type` varchar(10) NOT NULL default 'group' COMMENT 'Specifies whether this security entry pertains to a user, group, or role.',
            `sec_access_id` bigint(20) NOT NULL COMMENT 'The id of the user, group, or role this pertains to.',
            `sec_setting` varchar(10) NOT NULL default 'allow' COMMENT 'Set to either allow or restrict',
            `sec_cascades` tinyint(1) NOT NULL default '1' COMMENT 'If true, these settings inherit down through the pages ancestors. If false (default), settings affect this page only.',
            PRIMARY KEY (`ID`)
        )";

        //Create the tables
        $wpdb->show_errors();
        $wpdb->query($sql_create_groups);
        $wpdb->query($sql_create_group_relationships);
        $wpdb->query($sql_create_security);

        //Record what version of the db we're using (only works if option not already set)
        add_option("contexture_ps_db_version", "1.2");

        //Set plugin options (not db version)
        ctx_ps_set_options();

        /********* START UPGRADE PATH < 1.1 ***********/
        $dbver = get_option("contexture_ps_db_version");
        if($dbver == "" || (float)$dbver < 1.1){
            $wpdb->query("ALTER TABLE `{$ctxpscdb->groups}` ADD COLUMN `group_system_id` varchar(5) UNIQUE NULL COMMENT 'A unique system id for system groups' AFTER `group_date`");
            update_option("contexture_ps_db_version", "1.1");
        }
        /******** END UPGRADE PATH < 1.1 **************/

        /********* START UPGRADE PATH < 1.2 ***********/
        $dbver = get_option("contexture_ps_db_version");
        if($dbver == "" || (float)$dbver < 1.2){
            $wpdb->query("ALTER TABLE `{$ctxpscdb->group_rels}` ADD COLUMN `grel_expires` datetime COMMENT 'If set, user cannot access content after this date' AFTER `grel_user_id`");
            update_option("contexture_ps_db_version", "1.2");
        }
        /******** END UPGRADE PATH < 1.2 **************/

        //Check if our "Registered Users" group already exists
        $CntRegSmrtGrp = (bool)$wpdb->get_var("SELECT COUNT(*) FROM `{$ctxpscdb->groups}` WHERE `group_system_id` = 'CPS01' LIMIT 1");
        if(!$CntRegSmrtGrp){
            //Adds the Registered Users system group (if it doesnt exist)
            $wpdb->query("INSERT INTO `{$ctxpscdb->groups}` (`group_title`,`group_description`,`group_creator`,`group_system_id`) VALUES ('".__('Registered Users','contexture-page-security')."','".__('This group automatically applies to all authenticated users.','contexture-page-security')."','0','CPS01')");
        }
    }


     /**
     * Removes custom tables and options from the WP database.
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Props $ctxpscdb
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
     * @global CTXPSC_Props $ctxpscdb
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
     * Deletes a security record from the db
     *
     * @global wpdb $wpdb
     * @global CTXPSC_Props $ctxpscdb
     * @param string $content_id The id of the page or post to protect
     * @param string $protection_id The id of the group to use for protection
     * @param string $content_type Unused. Will tell PSC what type of content to protect.
     * @return mixed Either a boolean (false) if failed, or an int if succeeded (no rows affected)
     */
    public static function delete_security($content_id,$protection_id,$content_type='page',$protection_type='group'){
        global $wpdb, $ctxpscdb;

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

        return $wpdb->query($sql);
    }


}
}
?>
