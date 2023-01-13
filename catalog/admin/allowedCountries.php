<?php
require('includes/application_top.php');

define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');
define('TABLE_LAUGUAGES', 'languages');

//Check if module is installed
$result = tep_db_query("select * from " . TABLE_CONFIGURATION . " where configuration_key='MODULE_PAYMENT_PAGANTIS_STATUS'");
$resultSelect = tep_db_fetch_array($result);
if (tep_db_num_rows($result) == 0 || $resultSelect['configuration_value'] !=='True') {
    tep_redirect('/admin/index.php');
}

if ($HTTP_POST_VARS['submit'] == '1') {
    $allowedCountries = array();
    foreach ((array)$HTTP_POST_VARS['checkboxCountries'] as $countryId => $checkboxValue) {
        $allowedCountries[] = $countryId;
    }
    $query = "update ".TABLE_PAGANTIS_CONFIG." set value='".serialize($allowedCountries)."' where config='PAGANTIS_ALLOWED_COUNTRIES'";
    $result = tep_db_query($query);
}

$checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_CONFIG."'");
$allowedCountries = array();
if (tep_db_num_rows($checkTable) > 0) {
    $query       = "select value from ".TABLE_PAGANTIS_CONFIG." where config='PAGANTIS_ALLOWED_COUNTRIES'";
    $result      = tep_db_query($query);
    $resultSelect = tep_db_fetch_array($result);
    if ($resultSelect['value'] == '') {
        $allowedCountries = array();
    } else {
        $allowedCountries = array_values((array)unserialize($resultSelect['value']));
    }
}

$query        = "select * from ".TABLE_LAUGUAGES;
$result       = tep_db_query($query);
$availableLanguages = array();

while ($resultSelect = tep_db_fetch_array($result)) {
    $availableLanguages[$resultSelect['languages_id']] = array(
            'name' => $resultSelect['name'],
            'code' => $resultSelect['code'],
    );
}


$pagantisAllowdCountriesUrl = '/admin/allowedCountries.php';

require('includes/template_top.php');

?>

<div id="contextText" >
    <table border="0" width="100%" cellspacing="0" cellpadding="2">
        <tbody><tr>
            <td><table border="0" width="100%" cellspacing="0" cellpadding="0">
                    <tbody><tr>
                        <td class="pageHeading">Allowed Countries to use Pagantis</td>
                        <td class="pageHeading" align="right"><img src="images/pixel_trans.gif" border="0" alt="" width="1" height="40"></td>
                        <td align="right"><table border="0" width="100%" cellspacing="0" cellpadding="0">
                                <tbody><tr>
                                    <td class="smallText" align="right">
                                        <form name="search" action="http://oscommerce-dev.docker:8095/admin/categories.php" method="get">Search: <input type="text" name="search"></form>                </td>
                                </tr>
                                <tr>
                                    <td class="smallText" align="right">
                                        <form name="goto" action="http://oscommerce-dev.docker:8095/admin/categories.php" method="get">Go To: <select name="cPath" onchange="this.form.submit();"><option value="0" selected="selected">Top</option><option value="1">Hardware</option><option value="17">&nbsp;&nbsp;&nbsp;CDROM Drives</option><option value="4">&nbsp;&nbsp;&nbsp;Graphics Cards</option><option value="8">&nbsp;&nbsp;&nbsp;Keyboards</option><option value="16">&nbsp;&nbsp;&nbsp;Memory</option><option value="9">&nbsp;&nbsp;&nbsp;Mice</option><option value="6">&nbsp;&nbsp;&nbsp;Monitors</option><option value="5">&nbsp;&nbsp;&nbsp;Printers</option><option value="7">&nbsp;&nbsp;&nbsp;Speakers</option><option value="2">Software</option><option value="19">&nbsp;&nbsp;&nbsp;Action</option><option value="18">&nbsp;&nbsp;&nbsp;Simulation</option><option value="20">&nbsp;&nbsp;&nbsp;Strategy</option><option value="3">DVD Movies</option><option value="10">&nbsp;&nbsp;&nbsp;Action</option><option value="13">&nbsp;&nbsp;&nbsp;Cartoons</option><option value="12">&nbsp;&nbsp;&nbsp;Comedy</option><option value="15">&nbsp;&nbsp;&nbsp;Drama</option><option value="11">&nbsp;&nbsp;&nbsp;Science Fiction</option><option value="14">&nbsp;&nbsp;&nbsp;Thriller</option><option value="21">Gadgets</option></select></form>                </td>
                                </tr>
                                </tbody></table></td>
                    </tr>
                    </tbody></table></td>
        </tr>
        <tr>
            <td>
                <form method="POST" action="<?php echo $pagantisAllowdCountriesUrl; ?>" id="allowedCountriesForm" name="allowedCountriesForm">
                    <table border="0" width="100%" cellspacing="0" cellpadding="0">
                        <tbody><tr>
                            <td valign="top"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                                    <tbody><tr class="dataTableHeadingRow">
                                        <td class="dataTableHeadingContent">Country</td>
                                        <td class="dataTableHeadingContent" align="center">Allowed</td>
                                    </tr>
    <?php foreach ($availableLanguages as $countryId => $country) {
        $checked = (in_array($country['code'], $allowedCountries)) ? 'checked' : '';
        echo "<tr class='dataTableRow'><td class='dataTableContent'>". $country['name']."</td><td class='dataTableContent' align='center'><input name='checkboxCountries[".$country['code']."]' type='checkbox' $checked </td></tr>";
    }
                                    ?>
                                    <tr>
                                        <td colspan="3"><table border="0" width="100%" cellspacing="0" cellpadding="2">
                                                <tbody><tr>
                                                    <td class="smallText">Allowed countries:&nbsp;<?php echo count($allowedCountries)?><br>Total products:&nbsp;<?php echo count($availableLanguages)?></td>
                                                    <td align="right" class="smallText">
                                                        <span class="">
                                                            <button name="submit" id="tdb2" type="submit" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-priority-primary" role="button" aria-disabled="false" value="1">
                                                            <!--a id="tdb1" onclick="document.getElementById('allowedCountriesForm').submit();" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-icon-primary ui-priority-secondary" role="button" aria-disabled="false"-->
                                                                <span class="ui-button-icon-primary ui-icon ui-icon-plus"></span><span class="ui-button-text">Save</span></button>
                                                        </span>
                                                </tr>
                                                </tbody></table></td>
                                    </tr>
                                    </tbody></table></td>
                            <td width="50%" valign="top"></td>
                        </tr>
                        </tbody>
                    </table>
                </form>
            </td>
        </tr>
        </tbody></table>

<?php
require('includes/template_bottom.php');
?>
