<?php
/**
 * Class that generate Files for download in .csv Format
 */
class Download{
	// переменная которая возвращяется в ответе на инициализацию класса
	public $status;
	
	/**
	 * Контроллер запуска методов по условиям
	 * 
	 * @param string $dwl_val Значения определяющее вызываемый метод
	 */
	function __construct($dwl_val){
		// Выбор метода
		if ((int)$dwl_val>0) return $this->status=self::price_shop($dwl_val);
		elseif($dwl_val=="mvic") return $this->status=self::dif_mic('mvic');
		elseif($dwl_val=="moic") return $this->status=self::dif_mic('moic');
		elseif($dwl_val=="all") return $this->status=self::price_all();
		elseif ($dwl_val=="price") return $this->status=self::price_file();
		else{
			$query = Registry::get('db')->select('SELECT `id`,`name`,`minPrice`,`maxPrice`,`midiPrice`,`tovar_id`,`link`,`pos`,`mvic`,`moic`,`MPI`,`dif`,`five_shop`,`ten_shop`,`obsug`,`otziv` FROM `resultPrice` WHERE `user_id`=? AND `region_id`=?',$_SESSION['user'],$_SESSION['region']);			
			if (sizeof($query)>0){
				if ($dwl_val=="resultRate") {
					$rows=self::price_gener($query);
					$path='files/download/'.$_SESSION['region'].'_'.$_SESSION['user'].'_resultRate.csv';
				}
				elseif ($dwl_val=="Characteristics"){
					$rows=self::charct_gener($query);
					$path='files/download/'.$_SESSION['region'].'_'.$_SESSION['user'].'_charact.csv';
				}
				self::csv_write($rows, $path);
				return $this->status=$path;
			}
			else return "error: Таблица `resultPrice` пустая!";
		}
	}
	
	/**
	 * Раньше использовал митя, для своего excel
	 * 
	 * @param array $query выгрузка из базы
	 * 
	 * @return array сформированные и перекодированные строки
	 */
	private static function price_gener($query){
		$row[]=array('id','name','minPrice','maxPrice','midiPrice','tovar_id','link','pos','mvic','moic','MPI','dif','five_shop','ten_shop','obsug','otziv');
		foreach ($row[0] as $key=>$value) $row[0][$key]=self::decode($value);
		foreach ($query as $key=>$value) $row[]=$value;
		return $row;
	}

	/**
	 * Планировался отчёт для выгрузки цен по всем магазинам сразу
	 */
	private static function price_all(){
		echo "Temporary unavailable!<br/>";
	}
	
	/**
	 * Обработка запрета на "Вставание в первую цену, в цене/наличии"
	 * 
	 * @param string $top10 строка из базы с 10 магазинами
	 * @param array $our массив с названиями наших магазинов (чтоб под себя не вставали)
	 * @param int $position минимальная допустимая позиция в которую можно встать по цене
	 * 
	 * @return bool|int ошибка или цена от которой надо плясать
	 */
	private static function tabu($top10,$our,$position=3){
		// Парсим строку с магазинами
		$list=explode('+',$top10);
		// Убираем тупые знаки яндекса из кода
		foreach ($list as $ke=>$shops)	$list[$ke]=str_replace(chr(194).chr(160),"",$shops);
		// Сортируем по убыванию, на всякий случий (первое значение строки это цена)
		natsort($list);
		$list_conc=Array();
		$order=0;
		// парсим строки магазинов
		foreach($list as $k=>$shops){
			// Проверка на наличие значения
			if (strlen($shops)<9) continue;
			// парсим строку
			$shop_get=explode('|',$shops);
			// проверяем на название магазина (если наш то пропускаем)
			foreach ($our as $sh_name) if(stristr($shop_get[2],$sh_name)) continue(2);
			$list_conc[]=$shop_get;
			// Проверка на статус товара в магазине
			if($shop_get[1]=="Заказ")$order++;
		}
		// Не обрабатывать магазин для москвы если товар для заказе
		if($_SESSION['region']!="213" && $order<count($list_conc)){
			$list_conc2=Array();
			foreach ($list_conc as $shops)	if($shops[1]!="Заказ")$list_conc2[]=$shops;
			$list_conc=$list_conc2;
			unset($list_conc2);
		}
		// Формирования списка с ценами
		$prices=Array();
		foreach ($list_conc as $sh)$prices[]=$sh[0];
		// Удаление одинаковых цен
		$prices=array_unique($prices);
		// Сортировка ключей массива цен
		sort($prices);
		// Определение минимальной цены для товара на основе $position
		if(count($prices)>0){
			switch ($position) {
				case 2:	return $prices[0];break;
				case 4: return isset($prices[1])?$prices[1]:$prices[0];break;
				default: return $prices[0];break;
			}
		} 
		else return false;
	}

