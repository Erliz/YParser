<?php
class Charact extends Simple {
	protected $id;
	protected $name;
	protected $value;
	protected $data_id;
	protected $charact_id;

	private static $_charactName = array('id' => 'id', 'name' => 'name');
	private static $_charactValue = array('id' => 'id', 'charact_id'=>'charact_id', 'data_id' => 'data_id', 'value' => 'value');											
	
	public static function addNewData($params, $nam){
		$title = Registry::get('db')->selectCell('SELECT `query_id` FROM `resultPrice` WHERE `name`=? AND `user_id`=? AND `region_id`=? LIMIT 1', $nam,Registry::get('user_id'),Registry::get('region_id'));
		foreach ($params as $key=>$val) {
			$id = Registry::get('db')->selectCell('SELECT `id` FROM `charact_name` WHERE `name`=?', $key);
			if (sizeof($id)==0) {
				$params = array('id'=>'', 'name'=>$key);
				$id=Registry::get('db')->query('INSERT INTO `charact_name` SET ?a', $params);
			}
			$params = array('id' => '', 'charact_id' => $id, 'data_id'=>$title, 'value'=>$val);
			Registry::get('db')->query('INSERT INTO `charact_value` SET ?a', self::mappingFromDb($params, self::$_charactValue));
		}
		return $title;
	}
}
?>