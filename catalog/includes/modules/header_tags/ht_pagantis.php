<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2010 osCommerce

  Released under the GNU General Public License
*/

define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');
define('MODULE_HEADER_TAGS_PAGANTIS_SDK', 'https://cdn.pagantis.com/js/pg-v2/sdk.js');

class ht_pagantis {
    var $code = 'ht_pagantis';
    var $group = 'header_tags';
    var $title;
    var $description;
    var $enabled = false;
    var $langCode = null;

    /**
     * ht_pagantis constructor.
     */
    function ht_pagantis() {
        $this->title = MODULE_HEADER_TAGS_PAGANTIS_TITLE;
        $this->description = MODULE_HEADER_TAGS_PAGANTIS_DESCRIPTION;
        $this->sort_order = 0;

        if (defined('MODULE_HEADER_TAGS_PAGANTIS_STATUS')
            && defined('MODULE_PAYMENT_PAGANTIS_STATUS')
            && defined('MODULE_PAYMENT_PAGANTIS_SIMULATOR')
        ) {
            $this->enabled = ((MODULE_HEADER_TAGS_PAGANTIS_STATUS == 'True') &&
                (MODULE_PAYMENT_PAGANTIS_STATUS == 'True') &&
                (MODULE_PAYMENT_PAGANTIS_SIMULATOR == 'True')) ;
        }

        $this->extraConfig = $this->getExtraConfig();
        $this->pk = $this->getConfig('MODULE_PAYMENT_PAGANTIS_PK');
        $this->sdkFile = MODULE_HEADER_TAGS_PAGANTIS_SDK;

        if (defined('MODULE_HEADER_TAGS_PAGANTIS_LANG_CODE')) {
            $this->langCode = MODULE_HEADER_TAGS_PAGANTIS_LANG_CODE;
        }
    }

    /**
     * @return array
     */
    private function getExtraConfig()
    {
        $checkTable = tep_db_query("SHOW TABLES LIKE '".TABLE_PAGANTIS_CONFIG."'");
        $response = array();
        if (tep_db_num_rows($checkTable) > 0) {
            $query       = "select * from ".TABLE_PAGANTIS_CONFIG;
            $result      = tep_db_query($query);
            $response    = array();
            while ($resultArray = tep_db_fetch_array($result)) {
                $response[$resultArray['config']] = $resultArray['value'];
            }
        }
        return $response;
    }

    /**
     * @param string $config
     * @return array
     */
    private function getConfig($config = '')
    {
        $query       = "select * from ".TABLE_CONFIGURATION . " where configuration_key ='" . $config . "'";
        $result      = tep_db_query($query);
        $resultArray = tep_db_fetch_array($result);
        return $resultArray['configuration_value'];
    }

    /**
     * @return int
     */
    private function getProductPrice()
    {
        $query       = "select products_price from products where products_id ='" . urlencode($_GET['products_id']) . "'";
        $result      = tep_db_query($query);
        $resultArray = tep_db_fetch_array($result);
        return (int)$resultArray['products_price'];
    }

    /**
     * Execute function
     */
    function execute()
    {
        global $order, $oscTemplate;

        ob_start();
        $productId = $GLOBALS["HTTP_GET_VARS"]["products_id"];
        $checkoutPage = strpos($_SERVER[REQUEST_URI], "checkout_payment.php") > 0;

        if (isset($productId) && $checkoutPage!='1') {
            $productPrice = $this->getProductPrice();
            $pagantisDisplayMinAmount = $this->extraConfig['PAGANTIS_DISPLAY_MIN_AMOUNT'];
            $pagantisDisplayMaxAmount = $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_MAX_AMOUNT'];
            if (($productPrice > (int)$pagantisDisplayMaxAmount  && (int)$pagantisDisplayMaxAmount != '0') ||
                $productPrice < (int)$pagantisDisplayMinAmount) {
                return false;
            }
        }
        //Show promoted html
        if ($this->isPromoted($productId) && $checkoutPage!='1') {
            echo "<style> #promotedText{margin-left: 10%;} .pmt-no-interest{color: #00c1d5 }</style>";
            echo "<div id='promotedText' style='display:none'><br/>".$this->extraConfig['PAGANTIS_PROMOTED_PRODUCT_CODE']."</div>";
            echo '<script>'. PHP_EOL;
            echo '        function loadPromoted()'. PHP_EOL;
            echo '        {'. PHP_EOL;
            echo 'var positionSelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR']. '\';'. PHP_EOL;
            echo 'if (positionSelector === \'default\') {'. PHP_EOL;
            echo 'positionSelector = \'.buttonSet\''. PHP_EOL;
            echo '}'. PHP_EOL;
            echo 'var docFather = document.querySelector(positionSelector);'.PHP_EOL;
            echo 'if (typeof docFather != \'undefined\') {'. PHP_EOL;
            echo 'var promotedNode = document.getElementById("promotedText");'.PHP_EOL;
            echo 'docFather.appendChild(promotedNode);'.PHP_EOL;
            echo 'promotedNode.style.display=""' . PHP_EOL;
            echo '               clearInterval(window.OSPromotedId);'. PHP_EOL;
            echo '               return true;'. PHP_EOL;
            echo '       }'. PHP_EOL;
            echo '               return false;'. PHP_EOL;
            echo '       }'. PHP_EOL;
            echo '       window.OSPromotedId = setInterval(function () {'. PHP_EOL;
            echo '          loadPromoted();'. PHP_EOL;
            echo '       }, 2000);'. PHP_EOL;
            echo '</script>'. PHP_EOL;
        }