	/**
	 * FOR Electroburg
	 * Супер метод формирования цен для магазинов
	 * Внедренны условия на debug и точное указание товара (для проверки работы логики метода)
	 * 
	 * @param int $id ID магазина из `shops`
	 * @param bool $file для выдачи отчёта в виде файла
	 * 
	 * @return array
	 */
	public static function price_shop($id, $file=true){
		// Достаем отпаршенную базу текущего города
		$query = Registry::get('db')->select("SELECT `tovar_id`,`MPI`,`mvic`,`five_shop`,`ten_shop` FROM `resultPrice` WHERE `user_id`=? AND `region_id`=? AND `platform_id`=?",$_SESSION['user'],$_SESSION['region'],$_SESSION['platform']);
		if (count($query)==0) return 'Таблица пустая!<br/>';
		// Проверка для електробурга, всегда быть дешевле чем евробит.
		#Eurobit prices
			if ($id==1) {
				$main_price=Array();
				// Получение логина для поиска файла евробита
				$name=Registry::get('db')->selectCell("SELECT `sv_login` FROM `shop_id` WHERE `region`=? AND `shop_id`=2 LIMIT 1",$_SESSION['region']);
				// Поиск последнего файла с ценами евробита
				$files=glob('files/history/'.date("d_m_Y",time()).'*_'.$_SESSION['region'].'_price_'.$name.'.txt');
				// Выбираем самый последний файл
				$fp_price=array_pop($files);
				// Проверка на существование
				if(is_file($fp_price)){
					$cont=fopen($fp_price, "r");
					// парс файла 
					while ($row=fgetcsv($cont, 1000, "	"))
						if(preg_match('/[0-9]+/', $row[0]))
							// Занесение цен в массив
							$main_price[$row[0]]=$row[1];
					fclose($cont);
				}
			}
		#End forming array
		// получение id из shop_id
		$shop_id=Registry::get('db')->selectCell("SELECT `id` FROM `shop_id` WHERE `shop_id`=? AND `region`=?",$id,$_SESSION['region']);
		// получение данных магазина
		$shop=Registry::get('db')->selectRow("SELECT `id`,`name`,`price_5`,`price_10` FROM `shops` WHERE `id`=? LIMIT 1", $id);
		// получения списка названий, для всех наших магазинов
		$our=Registry::get('db')->selectCol("SELECT `name` FROM `shops`");
		// определение позиционирования магазина (блок,цена/наличие)
		if($shop['price_5']>0) {$top='five_shop';$position=$shop['price_5'];}
		elseif($shop['price_10']>0) {$top='ten_shop';$position=$shop['price_10'];}
		else return 'Для магазина '.$shop['name'].' не установлено позиционирование!<br/>';
		// Начало формирования файла
		$row[0]=array('Код','Цена');
		$num=1;
		// дополнительные условия (костыли)
		$siplatova=Array('1837','22208','22235','22263','22306','23523','25444','36574','58255','61364','62804','63029','64249','69556','82191','82366','82367','82431','82551','82757','82758');

		// ТОВАРЫ КОТОРЫМ МОЖНО ПОПАДАТЬ В ПЕРВЫЙ ЦЕННИК
		// --//-- ДО КОНЦА ИЮНЯ
		$vstroyka_first_place_30_06=Array('18504','25363','56649','25114','54961','18833','18413','18541','18407','54925','56118','18516','54904','56656','82147','4372','4798','6114','17777','18570','18579','18683','19242','19279','21910','44564','54883');
		// СПИСОК АЛТУХОВА ДЛЯ ВЫСТАВЛЕНИЯ ЦЕНЫ НА 2ю ПОЗИЦИЮ
		$vstroika_promote=Array('4798','6219','6221','6224','6225','9684','9689','10746','10747','10748','10754','10755','10756','10757','10759','12756','13095','17777','18386','18387','18388','18389','18390','18391','18392','18394','18395','18396','18397','18398','18399','18401','18402','18403','18404','18405','18407','18408','18409','18410','18411','18413','18414','18415','18416','18417','18418','18419','18420','18421','18422','18423','18424','18425','18427','18442','18445','18833','19058','19239','19240','19242','19243','19245','19246','19247','19249','19250','20642','20995','21775','21776','23334','23335','23336','23337','23338','23339','23870','23885','23957','23958','23962','23966','23967','23968','23971','23972','23975','23976','23977','23978','23979','23980','24696','24698','24700','25371','25372','25373','25374','25376','25423','30598','34818','37775','39597','39654','44976','45366','49943','51775','54113','54116','54117','54120','54564','54565','54566','54568','54569','54570','54571','54648','54653','54681','54682','54883','54901','54902','54904','54909','54910','54915','54925','54927','54928','54929','54930','54931','54932','54933','54943','54947','54948','54949','54960','54961','54963','54964','54965','56157','56158','56159','56166','56585','56586','56593','56613','56637','56642','56643','56645','56649','56650','56652','56653','56656','57625','58169','58667','58669','60516','61628','61632','62427','62428','63072','63073','63074','63081','63082','65774','66869','70428','70429','70430','70431','70432','70433','70434','70436','72371','81986','81995','82115','82116','82147','82185','82186','82187','82188','82190','82391','82441','82443','82444','82450','82676','82682','84780','85497');
		$vstroika=array_merge($vstroika_promote,$vstroyka_first_place_30_06);
		
		$first_place=array('60430','60431','60432','2275','3088','3509','4410','8972','5522','5815','6221','29946','54571','9989','10072','10681','54883','54904','54909','57624','63996','64000','69895','69896','69903','17427','18409','18411','18413','18416','18423','21418','35965','51775','54925','54929','54934','54943','55568','55570','55573','56592','56643','56646','56656','58169','61449','61465','63115','63821','63848','63851','63857','63869','63871','63872','63874','65774','69573','54681','54682','18833','30598','34818','37775','44976','45366','49943','51775','51776','54564','54565','54566','54568','54569','54570','54571','54648','54883','54886','54902','54904','54909','54910','54925','54928','54929','54930','54931','54932','54934','54943','54947','54948','54949','54960','54961','54964','54965','56586','56592','56593','56642','56643','56645','56646','56649','56652','56653','56656','57624','57625','63060','63071','63072','63073','63081','63082','63115','82147','82185','82188','18389','23968','25371','23967','18418','18390','18422','23339','23972','23962','18395','18420','23966','18421','19058','23971','23975','10747','18403','18404','23977','18391','18392','18415','19243','18423','23976','10746','18396','12756','25373','56157','56166','18425','19239','23334','60516','21776','9689','10754','10755','23336','23338','56158','24698','24700','18408','18411','23958','10757','18386','18388');
		$first_place=array_unique(array_merge($first_place,Array('9655','11674','61653','61655','61657','62700'),$vstroika,$siplatova));
		// ИГНОРИРОВАТЬ МАГАЗИН ТЕЛЕЛЮКС НА ЭТИХ ТОВАРАХ
		$telelux=Array('18511','18513','18512','18514','18529','18504','18576','18575','18574','18572','61061','61058','61045','70495','70492');
		// закончили с условиями
		// Обработка товаров
		foreach ($query as $key => $value) {
			// если ошибочный товар без кода или себестоимости пропускаем
			if ($value['tovar_id']==0 || $value['MPI']==0) continue;
			// защита от ситуации, когда МВИЦ меньше Себестоимости (Игнорирование МВИЦА)
			if($value['mvic']<$value['MPI']) $value['mvic']=0;
			// Заносим в строку id товара
			$row[$num][0]=$value['tovar_id'];
			// Если нету конкурентов
			if(strlen($value['five_shop'])==0 && strlen($value['ten_shop'])==0){
				if($value['MPI']<=5000)$row[$num][1]=$value['MPI']+($value['MPI']*0.2);
				else $row[$num][1]=$value['MPI']+($value['MPI']*0.1);
			}
			else{
					if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']){ echo "value $top: <pre>";var_dump(explode('+',$value[$top]));echo "</pre><br />";}
				#Stop for the third price!!!				
					if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "befor 3d price: ".$value['mvic'].', '.$value['MPI'].'<br />';
				// Проверка товара на допуск к первой цене и установка цены позиционирования 
				if(in_array($value['tovar_id'], $first_place)){
					switch ($shop['id']) {
						case '2': $position = $shop['price_5']+20;break;
						case '1': $position = $shop['price_10']+20;break;
						case '6': $position = $shop['price_10']+20;break;
						case '5': $position = $shop['price_10']+10;break;
					}
				}
				// Поиск минимальной цены
				else{
					// Установка позиционирование
					$position=$shop['price_5']>0?$shop['price_5']:$shop['price_10'];
					// Проверка рагиона
					if($_SESSION['region']=="213"){
						// Костыль для алтухова
						$price_position=in_array($value['tovar_id'], $vstroika_promote)?2:3;
						// Определение минимальной цены
						$tabu=self::tabu($value['ten_shop'],$our,$price_position);
							if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "tabu: ".$tabu.'<br />';
						// Указываем себестоимость ниже мвица на 50р.
						if($value['mvic']!=0)$value['MPI']=$value['mvic']-50;
						// Или используем минимальную допустимую цену в качестве себестоимости и от нее пляшем
						elseif($tabu!==false && $value['MPI']<$tabu)$value['MPI']=$tabu;
							if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "after tabu: ".$value['mvic'].', '.$value['MPI'].'<br />';
					}
				}
				#end
				$prices=Array();
				// Аналогично обработки в $this->tabu
				$list=explode('+',$value[$top]);
				foreach ($list as $ke=>$shops) $list[$ke]=str_replace(chr(194).chr(160),"",$shops);
				natsort($list);
				$list_conc=Array();
				$order=0;
				foreach($list as $k=>$shops){
					if (strlen($shops)<9) continue;
					$shop_get=explode('|',$shops);
					foreach ($our as $sh_name)
						if(stristr($shop_get[2],$sh_name)) continue(2);
					// Костыль для ТЕЛЕЛЮКСА
					if($_SESSION['region']=="213" && in_array($value['tovar_id'], $telelux) && $shop_get[2]=='Telelux' && $shop_id=='2') continue;
					$list_conc[]=$shop_get;
					if($shop_get[1]=="Заказ")$order++;
				}
				if($_SESSION['region']!="213" && $order<count($list_conc)){
					$list_conc2=Array();
					foreach ($list_conc as $shops)
						if($shops[1]!="Заказ")$list_conc2[]=$shops;
					$list_conc=$list_conc2;
					unset($list_conc2);
				}
				foreach($list_conc as $k=>$shops){
					// Проверка на вхождение в цену
					$price=round((($shops[0]-40)/($value['MPI']/100))-100,2);
					if($price<0) continue;
					else $prices[]=$shops[0];
				}
				// Попытка сделать защиту от демпинга. Неуспешная.
				if(count($prices)>0){
					$average=array_sum($prices)/count($prices);
					//$demping=$average-($average*0.03);
					$demping=0;
					foreach ($prices as $val){
						if($val>$demping){
							$row[$num][1]=$val;
							break;
						}
					}
				}
				else $row[$num][1]=0;
			}
				if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "befor math: ".$row[$num][1].'<br />';
			// Обработка если цену неудалось выставить
			if($row[$num][1]<=0) {
				//$row[$num][1]=Registry::get('db')->selectCell("SELECT `price` FROM `tovar_price` WHERE `opt_id`=? AND `shop_id`=? LIMIT 1",$row[$num][0],$shop_id);
				//if (is_null($row[$num][1])) $row[$num][1]=$value['MPI']+($value['MPI']*0.01);
				#3d price
				// Берем предыдущюю удачную цену
				$row[$num][1]=Registry::get('db')->selectCell("SELECT `price` FROM `tovar_price` WHERE `opt_id`=? AND `shop_id`=? LIMIT 1",$row[$num][0],$shop_id);
				// Иначе ставим МВИЦ если есть
				if ($value['mvic']!=0) $row[$num][1]=$value['mvic'];
				// И в конце концов выставляем, если ничего не подходит, выставляем +1% к себестоимости
				elseif (is_null($row[$num][1])) $row[$num][1]=$value['MPI']+($value['MPI']*0.01);
				# end 3d price
				//elseif (is_null($row[$num][1])) {$row[$num][1]=$value['MPI']+50; echo $value['tovar_id']'}
					if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo $row[$num][1].', '.$value['mvic'].', '.$value['MPI'].'<br />';
			}
			// Заносим удачно подобранную цену в базу, на будующее
			else Registry::get('db')->selectCell("REPLACE `tovar_price` (`shop_id`,`opt_id`,`price`) VALUES (?,?,?)",$shop_id,$row[$num][0] ,$row[$num][1]);
			// Округление цены
			$row[$num][1]=floor($row[$num][1]/10)*10;
			// Костыль для Алтухова на Аристон только для евробита!
			/*if(in_array($value['tovar_id'],$ariston) && $shop_id=='1')$row[$num][1]=$row[$num][1] - 50;
			elseif(in_array($value['tovar_id'],$first_30) && $shop_id=='1')$row[$num][1]=$row[$num][1] - 30;
			// Выставляем цену учитывая позиционирование магазинов
			else*/$row[$num][1]=$row[$num][1] - ($row[$num][1]<5000? $position/2 : $position);
				if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo $row[$num][1].'<br />';
			// Обработка если цена оказалась ниже себестоимости и МВИЦ не задан для евробита
			if($row[$num][1]<$value['MPI'] && $shop_id=='2' && $value['mvic']==0){
				$row[$num][1]=$value['MPI']+50;
				if((floor($row[$num][1]/10)*10)<=$value['MPI'])$row[$num][1]=ceil($row[$num][1]/10)*10;
				else $row[$num][1]=floor($row[$num][1]/10)*10;
			}
			// Обработка если цена оказалась ниже себестоимости и МВИЦ не задан для остальных магазинов
			elseif($row[$num][1]<$value['MPI'] && $value['mvic']==0){
				$row[$num][1]=$value['MPI']+($value['MPI']*0.01);
				if((floor($row[$num][1]/10)*10)<=$value['MPI'])$row[$num][1]=ceil($row[$num][1]/10)*10;
				else $row[$num][1]=floor($row[$num][1]/10)*10;
			}
			//Ставить мвиц если цена ниже мвица или она больше чем на 10% ($value['mvic']!=0 && ($row[$num][1]<$value['mvic'] || ($row[$num][1]-$value['mvic'])>($value['mvic']*0.1)))
			if($value['mvic']!=0 && $row[$num][1]<$value['mvic'])$row[$num][1]=$value['mvic']; 
			//Ценник МВИЦ не ставится если наценка > 10% и выгрузить в файл.
			if($value['mvic']!=0 && (($row[$num][1]-$value['mvic'])>($value['mvic']*0.1))) {
				file_put_contents('files/history/'.date("d_m_Y H:").'00_'.$_SESSION['region'].'_hand_price.txt', $value['tovar_id']."	".iconv("UTF-8", "CP1251", $shop['name'])."\r\n",FILE_APPEND);
				continue;
			}
			// Костыль для СТОХИТОВ он в бане у яндекса
			if($id=="7") $row[$num][1]=$row[$num][1]+($value['MPI']*0.2);
			// Финальное округление
			$row[$num][1]=round($row[$num][1]);
			//if(isset($main_price[$row[$num][0]]) && $row[$num][1]>=$main_price[$row[$num][0]]) echo $main_price[$row[$num][0]]." - ".$row[$num][1].":".($main_price[$row[$num][0]]-10)."<br />";
			// Проверка цены электробурга, чтоб была ниже евробитовской
			if($id==1 && isset($main_price[$row[$num][0]]) && $row[$num][1]>=$main_price[$row[$num][0]]) $row[$num][1]=$main_price[$row[$num][0]]-10;
				if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo $row[$num][1].', '.$value['mvic'].', '.$value['MPI'].'<br />';
			$num++;
		}
			if(isset($_GET['debug']) && $_GET['debug']==true) exit;
		// Выгрузка в файл
		if($file==true){
			$path='files/download/'.$shop['name'].'_'.$_SESSION['region'].'_'.$_SESSION['user'].'_price.csv';
			self::csv_write($row, $path);
			return $path;
		}
		elseif($file==false) return $row;
	}

