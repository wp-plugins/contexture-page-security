<?php

/**
 * Methods for handling individual views.
 */
class CTXPSC_Router{

    /**
     * Used to route all view requests to the correct controller.
     *
     * @global CTXPSC_Tables $fpdb
     * @param type $view
     */
    public static function render($view){
        global $fpdb;
        require_once CTXFPDIR.'/controllers/'.$view.'_controller.php';
    }

    public static function view_forms(){ self::render('group-delete'); }


}

?>
