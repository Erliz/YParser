<?php
/**
 * класс отвечающий за парс страницы яндекса
 */
class ParserPrice {
	// входная url
	private $url;
	// переменная где храниться текущий текст страницы
	private $pageText;
	// id запросса
	private $queryID;
	// статус объекта
	public $aborted=false;
	
	/**
	 * Метод основного парса и контроллер
	 * 
	 * @param string $url адресс
	 */
	public function __construct($url, $queryID, $mvic, $moic, $MPI, $pf){
		// инициализация перменных
		$this->url = $url;
		$this->queryID = $queryID;
		// установка кук региона для яндекса
		$pf->setcook('yandex_gid='.Registry::get('region_id'));
		// запрос страницы
		$this->pageText=$pf->getpage($this->url);
		if(isset($_GET['debug']) && $_GET['debug']==true) {
			echo "URL: ".$this->url."<br/>";
			var_dump($this->pageText);
		}
		// обработка ошибок
		if($this->pageText==FALSE) {
			$this->aborted=true;
			return false;
		}
		
		if (substr_count($this->pageText, 'b-page-title_type_model')) {
			// получение всех данных с карточки товара
			self::getModelParams($this->pageText, $this->queryID, $mvic, $moic, $MPI, $this->url);
			// получение ссылки на список цен
			$price_url='';
			preg_match_all('/<a class="b-top5-offers__all" href="\/offers.xml\?modelid=(.*?)">Все магазины<\/a>/', $this->pageText, $price_url);
			if (isset($price_url[1][0])){
				// филтрация по наличию и сортировка по цене
				$price_url=$price_url[1][0].'&how=aprice&onstock=1';
				$price_text="";
				// получение страницы
				$price_text=$pf->getpage('http://market.yandex.ru/offers.xml?modelid='.htmlspecialchars_decode($price_url));
				if(isset($_GET['debug']) && $_GET['debug']==true) {
					echo 'URL: <a href="http://market.yandex.ru/offers.xml?modelid='.htmlspecialchars_decode($price_url).'">http://market.yandex.ru/offers.xml?modelid='.htmlspecialchars_decode($price_url)."</a><br/>";
					var_dump($price_text);
				}
				//	проверка на ошибки
				if($price_text==false) {echo "PageFinder return (false)!<br/>";}
				self::getModelPrice($price_text);
				
				// Метод для забора характеристик товара
				/*$charact_text='';
				$charact_text=$pf->getpage('http://market.yandex.ru/'.htmlspecialchars_decode(str_replace("offers.xml", "model-spec.xml", $price_url)));
				if(isset($_GET['debug']) && $_GET['debug']==true) {
					echo "URL: ".'http://market.yandex.ru/'.htmlspecialchars_decode(str_replace("offers.xml", "model-spec.xml", $price_url))."<br/>";
					var_dump($charact_text);
				}
				if($charact_text==false) {echo "PageFinder return (false)!<br/>";}
				self::getModelCharact($charact_text);*/
			}
		}
		// если не попали на карточку модели
		else {
			// запись ошибки
			if (count($cards)>0) Registry::get('db')->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",Registry::get('parse_id'),time(),$this->queryID,$this->url,'Not right TITLE!!!');
			// поиск карточки с полным совпадением названи
			preg_match_all('/(<div class="b-offers b-offers_type_guru b-offers_type_guru_mix"(.*?)<\/h3>)/',$this->pageText,$cards);
			foreach ($cards[0] as $value){
				preg_match('/(class="b-offers__name">(.*?)<\/a>)/',$value,$name);
				$name_get=strip_tags($name[2]);
				$name_base=str_replace(array("http://market.yandex.ru/search.xml?text=","&cvredirect=1","&onstock=1"),"",urldecode($this->url));
				if (strstr($name_get,$name_base)){
					preg_match('/(<a href="(.*?)">)/',$value,$url_card);
					$card=str_replace('&amp;', '&', $url_card[2]);
					break;
				}
			}
			// если нашлась карточка товара
			if (isset($card)) new ParserPrice('http://market.yandex.ru'.$card, $this->queryID, $mvic, $moic, $MPI, $pf);
			// иначе парсим список товаров
			else{
				$name = str_replace(array("http://market.yandex.ru/search.xml?text=","&cvredirect=1","&onstock=1"),"",urldecode($this->url));
				#five price by Price sort
				preg_match_all('/(<div class="b-offers" id="(.*?)<p class="b-offers__spec">)/',$this->pageText,$offers);
				$count=(count($offers[0])<5) ? count($offers[0]) : 5;
				for ($i=0;$i<$count;$i++){
					preg_match('/(<div class="b-offers__price">(.*?)<\/div>)/', $offers[0][$i], $shops);
					preg_match('/(<div class="b-offers__delivery">(.*?)<\/div>)/', $offers[0][$i], $shops_deliv);
					$five_price[]=self::getShopPrice($shops[0],$shops_deliv[0]);
				}
				#END
				#ten price by Price sort
				$price_text="";
				$this->pageText = $pf->getpage("http://market.yandex.ru/search.xml?text=".urlencode($name)."&onstock=1&how=aprice&np=1");
				if($this->pageText==false) echo "PageFinder return (false)!<br/>";			
				preg_match_all('/(<div class="b-offers" id="(.*?)<p class="b-offers__spec">)/',$this->pageText,$offers);
				$count =(count($offers[0])<10) ? count($offers[0]) : 5; 
				for ($i=0;$i<$count;$i++){
					preg_match('/(<div class="b-offers__price">(.*?)<\/div>)/', $offers[0][$i], $shops);
					preg_match('/(<div class="b-offers__delivery">(.*?)<\/div>)/', $offers[0][$i], $shops_deliv);
					$ten_price[]=self::getShopPrice($shops[0],$shops_deliv[0]);
				}
				#END
				// формирование массива и запись в базу
				$price_summ=Array();
				if (isset($five_price))	foreach ($five_price as $value) $price_summ[]=(int)str_replace(chr(194).chr(160), "", $value);
				if (isset($ten_price)) foreach ($ten_price as $value) $price_summ[]=(int)str_replace(chr(194).chr(160), "", $value);
				if (count($price_summ)>0){
					sort($price_summ);
					$max_price=$price_summ[0];
					rsort($price_summ);
					$min_price=$price_summ[0];
					$ava_price=round(array_sum($price_summ)/count($price_summ));
				}						
				$adding = array(				'user_id' => Registry::get('user_id'),
												'parse_id' => Registry::get('parse_id'),
												'platform_id' => Registry::get('platform_id'),
												'region_id' => Registry::get('region_id'),
												'name' => $name,
												'minPrice' => @$max_price,
												'maxPrice' => @$min_price,
												'midiPrice' => @$ava_price,
												//'pos' => '',
												'tovar_id' => $this->queryID,
												'link' => $this->url,
												'mvic' => $mvic,
												'moic' => $moic,
												'MPI' => $MPI,
												//'dif' => 0,
												'five_shop' => @implode("+",@$five_price),
												'ten_shop' => @implode("+",@$ten_price),
												//'obsug' => 0,
												//'otziv' => 0
								);
				$prov = Registry::get('db')->select('SELECT SQL_NO_CACHE `id` FROM `resultPrice` WHERE `tovar_id`=? AND `user_id`=? AND `region_id`=? LIMIT 1', $this->queryID, Registry::get('user_id'), Registry::get('region_id'));
				if (sizeof($prov)<=0) PriceData::addNewData($adding);
			}
		}
	}
	