	/**
     * FOR EUROBIT
	 * Супер метод формирования цен для магазинов
	 * Внедренны условия на debug и точное указание товара (для проверки работы логики метода)
	 *
	 * @param int $id ID магазина из `shops`
	 * @param bool $file для выдачи отчёта в виде файла
	 *
	 * @return array
	 */
	public static function price_shop_old($id, $file=true){
		// Достаем отпаршенную базу текущего города
		$query = Registry::get('db')->select("SELECT `tovar_id`,`MPI`,`mvic`,`five_shop`,`ten_shop` FROM `resultPrice` WHERE `user_id`=? AND `region_id`=? AND `platform_id`=?",$_SESSION['user'],$_SESSION['region'],$_SESSION['platform']);
		if (count($query)==0) return 'Таблица пустая!<br/>';
		// Проверка для електробурга, всегда быть дешевле чем евробит.
		#Eurobit prices
			if ($id==2) {
				$main_price=Array();
				// Получение логина для поиска файла евробита
				$name=Registry::get('db')->selectCell("SELECT `sv_login` FROM `shop_id` WHERE `region`=? AND `shop_id`=1 LIMIT 1",$_SESSION['region']);
				// Поиск последнего файла с ценами евробита
				$files=glob('files/history/'.date("d_m_Y",time()).'*_'.$_SESSION['region'].'_price_'.$name.'.txt');
				// Выбираем самый последний файл
				$fp_price=array_pop($files);
				// Проверка на существование
				if(is_file($fp_price)){
					$cont=fopen($fp_price, "r");
					// парс файла
					while ($row=fgetcsv($cont, 1000, "	"))
						if(preg_match('/[0-9]+/', $row[0]))
							// Занесение цен в массив
							$main_price[$row[0]]=$row[1];
					fclose($cont);
				}
			}
		#End forming array
		// получение id из shop_id
		$shop_id=Registry::get('db')->selectCell("SELECT `id` FROM `shop_id` WHERE `shop_id`=? AND `region`=?",$id,$_SESSION['region']);
		// получение данных магазина
		$shop=Registry::get('db')->selectRow("SELECT `id`,`name`,`price_5`,`price_10` FROM `shops` WHERE `id`=? LIMIT 1", $id);
		// получения списка названий, для всех наших магазинов
		$our=Registry::get('db')->selectCol("SELECT `name` FROM `shops`");
		// определение позиционирования магазина (блок,цена/наличие)
		if($shop['price_5']>0) {$top='five_shop';$position=$shop['price_5'];}
		elseif($shop['price_10']>0) {$top='ten_shop';$position=$shop['price_10'];}
		else return 'Для магазина '.$shop['name'].' не установлено позиционирование!<br/>';
		// Начало формирования файла
		$row[0]=array('Код','Цена');
		$num=1;
		// дополнительные условия (костыли)
		// ТОВАРЫ КОТОРЫМ МОЖНО ПОПАДАТЬ В ПЕРВЫЙ ЦЕННИК
		$vstroyka_first_place=Array('56314','56295','56318','56292','56284','56283','57901','56277','56319','56303','69897','56291','56305','56304');
		// --//-- ДО КОНЦА МАЯ
		$vstroyka_first_place_31_05=Array('6221','18411','18413','18423','18541','25616','26817','29946','32668','33699','54571','54883','54904','54928','54929','54948','56142','56656','58169','61465','63848','63996','64012','64316','69573','71405','82147');
		// СПИСОК АЛТУХОВА ДЛЯ ВЫСТАВЛЕНИЯ ЦЕНЫ НА 2ю ПОЗИЦИЮ
		$vstroika_promote=Array('4798','6219','6221','6224','6225','9684','9689','10746','10747','10748','10754','10755','10756','10757','10759','12756','13095','17777','18386','18387','18388','18389','18390','18391','18392','18394','18395','18396','18397','18398','18399','18401','18402','18403','18404','18405','18407','18408','18409','18410','18411','18413','18414','18415','18416','18417','18418','18419','18420','18421','18422','18423','18424','18425','18427','18442','18445','18833','19058','19239','19240','19242','19243','19245','19246','19247','19249','19250','20642','20995','21775','21776','23334','23335','23336','23337','23338','23339','23870','23885','23957','23958','23962','23966','23967','23968','23971','23972','23975','23976','23977','23978','23979','23980','24696','24698','24700','25371','25372','25373','25374','25376','25423','30598','34818','37775','39597','39654','44976','45366','49943','51775','54113','54116','54117','54120','54564','54565','54566','54568','54569','54570','54571','54648','54653','54681','54682','54883','54901','54902','54904','54909','54910','54915','54925','54927','54928','54929','54930','54931','54932','54933','54943','54947','54948','54949','54960','54961','54963','54964','54965','56157','56158','56159','56166','56585','56586','56593','56613','56637','56642','56643','56645','56649','56650','56652','56653','56656','57625','58169','58667','58669','60516','61628','61632','62427','62428','63072','63073','63074','63081','63082','65774','66869','70428','70429','70430','70431','70432','70433','70434','70436','72371','81986','81995','82115','82116','82147','82185','82186','82187','82188','82190','82391','82441','82443','82444','82450','82676','82682','84780','85497');
		$vstroika=array_merge($vstroyka_first_place,$vstroyka_first_place_31_05);

		$first_place=array('60430','60431','60432','2275','3088','3509','4410','8972','5522','5815','6221','29946','54571','9989','10072','10681','54883','54904','54909','57624','63996','64000','69895','69896','69903','17427','18409','18411','18413','18416','18423','21418','35965','51775','54925','54929','54934','54943','55568','55570','55573','56592','56643','56646','56656','58169','61449','61465','63115','63821','63848','63851','63857','63869','63871','63872','63874','65774','69573','54681','54682','18833','30598','34818','37775','44976','45366','49943','51775','51776','54564','54565','54566','54568','54569','54570','54571','54648','54883','54886','54902','54904','54909','54910','54925','54928','54929','54930','54931','54932','54934','54943','54947','54948','54949','54960','54961','54964','54965','56586','56592','56593','56642','56643','56645','56646','56649','56652','56653','56656','57624','57625','63060','63071','63072','63073','63081','63082','63115','82147','82185','82188','18389','23968','25371','23967','18418','18390','18422','23339','23972','23962','18395','18420','23966','18421','19058','23971','23975','10747','18403','18404','23977','18391','18392','18415','19243','18423','23976','10746','18396','12756','25373','56157','56166','18425','19239','23334','60516','21776','9689','10754','10755','23336','23338','56158','24698','24700','18408','18411','23958','10757','18386','18388');
		$first_place=array_unique(array_merge($first_place,Array('9655','11674','61653','61655','61657','62700'),$vstroika));
		// ИГНОРИРОВАТЬ МАГАЗИН ТЕЛЕЛЮКС НА ЭТИХ ТОВАРАХ
		$telelux=Array('18511','18513','18512','18514','18529','18504','18576','18575','18574','18572','61061','61058','61045','70495','70492');
		// закончили с условиями
		// Обработка товаров
		foreach ($query as $key => $value) {
			// если ошибочный товар без кода или себестоимости пропускаем
			if ($value['tovar_id']==0 || $value['MPI']==0) continue;
			// защита от ситуации, когда МВИЦ меньше Себестоимости (Игнорирование МВИЦА)
			if($value['mvic']<$value['MPI']) $value['mvic']=0;
			// Заносим в строку id товара
			$row[$num][0]=$value['tovar_id'];
			// Если нету конкурентов
			if(strlen($value['five_shop'])==0 && strlen($value['ten_shop'])==0){
				if($value['MPI']<=5000)$row[$num][1]=$value['MPI']+($value['MPI']*0.2);
				else $row[$num][1]=$value['MPI']+($value['MPI']*0.1);
			}
			else{
					if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']){ echo "value $top: <pre>";var_dump(explode('+',$value[$top]));echo "</pre><br />";}
				#Stop for the third price!!!
					if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "befor 3d price: ".$value['mvic'].', '.$value['MPI'].'<br />';
				// Проверка товара на допуск к первой цене и установка цены позиционирования
				if(in_array($value['tovar_id'], $first_place)){
					switch ($shop['id']) {
						case '1': $position = $shop['price_5']+20;break;
						case '2': $position = $shop['price_10']+20;break;
						case '6': $position = $shop['price_10']+20;break;
						case '5': $position = $shop['price_10']+10;break;
					}
				}
				// Поиск минимальной цены
				else{
					// Установка позиционирование
					$position=$shop['price_5']>0?$shop['price_5']:$shop['price_10'];
					// Проверка рагиона
					if($_SESSION['region']=="213"){
						// Костыль для алтухова
						$price_position=in_array($value['tovar_id'], $vstroika_promote)?2:3;
						// Определение минимальной цены
						$tabu=self::tabu($value['ten_shop'],$our,$price_position);
							if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "tabu: ".$tabu.'<br />';
						// Указываем себестоимость ниже мвица на 50р.
						if($value['mvic']!=0)$value['MPI']=$value['mvic']-50;
						// Или используем минимальную допустимую цену в качестве себестоимости и от нее пляшем
						elseif($tabu!==false && $value['MPI']<$tabu)$value['MPI']=$tabu;
							if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "after tabu: ".$value['mvic'].', '.$value['MPI'].'<br />';
					}
				}
				#end
				$prices=Array();
				// Аналогично обработки в $this->tabu
				$list=explode('+',$value[$top]);
				foreach ($list as $ke=>$shops) $list[$ke]=str_replace(chr(194).chr(160),"",$shops);
				natsort($list);
				$list_conc=Array();
				$order=0;
				foreach($list as $k=>$shops){
					if (strlen($shops)<9) continue;
					$shop_get=explode('|',$shops);
					foreach ($our as $sh_name)
						if(stristr($shop_get[2],$sh_name)) continue(2);
					// Костыль для ТЕЛЕЛЮКСА
					if($_SESSION['region']=="213" && in_array($value['tovar_id'], $telelux) && $shop_get[2]=='Telelux' && $shop_id=='1') continue;
					$list_conc[]=$shop_get;
					if($shop_get[1]=="Заказ")$order++;
				}
				if($_SESSION['region']!="213" && $order<count($list_conc)){
					$list_conc2=Array();
					foreach ($list_conc as $shops)
						if($shops[1]!="Заказ")$list_conc2[]=$shops;
					$list_conc=$list_conc2;
					unset($list_conc2);
				}
				foreach($list_conc as $k=>$shops){
					// Проверка на вхождение в цену
					$price=round((($shops[0]-40)/($value['MPI']/100))-100,2);
					if($price<0) continue;
					else $prices[]=$shops[0];
				}
				// Попытка сделать защиту от демпинга. Неуспешная.
				if(count($prices)>0){
					$average=array_sum($prices)/count($prices);
					//$demping=$average-($average*0.03);
					$demping=0;
					foreach ($prices as $val){
						if($val>$demping){
							$row[$num][1]=$val;
							break;
						}
					}
				}
				else $row[$num][1]=0;
			}
				if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo "befor math: ".$row[$num][1].'<br />';
			// Обработка если цену неудалось выставить
			if($row[$num][1]<=0) {
				//$row[$num][1]=Registry::get('db')->selectCell("SELECT `price` FROM `tovar_price` WHERE `opt_id`=? AND `shop_id`=? LIMIT 1",$row[$num][0],$shop_id);
				//if (is_null($row[$num][1])) $row[$num][1]=$value['MPI']+($value['MPI']*0.01);
				#3d price
				// Берем предыдущюю удачную цену
				$row[$num][1]=Registry::get('db')->selectCell("SELECT `price` FROM `tovar_price` WHERE `opt_id`=? AND `shop_id`=? LIMIT 1",$row[$num][0],$shop_id);
				// Иначе ставим МВИЦ если есть
				if ($value['mvic']!=0) $row[$num][1]=$value['mvic'];
				// И в конце концов выставляем, если ничего не подходит, выставляем +1% к себестоимости
				elseif (is_null($row[$num][1])) $row[$num][1]=$value['MPI']+($value['MPI']*0.01);
				# end 3d price
				//elseif (is_null($row[$num][1])) {$row[$num][1]=$value['MPI']+50; echo $value['tovar_id']'}
					if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo $row[$num][1].', '.$value['mvic'].', '.$value['MPI'].'<br />';
			}
			// Заносим удачно подобранную цену в базу, на будующее
			else Registry::get('db')->selectCell("REPLACE `tovar_price` (`shop_id`,`opt_id`,`price`) VALUES (?,?,?)",$shop_id,$row[$num][0] ,$row[$num][1]);
			// Округление цены
			$row[$num][1]=floor($row[$num][1]/10)*10;
			// Костыль для Алтухова на Аристон только для евробита!
			/*if(in_array($value['tovar_id'],$ariston) && $shop_id=='1')$row[$num][1]=$row[$num][1] - 50;
			elseif(in_array($value['tovar_id'],$first_30) && $shop_id=='1')$row[$num][1]=$row[$num][1] - 30;
			// Выставляем цену учитывая позиционирование магазинов
			else*/$row[$num][1]=$row[$num][1] - ($row[$num][1]<5000? $position/2 : $position);
				if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo $row[$num][1].'<br />';
			// Обработка если цена оказалась ниже себестоимости и МВИЦ не задан для евробита
			if($row[$num][1]<$value['MPI'] && $shop_id=='1' && $value['mvic']==0){
				$row[$num][1]=$value['MPI']+50;
				if((floor($row[$num][1]/10)*10)<=$value['MPI'])$row[$num][1]=ceil($row[$num][1]/10)*10;
				else $row[$num][1]=floor($row[$num][1]/10)*10;
			}
			// Обработка если цена оказалась ниже себестоимости и МВИЦ не задан для остальных магазинов
			elseif($row[$num][1]<$value['MPI'] && $value['mvic']==0){
				$row[$num][1]=$value['MPI']+($value['MPI']*0.01);
				if((floor($row[$num][1]/10)*10)<=$value['MPI'])$row[$num][1]=ceil($row[$num][1]/10)*10;
				else $row[$num][1]=floor($row[$num][1]/10)*10;
			}
			//Ставить мвиц если цена ниже мвица или она больше чем на 10% ($value['mvic']!=0 && ($row[$num][1]<$value['mvic'] || ($row[$num][1]-$value['mvic'])>($value['mvic']*0.1)))
			if($value['mvic']!=0 && $row[$num][1]<$value['mvic'])$row[$num][1]=$value['mvic'];
			//Ценник МВИЦ не ставится если наценка > 10% и выгрузить в файл.
			if($value['mvic']!=0 && (($row[$num][1]-$value['mvic'])>($value['mvic']*0.1))) {
				file_put_contents('files/history/'.date("d_m_Y H:").'00_'.$_SESSION['region'].'_hand_price.txt', $value['tovar_id']."	".iconv("UTF-8", "CP1251", $shop['name'])."\r\n",FILE_APPEND);
				continue;
			}
			// Костыль для СТОХИТОВ он в бане у яндекса
			if($id=="7") $row[$num][1]=$row[$num][1]+($value['MPI']*0.2);
			// Финальное округление
			$row[$num][1]=round($row[$num][1]);
			//if(isset($main_price[$row[$num][0]]) && $row[$num][1]>=$main_price[$row[$num][0]]) echo $main_price[$row[$num][0]]." - ".$row[$num][1].":".($main_price[$row[$num][0]]-10)."<br />";
			// Проверка цены электробурга, чтоб была ниже евробитовской
			if($id==2 && isset($main_price[$row[$num][0]]) && $row[$num][1]>=$main_price[$row[$num][0]]) $row[$num][1]=$main_price[$row[$num][0]]-10;
				if(isset($_GET['debug']) && $_GET['debug']==true && isset($_GET['id']) && $value['tovar_id']==$_GET['id']) echo $row[$num][1].', '.$value['mvic'].', '.$value['MPI'].'<br />';
			$num++;
		}
			if(isset($_GET['debug']) && $_GET['debug']==true) exit;
		// Выгрузка в файл
		if($file==true){
			$path='files/download/'.$shop['name'].'_'.$_SESSION['region'].'_'.$_SESSION['user'].'_price.csv';
			self::csv_write($row, $path);
			return $path;
		}
		elseif($file==false) return $row;
	}
	
