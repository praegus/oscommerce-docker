<?php
$response = array('status'=>null);
chdir('../../../../');
require('includes/application_top.php');
define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');

/**
 * Variable which contains extra configuration.
 * @var array $defaultConfigs
 */
$defaultConfigs = array('PAGANTIS_TITLE'=>'Instant Financing',
                               'PAGANTIS_SIMULATOR_DISPLAY_TYPE'=>'sdk.simulator.types.PRODUCT_PAGE',
                               'PAGANTIS_SIMULATOR_DISPLAY_TYPE_CHECKOUT'=>'sdk.simulator.types.CHECKOUT_PAGE',
                               'PAGANTIS_SIMULATOR_DISPLAY_SKIN'=>'sdk.simulator.skins.BLUE',
                               'PAGANTIS_SIMULATOR_DISPLAY_POSITION'=>'hookDisplayProductButtons',
                               'PAGANTIS_SIMULATOR_START_INSTALLMENTS'=>3,
                               'PAGANTIS_SIMULATOR_MAX_INSTALLMENTS'=>12,
                               'PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR'=>'default',
                               'PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION'=>'sdk.simulator.positions.INNER',
                               'PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR'=>'default',
                               'PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR'=>'default',
                               'PAGANTIS_FORM_DISPLAY_TYPE'=>0,
                               'PAGANTIS_DISPLAY_MIN_AMOUNT'=>1,
                               'PAGANTIS_SIMULATOR_DISPLAY_MAX_AMOUNT' => '0',
                               'PAGANTIS_URL_OK'=>'',
                               'PAGANTIS_URL_KO'=>'',
                               'PAGANTIS_TITLE_EXTRA' => 'Paga hasta en 12 cómodas cuotas con Pagantis. Solicitud totalmente 
                            online y sin papeleos,¡y la respuesta es inmediata!'
);

$response = array();
$secretKey = $_GET['secret'];

$privateQuery = "select configuration_value from configuration where configuration_key = 'MODULE_PAYMENT_PAGANTIS_SK'";
$resultsSelect = tep_db_query($privateQuery);
$orderRow = tep_db_fetch_array($resultsSelect);
$privateKey = $orderRow['configuration_value'];


if ($privateKey != $secretKey) {
    $response['status'] = 401;
    $response['result'] = 'Unauthorized';
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (count($_POST)) {
        foreach ($_POST as $config => $value) {
            if (isset($defaultConfigs[$config]) && $response['status']==null) {
                $updateQuery = "update ".TABLE_PAGANTIS_CONFIG." set value='$value' where config='$config'";
                $resultsSelect = tep_db_query($updateQuery);
            } else {
                $response['status'] = 400;
                $response['result'] = 'Bad request';
            }
        }
    } else {
        $response['status'] = 422;
        $response['result'] = 'Empty data';
    }
}

$formattedResult = array();
if ($response['status']==null) {
    $query = "select * from ".TABLE_PAGANTIS_CONFIG;
    $resultsSelect = tep_db_query($query);
    while ($orderRow = tep_db_fetch_array($resultsSelect)) {
        $formattedResult[$orderRow['config']] = $orderRow['value'];
    }

    $response['result'] = $formattedResult;
}

$result = json_encode($response['result']);
header("HTTP/1.1 ".$response['status'], true, $response['status']);
header('Content-Type: application/json', true);
header('Content-Length: '.strlen($result));
echo($result);
exit();
