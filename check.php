<?php
/**
 * Скрипт для ajax запросов из парсеров
 */
####BOOT
require_once("autoload.php");
Registry::set('db',Simple::createConnection());

####LOGIC
if (isset($_GET['parse'])){
	if ($_GET['parse']=='get_stat'){
		if(isset($_GET['parse_id'])) get_parse_stat($_GET['parse_id']);
		else $ans['error'] = 'for `parse:get_stat` don`t isset `parse_id`';
	}
	elseif ($_GET['parse']=='get_error_count'){
		if(isset($_GET['parse_id'])) get_parse_error_count($_GET['parse_id']);
		else $ans['error'] = 'for `parse:get_error_count` don`t isset `parse_id`';
	}
	elseif ($_GET['parse']=='get_id'){
		if(isset($_GET['shop_id']) && isset($_GET['user_id'])) get_parse_id($_GET['shop_id'],$_GET['user_id']);
		else $ans['error'] = 'for `parse:get_id` don`t isset `shop_id` or `user_id`';
	}
	elseif ($_GET['parse']=='set_stop'){
		if(isset($_GET['parse_id'])) set_parse_stop($_GET['parse_id']);
		else $ans['error'] = 'for `parse:set_stop` don`t isset `parse_id`';
	}
	else {
		$ans['error'] = 'no such method for `parse`';
		echo json_encode($ans);
	}
}
else {
	$ans['error'] = 'no method';
	echo json_encode($ans);
}


####METHOD
/**
 * Получение последнего созданного парсера
 * 
 * @param int $shop_id
 * @param int $user_id
 */
function get_parse_id($shop_id,$user_id){
	$shop_id = (int)mysql_real_escape_string($shop_id);
	$user_id = (int)mysql_real_escape_string($user_id);
	$ans['parse_id'] = Registry::get('db')->selectCell("SELECT `t`.`id` FROM (SELECT `id` FROM `log_parse` WHERE `region_id`=? AND `user_id`=? ORDER BY `id` DESC) as `t` LIMIT 1",$shop_id,$user_id);
	echo json_encode($ans);
}

/**
 * Получение информации по парсеру
 * `time_start`, `time_stop`,`rows_plan`,`rows_done`
 * 
 * @param int $id ID парса
 */
function get_parse_stat($id){
	$id = (int)mysql_real_escape_string($id);
	$ans = Registry::get('db')->selectRow("SELECT `time_start`, `time_stop`,`rows_plan`,`rows_done` FROM `log_parse` WHERE `id`=? LIMIT 1",$id);
	if($ans==null) $ans['error'] = 'Такого парсера нет в базе!';
	//else $ans['rows'] = Registry::get('db')->selectCell("SELECT `rows_done` FROM `log_parse` WHERE `id`=? LIMIT 1",$id);	
    echo json_encode($ans);
}

/**
 * Получение кол-ва ошибок по парсеру
 * 
 * @param int $id ID парса
 */
function get_parse_error_count($id){
	$id = (int)mysql_real_escape_string($id);
	$ans = Registry::get('db')->selectCell("SELECT COUNT(*) FROM `log_errors` WHERE `parse_id`=? AND `value`='Not right TITLE!!!'",$id);
	if($ans==null) $ans['error'] = 'Такого парсера нет в базе!';
	//else $ans['rows'] = Registry::get('db')->selectCell("SELECT `rows_done` FROM `log_parse` WHERE `id`=? LIMIT 1",$id);	
    echo json_encode($ans);
	
}

/**
 * Остановить парсер
 * 
 * @param int $id ID парса
 */
function set_parse_stop($id){
	$id = (int)mysql_real_escape_string($id);
	$ans = Registry::get('db')->query("UPDATE `log_parse` SET `time_stop`=0 WHERE `id`=? LIMIT 1",$id);
	if($ans==null) $ans['error'] = 'Такого парсера нет в базе!';
	echo json_encode($ans);
}


?>