	/**
	 * Генерация отчёта по ценам
	 * 
	 * @return string путь к файлу который сформирован
	 */
	public static function price_file($history=false){
		// путь к файлу
		$file_path='files/download/'.$_SESSION['region'].'_'.$_SESSION['user'].'_price.csv';
		// Данные для формирования отчёта
		$query = Registry::get('db')->select("SELECT `yp`.`tovar_id`,`yp`.`name`,`yp`.`mvic`,`yp`.`MPI`,`yp`.`five_shop`,`yp`.`ten_shop`,`yp`.`midiPrice`,`n`.`vid`,`n`.`tip`,`n`.`podtip`,`n`.`status` FROM `resultPrice` as `yp` LEFT JOIN `nomencl` as `n` ON `n`.`id`=`yp`.`tovar_id` WHERE `user_id`=? AND `region_id`=?",$_SESSION['user'],$_SESSION['region']);
		// Получение списка всех запущенных парсеров этого юзера (Можно запускать парсер не чистя таблицу)
		$parse_list=Registry::get('db')->selectCol("SELECT DISTINCT `parse_id` FROM `resultPrice` WHERE `user_id`=? AND `region_id`=?",$_SESSION['user'],$_SESSION['region']);
		// Получение списка ошибок по id зупещенных парсеров
		$error = Registry::get('db')->select("SELECT `title`,`url`,`n`.`vid`,`n`.`tip`,`n`.`podtip`,`n`.`status` FROM `log_errors` LEFT JOIN `nomencl` as `n` ON `n`.`id`=`log_errors`.`title` WHERE `value`='Not right TITLE!!!' AND `parse_id` IN (?a)",$parse_list);
		if (count($query)==0) return 'Таблица пустая!<br/>';
		// Очистка файла для записи
		if (file_exists($file_path)) unlink($file_path);
		$fp=fopen($file_path, "a");
		// Названия колонок
		$row=array('Код','Марка','Название','МВИЦ','МПИ','Позиция','Сортировка','Цена','Магазин','Доставка','Наличие','Средняя Цена','Вид','Тип','ПодТип','Статус');
		// Конвертация кодировки колонок для excel
		foreach($row as $key=>$val)$row[$key]=self::decode($val);
		fputcsv($fp, $row, ";");
		// Пробег по всем данным парсера
		foreach ($query as $key => $value) {
			// Выделяем бренд
			$brand=explode(' ',$value['name']);
			// Формирование строки на неспаршенные товары или товары без конкурентов
			if(strlen($value['five_shop'])==0 && strlen($value['ten_shop'])==0){
				$row=Array();
				// Инициализация данных парсера
				$row[0]=$value['tovar_id'];
				$row[1]=$brand[0];
				$row[2]=$value['name'];
				$row[4]=$value['mvic'];
				$row[5]=$value['MPI'];
				$row[6]=1;
				$row[7]=1;
				$row[8]="N/A";
				$row[9]="N/A";
				$row[10]=0;
				$row[11]="N/A";
				// Средня цена
				if(isset($_POST['midPrice']) && $_POST['midPrice']=="true") $row[12]="N/A";
				else $row[12]="";
				// Подгрузка типов
				$row[13]=$value['vid'];
				$row[14]=$value['tip'];
				$row[15]=$value['podtip'];
				$row[16]=$value['status'];
				// Конвертация кодировки строки для excel
				foreach($row as $key=>$val)$row[$key]=self::decode($val);
				fputcsv($fp, $row, ";");
			}
			// Парс строк с конкурентами 
			$five=explode('+',$value['five_shop']);
			$ten=explode('+',$value['ten_shop']);
			// Убираение тупого знака яндекса
			foreach ($five as $ke=>$shops) $five[$ke]=str_replace(chr(194).chr(160),"",$shops);
			foreach ($ten as $ke=>$shops) $ten[$ke]=str_replace(chr(194).chr(160),"",$shops);
			// Сортировка по возрастанию
			natsort($five);
			natsort($ten);
			
			$count=1;
			// Формирование строк с информацией по конкурентам, для блока
			foreach($five as $k=>$shops){
				if (strlen($shops)<9) continue;
				// Парс информации о конкуренте
				$shop=explode('|',$shops);
				$row=Array();
				// Код товара
				$row[0]=$value['tovar_id'];
				// Бренд
				$row[1]=$brand[0];
				// Название с маркета
				$row[2]=$value['name'];
				$row[4]=$value['mvic'];
				// Себестоимость
				$row[5]=$value['MPI'];
				// Позиция магазина
				$row[6]=1+$k;
				// Позиция по цене
				$row[7]=$count;
				// Цена конкурента
				$row[8]=$shop[0];
				// Название магазина конкурента
				$row[9]=$shop[2];
				// Цена доставки конкурента
				$row[10]=$shop[3];
				// Статус наличия конкурента
				$row[11]=$shop[1];
				// Средняя цена
				if(isset($_POST['midPrice']) && $_POST['midPrice']=="true") $row[12]=$value['midiPrice'];
				else $row[12]="";
				// Подгрузка типов товара
				$row[13]=$value['vid'];
				$row[14]=$value['tip'];
				$row[15]=$value['podtip'];
				$row[16]=$value['status'];
				// Счётчик позиции по цене
				$count++;
				// Кодировка для excel
				foreach($row as $key=>$val)$row[$key]=self::decode($val);
				fputcsv($fp, $row, ";");
			}
			$count=1;
			// Тоже самое что и выше, только для цены наличия (Формирование строк с информацией по конкурентам,)
			foreach($ten as $k=>$shops){
				if (strlen($shops)<9) continue;
				$shop=explode('|',$shops);
				$row=Array();
			 	$row[0]=$value['tovar_id'];
				$row[1]=$brand[0];
				$row[2]=$value['name'];
				$row[4]=$value['mvic'];
				$row[5]=$value['MPI'];
				$row[6]=1+$k;
			 	$row[6].='b';
				$row[7]=$count.'b';
				$row[8]=$shop[0];
				$row[9]=$shop[2];
				$row[10]=$shop[3];
				$row[11]=$shop[1];
				if(isset($_POST['midPrice']) && $_POST['midPrice']=="true") $row[12]=$value['midiPrice'];
				else $row[12]="";
				$row[13]=$value['vid'];
				$row[14]=$value['tip'];
				$row[15]=$value['podtip'];
				$row[16]=$value['status'];
				$count++;
				foreach($row as $key=>$val)$row[$key]=self::decode($val);
				fputcsv($fp, $row, ";");
			}
		}
		// Запись списка ошибок по названиям товаров при парсе
		if (count($error)!=0){
			// Пропуск 2х строк
			fputcsv($fp, Array(' '), ";");
			fputcsv($fp, Array(' '), ";");
			fputcsv($fp, Array(self::decode('Ошибки в Названиях!')), ";");
			// перебор ошибок и запись их построчно
			foreach($error as $key=>$value){
				$row=Array();
				$row[0]=$value['title'];
				$row[1]=$value['url'];
				$row=array_merge($row,Array('','','','','','','','','',''));
				$row[13]=$value['vid'];
				$row[14]=$value['tip'];
				$row[15]=$value['podtip'];
				$row[16]=$value['status'];
				// Конвертация для excel
				foreach($row as $key=>$val)$row[$key]=self::decode($val);
				fputcsv($fp, $row, ";");
			}
		}
		fclose($fp);
		if($history) copy($file_path, 'files/history/'.date("d_m_Y H:i",time()).'_'.$_SESSION['region'].'_'.$_SESSION['user'].'_price.csv');
		//self::csv_write($row, $path);
		return $file_path;
	}
	
