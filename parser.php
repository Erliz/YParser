<?php
/**
 * Скрипт для запуска потока
 */
set_time_limit(0);
require_once("autoload.php");

new ChildParser((int)$_GET['pid_id'],@$_GET['pass']);

exit;
?>