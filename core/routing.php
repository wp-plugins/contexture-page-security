<?php
if(!class_exists('CTXPS_Router')){
/**
 * Methods for handling individual views.
 */
class CTXPS_Router{

    /**
     * Used to route all view requests to the correct controller and view files.
     * This will automatically process the controller and display the view (unless
     * the $auto_load parameter is overriden).
     *
     * @global CTXPSC_Tables $ctxpsdb
     * @global wpdb $wpdb
     * @param string $view The name of the view to load (filename conventions)
     * @param boolean $auto_load If true, a view will be automatically loaded. Set to false if controller will select view.
     */
    public static function render($view,$auto_load=true){
        global $wpdb,$ctxpsdb;
        //Load the controller
        require_once CTXPSPATH.'/controllers/'.$view.'_controller.php';
        //Load the view automatically unless overridden (some controllers may need to handle this dynamically)
        if ($auto_load) require_once CTXPSPATH.'/views/'.$view.'.php';
    }

    public static function group_add(){ self::render('group-add'); }
    public static function group_delete(){ self::render('group-delete'); }
    public static function group_edit(){ self::render('group-edit'); }
    public static function groups_list(){ self::render('groups-list'); }
    public static function user_groups(){ self::render('user-groups'); }
    public static function sidebar_security(){ self::render('sidebar-security'); }
}}

?>