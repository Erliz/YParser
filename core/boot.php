<?php
// определение названия сервера
if(isset($_SERVER['SERVER_NAME'])) $name=explode(".", $_SERVER['SERVER_NAME']);
else $name[0]="webtools";
#SQL SETTINGS#
define('DB_HOST', Config::$dbHost);
define('DB_USER_NAME', Config::$dbLogin);
define('DB_PASSWORD', Config::$dbPasswd);
define('DB_NAME', Config::$dbName);
define('DB_PREFIX', '');
#END SQL SETTINGS#

#PATH CONSTANTS#
define('PARS_ROOT',substr(dirname(__FILE__), 0,-5));
$url=explode('/',$_SERVER['PHP_SELF']);
unset($url[count($url)-1]);
define('PARS_URL',join('/',$url).'/');
define('CLASSES_PATH', 'core/classes/');
define('UTILS_PATH', 'core/utils/');
define('ABSTRACTS_PATH', 'core/abstracts/');
define('INTERFACES_PATH', 'core/interfaces/');
define('LOGS_PATH', 'logs/');
define('LOCK_FILE_PATH', 'temp/lock.tmp');
define('COUNTER_FILE_PATH', 'temp/count.tmp');
define('REQUEST_FILE_PATH', LOGS_PATH.'requested_files/');
#END PATH CONSTANTS#
?>