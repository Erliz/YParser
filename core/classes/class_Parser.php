<?php
class Parser {
	public $xml=false;
	private $url;
	private $pageText;
	private $queryID;
	public $aborted=false;
	
	/**
	 * Рекурсивный парс партнерки страницы "управление ставками"
	 * Поиск по бренду
	 * 
	 * @param string $url ссылка на страницу
	 * @param int $queryID ID бренда
	 * @param object $pf обьект класса PageFinder
	 * @param string $ref ссылка перехода
	 */
	public function __construct($url, $queryID, $pf, $ref=null){
		$this->url = $url;
		$this->queryID = $queryID;
		if(isset($_GET['debug']) && $_GET['debug']=='true') echo $this->url.'<br />';
		$this->pageText=$pf->getpage($this->url,true,$ref);
		if($this->pageText==FALSE) {
			$this->aborted=true;
			return false;
		}
			if(isset($_GET['debug']) && $_GET['debug']=='true')var_dump($this->pageText);
		if(preg_match('/Ошибка поиска\. Попробуйте повторить через некоторое время\./', $this->pageText)) echo "##retring##";
		//находим список ссылок	
		$text = preg_replace('/[\r\n]+/', '', $this->pageText);
		preg_match_all('/<tr class="offer g-js"(.*?)<\/tr>/', $text,$tovars);
		$list=Array();
			if(isset($_GET['debug']) && $_GET['debug']=='true'){echo "<pre>";var_dump($tovars);echo "</pre>";}
		foreach ($tovars[0] as $row) {
			$adding=Array();
			preg_match('/<input type="hidden" value="(.*?)" name="offerName[\d]+">.*?modelid=([0-9\-]*)&amp;rids=.*?<span class="place_corner">(.*?)<\/span>.*?<a class="rate-card first b-pseudo-link">([0-9.]+)<\/a>.*?<a class="rate-card block b-pseudo-link">([0-9.]+)<\/a>/', $row, $data);
				if(isset($_GET['debug']) && $_GET['debug']=='true'){if(isset($data) && count($data)>0) echo "first match!<br/>";}
			//if(count($data)==0)preg_match('/<input type="hidden" value="(.*?)" name="offerName[\d]+">.*?modelid=([0-9\-]*)&amp;rids=.*?<span class="place_corner">(.*?)<\/span>.*?<i class="icon_no-recommend"/', $row, $data);
			if(count($data)==0){
				preg_match('/<input type="hidden" value="(.*?)" name="offerName[\d]+">.*?modelid=([0-9\-]*)&amp;rids=.*?<span class="place_corner">(.*?)<\/span>.*?<td class="d">(.*?)<\/td><td class="d">(.*?)<\/td>/', $row, $data);
				$data[4]=trim(strip_tags($data[4]));
				$data[5]=trim(strip_tags($data[5]));
			}
				if(isset($_GET['debug']) && $_GET['debug']=='true'){echo "<pre>";var_dump($data);echo "</pre>";exit;}
			if(count($data)>0){
				$adding = array('name' => strip_tags($data[1]),
							'maxPrice' => isset($data[4])?(preg_match('/[0-9]+/', $data[4])?$data[4]:10):0,
							'minPrice' => isset($data[5])?$data[5]:0.12,
							'midiPrice' => 0,
							'representCount' => preg_match('/[0-9]+/', trim($data[3]))?$data[3]:0,
							'yandex_id' => (preg_match('/[0-9]+/', $data[2]) && $data[2]!=='-1')?$data[2]:0);
				if(isset($_GET['debug']) && $_GET['debug']=='true'){echo "<pre>";var_dump($adding);echo "</pre>";}
				$prov = Registry::get('db')->select('SELECT `title` FROM `resultRate` WHERE `title`=? AND `shop_id`=? AND `platform_id`=?', strip_tags($adding['name']),Registry::get('user_id'),Registry::get('platform_id'));
				if (sizeof($prov)==0 && strip_tags($adding['name'])!="") YandexData::addNewData($adding);
			}			
		}
		//Определяем ссылку на след. страницу
		preg_match_all('/<a class="b-pager__next" href="(.*?)">.*/', $this->pageText, $nextPage);
		$nextPage = html_entity_decode(@$nextPage[1][0]);
		if ($nextPage) {
			sleep(rand(1,4));
			new Parser('http://partner.market.yandex.ru'.$nextPage, $queryID, $pf, $this->url);
		}
	}
	
