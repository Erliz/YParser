<?php
set_time_limit(3600);
require_once 'autoload.php';
Registry::set('db', Simple::createConnection());
Registry::get('db')->setErrorHandler('Error::db_error');
Face::constan();
file_put_contents('pages/'.getmypid()."_proxyF.pid", ' ');
$interface=new FaceAuto();
Registry::set('parser_id',0);
Registry::set('parse_id',0);


$stat=new Konkurent_stat();

// контроллер
if(isset($_GET['action']) && $_GET['action']=='get'){
	if(isset($_GET['from_d'])){
		$from=Array((int)$_GET['from_d'],(int)$_GET['from_m'],(int)$_GET['from_y']);
		$to=Array((int)$_GET['to_d'],(int)$_GET['to_m'],(int)$_GET['to_y']);
		$stat->get($from,$to);
	}
	else{
		$today=getdate();
		$html='
		<html>
			<body>
				<form action="" method="get">
					<input type="hidden" name="action" value="get" />
					<span>От:</span><select name="from_d">';
					for($i=1;$i<=31;$i++)$html.='<option value="'.$i.'">'.$i.'</option>';
					$html.='</select><select name="from_m">';
					for($i=1;$i<=12;$i++)$html.='<option value="'.$i.'">'.$i.'</option>';
					$html.='</select><select name="from_y">';
					for($i=2012;$i<=2012;$i++)$html.='<option value="'.$i.'">'.$i.'</option>';
					$html.='</select><br />
					
					<span>До:</span><select name="to_d">';
					$html.='<option value="'.$today['mday'].'">'.$today['mday'].'</option>';
					for($i=1;$i<=31;$i++)$html.='<option value="'.$i.'">'.$i.'</option>';
					$html.='</select><select name="to_m">';
					$html.='<option value="'.$today['mon'].'">'.$today['mon'].'</option>';
					for($i=1;$i<=12;$i++)$html.='<option value="'.$i.'">'.$i.'</option>';					
					$html.='</select><select name="to_y">';
					$html.='<option value="'.$today['year'].'">'.$today['year'].'</option>';
					for($i=2012;$i<=2012;$i++)$html.='<option value="'.$i.'">'.$i.'</option>';
					$html.='</select><br />
					<input type="submit" value="Выгрузить"/>
				</form>
			</body>
		</html>';
		echo $html;
	}
}
else $stat->add();

/**
 * Класс для скачивания,записи и формирования отчёта по кликам конкурентов
 * Основан на статистике liveinternet.ru
 */
class Konkurent_stat{
	
	/**
	 * Получение отчёта в формате .csv
	 * 
	 * @param array $from DD:MM:YYYY формировать отчёт с даты
	 * @param array $to DD:MM:YYYY по дату
	 */
	public function get($from,$to){
		$from=mktime(0,0,0,$from[1],$from[0],$from[2]);
		$to=mktime(0,0,0,$to[1],$to[0],$to[2]);
		if($from>$to){$i=$from;$from=$to;$to=$i;unset($i);} //change
		$sql=Registry::get('db')->query("SELECT `ss`.`title`,`sv`.`value`,`sv`.`time` FROM `stat_value` as `sv` LEFT JOIN `stat_shops` as `ss` ON `ss`.`id`=`sv`.`stat_shop_id` WHERE `sv`.`time` BETWEEN ? AND ? ORDER BY `sv`.`time` ASC",$from,$to);
		$data=array();
		$times=array();
		foreach ($sql as $row) {
			$times[]=$row['time'];
			$data[$row['title']][$row['time']]=$row['value'];
		}
		$times=array_unique($times);
		$fp='files/download/stat_shop.csv';
		if(file_exists($fp))unlink($fp);
		$handle=fopen($fp, 'a');
		$row=array('Shops');
		foreach ($times as $time) $row[]=date("d.m.Y",$time);
		fputcsv($handle, $row, ';');
		foreach ($data as $title => $day){
			$row=Array($title);
			foreach ($times as $time) $row[]=isset($day[$time])?$day[$time]:'N/A';
			fputcsv($handle, $row, ';');
		}  
		fclose($handle);
		$this->download($fp);
	}
	
	/**
	 * метод для скачивания файла
	 * 
	 * @param string $fp путь к файлу
	 */
	private function download($fp){
		$name=basename($fp);
		$size=filesize($fp);
		header('Pragma: public');
		header('Content-Description: File Transfer');
		header('Content-Type: application/force-download');
		header('Content-Disposition: attachment; filename='.$name);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Content-Length: '.$size);
		ob_clean();
		flush();
		readfile($fp);
		exit;
	}
	
	/**
	 * Добавление статистика по конкурентам в базу
	 */
	public function add(){
		$date=getdate();
		$time=mktime(0,0,0,$date['mon'],$date['mday'],$date['year']);
		foreach (Registry::get('db')->select("SELECT * FROM `stat_shops`;") as $row)  $list[$row['id']]=Array('title'=>$row['title'],'li'=>$row['li']);
		foreach ($list as $id => $shop) Registry::get('db')->query("REPLACE `stat_value` (`time`,`stat_shop_id`,`value`) VALUE (?,?,?);",$time,$id,$this->get_page(strtolower($shop['li'])));
	}
	
	/**
	 * Получение страницы и парс ее
	 * 
	 * @param string $url название магазина из базы
	 * @param string $encode кодировка страницы liveinternet.ru
	 * 
	 * @return int кол-во кликов
	 */
	private function get_page($url,$encode="UTF-8"){
		$pf=new PageFinder('real');
		$page=$pf->getpage('http://www.liveinternet.ru/?'.$url,false);
		preg_match('/\/.*?\/index\.html(:?\?page=[0-9]+\#|\#)'.str_replace('.', '\.', $url).'/',$page,$url_stat);
		$page=$pf->getpage('http://www.liveinternet.ru/'.$url_stat[0],false);
		$page=preg_replace('/[\r\n]+/', '', $page);		
		preg_match('/name="'.str_replace('.', '\.', $url).'".*?<td align="right" width="100">([0-9,]+)<\/td>/',$page,$stat);		
		$pf->curlclose();
		if($encode!=='UTF-8')$page=iconv($encode,'UTF-8',$page);
		return (int)str_replace(',', '', $stat[1]);
	}
}
unlink('pages/'.getmypid()."_proxyF.pid");
exit;
?>