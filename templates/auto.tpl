<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"> 
	</head>
	<script src="http://code.jquery.com/jquery-1.6.1.min.js" type="text/javascript"></script>
	<body>
		<span style="display:block;margin-left:auto;margin-right:auto;font-size:22px;color:purple;">Рубка!</span><br/> 
		<div>
			<span style="color:#FF3300; font-size:large; font-weight:200;" id="message">#MESSAGE#</span>
		</div>
		<br/>
		#FORCESV#
		<br/>
		<div>
			<span>Цены</span><br/>
			<span>Расписание: #CRON_PRICE#</span><br/>
			<span>МСК #FILE_AUTOPRICE_MSK#</span><br/>
			<span>СПБ #FILE_AUTOPRICE_SPB#</span><br/>
			<span>КРД #FILE_AUTOPRICE_KRD#</span><br/>
			<span>НВС #FILE_AUTOPRICE_NVS#</span><br/>
			<span><form action="" method="get"><input type="text" size="7" name="opt_id"><input type="submit" value="Проверить ID" /></form></span><br/><br/>	
			
			<form action="" method="post" enctype="multipart/form-data">
				<span>Артикул;Название;Цена</span><br/>
				<input type="file" name="update_price" /><br/>
				<select name="reg">
					<option value="213">Москва</option>
					<option value="2">СПБ</option>
					<option value="35">Краснодар</option>
					<option value="65">Новосибирск</option>
				</select>
				<input type="submit" value="Обновить Цены на Товары" name="submit" />
			</form>
		</div>
		<div style="float: right;margin-top:-250px;margin-right:400px"><span style="color:#F00;">#ERRORLIST_PRICE#</span></div>
		<div style="float: right;margin-top:-250px;"><span style="color:#00F;">#FILELIST_PRICE#</span></div>
		<br/>
		<div>
			<span>Ставки</span><br/>
			<span>Расписание: #CRON_RATE#</span><br/>
			<span>#FILE_AUTORATE#</span><br/><br/>
			<form action="" method="post" enctype="multipart/form-data">
				<span>Порядковый №;Производитель</span><br/>
				<input type="file" name="update_rate" /><br/>
				<input type="submit" value="Обновить список Брендов" name="submit" />
			</form>
		</div>
		<div style="float: right;margin-top:-170px;position: relative;display: table"><span style="color:#00F;">#FILELIST_RATE#</span></div>
		<br />
		<div>
			<span>Статистика</span><br/>
			<span>Расписание: #CRON_STAT#</span><br/>
			<span>#BASE_STAT#</span><br/><br/><br/><br/>
		</div>
		<div style="float: right;margin-top:-150px;position: relative;display: table"><span style="color:#00F;">#FILELIST_STAT#</span></div>
		<br />
		<a style="color:#CCC; font-size:12px;" href="?logout=now">Выход</a>
	</body>
<html>