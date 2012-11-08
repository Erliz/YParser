<?php
/**
 * Абстрактный класс IP для работы над списками Прокси
 */
abstract class Ip {
	// Массив с "ip:port|login:pass" ... да, знаю, формат идиотский
	protected $pool; 
	// Количество использованных IP в $this->pool
	protected $counter; 
	// Общее количество IP в $this->pool
	protected $count; 
	// Статус прокси (Отчёт о работоспособности)
	public $status;
	// Текущий id IP адресса
	public $id;	
	// Текущий IP адресс с портом
	public $ip;
	// Текущий логин
	public $login;
	// Название прокси (dynamic, static)
	public $name;
	
	/**
	 * Generate pool from list
	 */
	abstract protected function genPool();
	
	/**
	 * Set new proxy to $ip and $login
	 */
	abstract public function getproxy();
	
	/**
	 * Select single ip/login from pool
	 */
	abstract protected function select();
	
	/**
	 * Set current ip to ban
	 */
	abstract public function setbanned();
	
	/**
	 * Вывод всех ip адресов из базы со статическими ip
	 * 
	 * @return array List of all ip from base
	 */
	public static function getlist() {
		$result=Registry::get('db')->select("SELECT * FROM `ip`");
		return $result;
	}
	
	/**
	 * Логирование использования ip адреса в базу
	 * 
	 * @param string $url строка с адресом который запрашивали через текущий IP
	 * 
	 * @return bool true
	 */
	public function usage($url='') {
		Registry::get('db')->query("INSERT INTO `log_ip` (`parse_id`,`time`,`memory`,`ip`,`url`) VALUES (?,?,?,?,?)", Registry::get('parse_id'), time(), memory_get_usage(), $this->ip, $url);
		return true;
	}
}
?>