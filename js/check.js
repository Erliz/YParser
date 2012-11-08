function parse_stat() {
    user_id = document.getElementById('user_id').value;
    shop_id = document.getElementById('shop_id').value;
    $.post('check.php',{mod: 'get_parse',shop_id: shop_id,user_id: user_id}, function(res) {
        if (res['parse_id'] !== 'undefined') {
             $.post('check.php',{mod: 'stat',parse_id: res['parse_id']}, function(stat) {
             	var time_start = new Date();
             	time_start.setTime(stat['time_start'] * 1000);
             	if (stat['time_stop'] !== null){
	             	time_stop.setTime(stat['time_stop'] * 1000);
	             	var time_stop = new Date();
	             }
             	document.getElementById('message').innerHTML='Парс начался: '+time_start.toUTCString+'. Пройдено '+stat['rows_done']+' из '+stat['rows_plan']+' строк.<br/>';
             },'json');
        }
        else {
            document.getElementById('message').innerHTML='';
        }
    },'json');
    //      alert(email);
}