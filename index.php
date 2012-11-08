<?php
//Инициализация и соединение с базой
session_name('parse');
session_start();
set_time_limit(3600);
require_once ("autoload.php");
Registry::set('db', Simple::createConnection());
Registry::get('db')->setErrorHandler('Error::db_error');

/**
 * View для главной панели парсера.
 * @param $interface Обьект интерфейса определённый заранее
 * @return $html string HTML Страница
 */
function panel($interface) {
	// проверка существования TPL файла
	if (file_exists("templates/index.tpl")) {
		$html=file_get_contents("templates/index.tpl");
		// отключение кнопок 
		foreach ($interface->setDisable() as $key=>$val) $html=str_replace($key, $val, $html);
		// проверка базы
		$interface->chkDb();
		// Инициализиция TPL
		$html=str_replace('#MESSAGE#', $interface->message, $html);
		$html=str_replace('#FILE_PARSE#', $interface->chkFile(), $html);
		$html=str_replace('#FILE_RATE#', $interface->chkFile_rate(), $html);
		// Название площадок
		$html=str_replace('#PLATFORM#', Registry::get('db')->selectCell('SELECT `title` FROM `platform` WHERE `id` = ? LIMIT 1', $_SESSION['platform']), $html);
		$html=str_replace('#SHOPS#', $_SESSION['shops'], $html);
		$html=str_replace('#LOGIN#', $_SESSION['login'], $html);
		$html=str_replace('#ID#', $_SESSION['id'], $html);
		return $html;
	}
	else echo 'TPL not Found!';
}

/**
 * View для панели статистики.
 * @param $interface Обьект интерфейса определённый заранее
 * @return $html string HTML Страница
 */
function panel_stat($interface) {
	// проверка существования TPL файла
	if (file_exists("templates/statistic.tpl")) {
		$html=file_get_contents("templates/statistic.tpl");
		// Дата последнего сбора статистики
		$html=str_replace('#LAST_STAT#', $interface->last_stat(), $html);
		$html=str_replace('#MESSAGE#', $interface->message, $html);
		return $html;
	}
	else echo 'TPL not Found!';
}

/**
 * View для панели хитов.
 * @param $interface Обьект интерфейса определённый заранее
 * @return $html string HTML Страница
 */
function panel_hits($interface) {
	// проверка существования TPL файла
	if (file_exists("templates/hits.tpl")) {
		$html=file_get_contents("templates/hits.tpl");
		// Каталоги из базы
		$html=str_replace('#CATS#', $interface->getCats(@$_POST['brand']), $html);
		// Бренды из базы
		$html=str_replace('#BRANDS#', $interface->getBrands(@$_POST['cat']), $html);
		$html=str_replace('#MESSAGE#', $interface->message, $html);
		return $html;
	}
	else echo 'TPL not Found!';
}

/**
 * View для панели Цен.
 * @param $interface Обьект интерфейса определённый заранее
 * @return $html string HTML Страница
 */
function panel_price($interface) {
	if (file_exists("templates/price.tpl")) {
		$html=file_get_contents("templates/price.tpl");
		// отключение кнопок
		foreach ($interface->setDisable() as $key=>$val) $html=str_replace($key, $val, $html);
		$html=str_replace('#MESSAGE#', $interface->message, $html);
		// Инициализация списков
		$html=str_replace('#USER#', Registry::get('db')->selectCell('SELECT `name` FROM `users` WHERE `id` = ? LIMIT 1', $_SESSION['user']), $html);
		$html=str_replace('#REGION#', Registry::get('db')->selectCell('SELECT `name` FROM `regions` WHERE `id` = ? LIMIT 1', $_SESSION['region']), $html);
		$html=str_replace('#PLATFORM#', Registry::get('db')->selectCell('SELECT `title` FROM `platform` WHERE `id` = ? LIMIT 1', $_SESSION['platform']), $html);
		// Для Auto Парса сделан интерфейс выгрузки цен
		$html=str_replace('#SHOPS_PRICE#', $_SESSION['user']==99?$interface->getShops():'', $html);
		$html=str_replace('#SHOP_ID#', $_SESSION['region'], $html);
		$html=str_replace('#USER_ID#', $_SESSION['user'], $html);
		return $html;
	}
	else echo 'TPL not Found!';
}