	/**
	 * Получение цен со списка цен товара
	 */
	private function getModelPrice($text){
		$text=explode("Доставка из других регионов", $text);
		$text=$text[0];
		#Надо сделать Одну выборку для каждой карточки
		preg_match_all('/(<div class="b-offers__price">(.*?)<\/div>)/', $text, $shops);
		preg_match_all('/(<div class="b-offers__delivery">(.*?)<\/div>)/', $text, $shops_deliv);
		for ($i=0;$i<count($shops[0]);$i++) $shop_price[]=self::getShopPrice($shops[0][$i],@$shops_deliv[0][$i]);
		if (is_array(@$shop_price)){
			$adding=implode("+",$shop_price);
			Registry::get('db')->query("UPDATE `resultPrice` SET `ten_shop`=? WHERE `tovar_id`=? AND `user_id`=? AND `region_id`=? LIMIT 1", $adding, $this->queryID, Registry::get('user_id'), Registry::get('region_id'));
		}
	}
	
	/**
	 * Получение данных из предложения магазина
	 * 
	 * @param string $shops html текст с инфой о предложении магазином
	 * @param string $shops_deliv текс с доставкой
	 * 
	 * @return string $shop_price
	 */
	private function getShopPrice($shops,$shops_deliv){
		preg_match('/(<\/span><\/b><\/span>(.*?)<a target="_blank" class="shop-link black" )/', $shops, $stat_sh);
		preg_match('/(<span class="b-prices__num">(.*?)<\/span>)/', $shops, $price_sh);
		$stat_sh=strip_tags($stat_sh[0]);
		if(preg_match('/наличии/', $stat_sh)==1)$stat_sh="Наличие";
		elseif(preg_match('/заказ/', $stat_sh)==1)$stat_sh="Заказ";
		preg_match('/(<a target="_blank" class="shop-link black"(.*?)<\/a>)/', $shops, $name_sh);
		preg_match('/(<b>(.*?)<\/b>)/', $shops_deliv, $dost_sh);
		$shop_price=str_replace(chr(194).chr(160), "",strip_tags(@$price_sh[2])).'|'.$stat_sh.'|'.strip_tags($name_sh[0]).'|'.(int)@str_replace('руб.','',$dost_sh[2]);
		return $shop_price;
	}
	
