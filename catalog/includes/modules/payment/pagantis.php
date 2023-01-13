<?php

use Pagantis\ModuleUtils\Exception\OrderNotFoundException;
use Pagantis\OrdersApiClient\Model\Order\User\Address;
use Pagantis\ModuleUtils\Exception\UnknownException;
use Pagantis\ModuleUtils\Model\Log\LogEntry;

define('TABLE_PAGANTIS_LOG', 'pagantis_log');
define('TABLE_PAGANTIS_CONFIG', 'pagantis_config');
define('TABLE_PAGANTIS_ORDERS', 'pagantis_order');
define('TABLE_PAGANTIS_CONCURRENCY', 'pagantis_concurrency');
define('MODULE_PAYMENT_PAGANTIS_TEXT_ADMIN_TITLE', 'Pagantis');
define('__ROOT__', dirname(dirname(__FILE__)));

class pagantis
{
    /** @var  String $is_guest */
    public $is_guest;

    /** @var Array $extraConfig */
    public $extraConfig;

    /** @var String $form_action_url */
    public $form_action_url;

    /** @var String $base_url */
    public $base_url;

    /** @var String $os_order_reference */
    public $os_order_reference;

    /** @var notifyController $pgNotify */
    public $pgNotify;

    /** @var string $langCode */
    public $langCode = null;

    /** @var string $errorMessage */
    public $errorMessage;

    /** @var string $errorLinkMessage */
    public $errorLinkMessage;

    public $defaultConfigs = array(
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
        'PAGANTIS_SIMULATOR_CSS_PRICE_SELECTOR_CHECKOUT'=>'default',
        'PAGANTIS_FORM_DISPLAY_TYPE'=>0,
        'PAGANTIS_DISPLAY_MIN_AMOUNT'=>1,
        'PAGANTIS_SIMULATOR_DISPLAY_MAX_AMOUNT'=>'0',
        'PAGANTIS_URL_OK'=>'',
        'PAGANTIS_URL_KO'=>'',
        'PAGANTIS_TITLE_EXTRA' => 'Paga hasta en 12 cómodas cuotas con Pagantis. Solicitud totalmente online y sin papeleos,¡y la respuesta es inmediata!',
        'PAGANTIS_PROMOTION' => '',
        'PAGANTIS_PROMOTED_PRODUCT_CODE' => 'Finance this product <span class="pmt-no-interest">without interest!</span>',
        'PAGANTIS_ALLOWED_COUNTRIES' => 'a:3:{i:0;s:2:"es";i:1;s:2:"it";i:2;s:2:"fr";}',
        'PAGANTIS_SIMULATOR_THOUSANDS_SEPARATOR' => '.',
        'PAGANTIS_SIMULATOR_DECIMAL_SEPARATOR' => ','

    );

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->version = '8.2.0';
        $this->code = 'pagantis';
        $this->sort_order = 0;
        $this->description = $this->getDescription();
        $this->extraConfig = $this->getExtraConfig();

        if (strpos($_SERVER[REQUEST_URI], "checkout_payment.php") <= 0) {
            $this->title = MODULE_PAYMENT_PAGANTIS_TEXT_ADMIN_TITLE; // Payment module title in Admin
        } else {
            $this->title = MODULE_PAYMENT_PAGANTIS_TEXT_CHECKOUT .'<br/><br/><div class="buttonSet" style="display:none"></div><br/>'; // Payment module title in Catalog
        }

        if (defined('MODULE_PAYMENT_PAGANTIS_LANG_CODE')) {
            $this->langCode = strtoupper(MODULE_PAYMENT_PAGANTIS_LANG_CODE);
        }

        $allowedCountries = unserialize($this->extraConfig['PAGANTIS_ALLOWED_COUNTRIES']);

        $this->enabled = ((MODULE_PAYMENT_PAGANTIS_STATUS == 'True' && in_array(strtolower($this->langCode), $allowedCountries)) ? true : false);

        $this->base_url = dirname(
            sprintf(
                "%s://%s%s%s",
                isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
                $_SERVER['SERVER_NAME'],
                isset($_SERVER['SERVER_PORT']) ? ":" . $_SERVER['SERVER_PORT'] : '',
                $_SERVER['REQUEST_URI']
            )
        );