        if (isset($productId) || $checkoutPage) {
            $simulatorCode = 'pgSDK';

            //Promoted amount on checkout page
            $promotedAmount = 0;
            foreach ((array)$order->products as $item) {
                $productId = explode('{', $item['id'], 1);
                $productId = array_shift($productId);
                $promotedProduct = $this->isPromoted($productId);
                if ($promotedProduct) {
                    $promotedAmount+=number_format(($item['price'] * $item['qty']), 2);
                }
            }

            echo "<script src='".$this->sdkFile."'></script>". PHP_EOL;
            echo '<style>#bodyContent>form>div>iframe {float: right; width: 280px !important; margin-right: -90px;} .contentContainer {clear: both;}</style>'. PHP_EOL;
            echo '<script>'. PHP_EOL;
            echo '        function loadSimulator()'. PHP_EOL;
            echo '        {'. PHP_EOL;
            echo '           if (typeof '.$simulatorCode.' != \'undefined\') {'. PHP_EOL;
            echo '               var sdk = '.$simulatorCode.';'. PHP_EOL;
            echo '               var positionSelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_POSITION_SELECTOR']. '\';'. PHP_EOL;
            echo '               var priceSelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR']. '\';'. PHP_EOL;
            echo '               var checkoutPriceSelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR']. '\';'. PHP_EOL;
            echo '               var quantitySelector = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_CSS_QUANTITY_SELECTOR']. '\';'. PHP_EOL;
            echo '               var checkoutPage =     \'' . $checkoutPage.'\';'. PHP_EOL;
            echo '               var promotedAmount =     \'' . $promotedAmount.'\';'. PHP_EOL;
            echo '               var langCode =     \'' . $this->langCode.'\';'. PHP_EOL;

            echo '               if (positionSelector === \'default\') {'. PHP_EOL;
            echo '                   positionSelector = \'#bodyContent>form>div\''. PHP_EOL;
            echo '               }'. PHP_EOL;

            echo '               if (priceSelector === \'default\') {'. PHP_EOL;
            echo '                   priceSelector = \'#bodyContent>form>div>h1:first-child\''. PHP_EOL;
            echo '               }'. PHP_EOL;

            echo '               if (checkoutPriceSelector == \'default\' && checkoutPage == \'1\')  {'. PHP_EOL;
            echo '                   priceSelector = \'#columnRight > .infoBoxContainer > .infoBoxContents > tbody > tr:last-child > td\';'. PHP_EOL;
            echo '               }'. PHP_EOL;

            echo '               product_simulator = {};'. PHP_EOL;
            echo '               product_simulator.locale = \'' . strtolower($this->langCode) . '\';'. PHP_EOL;
            echo '               product_simulator.country = \'' . strtolower($this->langCode) . '\';'. PHP_EOL;
            echo '               product_simulator.publicKey = \'' . $this->pk . '\';'. PHP_EOL;
            echo '               product_simulator.selector = positionSelector;'. PHP_EOL;
            echo '               product_simulator.numInstalments = \'' . $this->extraConfig['PAGANTIS_SIMULATOR_START_INSTALLMENTS'] . '\';'. PHP_EOL;
            echo '               product_simulator.type = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_TYPE'] . ';'. PHP_EOL;
            echo '               product_simulator.skin = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_SKIN'] . ';'. PHP_EOL;
            echo '               product_simulator.position = ' . $this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_CSS_POSITION'] . ';'. PHP_EOL;
            echo '               product_simulator.amountParserConfig = {};'. PHP_EOL;
            echo '               product_simulator.amountParserConfig.thousandSeparator = "' . $this->extraConfig['PAGANTIS_SIMULATOR_THOUSANDS_SEPARATOR'] . '";'. PHP_EOL;
            echo '               product_simulator.amountParserConfig.decimalSeparator = "' . $this->extraConfig['PAGANTIS_SIMULATOR_DECIMAL_SEPARATOR'] .  '";'. PHP_EOL;

            //Amount in product page
            echo '               var promotedProduct = \'' . $this->isPromoted($productId) .'\';'. PHP_EOL;
            echo '               if(checkoutPage != \'1\' ) {'. PHP_EOL;
            echo '               product_simulator.itemAmountSelector = priceSelector;'. PHP_EOL;
            echo '                   if(promotedProduct == \'1\') { ' . PHP_EOL;
            echo '                    product_simulator.itemPromotedAmountSelector = priceSelector;'. PHP_EOL;
            echo '                   }' . PHP_EOL;
            echo '               }';

            //Amount in checkout page
            echo '               if(checkoutPage == \'1\' ) { ' . PHP_EOL;
            echo '                  product_simulator.totalAmountSelector = priceSelector;'. PHP_EOL;
            echo '                  if(promotedAmount != \'0\' && checkoutPage == \'1\' ) { ' . PHP_EOL;
            echo '                      product_simulator.totalPromotedAmount = promotedAmount;'. PHP_EOL;
            echo '                  }' . PHP_EOL;
            echo '                  product_simulator.selector = \'.buttonSet\';' . PHP_EOL;
            echo '                  product_simulator.type = ' .$this->extraConfig['PAGANTIS_SIMULATOR_DISPLAY_TYPE_CHECKOUT'] . PHP_EOL;
            echo '               }' . PHP_EOL;
            echo '               sdk.simulator.init(product_simulator);'. PHP_EOL;
            echo '               clearInterval(window.OSSimulatorId);'. PHP_EOL;
            echo '               return true;'. PHP_EOL;
            echo '           }'. PHP_EOL;
            echo '           return false;'. PHP_EOL;
            echo '       }'. PHP_EOL;

            //Invoke to main method
            echo '       window.OSSimulatorId = setInterval(function () {'. PHP_EOL;
            echo '          loadSimulator();'. PHP_EOL;
            echo '       }, 2500);'. PHP_EOL;
            echo '</script>'. PHP_EOL;

            //Checkout simulator
            if ($checkoutPage) {
                echo '<script>' . PHP_EOL;

                echo 'function checkSelected(value)'. PHP_EOL;
                echo '{ '. PHP_EOL;
                echo 'var simulator = document.getElementsByClassName("buttonSet");'  . PHP_EOL;
                echo ' if(simulator == "undefined") { return false;  } '. PHP_EOL;
                echo 'var pagantisCheckbox = document.querySelector("input[value=\'pagantis\']"); ' . PHP_EOL;
                echo 'var grandparentNode = pagantisCheckbox.parentNode.parentNode;' . PHP_EOL;
                echo 'if(grandparentNode == value) { var status="" } ' . PHP_EOL;
                echo 'else { var status="none";} '. PHP_EOL;
                echo 'simulator[0].style.display=status; ' . PHP_EOL;
                echo '}'. PHP_EOL;

                echo 'function showSimulator()'. PHP_EOL;
                echo '{'. PHP_EOL;
                echo 'var elements = document.querySelectorAll("tr[class^=\'moduleRow\']");' . PHP_EOL;
                echo 'if(elements == null) { return false };' . PHP_EOL;
                echo 'for(var i = 0, max = elements.length; i < max; i++) { elements[i].onclick = function() {
                        checkSelected(this);
                    } }' . PHP_EOL;
                echo 'clearInterval(window.OSdisplayId);';
                echo 'return true;'. PHP_EOL;
                echo '};'. PHP_EOL;

                echo '       window.OSdisplayId = setInterval(function () {'. PHP_EOL;
                echo '          showSimulator();'. PHP_EOL;
                echo '       }, 2000);'. PHP_EOL;
                echo '</script>'. PHP_EOL;
            }
        }
        $oscTemplate->addBlock(ob_get_clean(), $this->group);
    }

    /**
     * @return bool
     */
    function isEnabled() {
        return $this->enabled;
    }

    /**
     * @return bool
     */
    function check() {
        return defined('MODULE_HEADER_TAGS_PAGANTIS_STATUS');
    }

    /**
     * install
     */
    function install() {
        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Pagantis Module', 'MODULE_HEADER_TAGS_PAGANTIS_STATUS', 'True', '', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");

    }

    /**
     * remove
     */
    function remove() {
        tep_db_query("delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode("', '", $this->keys()) . "')");
    }

    /**
     * @return array
     */
    function keys() {
        return array('MODULE_HEADER_TAGS_PAGANTIS_STATUS');
    }

    /**
     * @param $productId
     *
     * @return bool
     */
    private function isPromoted($productId)
    {
        if (!isset($productId)) {
            return false;
        }

        if ($this->extraConfig['PAGANTIS_PROMOTION'] == '') {
            $promotedProducts = array();
        } else {
            $promotedProducts = array_values((array)unserialize($this->extraConfig['PAGANTIS_PROMOTION']));
        }

        return (in_array($productId, $promotedProducts));
    }
}
