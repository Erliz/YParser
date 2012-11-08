<?php
/**
 * класс для работы с сессиями
 * @author Vladimir Kuptcov
 * @version 1.1
 */
class Session{
	private $name;
	private $SID;

	function __construct($name){
		$this->name = $name;
		@session_start($this->name);
		$this->SID = session_id();
	}
	
	function getSessionData(){
		return (isset($_SESSION[$this->name])) ? unserialize($_SESSION[$this->name]) : null;
	}
	
	function setSessionData($data){
		$_SESSION[$this->name] = serialize($data);
	}
	
	
	function unsetSessionData(){
		unset($_SESSION[$this->name]);
		session_unregister($this->name);
	}
	
	function getSID(){
		return $this->SID;
	}
	
	function getName(){
		return $this->name;
	}
}
?>