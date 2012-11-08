<?php
/**
 * Класс для DBSimple для записи строки парса ставок
 */
class YandexData extends Simple {
	protected $yandex_id;
	protected $name;
	protected $minPrice;
	protected $maxPrice;
	protected $midiPrice;

	private static $_objectProperties = array(	'title' => 'name',
												'minPrice' => 'minPrice',
												'maxPrice' => 'maxPrice',
												'midiPrice' => 'midiPrice',
												'representCount' => 'representCount',
												'yandex_id'=>'yandex_id'
												);
	public static function addNewData($params){
		Registry::get('db')->query('INSERT INTO `resultRate` SET ?a , `shop_id`=?, `parse_id` =?, platform_id =?', self::mappingFromDb($params, self::$_objectProperties),Registry::get('user_id'),Registry::get('parse_id'),Registry::get('platform_id'));
	}
}
?>