	/**
	 * Получение характеристик товара с карточки
	 * 
	 * @param string $text страница с характеристиками товара
	 */
	private function getModelCharact($text){
		preg_match('/.*?<h1 class="b-page-title b-page-title_type_model">(.*?)<\/h1>.*/', $text, $name);
		$text2 = preg_replace('/[\r\n]+/','',$text);
		preg_match('/(<table class="b-properties"><tbody><tr>(.*?)<p class="b-modelspec__note">)/',$text2,$text2);
		preg_match_all('/(<th class="b-properties__label b-properties__label-title"><span>(.*?)<\/span>)/', $text2[1], $charactname);
		preg_match_all('/(<td class="b-properties__value">(.*?)<\/td>)/', $text2[1], $charactvalue);
		$fid = Charact::addNewData(array_combine($charactname[2], $charactvalue[2]),$name[1]);
	}
	
	/**
	 * Парс инфы с карточки модели
	 * 
	 * @param string $text страница с карточкой
	 * @param int $queryID ID запроса
	 * @param int $mvic МВИЦ
	 * @param int $moic МОИЦ
	 * @param int $MPI Себестоимость
	 * @param string $link url адрес
	 */
	private function getModelParams($text, $queryID, $mvic, $moic, $MPI, $link){
		// Имя с карточки товара
		preg_match('/.*?<h1 class="b-page-title b-page-title_type_model">(.*?)<\/h1>.*/', $text, $name);
		$nm = $name[1];
		$name = str_replace('новинка', '', strip_tags(@$name[1]));
		// проверка на "Новинка"
		$position = ($name!==$nm) ? 1:0;
		// получение yandex_id
		preg_match('/\/offers.xml\?modelid=[0-9]*&amp;hid=[0-9]*&amp;hyperid=([0-9]*)&amp;grhow=shop/', $text,$yandex_id);
		// получение бренда
		$brand=explode(' ',$name);
		// запись в товары.
		//if(isset($yandex_id[1]) && Registry::get('user_text')!==null)Registry::get('db')->query("INSERT INTO `dataTovar` (`opt_id`,`user_text`,`yandex_id`,`brand`,`model`) VALUES (?,?,?,?,?)",$queryID,Registry::get('user_text'),$yandex_id[1],array_shift($brand),join(" ",$brand));
		// получение ссылки на картинку
		preg_match_all('/ href="http:\/\/mdata\.yandex\.net\/i\?path=(.*?)\.jpg/', $text, $img);
		// очистка страницы
		$text2 = preg_replace('/[\r\n]+/','',$text);
		// парс и формирование записи в базу
		preg_match_all('/<b class="b-prices__i"><span class="b-prices__num">(.*?)<\/span>/', $text, $pr);
		preg_match_all('/<i class="b-form-button__left"><\/i><span class="b-form-button__content"><span class="b-form-button__text">(.*?)<\/span>/', $text, $nl);
		$shop_price = array();
		preg_match_all('/<a class="shop-link b-top5-offers__shop-link" target="_blank" (.*?)<\/a>/', $text, $shopname);
		preg_match_all('/<td class="b-top5-offers__list__delivery">(.*?)<\/td>/', $text, $shopdeliv);
		$i=0;
		foreach ($pr[0] as $k=>$val) {
			if ($i==5)break;
			$shop_name=strip_tags(@$shopname[0][$k]);
			$shop_pr=strip_tags(str_replace(chr(194).chr(160), "", $val));
			$shop_stat=strip_tags($nl[0][$k]);
			if(isset($shopdeliv[1][$k])){
				if(strpos($shopdeliv[1][$k], "из")===0) continue;
				$shop_deliv=(int)str_replace(chr(194).chr(160), "", str_replace('руб.', '', $shopdeliv[1][$k]));
			}
			else $shop_deliv=0;
			if ($shop_stat=="" || $shop_name=="") break;
			if ($shop_stat=="Купить")$shop_stat="Наличие";
			// пропускаем магазины со строкой "Заказать", если это москва
			elseif ($shop_stat=="Заказать")	if(Registry::get('region_id')!="213")continue;
			// конкатенация данных
			$shop_price[] = $shop_pr.'|'.$shop_stat.'|'.$shop_name.'|'.$shop_deliv;
			$i++;
		}
		unset($i);
		preg_match_all('/<span class="b-switcher__cnt">(.*?)<\/span>/', $text, $re);
		$reiting = array();
		if(!isset($re[1][4])){
			$reiting['otziv'] = $re[1][2];
			$reiting['obsug'] = $re[1][3];
		}
		else{
			$reiting['otziv'] = $re[1][3];
			$reiting['obsug'] = $re[1][4];
		}
		preg_match_all('/<td colspan="4" class="defText">(.*?)<\/td>/', $text, $textA);
		preg_match_all('/<span class="b-switcher__cnt">(.*?)<\/span><\/span><\/li>/', $text, $re2);
		$reiting['count'] = $re2[1][0];
		$img = "http://mdata.yandex.net/i?path=".@$img[1][0].".jpg";
		if (strpos($text, 'Средняя цена')!==false){
			$start = strpos($text, 'Средняя цена');
			if ($start === false){
				$start = strpos($text, 'Цена');
			}
			$text1 = substr($text,$start,strpos($text, '<p class="b-rating__with_text">', $start) - $start);
			$text1 = str_replace(' ', '', strip_tags($text1));
			$text1 = str_replace(chr(160), '', strip_tags($text1));
			preg_match_all('/[\D\.]*?(\d{1,})[\D\.]*?/', $text1, $params);
			$sdkol = strpos($text, 'Все цены</a>');
			$kol = substr($text, $sdkol+19, 5);
			$kol = preg_replace('/[^\d]/','',$kol);	
			$new = 0;
		}
		else {
			if (strpos($text, 'Скоро в продаже')!==false && $position==1) $new = 1; elseif (strpos($text, 'Нет в продаже')!==false) $new = 2;
			$params = array(1 => array(0=>0,1=>0));
			$kol = 0;
		}
		if (is_array(@$params[1]) && ($countP = count($params[1]))){
			switch ($countP){
				case 2: $param = array('minPrice' => $params[1][0].$params[1][1],
										'maxPrice' => $params[1][0].$params[1][1],
										'midiPrice' => $params[1][0].$params[1][1]
										);break;
				case 1: $param = array('minPrice' => $params[1][0],
										'maxPrice' => $params[1][0],
										'midiPrice' => $params[1][0]
										);break;
				case 6: $param = array('minPrice' => $params[1][2].$params[1][3],
										'maxPrice' => $params[1][4].$params[1][5],
										'midiPrice' => $params[1][0].$params[1][1]
										);break;
				case 5: $param = array(	'minPrice' => $params[1][2],
										'maxPrice' => $params[1][3].$params[1][4],
										'midiPrice' => $params[1][0].$params[1][1]
										);break;
				case 4: $param = array(	'minPrice' => $params[1][1],
										'maxPrice' => $params[1][2].$params[1][3],
										'midiPrice' => $params[1][0]
										);break;
				case 3: $param = array(	'minPrice' => $params[1][1],
										'maxPrice' => $params[1][2],
										'midiPrice' => $params[1][0]
										);break;
			}
			$adding = array('user_id' => Registry::get('user_id'),
							'parse_id' => Registry::get('parse_id'),
							'region_id' => Registry::get('region_id'),
							'platform_id' => Registry::get('platform_id'),
							'name' => $name,
							//'pos' => $img,
							'tovar_id' => $queryID,
							'link' => $link,
							//'dif' => $new,
							'five_shop' => implode("+",$shop_price),
							'moic' => $moic,
							'mvic' => $mvic,
							'MPI' => $MPI,
							//'obsug' => $reiting['obsug'],
							//'otziv' => $reiting['otziv'],
						);
			$adding=array_merge($adding,$param);
			$prov = Registry::get('db')->selectCell('SELECT COUNT(*) FROM `resultPrice` WHERE `tovar_id`=? AND `user_id`=? AND `region_id`=? LIMIT 1', $this->queryID, Registry::get('user_id'), Registry::get('region_id'));
			if ($prov==0) PriceData::addNewData($adding);
		}
	}
}
?>