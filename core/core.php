<?php
//Get our globals out of the way
define('CTXPSCDIR',basename(dirname(__FILE__)));
global $wpdb, $ctxpscdb;

if(!function_exists('str_truncate')){
/**
 * Truncates a string, if needed, to a specified number of characters.
 * 
 * @param string $string A string to truncate.
 * @param int $limit The number of characters to limit the string to.
 * @return string Truncated string
 */
function str_truncate($string,$limit){
    if (strlen($string) > $limit){
        $string = substr($string, 0, $limit);
    }
    return $string;
}
}
?>