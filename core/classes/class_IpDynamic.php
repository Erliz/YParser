<?php
/**
* Класс отвечающий за работу с неопределённым количеством количеством медленных ip
*/
Class IpDynamic extends Ip{
	// время потраченное на запрос с использованием текущего ip
	private $time;
	
	/**
	 * Проверка доступности ip
	 */
	function __construct() {
		$this->name='dynamic';
		$id=$this->check();
		if ($id==false){
			$this->status=false;
			return false;
		}
		else $this->status=true;
	}
	
	/**
	 * Создание временной базы из файла с ip
	 * Удаление забаненных ip
	 * 
	 * @param int $id ID поставщика прокси
	 * 
	 * @return bool
	 */
	public static function set_ip_table($id = 1){
		// удаление давно забанненых ip
		self::unban();
		// поиск данных на ip сервис
		$settings=Registry::get('db')->selectRow("SELECT `url`,`login`,`passwd` FROM `ip_d_list` WHERE `id`=?",$id);
		// парс файла
		$list=explode("\n", file_get_contents($settings['url']));
		// при отсутствии файла
		if (!is_array($list) || count($list)==0) return false;
		// создание таблицы если не существует
		Registry::get('db')->query("CREATE TABLE IF NOT EXISTS `ip_d_tmp` (
									 `id` smallint(3) NOT NULL AUTO_INCREMENT,
									 `ip` varchar(21) NOT NULL,
									 `login` varchar(32) DEFAULT NULL,
 									 `pass` varchar(32) DEFAULT NULL,
									 `speed` smallint(2) DEFAULT NULL,
									 `last_use` int(11) DEFAULT NULL,
									 `count` int(4) NOT NULL DEFAULT '0',
									 PRIMARY KEY (`id`),
									 UNIQUE KEY `ip` (`ip`)
									) ENGINE=MyISAM DEFAULT CHARSET=utf8");
		// если в таблице нет записей заносим новые из файла
		if(Registry::get('db')->selectCell("SELECT COUNT(*) FROM `ip_d_tmp`")==0){
			foreach ($list as $row) {
				// проверка на валидность ip адреса
				if(preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]{4,5}/', $row)) 
					Registry::get('db')->query("INSERT INTO `ip_d_tmp` (`ip`,`login`,`pass`) VALUES (?,?,?)",trim($row),$settings['login'],$settings['passwd']);
			}
			// удаление забаненных ip
			Registry::get('db')->query("DELETE FROM `ip_d_tmp` WHERE `ip` IN (SELECT `ip` FROM `ip_d_ban`)");
		}
		return true;
	}

	/**
	 * Удаление временной базы
	 */
	public static function delete_table(){
		// проверка на отсутствие запущенных парсеров исполюзующих динамическе адреса
		if(Registry::get('db')->selectCell("SELECT COUNT(*) FROM `log_parse` WHERE `proxy`='dynamic' AND `time_stop` IS NULL")==0){
			// удаление базы
			Registry::get('db')->query("DROP TABLE IF EXISTS `ip_d_tmp`;");
			return true;
		}
		else return false;
	}
	
	/**
	 * НЕ ИСПОЛЬЗУЕТСЯ!
	 * Метод для хранения всего списка адресов ip в каждом процессе
	 * IP не сохраняются в базу
	 * Подходит для менее 500 записей
	 * Сильно загружает ЦП
	 * 
	 * @param int $id ID поставщика прокси
	 */
	protected function genPool($id = 1) {
		$list=explode("\n", file_get_contents($settings['url']));
		// при отсутствии файла
		if (!is_array($list) || count($list)==0) return false;
		$settings=Registry::get('db')->selectRow("SELECT `url`,`login`,`passwd` FROM `ip_d_list` WHERE `id`=?",$id);
		$this->pool=Array();
		foreach ($list as $row) {
			// проверка на валидность ip адреса
			if(preg_match('/[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}:[0-9]{4,5}/', $row)) 
				// формирование пула
				$this->pool[]=trim($row)."|".$settings['login'].":".$settings['passwd'];
		}
		// преобразования для уникальности обьекта
		shuffle($this->pool);
		reset($this->pool);
		$this->counter=0;
		$this->count=count($this->pool);
	}

	/**
	 * Выбор нового ip
	 * # помечанны функции для genPool метода
	 * 
	 * @return bool
	 */
	public function getproxy() {
		$count=0;
		while (1) {
			#if ($this->counter>$this->count) $this->genPool();
			$id=$this->select();
			$this->counter++;
			#if (isset($this->pool[$id])) {
			if (is_array($id)) {
				#$proxy=explode("|", $this->pool[$id]);
				$this->id=$id['id'];
				#if($this->in_ban($proxy[0])) continue;
				$this->ip=$id['ip'];
				$this->login=$id['login']!="" ? $id['login'] : null;
				return true;
			}
			else {
				$count++;
				if ($count==1000) {
					file_put_contents(LOGS_PATH.'/error.txt', "Нет прокси в течении 33 минут. Парсер остановлен.", FILE_APPEND);
					exit ;
				}
			}
		}
	}
	
	/**
	 * НЕ ИСПОЛЬЗУЕТСЯ!
	 * Метод для проверки забанненых ip
	 * 
	 * @param string $ip ip адрес
	 * 
	 * @return bool в банне ip или нет
	 */
	private function in_ban($ip){
		$banned=Registry::get('db')->selectCell("SELECT COUNT(*) FROM `ip_d_ban` WHERE `ip`=? LIMIT 1;",$ip);
		return ($banned==1)?true:false;
	}
	
	/**
	 * НЕ ИСПОЛЬЗУЕТСЯ!
	 * Метод для получения ID адреса
	 * 
	 * @return int ID вдреса для пула
	 */
	private function select_old(){
		$id=mt_rand(0, $this->count);
		if($this->count<200) usleep(mt_rand(TIMEOUT_MIN, TIMEOUT_MAX) * 1000);
		echo "END SELECT DYNAMIC ".time()."<br />";
		return $id;
	}
	
	/**
	 * Выбор нового ip адреса из базы
	 * 
	 * @return array массив с информацией IP
	 */
	protected function select() {
		// массив с количеством ip под условиями
		$count=Registry::get('db')->selectCol("SELECT COUNT(*) FROM `ip_d_tmp` UNION 
												SELECT COUNT(*) FROM `ip_d_tmp` WHERE count>0 UNION 
												SELECT COUNT(*) FROM `ip_d_tmp` WHERE count=0;");
		// если таблицы не существует или она пустая делаем новую
		if($count===false) self::set_ip_table();
		// логика выбора ip
		if((!isset($count[2]) || $count[2]>0)  && ($count[1]<100 || mt_rand(0,3)==0)){
			$condition="WHERE `count`=0 ORDER BY RAND()";
			$rnd=mt_rand(0,$count[2]-1);
		}
		else{
			$condition="WHERE `count`>0 AND `speed` IS NOT NULL ORDER BY `speed` ";
			$rnd=mt_rand(0,($count[0]>100?100:$count[0]));
		}
		// Данные по ip
		$sql=Registry::get('db')->selectRow("SELECT `id`,`ip`,CONCAT(`login`,':',`pass`) as `login` FROM `ip_d_tmp` $condition LIMIT ?d,1",$rnd);
		$ip=Array('id'=>$sql['id'],'ip'=>$sql['ip'],'login'=>$sql['login']);
		// метка о времени использовании
		$this->time=time();
		return $ip;
	}
	
	/**
	 * Мнимый метод проверки
	 * 
	 * @return bool
	 */
	protected function check(){
		return true;
	}

	/**
	 * Занесение текущего ip в бан
	 * 
	 * @return bool
	 */
	public function setbanned() {
		// заносим в таблицу баннов
		Registry::get('db')->query("REPLACE `ip_d_ban` (`ip`,`time`) VALUES (?,?)", $this->ip, time());
		// удаляем из текущей временной таблици
		$this->delete();
		// удаляем из пула
		unset($this->pool[$id]);
		$this->count=$this->count-1;
		return true;
	}
	
	/**
	 * Разбанн ip которые были забанены 3 дня назад
	 * 
	 * @return bool
	 */
	public static function unban() {
		$base_time=Registry::get('db')->selectCell("DELETE FROM `ip_d_ban` WHERE `time`<?",time()-(2*24*60*60));//2 day before
		return true;
	}
	
	/**
	 * Удаление текущего ip из временной базы
	 * 
	 * @return bool
	 */
	public function delete(){
		Registry::get('db')->selectCell("DELETE FROM `ip_d_tmp` WHERE `id`=?",$this->id);
		return true;
	}
	
	/**
	 * Метка о времени использования ip
	 * 
	 * @return bool
	 */
	public function usage($url='') {
		parent::usage($url);
		Registry::get('db')->query("UPDATE `ip_d_tmp` SET `speed`=?,`last_use`=?,`count`=`count`+1 WHERE `id`=? LIMIT 1",(time()-$this->time),time(),$this->id);
		return true;
	}
	

}
?>