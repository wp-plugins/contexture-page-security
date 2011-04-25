<?php
global $taxonomy,$tag,$wpdb;
$protected_status = CTXPS_Queries::get_term_protection($_REQUEST['tag_ID']);

?>