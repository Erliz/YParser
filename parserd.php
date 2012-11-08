<?php
/**
 * скрипт для запуска демона
 */
set_time_limit(0);
require_once("autoload.php");
Registry::set('db',Simple::createConnection());
Registry::get('db')->setErrorHandler('Error::db_error');

if (isset($_GET['parse_id'])) new ParserDaemon((int)$_GET['parse_id']);
else echo "parse_id not set!";
exit;
?>