<?php
// ежедневная очистка базы
##var##
require_once('core/classes/class_Config.php');
$dbhostname = Config::$dbHost; //host
$dbusername = Config::$dbLogin; //user
$dbpassword = Config::$dbPasswd; //pass
$dbName = Config::$dbName; //table name

$site_path = "/usr/home/devel/projects/parsers/parser/";
$log_path = "logs/";
$pages_path = "pages/";
##end var##

$time_up=time()-(2*24*60*60);

##mysql_cleaning##
mysql_connect($dbhostname,$dbusername,$dbpassword) or die(mysql_error()); //connect to host
mysql_select_db($dbName) or die(mysql_error()); //connect to base
mysql_query("SET names 'utf8'"); //charset utf-8

mysql_query("DELETE FROM `log_errors` WHERE `time`<'{$time_up}'");
mysql_query("DELETE FROM `log_ip` WHERE `time`<'{$time_up}'");
mysql_query("DELETE FROM `log_parse` WHERE `time_start`<'{$time_up}'");
mysql_query("DELETE FROM `log_pid` WHERE `time_start`<'{$time_up}'");

$list=mysql_query("SELECT `qp`.`id` FROM `queryPrice` as `qp` LEFT OUTER JOIN `log_parse` as `lp` ON `qp`.`parse_id` = `lp`.`id` WHERE `lp`.`id` IS NULL") or die(mysql_error());
$list_id="";
mysql_fetch_array($list) or die(mysql_error());
foreach (mysql_fetch_array($list) as $value) $list_id[]=$value;
mysql_query("DELETE FROM `queryPrice` WHERE `id` IN (".join(",", $list_id).")");
mysql_query("DELETE FROM `queryPrice` WHERE `title`=''");

$list=mysql_query("SELECT `qp`.`id` FROM `queryData` as `qp` LEFT OUTER JOIN `log_parse` as `lp` ON `qp`.`parse_id` = `lp`.`id` WHERE `lp`.`id` IS NULL") or die(mysql_error());
$list_id="";
foreach (mysql_fetch_array($list) as $value) $list_id[]=$value;
mysql_query("DELETE FROM `queryData` WHERE `id` IN (".join(",", $list_id).")");

mysql_close();
##mysql_end##

##hosting cleaning##
foreach (glob($site_path.$log_path."*.log") as $filename) {
	$name=explode('.',$filename);
	$name=explode("_", $name[1]);
	if(mktime(0, 0, 0, $name[1], $name[0], $name[2])<$time_up) unlink($filename);
} 
//foreach (glob($site_path.$pages_path."*") as $filename) if(filemtime($filename)<$time_up) unlink($filename);
##hosting end##
?>