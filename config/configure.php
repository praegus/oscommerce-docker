<?php
define('HTTP_SERVER', getenv('OSCOMMERCE_URL'));
define('HTTPS_SERVER', getenv('OSCOMMERCE_URL'));
define('ENABLE_SSL', false);
define('HTTP_COOKIE_DOMAIN', '');
define('HTTPS_COOKIE_DOMAIN', '');
define('HTTP_COOKIE_PATH', '/');
define('HTTPS_COOKIE_PATH', '/');
define('DIR_WS_HTTP_CATALOG', '/');
define('DIR_WS_HTTPS_CATALOG', '/');
define('DIR_WS_IMAGES', 'images/');
define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
define('DIR_WS_INCLUDES', 'includes/');
define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');

define('DIR_WS_DOWNLOAD_PUBLIC', 'pub/');
define('DIR_FS_CATALOG', '/var/www/html');
define('DIR_FS_DOWNLOAD', DIR_FS_CATALOG . 'download/');
define('DIR_FS_DOWNLOAD_PUBLIC', DIR_FS_CATALOG . 'pub/');

define('DB_SERVER', getenv('OSCOMMERCE_DB_HOST'));
define('DB_SERVER_USERNAME', getenv('OSCOMMERCE_DB_USER'));
define('DB_SERVER_PASSWORD', getenv('OSCOMMERCE_DB_PASSWORD'));
define('DB_DATABASE', getenv('OSCOMMERCE_DB_NAME'));
define('USE_PCONNECT', 'false');
define('STORE_SESSIONS', 'mysql');
define('CFG_TIME_ZONE', 'UTC');
