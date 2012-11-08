<?php
/**
* Класс отвечающий за скачку страницы с использованием реального ip
*/
Class IpReal extends Ip{
	
	/**
	 * проверка валидности адреса
	 * поиск реального ip
	 */ 
	function __construct() {
		// Инициализация названия
		$this->name='real';
		if ($this->check()==FALSE){
			$this->status=FALSE;
			return FALSE;
		}
		else $this->status=TRUE;
		$this->genPool();
	}

	/**
	 * нахождения текущего адреса и запись его в переменную
	 */
	protected function genPool() {
		// страница с текущим адресом
		$page=file_get_contents('http://myip.yandex.ru/');
		// поиск адреса на странице
		preg_match('/([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)/', $page,$ip);
		// если ip не удалось определить 
		if(count($ip)==0){
			// скачиваем страницу с другого ресурса 
			$page=file_get_contents('http://www.myip.ru/get_ip.php');
			preg_match('/<TR><TD bgcolor=white align=center valign=middle>([0-9]+\.[0-9]+\.[0-9]+\.[0-9]+)<\/TD><\/TR>/', $page,$ip);
		}
		$this->ip=$ip[1];
		$this->id=1;
		return true;
	}

	/**
	 * обработка события когда ip забаннен
	 */ 
	public function setbanned(){
		echo "REAL IP IS BANNED!!!";
		Registry::get('db')->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",Registry::get('parse_id'),time(), Registry::get('pid'),$url,"real IP banned");
		exit;
	}
	
	/**
	 * получение мнимого нового прокси
	 */
	public function getproxy(){
		//$this->genPool();
		return true;
	}
	
	/**
	 * получение мнимого нового прокси
	 */ 
	public function select(){
		//$this->genPool();
		return true;
	}
	
	/**
	 * проверка на валидность использования текущего ip
	 */
	public function check(){
		$count=file_get_contents('http://ya.ru');
		if (strlen($count)>50) return(TRUE);
		else return(FALSE);
	}
	
	/**
	 * банн реального адреса, не заносится в базу
	 * разбанна нет
	 */	 
	protected function unban(){
		return false;
	}
}
