<?php
define('TABLE_PAGANTIS_LOG', 'pagantis_log');
chdir('../../../../');
require('includes/application_top.php');

$response = array();
$secretKey = $_GET['secret'];

$privateQuery = "select configuration_value from configuration where configuration_key = 'MODULE_PAYMENT_PAGANTIS_SK'";
$resultsSelect = tep_db_query($privateQuery);
$orderRow = tep_db_fetch_array($resultsSelect);
$privateKey = $orderRow['configuration_value'];

if ($secretKey!='' && $privateKey!='') {
    $query ="select log, createdAt from ".TABLE_PAGANTIS_LOG;

    $where = array();
    if (isset($_GET['from'])) {
        $where[] = "createdAt < ".$_GET['from'];
    }

    if (isset($_GET['to'])) {
        $where[] = "createdAt > ".$_GET['to'];
    }


    if (count($where)>0) {
        $query.=" where ";
        foreach ((array)$where as $clause) {
            $query.=" $clause AND";
        }
        $query = substr($query, 0, -3);
    }

    $limit = ($_GET['limit']) ? $_GET['limit'] : 50;
    $query.=" order by createdAt desc limit $limit";

    $response = array();
    $resultsSelect = tep_db_query($query);
    if (isset($resultsSelect) && $privateKey == $secretKey) {
        $i = 0;
        while ($orderRow = tep_db_fetch_array($resultsSelect)) {
            $response[$i]['timestamp'] = $orderRow['createdAt'];
            $response[$i]['log']       = $orderRow['log'];
            $i++;
        }
    } else {
        $response['result'] = 'Error';
    }

    $response = json_encode($response);
    header("HTTP/1.1 200", true, 200);
    header('Content-Type: application/json', true);
    header('Content-Length: '.strlen($response));
    echo($response);
    exit();
}
