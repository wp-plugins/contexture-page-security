<?php
global $taxonomy,$tag,$wpdb;
$protected_status = CTXPS_Queries::get_term_protection($_REQUEST['tag_ID']);

//Put all those options into the select box
$selectbox = CTX_Helper::gen('select',array('id'=>'groups-available','name'=>'groups-available'),$group_avail_opts);

?>