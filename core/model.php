<?php
if(!class_exists('CTXPSC_Tables')){
class CTXPSC_Tables{
    /** @var string Table name for groups  */
    public $prefix = 'ps_';
    /** @var string Table name for groups  */
    public $groups;
    /** @var string Table name for group relationships  */
    public $group_rels;
    /** @var string Table name for security settings  */
    public $security;
    /** @var string The plugin basename */
    public $pluginbase;

    public function __construct() {
        //Initialize properties
        $this->groups =     $wpdb->prefix.$this->prefix.'groups';
        $this->group_rels = $wpdb->prefix.$this->prefix.'group_relationships';
        $this->security =   $wpdb->prefix.$this->prefix.'security';
        $this->pluginbase = CTXPSCDIR.'/contexture-page-security.php';
    }
}
}
$ctxpscdb = new CTXPSC_Tables();
?>
