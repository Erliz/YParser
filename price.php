<?php
function sv_tovar($file_auto,$region){
	$interface=new FaceAuto();
	$sv_reg=Registry::get('db')->selectCell("SELECT `sv_reg` FROM `regions` WHERE `id`=?",$region);
	$conn = mssql_connect ('SV', 'WebClient', 'sif@#69Wx!') 
	      or die ("Can't connect to Microsoft SQL Server");
	mssql_select_db('uchet', $conn) or die ("Can't select databes");
	$result = @mssql_query("EXEC [dbo].[cllc_GetTovarPriceByRegion] @region=".$sv_reg);
	if(mssql_get_last_message()!=="Changed database context to 'uchet'." && !strpos(mssql_get_last_message(), "deadlock")) 

{echo "</br><b>MSSQL ERROR:</b>".mssql_get_last_message();exit;}
	while (strpos(mssql_get_last_message(), "deadlock")) {
		echo mssql_get_last_message();
		sleep(300);
		$result = @mssql_query("EXEC [dbo].[cllc_GetTovarPriceByRegion] @region=".$sv_reg);
	}
	$proof=$interface->tovarlist_price();
	$union=$interface->unionlist();
	$exlusive=$interface->getAscArray('files/autoprice_'.$region.'.csv');
	
	Registry::get('db')->query("DELETE FROM `dev_parser`.`dataTovar_trash` WHERE `id` IN (?a)", $proof);
	//if(file_exists($file_auto))unlink($file_auto); 
	if(is_bool($result)) exit;
	for ($i = 0; $i < mssql_num_rows($result); ++$i){
		$line = mssql_fetch_assoc($result);
		$tovars[$line['code']]=Array((int)$line['count'],(int)ceil($line['mpBp']),(int)ceil($line['mvic']),(int)ceil

($line['moic']));
	}
	$result=Array();
	//$handle=fopen($file_auto, 'a');
	foreach ($tovars as $key => $value) {
		if(in_array($key, $proof)) {
			$price=$value[1];
			$mvic=$value[2];
			$moic=$value[3];
			$union_id=array_search($key,$union[0]);
			if($union_id!==false) {
				$close=true;
				if(isset($tovars[$union[1][$union_id]][1])){
					$price+=$tovars[$union[1][$union_id]][1];
					$mvic+=@$tovars[$union[1][$union_id]][2];
					$moic+=@$tovars[$union[1][$union_id]][3];
				}
				else{
					unset($union_id);
					continue;
				}
			}
			if(isset($exlusive[$key]) AND $price<$exlusive[$key][1]) $price=$exlusive[$key][1];
			//fputcsv($handle, Array($key,'',ceil($price),ceil($mvic),ceil($moic)),';');
			//if($mvic>0 OR $moic>0) $result[$key]=Array(ceil($price),ceil($mvic),ceil($moic));
			$result[$key]=Array(ceil($price),ceil($mvic),ceil($moic));
			unset($union_id);
		}
		elseif (in_array($key, $union[1]))continue;
		else Registry::get('db')->query("REPLACE INTO `dev_parser`.`dataTovar_trash` (`id`) VALUE (?)",$key);
	}
	//fclose($handle);
	//return true;
	return $result;
}
function dif_mic($uid,$reg,$mic){
	$tovars=Registry::get('db')->select("SELECT `yp`.`query_id`,`yp`.`name`,`yp`.`".

$mic."`,`yp`.`five_shop`,`yp`.`ten_shop`,`n`.`status` FROM `pricePrice` as `yp` LEFT JOIN `nomencl` as `n` ON 

`n`.`id`=`yp`.`query_id` WHERE `yp`.`".$mic."`!=0 AND `user_id`=? AND `shops_id`=?",$uid,$reg);
	if (count($tovars)==0) return 'Таблица пустая!<br/>';
	$file_path='files/history/'.date("d_m_Y H:i",time()).'_'.$reg.'_'.$uid.'_priceru_'.$mic.'.csv';
	if (file_exists($file_path)) unlink($file_path);
	$fp=fopen($file_path, "a");
	$our=Registry::get('db')->selectCol("SELECT `name` FROM `shops` UNION SELECT `name` FROM `dealers`");
	$row=array('Код модели','КОД','Brand',strtoupper($mic),'Статус товара','N','Дилер','Магазин','Цена','Отклонение от 

'.strtoupper($mic),'Отклонение от '.strtoupper($mic).' %');
	foreach($row as $key=>$val)$row[$key]=Download::decode($val);
	fputcsv($fp, $row, ";");
	
	foreach ($tovars as $key => $value) {
		$brand=explode(' ',$value['name']);
		if(strlen($value['five_shop'])==0 && strlen($value['ten_shop'])==0) continue;
		//$five=explode('+',$value['five_shop']);
		$ten=explode('+',$value['ten_shop']);
		//foreach ($five as $ke=>$shops)	$five[$ke]=str_replace(chr(194).chr(160),"",$shops);
		foreach ($ten as $ke=>$shops)	$ten[$ke]=str_replace(chr(194).chr(160),"",$shops);
		
		//natsort($five);
		natsort($ten);
		
		/*$count=1;
		foreach($five as $k=>$shops){
			if (strlen($shops)<9) continue;
			$shop=explode('|',$shops);
			$row=Array();
			$row[0]=$brand[0];
			$row[1]=$value['query_id'];
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
			foreach($row as $key=>$val)$row[$key]=Download::decode($val);
			fputcsv($fp, $row, ";");
		}*/
		$count=1;
		foreach($ten as $k=>$shops){
			if (strlen($shops)<9 || $count==11) continue;
			$shop=explode('|',$shops);
			if(!preg_match('/[a-z0-9-_]*\.[a-z]{2,3}/', $shop[2]))continue;
			$row=Array();
		 	$row[0]=$brand[0];
			$row[1]=$value['query_id'];
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
			foreach($row as $key=>$val)$row[$key]=Download::decode($val);
			fputcsv($fp, $row, ";");
		}
	}
	fclose($fp);
	return $file_path;
}
function price_file($uid,$reg){
	$file_path='files/history/'.date("d_m_Y H:i",time()).'_'.$reg.'_'.$uid.'_priceru_price.csv';
	$query = Registry::get('db')->select("SELECT 

`yp`.`query_id`,`yp`.`name`,`yp`.`mvic`,`yp`.`MPI`,`yp`.`five_shop`,`yp`.`ten_shop`,`yp`.`midiPrice`,`n`.`vid`,`n`.`tip`,`n`.`podt

ip`,`n`.`status` FROM `pricePrice` as `yp` LEFT JOIN `nomencl` as `n` ON `n`.`id`=`yp`.`query_id` WHERE `user_id`=? AND 

`shops_id`=?",$uid,$reg);
	if (count($query)==0) return 'Таблица пустая!<br/>';
	if (file_exists($file_path)) unlink($file_path);
	$fp=fopen($file_path, "a");
	$row=array('Код','Марка','Название','МВИЦ','МПИ','Позиция','Сортировка','Цена','Магазин','Доставка','Статус','Средняя 

Цена','Вид','Тип','ПодТип','Статус');
	foreach($row as $key=>$val)$row[$key]=Download::decode($val);
	fputcsv($fp, $row, ";");
	foreach ($query as $key => $value) {
		$brand=explode(' ',$value['name']);
		if(strlen($value['five_shop'])==0 && strlen($value['ten_shop'])==0){
			$row=Array();
			$row[0]=$value['query_id'];
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
			if(isset($_POST['midPrice']) && $_POST['midPrice']=="true") $row[12]="N/A";
			else $row[12]="";
			$row[13]=$value['vid'];
			$row[14]=$value['tip'];
			$row[15]=$value['podtip'];
			$row[16]=$value['status'];
			foreach($row as $key=>$val)$row[$key]=Download::decode($val);
			fputcsv($fp, $row, ";");
		}
		
		$five=explode('+',$value['five_shop']);
		$ten=explode('+',$value['ten_shop']);
		foreach ($five as $ke=>$shops)	$five[$ke]=str_replace(chr(194).chr(160),"",$shops);
		foreach ($ten as $ke=>$shops)	$ten[$ke]=str_replace(chr(194).chr(160),"",$shops);
		
		natsort($five);
		natsort($ten);
		
		$count=1;
		foreach($five as $k=>$shops){
			if (strlen($shops)<9) continue;
			$shop=explode('|',$shops);
			if(!preg_match('/[a-z0-9-_]*\.[a-z]{2,3}/', $shop[2]))continue;
			$row=Array();
			$row[0]=$value['query_id'];
			$row[1]=$brand[0];
			$row[2]=$value['name'];
			$row[4]=$value['mvic'];
			$row[5]=$value['MPI'];
			$row[6]=1+$k;
			$row[7]=$count;
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
			foreach($row as $key=>$val)$row[$key]=Download::decode($val);
			fputcsv($fp, $row, ";");
		}
		$count=1;
		foreach($ten as $k=>$shops){
			if (strlen($shops)<9) continue;
			$shop=explode('|',$shops);
			if(!preg_match('/[a-z0-9-_]*\.[a-z]{2,3}/', $shop[2]))continue;
			$row=Array();
		 	$row[0]=$value['query_id'];
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
			foreach($row as $key=>$val)$row[$key]=Download::decode($val);
			fputcsv($fp, $row, ";");
		}
	}
	fclose($fp);
	return $file_path;
}

session_name('parse');
session_start();
set_time_limit(3600);
require_once ("autoload.php");
file_put_contents('pages/'.getmypid()."_price.pid", ' ');
Registry::set('db', Simple::createConnection());
Registry::get('db')->setErrorHandler('Error::db_error');
Face::constan();

$_SESSION['proxy']='static';
$_SESSION['id']='priceru';
$_SESSION['user']=96;
Registry::set("parse_id",96);

if(!isset($_GET['reg'])) {echo "regiont not selected!";exit;}
switch ($_GET['reg']) {
	case 'msk':$region="&city=1";$reg=213;break;
	case 'spb':$region="&city=2";$reg=2;break;
	
	default:
		
		break;
}
if(isset($_GET['file'])){
	switch ($_GET['file']) {
		case 'mvic':$fp=dif_mic($_SESSION['user'],$reg,'mvic');break;
		case 'moic':$fp=dif_mic($_SESSION['user'],$reg,'moic');break;
		case 'price':$fp=price_file($_SESSION['user'],$reg);break;
		default: echo "no such file type:".$_GET['file'];exit;
	}
	echo '<a href="'.$fp.'">'.$fp.'</a>';
	exit;
}

Registry::get('db')->query("DELETE FROM `pricePrice` WHERE `shops_id`=?",$reg);
$dir='pages/priceru/';
$op_dir=opendir($dir);
if($op_dir){
	while ($file=readdir($op_dir))
		if ($file!="." && $file!="..")
			unlink($dir.$file);
	closedir($op_dir);
}

$tovars=sv_tovar(false,$reg);
$pf=new PageFinder('real','priceru');
foreach ($tovars as $key => $value) {
	if(Registry::get('db')->selectCell("SELECT COUNT(*) FROM `pricePrice` WHERE `user_id`=? AND `shops_id`=? AND `query_id`=? 

LIMIT 1;",$_SESSION['user'],$reg,$key)>0) continue;
	$title=Registry::get('db')->selectCell("SELECT CONCAT(`brand`,' ',`model`) as `title` FROM `dataTovar_proof` WHERE 

`opt_id`=? LIMIT 1 UNION SELECT `title` FROM `dataTovar_nocard` WHERE `id`=? LIMIT 1",$key,$key);
	$url='http://price.ru/search?query='.urlencode($title).$region;
	$page=$pf->getpage($url);
	$page=preg_replace('/[\r\n]+/','',$page);
	preg_match_all('/	<tr valign="top">	<td width="5%" valign="top"(.*?)<\/div><\/td><\/tr>/',$page,$shops);
	$min=0;
	$max=0;
	$mid=0;
	$rows=Array();
	foreach ($shops[1] as $val) {
		preg_match('/(?:\/[a-z0-9]+\.|\/)([a-z0-9\_\-]*\.[a-z]{2,3})\//', $val, $name);
		preg_match('/	([0-9]+)\&nbsp;р/', $val, $price);
		if(count($name)==0)preg_match('/<a href="\/firm\?id=[0-9]*">(.*?)</', $val, $name);
		if(isset($name[1]) && isset($price[1])){
			$domen=explode('.', $name[1]);
			$dc=count($domen);
			if($dc>1)$name[1]=$domen[$dc-2].".".$domen[$dc-1];
			$rows[]=join("|",Array($price[1],'Наличие',$name[1],0));
			
			if($min==0)$min=$price[1];
			elseif($price[1]<$min)$min=$price[1];
			if($price[1]>$max)$max=$price[1];
			$mid+=$price[1];
		}
		else var_dump($url,$val);
	}
	if(count($rows)>0)$mid=round($mid/count($rows));
	Registry::get('db')->query("INSERT INTO `pricePrice` VALUES (null,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",$_SESSION['user'],

$reg,Registry::get("parse_id"),$title,$min,$max,$mid,$key,$url,$value[1],$value[2],$value[0],"",join("+",$rows));
}
?>