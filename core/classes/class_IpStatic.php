<?php
/**
* Класс отвечающий за работу с определенным количеством быстрых ip
*/
Class IpStatic extends Ip{
	// попытка сделать определение последней использованной подсети 
	private $last_subnet;
	
	/**
	 * проверка ip адресов
	 * генерация pool
	 */
	function __construct() {
		$this->name='static';
		if ($this->check()==FALSE){
			$this->status=FALSE;
			return FALSE;
		}
		else $this->status=TRUE;
		$this->genPool();
	}
	
	/**
	 * Генерация массива с адресами и логинами
	 */
	protected function genPool() {
		// запускам разбан старых ip
		self::unban();
		// получаем список адресов
		$pool=Registry::get('db')->select("SELECT `id`,CONCAT_WS(':',`ip`,`port`) as `ip`, CONCAT_WS(':',`login`,`pass`) as `login` FROM `ip` WHERE `flag`!='0' AND `count`<?", IP_MAX_QUERIES);
		// очистка пула
		unset($this->pool);
		// инициализация пула
		foreach ($pool as $value) $this->pool[(string)$value['id']]=$value['ip'].'|'.$value['login'];
		reset($this->pool);
		$this->counter=0;
		$this->count=count($this->pool);		
	}

	/**
	 * Получение нового прокси
	 */
	public function getproxy(){
		$id=null;
		$count=0;
		// пока есть не забаненные ip в пуле
		while (!isset($this->pool[$id])) {
			#set last_subnet
			if(isset($this->ip)){
				$segments=explode('.',$this->ip);
				$this->last_subnet=join('.',array_slice($segments, 0, 3));
			}
			#end
			// если уже количство обращений больше чем количество ip в пуле, обновить пул
			if ($this->counter>$this->count) $this->genPool();
			// получение валидного id
			$id=$this->select();
			// увеличение количества использования пула
			$this->counter++;
			// если есть такой id
			if (isset($this->pool[(int)$id])){
				// обновляем кол-во использований ip и время
				Registry::get('db')->query("UPDATE `ip` SET `time`=?, `count`=`count`+1 WHERE `id`=? LIMIT 1;", time(), $id);
				// инициализируем ip
				$proxy=explode("|", $this->pool[(int)$id]);
				$this->id=$id;
				$this->ip=$proxy[0];
				$this->login=($proxy[1]!="") ? $proxy[1] : null;
				return true;
			}
			// если id нету в пуле
			else {
				$count++;
				if ($count==500) {
					file_put_contents(LOGS_PATH.'/error.txt', "Нет прокси в течении 33 минут. Парсер остановлен.", FILE_APPEND);
					echo 'Нет прокси в течении 33 минут. Парсер остановлен.';
					return false;
				}
				sleep(2);
			}
		}
	}
	
	/**
	 * Получение валидного id для пула
	 * 
	 * @return int ID адреса
	 */
	protected function select(){
		// тайм аут скрипта
		usleep(mt_rand(TIMEOUT_MIN, TIMEOUT_MAX) * 1000);
		// ограничение на поиск ip с последним вызовом менее чем указаного времени
		$timeout=time() - (mt_rand(SESSION_BREAK_START, SESSION_BREAK_END) / 1000);
		// поиск id
		$id=Registry::get('db')->selectCell("SELECT `id` FROM `ip` WHERE `time`<? AND `flag`='1' AND `count`<? ORDER BY rand() limit 1", $timeout, IP_MAX_QUERIES);
		return $id;
	}

	/**
	 * Установка ip в положение банна
	 */
	public function setbanned(){
		Registry::get('db')->query("UPDATE `ip` SET `flag`='0' WHERE `id`=?", $this->id);
		$this->genPool();
		return true;
	}
	
	/**
	 * Проверка на наличие валидных ip адресов в базе
	 * 
	 * @return bool
	 */
	public function check(){
		$count=Registry::get('db')->select("SELECT COUNT(*) FROM `ip` WHERE `flag`='1' AND `count`<?",IP_MAX_QUERIES);
		if ($count>0) return(TRUE);
		else return(FALSE);
	}
	
	/**
	 * Разбанн ip которые были забанены вчера
	 * 
	 * @return bool
	 */
	protected static function unban(){
		$date_time=strtotime(date("Y-m-d", time())." 00:00:00");
		$base_time=Registry::get('db')->selectCell("SELECT `time` FROM `ip` WHERE `flag`!=0 ORDER BY `time` DESC LIMIT 1;");
		if ($base_time<$date_time) {
			Registry::get('db')->query("UPDATE `ip` SET `count`='0'");
			return true;
		}
		else
			return false;
	}

}