        $this->form_action_url = $this->base_url . '/ext/modules/payment/pagantis/bypass.php';

        if (defined('MODULE_PAYMENT_PAGANTIS_ERROR_MESSAGE')) {
            $this->errorMessage = strtoupper(MODULE_PAYMENT_PAGANTIS_ERROR_MESSAGE);
        }

        if (defined('MODULE_PAYMENT_PAGANTIS_ERROR_MESSAGE')) {
            $this->errorLinkMessage = strtoupper(MODULE_PAYMENT_PAGANTIS_ERROR_LINK_MESSAGE);
        }
    }

    /***************
     *
     * CLASS METHODS
     *
     **************/
    /**
     * Here you can implement using payment zones (refer to standard PayPal module as reference)
     */
    public function update_status()
    {

    }

    /*
    * Here you may define client side javascript that will verify any input fields you use in the payment method
    * selection page. Refer to standard cc module as reference (cc.php).
    */
    public function javascript_validation()
    {
        return false;
    }

    /*
    * Llamada cuando el usuario esta en la pantalla de eleccion de tipo de pago
     * This function outputs the payment method title/text and if required, the input fields.
    *
    * Si hay un pedido generado previamente y no confirmado, se borra
    * Caso de uso:
    * - el usuario llega a la pantalla de confirmacion
    * - se genera el pedido (pero no se genera entrada en orders_status_history)
    * - el usuario decide realizar algun cambio en su compra antes de pasar a pagantis
    * - entra de nuevo en la pantalla de seleccion de tipo de pago (puede elegir otra forma de pago)
    * - se comprueba que no exista el pedido generado anteriormente
    * - se borra el pedido que se habia generado inicialmente. Ya no es valido
    *
    */
    public function selection()
    {
        return array('id' => $this->code, 'module' => $this->title);
    }

    /*
    * Use this function implement any checks of any conditions after payment method has been selected. You most probably
    *  don't need to implement anything here.
    */
    public function pre_confirmation_check()
    {
        return false;
    }

    /*
     * Implement any checks or processing on the order information before proceeding to payment confirmation. You most
    probably don't need to implement anything here.
    * Llamada cuando el usuario entra en la pantalla de confirmacion
    *
    * Se genera el pedido:
    * - con el estado predefinido para el modulo pagantis
    * - sin notificacion a cliente ni administrador
    * - no se borra el carrito asociado al pedido
    *
    */
    public function confirmation()
    {
        return false;
    }

    /**
     * Build the data and actions to process when the "Submit" button is pressed on the order-confirmation screen.
     * This sends the data to the payment gateway for processing.
     * (These are hidden fields on the checkout confirmation page)
     */
    public function process_button()
    {
        try {
            include_once('./ext/modules/payment/pagantis/vendor/autoload.php');
            global $order, $customer_id, $sendto, $billto, $cart, $languages_id, $currency, $currencies, $shipping,
                   $payment, $comments, $customer_default_address_id, $cartID;
            $global_vars = array();
            $global_vars['customer_id'] = serialize($customer_id);
            $global_vars['sendTo'] = serialize($sendto);
            $global_vars['billTo'] = serialize($billto);
            $global_vars['cart'] = serialize($cart);
            $global_vars['languages_id'] = serialize($languages_id);
            $global_vars['currency'] = serialize($currency);
            $global_vars['currencies'] = serialize($currencies);
            $global_vars['shipping'] = serialize($shipping);
            $global_vars['payment'] = serialize($payment);
            $global_vars['comments'] = serialize($comments);
            $global_vars['$customer_default_address_id'] = serialize($customer_default_address_id);
            $global_vars['cartID'] = serialize($cartID);
            $global_vars['sessiontoken'] = serialize($_SESSION['sessiontoken']);

            if (!isset($order)) {
                throw new UnknownException("Order not found");
            }

            $id_hash = time().serialize($order->products).''.serialize($order->customer).''.serialize($order->delivery);
            $this->os_order_reference = md5($id_hash);
            $_SESSION['order_id'] = $this->os_order_reference;

            $national_id = $this->getNationalId();
            $tax_id = $this->getTaxId();

            $fullName = $order->billing['firstname'] . ' ' . $order->billing['lastname'];
            if ($fullName == ' ') {
                $fullName = $order->customer['firstname'] . ' ' . $order->customer['lastname'];
            }
            if ($fullName == ' ') {
                $fullName = $order->delivery['firstname'] . ' ' . $order->delivery['lastname'];
            }
            $fullName = utf8_encode($fullName);
            $userAddress = new Address();
            $userAddress
                ->setZipCode($order->billing['postcode'])
                ->setFullName($fullName)
                ->setCountryCode($order->billing['country']['iso_code'])
                ->setCity($order->billing['city'])
                ->setAddress($order->billing['street_address'])
                ->setFixPhone($order->customer['telephone'])
                ->setMobilePhone($order->customer['telephone'])
                ->setNationalId($national_id)
                ->setDni($national_id)
                ->setTaxId($tax_id);

            $orderBillingAddress = $userAddress;
            $orderShippingAddress = new Address();
            $orderShippingAddress
                ->setZipCode($order->delivery['postcode'])
                ->setFullName($fullName)
                ->setCountryCode($order->delivery['country']['iso_code'])
                ->setCity($order->delivery['city'])
                ->setAddress($order->delivery['street_address'])
                ->setFixPhone($order->customer['telephone'])
                ->setMobilePhone($order->customer['telephone']);

            $orderUser = new \Pagantis\OrdersApiClient\Model\Order\User();
            $orderUser
                ->setAddress($userAddress)
                ->setFullName($fullName)
                ->setBillingAddress($orderBillingAddress)
                ->setEmail($order->customer['email_address'])
                ->setFixPhone($order->customer['telephone'])
                ->setMobilePhone($order->customer['telephone'])
                ->setShippingAddress($orderShippingAddress)
                ->setNationalId($national_id)
                ->setDni($national_id)
                ->setTaxId($tax_id);

            $previousOrders = $this->getOrders();
            foreach ((array)$previousOrders as $k => $previousOrder) {
                $orderHistory = new \Pagantis\OrdersApiClient\Model\Order\User\OrderHistory();
                $orderHistory
                    ->setAmount(intval(100 * $previousOrder['value']))
                    ->setDate(new \DateTime($previousOrder['date_purchased']));
                $orderUser->addOrderHistory($orderHistory);
            }

            $details = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details();
            $shippingCost = number_format($order->info['shipping_cost'], 2, '.', '');
            $details->setShippingCost(intval(strval(100 * $shippingCost)));

            $metadataOrder = new \Pagantis\OrdersApiClient\Model\Order\Metadata();
            $metadata = array(
                'member_since' => $this->getMemberSince(),
                'pg_module' => 'oscommerce',
                'pg_version' => $this->version,
                'ec_module' => 'oscommerce',
                'ec_version' => PROJECT_VERSION
            );
            foreach ($metadata as $key => $metadatum) {
                $metadataOrder->addMetadata($key, $metadatum);
            }

            $promotedAmount = 0;
            foreach ($order->products as $item) {
                $promotedProduct = $this->isPromoted($item);
                $product = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart\Details\Product();
                $product
                    ->setAmount(intval(100 * number_format(($item['final_price'] * $item['qty']), 2)))
                    ->setQuantity(intval($item['qty']))
                    ->setDescription($item['name']);
                if ($promotedProduct) {
                    $promotedAmount+=$product->getAmount();
                    $promotedMessage = $product->getDescription()."-Price:".$item['final_price']."-Qty:".$product->getQuantity();
                    $metadataOrder->addMetadata('promotedProduct', $promotedMessage);
                }
                $details->addProduct($product);
            }

            $orderShoppingCart = new \Pagantis\OrdersApiClient\Model\Order\ShoppingCart();
            $orderShoppingCart
                ->setDetails($details)
                ->setOrderReference($this->os_order_reference)
                ->setTotalAmount(intval($order->info['total'] * 100))
                ->setPromotedAmount($promotedAmount);

            $callback_url = $this->base_url.'/ext/modules/payment/pagantis/callback.php?order_id='.$this->os_order_reference;
            $checkoutProcessUrl = htmlspecialchars_decode(
                tep_href_link(FILENAME_CHECKOUT_PROCESS, "order_id=$this->os_order_reference&from=order", 'SSL', true)
            );

            $cancelUrl = trim(tep_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL', false));
            if ($this->extraConfig['PAGANTIS_URL_KO']!='') {
                $koUrl = $this->extraConfig['PAGANTIS_URL_KO'];
            } else {
                $koUrl = $cancelUrl;
            }

            $orderConfigurationUrls = new \Pagantis\OrdersApiClient\Model\Order\Configuration\Urls();
            $orderConfigurationUrls
                ->setCancel($cancelUrl)
                ->setKo($koUrl)
                ->setAuthorizedNotificationCallback($callback_url)
                ->setRejectedNotificationCallback($callback_url)
                ->setOk($checkoutProcessUrl);


            $orderChannel = new \Pagantis\OrdersApiClient\Model\Order\Configuration\Channel();
            $orderChannel
                ->setAssistedSale(false)
                ->setType(\Pagantis\OrdersApiClient\Model\Order\Configuration\Channel::ONLINE);
            $orderConfiguration = new \Pagantis\OrdersApiClient\Model\Order\Configuration();
            $orderConfiguration
                ->setChannel($orderChannel)
                ->setUrls($orderConfigurationUrls)
                ->setPurchaseCountry($this->langCode)
            ;

            $orderApiClient = new \Pagantis\OrdersApiClient\Model\Order();
            $orderApiClient
                ->setConfiguration($orderConfiguration)
                ->setMetadata($metadataOrder)
                ->setShoppingCart($orderShoppingCart)
                ->setUser($orderUser);

            $publicKey = trim(MODULE_PAYMENT_PAGANTIS_PK);
            $secretKey = trim(MODULE_PAYMENT_PAGANTIS_SK);
            $orderClient = new \Pagantis\OrdersApiClient\Client($publicKey, $secretKey);
            $pagantisOrder = $orderClient->createOrder($orderApiClient);
            if ($pagantisOrder instanceof \Pagantis\OrdersApiClient\Model\Order) {
                $url = $pagantisOrder->getActionUrls()->getForm();
                $this->insertRow($this->os_order_reference, $pagantisOrder->getId(), serialize($global_vars));
            } else {
                throw new OrderNotFoundException();
            }

            if ($url == "") {
                throw new UnknownException(_("No ha sido posible obtener una respuesta de Pagantis"));
            } else {
                $output = "\n";
                $output .= tep_draw_hidden_field("formUrl", $url) . "\n";
                $output .= tep_draw_hidden_field("cancelUrl", $cancelUrl) . "\n";
                return $output;

            } //TODO IFRAME
        } catch (\Exception $exception) {
            $this->insertLog($exception);
            $output = "\n";
            $output .= tep_draw_hidden_field("cancelUrl", $cancelUrl) . "\n";
            $output .= tep_draw_hidden_field("errorMessage", $exception->getMessage()) . "\n";
            $output .= tep_draw_hidden_field("errorCode", $exception->getCode()) . "\n";
            $output .= "<p>".$this->errorMessage.", <a href='$cancelUrl' style='text-decoration:underline'><b>";
            $output .= $this->errorLinkMessage." </b></a></p>";

            return $output;
        }
    }

    /**
     * @throws Exception
     */
    public function before_process()
    {
        include_once('./ext/modules/payment/pagantis/notifyController.php');
        $this->pgNotify = new notifyController();
        $this->pgNotify->setOscommerceOrderId($_GET['order_id']);
        $this->pgNotify->setOrigin(isset($_GET['from']) ? ($_GET['from']) : 'order');
        $this->pgNotify->processInformation();
    }

    /**
     * Post-processing activities
     *
     * @return boolean
     */
    public function after_process()
    {
        $this->pgNotify->confirmInformation();
    }

    /**
     * @return bool
     */
    public function output_error()
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function check()
    {
        if (!isset($this->_check)) {
            $query = "select * from ".TABLE_CONFIGURATION." where configuration_key = 'MODULE_PAYMENT_PAGANTIS_STATUS'";
            $check_query = tep_db_query($query);
            $this->_check = tep_db_num_rows($check_query);
        }
        $this->installPagantisTables();
        return $this->_check;
    }

    /**
     * This is where you define module's configurations (displayed in admin).
     */
    public function install()
    {
        global $messageStack;

        if (defined('MODULE_PAYMENT_PAGANTIS_STATUS')) {
            tep_redirect(tep_href_link(FILENAME_MODULES, 'set=payment&module=pagantis', 'NONSSL'));
            return 'failed';
        }

        tep_db_query("insert into " . TABLE_CONFIGURATION . "
        (
            configuration_title,
            configuration_key,
            configuration_value,
            configuration_description,
            configuration_group_id,
            sort_order,
            set_function,
            date_added) 
        values 
        (
            'Enable module',
            'MODULE_PAYMENT_PAGANTIS_STATUS',
            'True',
            '',
            '6',
            '0',
            'tep_cfg_select_option(array(\'True\',
            \'False\'),
            ',
            now()
        )");
        tep_db_query("insert into " . TABLE_CONFIGURATION . "
        (
            configuration_title,
            configuration_key,
            configuration_value,
            configuration_description,
            configuration_group_id,
            sort_order,
            date_added
        ) 
        values 
        (
            'Public Key',
            'MODULE_PAYMENT_PAGANTIS_PK',
            '',
            'MANDATORY. You can get in your pagantis profile',
            '6',
            '0',
            now()
        )");
        tep_db_query("insert into " . TABLE_CONFIGURATION . "
        (
            configuration_title,
            configuration_key,
            configuration_value,
            configuration_description,
            configuration_group_id,
            sort_order,
            date_added
        ) 
        values 
        (
            'Secret Key',
            'MODULE_PAYMENT_PAGANTIS_SK',
            '',
            'MANDATORY. You can get in your pagantis profile',
            '6',
            '0',
            now()
        )");
        tep_db_query("insert into " . TABLE_CONFIGURATION . "
        (
            configuration_title,
            configuration_key,
            configuration_value,
            configuration_description,
            configuration_group_id,
            sort_order,
            set_function,
            date_added
        ) 
        values 
        (
            'Include simulator',
            'MODULE_PAYMENT_PAGANTIS_SIMULATOR',
            'True',
            'Do you want to include Pagantis simulator',
            '6',
            '3',
            'tep_cfg_select_option(array(\'True\',\'False\'), ',
            now())"
        );
        $this->installPagantisTables();

        $this->installSimulator();
    }

    /**
     * Create the neccesary tables for the module
     */
    private function installPagantisTables()
    {
        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_LOG . " ( 
                          id int NOT NULL AUTO_INCREMENT, 
                          log text NOT NULL, 
                          createdAt timestamp DEFAULT CURRENT_TIMESTAMP, 
                          UNIQUE KEY id (id))";
        tep_db_query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_CONFIG . " (
                            id int NOT NULL AUTO_INCREMENT, 
                            config varchar(60) NOT NULL, 
                            value varchar(200) NOT NULL, 
                            UNIQUE KEY id(id))";
        tep_db_query($sql);

        // check if table has records
        $check_query = tep_db_query("select value from " . TABLE_PAGANTIS_CONFIG);
        if (tep_db_num_rows($check_query) === 0) {
            foreach ((array)$this->defaultConfigs as $configKey => $configValue) {
                $query = "INSERT INTO " . TABLE_PAGANTIS_CONFIG . "
                (
                    config,
                    value
                ) 
                values 
                (
                    '$configKey',
                    '$configValue'
                )";
                tep_db_query($query);
            }
        }

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_ORDERS . " (
                            id int NOT NULL AUTO_INCREMENT, 
                            os_order_id varchar(50), 
                            os_order_reference varchar(50) NOT NULL,
                            pagantis_order_id varchar(50) NOT NULL, 
                            globals text,
                            UNIQUE KEY id(id))";
        tep_db_query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS " . TABLE_PAGANTIS_CONCURRENCY . " (
                            id varchar(50) NOT NULL,
                            `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            UNIQUE KEY id(id))";
        tep_db_query($sql);
    }

    /**
     * Standard functionality to uninstall the module.
     */
    public function remove()
    {
        $checkTable = tep_db_query("SHOW TABLES LIKE '" . TABLE_PAGANTIS_LOG . "'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_LOG);
        }

        $checkTable = tep_db_query("SHOW TABLES LIKE '" . TABLE_PAGANTIS_CONFIG . "'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_CONFIG);
        }

        $checkTable = tep_db_query("SHOW TABLES LIKE '" . TABLE_PAGANTIS_ORDERS . "'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_ORDERS);
        }

        $checkTable = tep_db_query("SHOW TABLES LIKE '" . TABLE_PAGANTIS_CONCURRENCY . "'");
        if (tep_db_num_rows($checkTable) > 0) {
            tep_db_query("drop table " . TABLE_PAGANTIS_CONCURRENCY);
        }

        tep_db_query("DELETE FROM ". TABLE_CONFIGURATION ." where configuration_key in ('MODULE_PAYMENT_PAGANTIS_STATUS','MODULE_PAYMENT_PAGANTIS_PK','MODULE_PAYMENT_PAGANTIS_SK')");

        $query = "delete from " . TABLE_CONFIGURATION . " where configuration_key like '%_PAGANTIS_%'";
        tep_db_query($query);

        $this->uninstallSimulator();
    }

    /**
     * Internal list of configuration keys used for configuration of the module
     *
     * @return array
     */
    public function keys()
    {
        return array(
            'MODULE_PAYMENT_PAGANTIS_STATUS',
            'MODULE_PAYMENT_PAGANTIS_PK',
            'MODULE_PAYMENT_PAGANTIS_SK',
            'MODULE_PAYMENT_PAGANTIS_SIMULATOR'
        );
    }

    /**
     * @return array
     */
    private function getOrders()
    {
        $this->is_guest = 'true';
        if (trim($_SESSION['customer_id']) != '') {
            $this->is_guest = 'false';
            $query = sprintf(
                "select orders_total.value, orders.date_purchased from orders 
JOIN orders_status_history ON orders.orders_id=orders_status_history.orders_id 
JOIN orders_total ON orders.orders_id=orders_total.orders_id 
where orders.customers_id='%s' and orders_status_history.orders_status_id in ('2','3') 
and orders_total.class='ot_total'",
                $_SESSION['customer_id']
            );

            $response = array();
            $resultsSelect = tep_db_query($query);
            while ($orderRow = tep_db_fetch_array($resultsSelect)) {
                $response[] = $orderRow;
            }
        }

        return $response;
    }

    /**
     * @return array
     */
    private function getMemberSince()
    {
        $memberSince = null;
        if (trim($_SESSION['customer_id']) != '') {
            $query = sprintf(
                "select customers_info_date_account_created from customers_info where customers_info_id='%s'",
                $_SESSION['customer_id']
            );

            $response = array();
            $resultsSelect = tep_db_query($query);
            $orderRow = tep_db_fetch_array($resultsSelect);
            $memberSince = date('Y-m-d', strtotime($orderRow['customers_info_date_account_created']));
        }
        return $memberSince;
    }

    /**
     * @param $orderId
     * @param $pagantisOrderId
     * @param $globalVars
     */
    private function insertRow($orderId, $pagantisOrderId, $globalVars)
    {
        $query = "select * from " . TABLE_PAGANTIS_ORDERS . " where os_order_reference='$orderId'";
        $resultsSelect = tep_db_query($query);
        $countResults = tep_db_num_rows($resultsSelect);
        if ($countResults == 0) {
            $query = "INSERT INTO " . TABLE_PAGANTIS_ORDERS . " 
                (os_order_reference, pagantis_order_id, globals) values ('$orderId', '$pagantisOrderId','$globalVars')";
        } else {
            $query = "UPDATE ".TABLE_PAGANTIS_ORDERS." set pagantis_order_id='$pagantisOrderId' 
                        where os_order_reference='$orderId'";
        }
        tep_db_query($query);
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
     * @param $item
     *
     * @return bool
     */
    private function isPromoted($item)
    {
        $productId = explode('{', $item['id'], 1);
        $productId = $productId['0'];

        if ($this->extraConfig['PAGANTIS_PROMOTION'] == '') {
            $promotedProducts = array();
        } else {
            $promotedProducts = array_values((array)unserialize($this->extraConfig['PAGANTIS_PROMOTION']));
        }

        return (in_array($productId, $promotedProducts));
    }

    /**
     * @return string
     */
    private function getDescription()
    {
        $descriptionCode = "<img src=\"images/icon_info.gif\" border=\"0\" alt=\"Info\" title=\"Info\">&nbsp;<strong>Module version:</strong> $this->version<br/><br/>";
        $descriptionCode.= "<img src=\"images/icon_info.gif\" border=\"0\">&nbsp;<a href='https://developer.pagantis.com/' target=\"_blank\" style=\"text-decoration: underline; font-weight: bold;\">View Online Documentation</a><br/><br/>";
        $descriptionCode.= "<img src='images/icon_popup.gif'  border='0'>        <a href='http://pagantis.com' target='_blank' style='text-decoration: underline; font-weight: bold;'>Visit Pagantis Website</a><br/><br/><br/>";

        if (MODULE_PAYMENT_PAGANTIS_STATUS == 'True') {
            $pagantisPromotionUrl = $this->base_url.'/admin/promotion.php';
            $linkDescription = "Si deseas ofrecer financiación sin intereses para alguno de tus productos ";
            $descriptionCode.= "<img src='images/icon_info.gif' border='0'/> $linkDescription<a href='$pagantisPromotionUrl' style='text-decoration: underline; font-weight: bold;'>haz click aquí</a>";

            $pagantisAllowedCountriesUrl = $this->base_url.'/admin/allowedCountries.php';
            $linkDescription = "Para gestionar paises en los que operar con Pagantis ";
            $descriptionCode.= "<br/><br/><img src='images/icon_info.gif' border='0'/> $linkDescription<a href='$pagantisAllowedCountriesUrl' style='text-decoration: underline; font-weight: bold;'>haz click aquí</a>";
        }

        return $descriptionCode;
    }

    /**
     * @return bool
     */
    private function installSimulator()
    {
        $checkSimulator = tep_db_query("select configuration_key, configuration_value from " .TABLE_CONFIGURATION ." 
                                    where configuration_key like 'MODULE_HEADER_TAGS_INSTALLED'
                                    and configuration_value like '%ht_pagantis.php%';");
        if (tep_db_num_rows($checkSimulator) > 0) {
            return true;
        }

        $query = "UPDATE " . TABLE_CONFIGURATION . " set configuration_value = concat(configuration_value, ';ht_pagantis.php')
                        where configuration_key like 'MODULE_HEADER_TAGS_INSTALLED'";
        tep_db_query($query);

        tep_db_query("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable Pagantis Module', 'MODULE_HEADER_TAGS_PAGANTIS_STATUS', 'True', '', '6', '1', 'tep_cfg_select_option(array(\'True\', \'False\'), ', now())");
    }

    /**
     * @return bool
     */
    private function uninstallSimulator()
    {
        $checkSimulator = tep_db_query("select configuration_key, configuration_value from " .TABLE_CONFIGURATION ." 
                                    where configuration_key like 'MODULE_HEADER_TAGS_INSTALLED'
                                    and configuration_value like '%ht_pagantis.php%';");
        if (tep_db_num_rows($checkSimulator) == 0) {
            return true;
        }

        $query = "UPDATE " . TABLE_CONFIGURATION . " set configuration_value = REPLACE(configuration_value, ';ht_pagantis.php', '')
                        where configuration_key like 'MODULE_HEADER_TAGS_INSTALLED'";
        tep_db_query($query);

        $query = "delete from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_HEADER_TAGS_PAGANTIS_STATUS'";
        tep_db_query($query);
    }

    /**
     * @param $exception
     */
    private function insertLog($exception)
    {
        if ($exception instanceof \Exception) {
            $logEntry= new LogEntry();
            $logEntryJson = $logEntry->error($exception)->toJson();
            $logEntryJson = addslashes($logEntryJson);

            $query = "insert into ".TABLE_PAGANTIS_LOG."(log) values ('$logEntryJson')";
            tep_db_query($query);
        }
    }

    /**
     * @return null
     */
    private function getNationalId()
    {
        global $order;
        if (isset($order->customer['national_id'])) {
            return $order->customer['national_id'];
        } elseif (isset($order->billing['piva'])) {
            return $order->billing['piva'];
        } else {
            return null;
        }
    }

    /**
     * @return null
     */
    private function getTaxId()
    {
        global $order;
        if (isset($order->customer['tax_id'])) {
            return $order->customer['tax_id'];
        } elseif (isset($order->billing['cf'])) {
            return $order->billing['cf'];
        } else {
            return null;
        }
    }
}
