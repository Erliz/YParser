<?php
/**
 * Класс обработки ошибок 
 */
class Error {
	
	/**
	 * Поимка ошибок базы и занос отчёта в базу
	 * Если стоит флаг $_GET['debug'] то вывод ошибки на экран
	 * 
	 * @param string $message строка с ошибкой
	 * @param array $info ассоциативный массив с данными по ошибке 
	 */
	public static function db_error($message, $info){
		$parser_id=Registry::get('parse_id');
		$title=Registry::get('pid');
		if($parser_id==null)$parser_id=0;
		if($title==null)$title=0;
		
		if(isset($_GET['debug']) && $_GET['debug']==true) self::trace();
		else Registry::get('db')->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",$parser_id,time(),$title,$info['message'].' : '.$info['query'],'db_error');
		Registry::get('db')->query("UPDATE `log_pid` SET `time_stop`=0, `aborted`=1 WHERE `id`=?",$title);
		exit;
	}
	
	/**
	 * Вывод информации при ошибке и ее полный ее путь
	 */
	public static function trace(){
		$trace=debug_backtrace();
		/*echo "<pre>";
		var_dump($trace);
		echo "</pre>";*/		
		echo '<span style="color: #F00">'.$trace[1]['function'].' : '.$trace[1]['args'][0]; ;// ('.$info['code'].'): '.$info['message'].'<br/>Query: '.$info['query'].'</span><br/>';}
        exit;
	}
}
?>