/**
 * View для панели auto.
 * @param $interface Объект интерфейса определённый заранее
 * @return $html string HTML Страница
 */
function panel_auto($interface) {
	if (file_exists("templates/auto.tpl")) {
		$html=file_get_contents("templates/auto.tpl");
		$html=str_replace('#MESSAGE#', $interface->message, $html);
		// Проверка файлов с ценами для городов
		$html=str_replace('#FILE_AUTOPRICE_MSK#',$interface->chkFile_autoprice('213'), $html);
		$html=str_replace('#FILE_AUTOPRICE_SPB#',$interface->chkFile_autoprice('2'), $html);
		$html=str_replace('#FILE_AUTOPRICE_KRD#',$interface->chkFile_autoprice('35'), $html);
		$html=str_replace('#FILE_AUTOPRICE_NVS#',$interface->chkFile_autoprice('65'), $html);
		// Проверка файлов с брендами для ставок
		$html=str_replace('#FILE_AUTORATE#',$interface->chkFile_autorate(), $html);
		// парс крона для показа времени задач
		$html=str_replace('#CRON_PRICE#',$interface->cron_price(), $html);
		$html=str_replace('#CRON_RATE#',$interface->cron_rate(), $html);
		$html=str_replace('#CRON_STAT#',$interface->cron_stat(), $html);
		// Инициализация списков файлов для отчетов
		$html=str_replace('#FILELIST_PRICE#',$interface->filelist_price(),$html);
		$html=str_replace('#FILELIST_RATE#',$interface->filelist_rate(),$html);
		$html=str_replace('#FILELIST_STAT#',$interface->filelist_stat(),$html);
		// Инициализация списков файлов с ошибками
		$html=str_replace('#ERRORLIST_PRICE#',$interface->errorlist_price(),$html);
		// Информация по площядкам из базы.
		$html=str_replace('#BASE_STAT#', $interface->basestat_stat(),$html);
		// Условия для заливки цен из магазина в магазин.
		if(isset($_GET['sv']) && $_GET['sv']==true) $forcesv = $interface->forcesv();
		// Показ стоп листа
		elseif(isset($_GET['stop'])  && $_GET['stop']==true) $forcesv = $interface->forcestop();
		else  $forcesv="";
		// Вывод в HTML файл
		$html=str_replace('#FORCESV#', $forcesv, $html);
		return $html;
	}
	else echo 'TPL not Found!';
}

// Показ страницы с настройками.
if (isset($_GET['settings'])) {
	if ($_GET['settings']=='check') {
		// обновление статических IP из файла.
		if (isset($_FILES['update_ip']) && Face::upload()==true) echo 'IP успешно обновлены!';
		else $html='<html><body>
					<form action="" method="post" enctype="multipart/form-data">
						<input type="file" name="update_ip" /><br/>
						<input type="submit" value="Запустить Парсер!" name="Update" />
					</form><br/>';
		
		$const_list=Face::getconstan();
		$proxy_list=Ip::getlist();
		$html.='CONSTANTS<br/><table border="1px">';
		foreach ($const_list as $val) $html.="<tr><td>".$val['name']."</td><td>".$val['value']."</td></tr>";
		$html.='</table>';
		$html.='<br/><br/>';
		if (count($proxy_list)>0) {
			$html.='PROXY<br/><table border="1px"><tr align="center">';
			$proxy_title=array_keys($proxy_list[0]);
			foreach ($proxy_title as $val) $html.="<td>$val</td>";
			$html.='</tr>';
			foreach ($proxy_list as $value) {
				$html.="<tr>";
				foreach ($value as $key=>$val) $html.="<td>$val</td>";
				$html.="</tr>";
			}
			$html.='</table>';
		}
		$html.='</body></html>';
		echo $html;
		exit(0);
	}
	elseif ($_GET['settings']=='set') {
		if (file_exists("templates/settings_set.tpl")) $html=file_get_contents("templates/index.tpl");
		else echo 'settings_set TPL not Found!';
	}
}

