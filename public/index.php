<?php

/**
 * @author Jason F. Irwin
 * 
 * This Is Where the Streams Come to Life
 */
define('BASE_DIR', dirname(__FILE__));
define('THEME_DIR', BASE_DIR . '/themes');
define('FLATS_DIR', BASE_DIR . '/../flats');
define('CONF_DIR', BASE_DIR . '/../conf');
define('LANG_DIR', BASE_DIR . '/../lang');
define('LOG_DIR', BASE_DIR . '/../logs');
define('LIB_DIR', BASE_DIR . '/../lib');
define('SQL_DIR', BASE_DIR . '/../sql');
define('TMP_DIR', BASE_DIR . '/../tmp');
require_once(LIB_DIR . '/main.php');

error_reporting(E_ERROR | E_PARSE);
mb_internal_encoding("UTF-8");

// Set the default time zone to UTC
date_default_timezone_set('UTC');

$data = new Streams;
$data->buildResult();
unset( $data );

?>