<?php
if(!class_exists('CTXAjax')){
class CTXAjax{
    /**
     * Takes an associative array and outputs an XML ajax response.
     */
    public static function response($AssocArray=array()){

        //ctx_ps_ajax_response()

        if(!isset($AssocArray['code'])){
            $AssocArray['code'] = 0;
        }

        @header('Content-Type: text/xml; charset=' . get_option('blog_charset'));
        $response = "<?xml version='1.0' standalone='yes'?><ajax>";
        foreach($AssocArray as $element=>$value){
            $element = strtolower($element);
            $element = sanitize_title_with_dashes($element);
            $response .= "<{$element}>{$value}</{$element}>";
        }
        $response .= "</ajax>";
        die($response);
    }
}
}

class CTXPSAjax extends CTXAjax {


}
?>