// Инициализация данных в СЕССИИ, для загрузки нужного модуля.
if (isset($_POST['parser'])) {
	if ($_POST['parser']=='Цены') $_SESSION['parser']='price';
	elseif ($_POST['parser']=='Ставки')	$_SESSION['parser']='partner';
	elseif ($_POST['parser']=='Статистика')	$_SESSION['parser']='statistic';
	elseif ($_POST['parser']=='Хиты') $_SESSION['parser']='hits';
}
// Условие для входа в админку
if (isset($_GET['parser'])) if ($_GET['parser']=='auto') $_SESSION['parser']='auto';
// Инициализация интерфейса и Данных о регионе, юзере и площадки
if (isset($_SESSION['parser'])) {
	if ($_SESSION['parser']=='price') {
		if (isset($_POST['region'])) $_SESSION['region']=$_POST['region'];
		if (isset($_POST['user'])) $_SESSION['user']=$_POST['user'];
		if (isset($_POST['user'])) $_SESSION['platform']=$_POST['platform'];
		$interface=new FacePrice();
	}
	elseif ($_SESSION['parser']=='partner')	$interface=new FaceRate();
	elseif ($_SESSION['parser']=='statistic') $interface=new FaceStat();
	elseif ($_SESSION['parser']=='hits') $interface=new FaceHits();
	elseif ($_SESSION['parser']=='auto') $interface=new FaceAuto();
}
else {
	$_SESSION['parser']='partner';
	$interface=new FaceRate();
}

// ACHTUNG!!! Полная очистка данных в базе по парсеру цен. (Использовалась на dev сервере)
if (isset($_GET['full_clean'])) $interface->full_clean($_GET['full_clean']);
// Выход на главную панель
if (isset($_GET['logout'])) $interface->logout();


