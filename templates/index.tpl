<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
	</head>
	<script src="http://code.jquery.com/jquery-1.6.1.min.js" type="text/javascript"></script>
	<script type="text/javascript">
	var stat_init = 0;
	function parse_stat() {
		if (stat_init == 0){
			document.getElementById('message').innerHTML='';
		    var user_id = document.getElementById('user_id').innerHTML;
		    var shop_id = document.getElementById('shop_id').innerHTML;
	    	stat_init = 1;
		    $.get('check.php',{parse: 'get_id', shop_id: shop_id, user_id: user_id}, function(res) {
		        if (typeof (res['parse_id']) !== 'undefined') {
			       		 var cycle = setInterval(function() {
				             $.get('check.php',{parse: 'get_stat',parse_id: res['parse_id']}, function(stat) {
				             	var time_start = new Date(stat['time_start'] * 1000);
				             	if (stat['time_stop'] > 0){
				             		var time_stop = new Date(stat['time_stop'] * 1000);
				             		$.get('check.php',{parse: 'get_error_count',parse_id: res['parse_id']},function(error_count) {
				             			document.getElementById('message').innerHTML='Парс начался: '+time_start.toLocaleTimeString()+' и Закончился: '+time_stop.toLocaleTimeString()+'<br/>Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/>Было найдено: '+error_count+' ошибок.<br/>Обновите страницу!';
				             			clearInterval(cycle);
				             		},'json');
			             		}
				             	else document.getElementById('message').innerHTML='Парс начался: '+time_start.toLocaleTimeString()+'<br/>Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/>';
				             },'json')
				         },10000);
		        }
		        else document.getElementById('message').innerHTML=res['error'];
		    },'json');
	    }
	}
	</script>	
	<body>
		<span style="display:block;margin-left:auto;margin-right:auto;font-size:22px;color:purple;" onclick="parse_stat()";>СТАВКИ</span><br/> 
		<div>
			<span style="color:#FF3300; font-size:large; font-weight:200;" id="message">#MESSAGE#</span>
		</div>
		<br/>
		<div style="position:absolute;top:0px;right:10px;">
			<span style="color:#CCC; font-size:15px;">
				Логин: #LOGIN#
			<br/>
			ID: <span id="shop_id">#SHOPS#</span>
			<br/>
			Маг:<span id="id">#ID#</span><br/>
			Площадка: <span id="platform_id">#PLATFORM#</span><br/>
				<!-- Pswd: #PASS# <br/> -->
			<a style="color:#CCC; font-size:12px;" href="?logout=now">Выход</a>
			</span>
		</div>
		<br />
		<div>
			<form action="" method="post" enctype="multipart/form-data">
				<span>Тип прокси:</span><br/>
				<input type="radio" id="pr_s" name="proxy" value="static" checked="checked"/><label for="pr_s">Наши</label><br/>
				<input type="radio" id="pr_d" name="proxy" value="dynamic" /><label for="pr_d">Покупные</label><br/><br/>
				<!--<input type="file" name="upload" /><br/>-->
				<input type="submit" value="Запустить Парсер!" name="parse" />
			</form>
		</div>
		<br />
		<form action="" method="POST">
			<input type="submit" onClick="confirm(\'Уверены, что хотите очистить базу???\')" value="Очистить Таблицу" #FILE_GEN_DIS# name="TRUNCATE"/>
		<br />
			<input type="submit" onClick="confirm(\'Уверены, что хотите кэш???\')" value="Очистить Кэш" #CASH_CLEAN# name="Cache"/>
		<br />
		<br />		
			#FILE_PARSE#<br/>
			<input type="submit" value="Скачать Отчёт" #FILE_DWL_DIS# name="download"/>
			&nbsp;&nbsp;&nbsp;	
			<input type="submit" value="Сгенерировать Отчёт" #FILE_GEN_DIS# name="generate"/>
		<br />
		<br />
			#FILE_RATE#<br/>
			<input type="submit" value="Скачать Ставки" #FILE_RATE_DWL_DIS# name="rate_dwl"/>
			&nbsp;&nbsp;&nbsp;	
			<input type="submit" value="Генерировать Ставки" #FILE_RATE_GEN_DIS# name="rate"/><br/>
		</form>
		<br />
		<div>
			<form action="" method="post" enctype="multipart/form-data">
				<span>Артикул;Название;Цена</span><br/>
				<input type="file" name="update" /><br/>
				<input type="submit" value="Обновить Товары" name="submit" />
			</form>
		</div>
		<br />
		<div>
			<form action="" method="post" enctype="multipart/form-data">
				<span>Артикул;Категория<br/>
					Категории:<br/>
					1 - Ассортимент<br/>
					2 - Хит<br/>
					3 - МВИЦ<br/>
				</span>
				<input type="file" name="update_group" /><br/>
				<input type="submit" value="Обновить Группы" name="submit" />
			</form>
		</div>
	</body>
<html>

