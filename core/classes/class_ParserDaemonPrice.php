<?php
/**
 * НЕ ИСПОЛЬЗУЕТСЯ (внедрён в ParserDaemon)
 */
class ParserDaemonPrice extends ParserDaemon{
	
	function __construct($parse_id){
		$this->parse_id=$parse_id;
		$data=Registry::get('db')->selectRow("SELECT `user_id`,`shops_id`,`file` FROM `log_parse` WHERE `id`=?",$this->parse_id);
		$this->url="&type=price";
		$this->starting($data);
	}
	
	protected function task_set($list){
		$count=ceil(sizeof($list)/$this->numbers);
		for ($i=0;$i<$this->numbers;$i++){
			$this->childs_id[$i]=Registry::get('db')->query("INSERT INTO `log_pid` (`parse_id`) VALUES (?)",$this->parse_id);			
			for($y=0;$y<$count;$y++){
				$num=$y+($count*$i);				
				if(isset($list[$num][0]) && $list[$num][0]!=0 && count($list[$num]>1)){
					$MPI=isset($list[$num][2])? round(str_replace(",", ".", str_replace(" ","",$list[$num][2])),2) : 0;
					$mvic=isset($list[$num][3])? round(str_replace(",", ".", str_replace(" ","",$list[$num][3])),2) : 0;
					$moic=isset($list[$num][4])? round(str_replace(",", ".", str_replace(" ","",$list[$num][4])),2) : 0;
					$this->task_id[$i][]=Registry::get('db')->query("INSERT INTO `queryPrice` (`parse_id`,`pid_id`,`tovar_id`,`title`,`mvic`,`moic`,`MPI`) VALUES (?,?,?,?,?,?,?)",$this->parse_id,$this->childs_id[$i],trim((int)$list[$num][0]),trim($list[$num][1]),$mvic,$moic,$MPI);
				}
				elseif (isset($list[$num][0])) echo $list[$num][0].": Недостаточно колонок для парсинга! <br/>";
			}
			if(isset($this->childs_id[$i])) Registry::get('db')->query("UPDATE `log_pid` SET `rows_plan`=? WHERE `id`=?",count(@$this->task_id[$i]),$this->childs_id[$i]);
		}
		if (count($this->task_id)==count($list)) return true;
		else return false;
	}
}
?>