	/**
	 * Генерация отчётного файла для МВИЦ и МОИЦ
	 * 
	 * @param string $mic название mvic или moic
	 * 
	 * @return string ссылка на сгенерированный файл
	 */
	public static function dif_mic($mic){
		// Получение данных из базы для обработки
		$tovars=Registry::get('db')->select("SELECT `yp`.`tovar_id`,`yp`.`name`,`yp`.`".$mic."`,`yp`.`five_shop`,`yp`.`ten_shop`,`n`.`status` FROM `resultPrice` as `yp` LEFT JOIN `nomencl` as `n` ON `n`.`id`=`yp`.`tovar_id` WHERE `yp`.`".$mic."`!=0 AND `user_id`=? AND `region_id`=?",$_SESSION['user'],$_SESSION['region']);
		if (count($tovars)==0) return 'Таблица пустая!<br/>';
		// Файл для записи
		$file_path='files/download/'.$_SESSION['region'].'_'.$_SESSION['user'].'_'.$mic.'.csv';;
		// Очистка файла
		if (file_exists($file_path)) unlink($file_path);
		$fp=fopen($file_path, "a");
		// Список наших магазинов
		$our=Registry::get('db')->selectCol("SELECT `name` FROM `shops` UNION SELECT `name` FROM `dealers`");
		// Список заголовков колонок
		$row=array('Код модели','КОД','Brand',strtoupper($mic),'Статус товара','N','Дилер','Магазин','Цена','Отклонение от '.strtoupper($mic),'Отклонение от '.strtoupper($mic).' %');
		foreach($row as $key=>$val)$row[$key]=self::decode($val);
		fputcsv($fp, $row, ";");
		
		foreach ($tovars as $key => $value) {
			// выделение бренда 
			$brand=explode(' ',$value['name']);
			if(strlen($value['five_shop'])==0 && strlen($value['ten_shop'])==0)continue;
			// Парс строк с конкурентами 
			$five=explode('+',$value['five_shop']);
			$ten=explode('+',$value['ten_shop']);
			// Убираение тупого знака яндекса
			foreach ($five as $ke=>$shops)	$five[$ke]=str_replace(chr(194).chr(160),"",$shops);
			foreach ($ten as $ke=>$shops)	$ten[$ke]=str_replace(chr(194).chr(160),"",$shops);
			// Сортировка по возрастанию
			natsort($five);
			natsort($ten);
			
			$count=1;
			// Обработка магазинов в блоке
			foreach($five as $k=>$shops){
				if (strlen($shops)<9) continue;
				$shop=explode('|',$shops);
				$row=Array();
				// Бренд
				$row[0]=$brand[0];
				// Код товара
				$row[1]=$value['tovar_id'];
				// Название товарв
				$row[2]=$value['name'];
				// МВИЦ или МОИЦ товара
				$row[3]=$value[$mic];
				// Статус товара
				$row[4]=$value['status'];
				// Позиция магазина
				$row[5]=$count;
				// Пометка о том что магазин наш
				foreach ($our as $m) {
					if(stristr($shop[2],$m)) {$row[6]="Наш";break;}
					else $row[6]=" ";
				}
				// Название Магазин
				$row[7]=$shop[2];
				// Цена магазина на товар
				$row[8]=$shop[0];
				// Отклонение от стандарта
				$row[9]=$shop[0]-$value[$mic];
				// Преобразование в число для excel
				$row[10]=str_replace(".", ",", (string)round($row[9]/$shop[0]*100,2));
				$count++;
				foreach($row as $key=>$val)$row[$key]=self::decode($val);
				fputcsv($fp, $row, ";");
			}
			$count=1;
			// Аналогично и для цены наличия
			foreach($ten as $k=>$shops){
				if (strlen($shops)<9) continue;
				$shop=explode('|',$shops);
				$row=Array();
			 	$row[0]=$brand[0];
				$row[1]=$value['tovar_id'];
				$row[2]=$value['name'];
				$row[3]=$value[$mic];
				$row[4]=$value['status'];
				$row[5]=$count;
				foreach ($our as $m) {
					if(stristr($shop[2],$m)) {$row[6]="Наш";break;}
					else $row[6]=" ";
				}
				$row[7]=$shop[2];
				$row[8]=$shop[0];
				$row[9]=$shop[0]-$value[$mic];
				$row[10]=str_replace(".", ",", (string)round($row[9]/$shop[0]*100,2));
				$count++;
				foreach($row as $key=>$val)$row[$key]=self::decode($val);
				fputcsv($fp, $row, ";");
			}
		}
		fclose($fp);
		return $file_path;
	}
	