	/**
	 * НЕ ИСПОЛЬЗУЕТСЯ!!!
	 * старый вид парса
	 */
	public function old($url, $queryID, $pf){
		$this->url = $url;
		$this->queryID = $queryID;
		
		$this->pageText=$pf->getpage($this->url);
		var_dump($this->pageText);
		if($this->pageText==FALSE) {
			$this->aborted=true;
			return false;
		}
		
		$cost=0;
		//находим список ссылок		
		$text =  str_replace("\r\n","",$this->pageText);
		$text =  str_replace("\n","",$text);
		$text =  str_replace("</td>","</td>\n",$text);
		$text =  str_replace("</tr>","</tr>\n",$text);		
		preg_match_all('/.*<td class="d">(.*?)<\/td>.*/', $text, $links);
		$links = $links[1];
		preg_match_all('/<td class="d name-cell"><a href=".*?">(.*?)<\/a>/', preg_replace('/[\r\n]+/','',$this->pageText), $name);
		//var_dump(preg_replace('/[\r\n]+/','',$this->pageText));
		//var_dump($name);
		//exit;
		$name = $name[1];
		$xml_er = false;
		if (Xml::$xml==false) {
			
			if (!file_exists("myd.xml")) {
				$f=null;
				file_put_contents("myd.xml",$f);
			}
			
			if (!Xml::$xml=$this->loadXml('myd.xml')) {
				$xml_er = true;
			}  	
		}
		$j=0;$_st = 0;
                if (isset($_GET['debug']) && $_GET['debug']='true') {
                    echo $this->pageText;
                    print_r($links);
                }
		$n = sizeof($links);
                $shag = 0;
		for ($i=0;$i<$n;$i++) {
			if (!isset($links[$i+1]) || strpos($links[$i+1], 'class="checkbox"><input type="hidden" name=')!==false) { 
                                $inc = (($shag+1) % 10 == 0) ? 3 :0; 
                                if (isset($links[$i+1]) && strpos($links[$i+1], 'class="checkbox"><input type="hidden" name=')===false) continue;  
				if (isset($links[$i-2-$inc])) {
					preg_match_all('/.*<span class="place_corner">(.*?)<\/span>.*/', $links[$i-2-$inc], $cc);
					preg_match_all('/modelid=([0-9]*)&/', $links[$i-2-$inc], $yid);
				}
				else{
					$cc=null;
					$yid=null;
				}
				if (isset($links[$i-1-$inc]))  preg_match_all('/.*<a class="rate-card first b-pseudo-link">(.*?)<\/a>.*/', $links[$i-1-$inc], $max); else $max=null;
				if (isset($links[$i-$inc])) preg_match_all('/.*<a class="rate-card block b-pseudo-link">(.*?)<\/a>.*/', $links[$i-$inc], $val); else $val=null;
				$tov = (!$xml_er) ? Xml::$xml->xpath('/tovars/tovar[@name="'.trim($name[$j]).'"]') : array(0=>array('cbid'=>'15','bid'=>'15'));
				$val = (isset($val[1][0])) ? $val[1][0]: 0.12;
				$st = (isset($tov[0]['cbid'])) ? (int)$tov[0]['cbid']/100 : 0.12;
				$cc = (isset($cc[1][0])) ? $cc[1][0]: 0;
				$max = (isset($max[1][0])) ? $max[1][0]: 0;
				$yid = (isset($yid[1][0])) ? $yid[1][0]: 0;
				$marg = 0;
				if (isset($_GET['debug']) && $_GET['debug']='true') {
                    echo "Шаг: ".$i."\n";
                    echo "Место: ".$cc."\n";
                    echo "Макс: ".$max."\n";
                    echo "Ставка:".$val."\n";
					echo "Yandex ID:".$yid."<br/>\n";
                }
				$val1 = ($st>$val && $st!=0) ? $st : $val; 
				if ($cc==0) {
					if ($val1<=0.2) $marg = 0.2;
					elseif ($val1<=0.5) $marg = 0.4;
					elseif ($val1<=1)   $marg = 0.6;
					elseif ($val1<=1.5) $marg = 0.7;
					else $marg = 0.75;
				} elseif ($cc==1) {
					if ($val1<=0.2) $marg = -0.1;
					elseif ($val1<=0.5) $marg = -0.15;
					elseif ($val1<=1)   $marg = -0.25;
					elseif ($val1<=1.5) $marg = -0.3;
					else $marg = -0.32;
				} elseif ($cc==2) {
					if ($val1<=0.2) $marg = -0.05;
					elseif ($val1<=0.5) $marg = -0.1;
					elseif ($val1<=1)   $marg = -0.15;
					elseif ($val1<=1.5) $marg = -0.2;
					else $marg = -0.21;
				}
				$val1 = ($cc==5) ? $val1+0.1 : $val1;
				if (abs($val1+$marg-$val)>1.1 && $cc==0) $val1=$vall;
				if ($max>0 && $max<=$val1) {
					if ($val1<=0.2) $marg = -0.05;
					elseif ($val1<=0.5) $marg = -0.1;
					elseif ($val1<=1)   $marg = -0.15;
					elseif ($val1<=1.5) $marg = -0.2;
					else $marg = -0.21;
					$val1 = $max+$marg;
				}
				$adding = array('name' => strip_tags($name[$j]),
						'minPrice' => $val,
						'maxPrice' => $max,
						'midiPrice' => $val1+$marg,
						'representCount' => $cc,
						'yandex_id' => $yid
				);
				$prov = Registry::get('db')->select('SELECT `title` FROM `resultRate` WHERE `title`=? AND `shop_id`=? AND `platform_id`=?', strip_tags($name[$j]),Registry::get('user_id'),Registry::get('platform_id'));
				if (sizeof($prov)==0 && strip_tags($name[$j])!="") YandexData::addNewData($adding);
				$j++;
			}
		}
		//Определяем ссылку на след. страницу
		preg_match_all('/<a class="b-pager__next" href="(.*?)">.*/', $this->pageText, $nextPage);
		$nextPage = html_entity_decode(@$nextPage[1][0]);
		if ($nextPage){ 
			new Parser('http://partner.market.yandex.ru/' . $nextPage, $queryID, $pf);
		}
	}
	
	/**
	 * НЕ ИСПОЛЬЗУЕТСЯ
	 * требуется для старого вида парса
	 */
	private static function loadXml($xmlFile){
		try {
			if (!file_exists($xmlFile)){
				throw new Exception();
			}
			return simplexml_load_file($xmlFile);
		} catch (Exception $e){
			echo 'Can\'t load xml file '.$xmlFile;
		}
	}
}
?>