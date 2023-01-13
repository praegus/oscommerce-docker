<?php

define('TABLE_PAGANTIS_ORDERS', 'pagantis_order');
chdir('../../../../');
require('includes/application_top.php');

if (isset($_GET['order_id'])) {
    $query = "select globals from ".TABLE_PAGANTIS_ORDERS." where os_order_reference='".$_GET['order_id']."' limit 1";
    $resultsSelect = tep_db_query($query);
    while ($orderRow = tep_db_fetch_array($resultsSelect)) {
        $globals = $orderRow['globals'];
    }
    $result = unserialize($globals);
    foreach ((array)$result as $var => $content) {
        $GLOBALS[$var] = unserialize($content);
        tep_session_register($var);
    }
    $destUrl = tep_href_link(FILENAME_CHECKOUT_PROCESS, '', 'SSL', true, false);
    $destUrl.="?order_id=$_GET[order_id]&from=notify";
} else {
    $destUrl = tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL', true, false);
}

header("Location: $destUrl");
exit;