	/**
	 * НЕ ИСПОЛЬЗУЕТСЯ!
	 * Генерация файла с характеристиками товаров из карточек маркета
	 * 
	 * @param array $query Масив с данными из базы сформированный в constructer
	 * 
	 * @return string ссылка на сгенерированный файл
	 */
	private static function charct_gener($query){
		$char=Registry::get('db')->select("SELECT `val`.`charact_id` AS `id`,`name`.`name` AS `name` FROM `charact_value` AS `val`,`charact_name` AS `name` WHERE `val`.`charact_id`=`name`.`id` GROUP BY `val`.`charact_id` ORDER BY `val`.`charact_id`;");
		$id_deep=Registry::get('db')->selectCell("SELECT `id` FROM `charact_name` WHERE `name`='Размеры (ШхВхГ)'");
		$id_length=Registry::get('db')->selectCell("SELECT `id` FROM `charact_name` WHERE `name`='Размеры (ШхВхД)'");
		$row=array('lvl'=>'Уровень','art'=>'Артикул','id_mark'=>'ID магазина','text_id'=>'TEXT_ID группы', 'null1'=>'','name'=>'Название','sell_eur'=>'Продажа евро','sell_rub'=>'Продажа рубли','buy_er'=>'Покупка евро','buy_rub'=>'Покупка рубли','exist'=>'Наличие','delivery'=>'Срок доставки','null2'=>'','null3'=>'','acces'=>'Аксессуары / Сопутствующие товары','near_price'=>'Ближайшие по цене','short_disc'=>'Короткое описание','disc'=>'Полное описание','brand'=>'Производитель','model'=>'Серийный номер','seo_title'=>'SEO_title','seo_keys'=>'SEO_keys','seo_desc'=>'SEO_descr','1_brand'=>'1_Производитель','2_id'=>'2_Артикул','3_model'=>'3_Модель');
		foreach ($char as $key=>$val){
		$row[$val['id']]=$val['id'].'_'.$val['name'];
		}
		$path='files/download/'.$_SESSION['region'].'_'.$_SESSION['user'].'_charact.csv';
		if (file_exists($path)) unlink($path);
		$fp=fopen($path, "a");
		foreach($row as $key=>$value)$row[$key]=self::decode($value);
		fputcsv($fp, $row, ";");
		$num=0;
		foreach($query as $key=>$value) {
			foreach($row as $key=>$val)$row[$key]=' ';
			$name=$value['name'];
			$row['art']= isset($articl)? $articl: $value['tovar_id'];
			$row['id_mark']='1';
			$row['text_id']=@$text_id;
			$row['name']= isset($name)? $name: $value['name'];
			$row['1_brand']=@$brand;
			$row['2_id']= isset($articl)? $articl: $value['tovar_id'];;
			$row['3_model']=iconv("UTF-8", "CP1251",@$good->model);
			$char_list_tovar=Registry::get('db')->select('SELECT `charact_id`,`value` FROM `charact_value` WHERE `data_id`=?', $value['id']);
			
			if (sizeof($char_list_tovar)>0){
				foreach ($char_list_tovar as $val)
					$row[$val['charact_id']]=str_replace(",", ";", $val['value']);
				foreach($row as $key=>$value)$row[$key]=self::decode($value);
				fputcsv($fp, $row, ";");
			}
		}
		fclose($fp);
	}
	
	/**
	 * Перекодирование строки из UTF-8 в CP1251
	 * Создан для укороченного написания и упрощения жизни (Менять кодировку выходного файла проще тут)
	 * 
	 * @param string $value строка для перекодировки
	 */
	public static function decode($value){
		$value=@iconv("UTF-8", "CP1251", $value);
		return $value;
	}
	
	/**
	 * Запись данных из массива в файл
	 * 
	 * @param array $rows массив с данными
	 * @param string $file_path путь к файлу для записи
	 */
	public static function csv_write($rows,$file_path){
		if (isset($rows)){
			if (file_exists($file_path)) unlink($file_path);
			$fp=fopen($file_path, "a");
			foreach ($rows as $value){
				foreach($value as $key=>$val)$value[$key]=self::decode($val);
				fputcsv($fp, $value, ";");
			}
			fclose($fp);
		}
	}
}
?>