// Далее идёт Сам о великий контроллер -_-
// Класс ставок, он же партнёрка. Тут же происходит авторизация.
if ($_SESSION['parser']=='partner') {
	// Если данные в сессии пустые, то проиходит вызов Главной панели для авторизации.
	if (!isset($_SESSION['shops']) || !isset($_SESSION['login']) || !isset($_SESSION['id']) || !isset($_SESSION['platform'])) $interface->auth();
	// Объявление файла для класса 
	$interface->file='files/download/'.$_SESSION['shops'].'_'.$_SESSION['platform'].'_dwl.csv';
	if (isset($_FILES['update']['tmp_name'])) $interface->update($_FILES['update']['tmp_name']);
	elseif (isset($_FILES['update_group']['tmp_name']))	$interface->update_group($_FILES['update_group']['tmp_name']);
	elseif (isset($_POST['TRUNCATE']))	$interface->truncate();
	elseif (isset($_POST['Cache']))	$interface->cache_clean();
	elseif (isset($_POST['generate']))	$interface->generate_file();
	elseif (isset($_POST['rate']))	$interface->generate_rate();
	elseif (isset($_POST['rate_dwl']))	$interface->download_rate();
	elseif (isset($_POST['download']))	$interface->download_fail();
	elseif (isset($_POST['parse'])) {
		if (isset($_POST['proxy'])) {
			$_SESSION['proxy']=$_POST['proxy'];
			$interface->start_daemon("files/autorate.csv");
		}
		else $interface->message='Выберите тип прокси!<br />';
	}
	$html=panel($interface);
}
// Контроллер для парсера цен.
elseif ($_SESSION['parser']=='price') {
	// Объявление файла для класса 	
	$interface->file='files/download/'.@$_SESSION['region'].'_'.@$_SESSION['user'].'_dwl.csv';
	// Скачивание отчёта по последнему парсу.
	if (isset($_POST['download'])) $interface->generate_file();
	// Проверка товаров на наличие в базе. (Сделано было для мониторщиков)
	elseif (isset($_POST['tovar_check']) && $_POST['tovar_check']=="Проверить товары в базе" && $_FILES['tovar_check']['error']==0) $interface->chkTovar($_FILES['tovar_check']['tmp_name']);
	elseif (isset($_POST['parse'])) {
		if (isset($_POST['proxy'])) {
			// занесение типа прокси в сессиию, для быстрого парса в 1 поток.
			$_SESSION['proxy']=$_POST['proxy'];
			if(isset($_GET['debug']) && $_GET['debug']==true) $interface->getFile($_FILES['upload']['tmp_name']);
			// Запуск Демона Автопарса по готовому файлу на хостинге.
			elseif($_SESSION['user']==99) $interface->start_daemon("files/autoprice_".$_SESSION['region'].".txt",'static');
			//  Проверка файла на ошибки, сохранение оного и Запуск демона.
			elseif ($_FILES['upload']['error']==0) {
				copy($_FILES['upload']['tmp_name'], 'files/upload/'.$_SESSION['user'].'_'.$_SESSION['region'].'.csv');
				$interface->start_daemon('files/upload/'.$_SESSION['user'].'_'.$_SESSION['region'].'.csv',$_POST['proxy']);
			}
			else $interface->message.='Не правильный файл! <br/>';
		}
		else $interface->message='Выберите тип прокси!<br />'; 
		$html=panel_price($interface);
	}
	// Очистка базы текущего юзера
	elseif (isset($_POST['TRUNCATE'])) $interface->truncate();
	// Очистка кэша текущего города.
	elseif (isset($_POST['Cache']))	$interface->cache_clean();
	$html=panel_price($interface);
}
// Панель статистики
elseif ($_SESSION['parser']=='statistic') {
	// Абстрактный регион
	$_SESSION['region']='statistic';
	// Тут только 1 пользователь 
	$_SESSION['user']=99;
	// Объявление файла для класса
	$interface->file='files/download/'.@$_SESSION['region'].'_'.@$_SESSION['user'].'_dwl.csv';
	// Генерация файла для отчёта.
	if (isset($_POST['download'])) $interface->generate_file();
	elseif (isset($_POST['parse'])) {
		if (isset($_POST['proxy'])) {
			$_SESSION['proxy']=$_POST['proxy'];
			// Запуск парса для сбора вчерашней инфы
			$interface->parser_one_row();
		}
		else $interface->message='Выберите тип прокси!<br />';
	}
	$html=panel_stat($interface);
}
// Панель для Хитов
elseif ($_SESSION['parser']=='hits') {
	// Абстрактный регион
	$_SESSION['region']='hits';
	// Тут только 1 пользователь 
	$_SESSION['user']=98;
	// Объявление файла для класса 
	$interface->file='files/download/'.@$_SESSION['region'].'_'.@$_SESSION['user'].'_dwl.csv';
	if (isset($_POST['download']) && $_POST['download']=='Сформировать и Скачать') $interface->generate_file();
	elseif (isset($_POST['parse'])) {
		// Запуск парсера при выборе Каталога и Бренда
		if (isset($_POST['brand']) && isset($_POST['cat']))	$interface->parser_one_row();
		else $interface->message='Выберите тип прокси!<br />';
	}
	$html=panel_hits($interface);
}
// Панель для Админки
elseif ($_SESSION['parser']=='auto') {
	// Абстрактный регион
	$_SESSION['region']='auto';
	// Тут только 1 пользователь 
	$_SESSION['user']=99;
	// Проверка данных на код товара. (Усовершенствонная версия лежит в add_card.php)
	if(isset($_GET['opt_id'])) $interface->message.=$interface->check_id((int) $_GET['opt_id']);
	// Коннтроллер по кнопкам -_-
	if (isset($_POST['submit'])){
		// Кнопки описывают действия.
		if($_POST['submit']=='Обновить Цены на Товары') if(isset($_FILES['update_price']['tmp_name'])) $interface->update_price($_FILES['update_price']['tmp_name'],$_POST['reg']);
		if($_POST['submit']=='Обновить список Брендов') if(isset($_FILES['update_rate']['tmp_name'])) $interface->update_rate($_FILES['update_rate']['tmp_name']);
		if($_POST['submit']=='Залить цены')	$interface->fastsv_update($_POST['from'],$_POST['to'],$_POST['num'],$_POST['action']);
		if($_POST['submit']=='Залить стоп позиции') $_FILES['list']['error']!=4? $interface->message.=$interface->stop_file($_FILES['list']['tmp_name'],$_POST['shops']):$interface->message.=$interface->stop_add($_POST['id'],$_POST['shops']);
		if($_POST['submit']=='Удалить товар')$interface->message.=$interface->stop_delete($_POST['tovar'],'all');
		if($_POST['submit']=='Удалить ВСЁ!!!')$interface->message.=$interface->stop_delete('all','all');
	}
	// Проверка списка товаров в файле на паср. (Усовершенствонная версия лежит в add_card.php)
	elseif (isset($_POST['TovarListIntersect_price']) && $_POST['TovarListIntersect_price']=="Список Артикулов в парсе") $interface->message.=$interface->TovarListIntersect_price();
	$html=panel_auto($interface);
}
echo $html;
exit(0);
?>