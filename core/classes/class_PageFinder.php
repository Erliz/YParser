<?php
/**
 * Класс обеспечивающий скачку страниц с интернета
 */
class PageFinder {
	// handle для библиотеке curl
	protected $curl;
	// handle для обьекта по работе с ip
	protected $proxy;
	// путь к папке с кешем
	protected $cache;
	// изначальные настройки
	protected $proxy_src;
	protected $cache_src;
	protected $cookie_src;
	
	/**
	 * Switch ssl varify check
	 * 
	 * @param boolen $swith - 1-on, 2-off
	 */
	public function curl_ssl($switch){
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $switch); // не проверять SSL сертификат (1-on, 2-off)
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $switch); // не проверять Host SSL сертификата (1-on, 2-off)
	}
	
	/**
	 * Switch POST and fields sent
	 * 
	 * @param multiply $fields - array(name=>val) / string(name=val) / int 0 - post_off;
	 */
	public function curl_post($fields){
		if($fields===0){
			curl_setopt($this->curl, CURLOPT_POST, 0);
			return true;
		}
		/*$query="";
		if(is_array($fields)){
			foreach ($fields as $key => $value)	$query.=$key."=".$value."&";
			$query=substr($query, 0,-1);
		}
		else $query=$fields;*/
		curl_setopt($this->curl, CURLOPT_POST, TRUE);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $fields); // 
	}
	
	/**
	 * Set browser, OS, version to curl useragent
	 */
	private function curl_useragent(){
		$list=Array(0=>Array(
			array('title'=>"Chrome 4.0.249.0 (Win 7)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/532.5 (KHTML, like Gecko) Chrome/4.0.249.0 Safari/532.5"),
			array('title'=>"Chrome 5.0.310.0 (Server 2003)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US) AppleWebKit/532.9 (KHTML, like Gecko) Chrome/5.0.310.0 Safari/532.9"),
			array('title'=>"Chrome 7.0.514.0 (Win XP)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/534.7 (KHTML, like Gecko) Chrome/7.0.514.0 Safari/534.7"),
			array('title'=>"Chrome 9.0.601.0 (Vista)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/534.14 (KHTML, like Gecko) Chrome/9.0.601.0 Safari/534.14"),
			array('title'=>"Chrome 10.0.601.0 (Win 7)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.14 (KHTML, like Gecko) Chrome/10.0.601.0 Safari/534.14"),
			array('title'=>"Chrome 11.0.672.2 (Win 7)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/534.20 (KHTML, like Gecko) Chrome/11.0.672.2 Safari/534.20"),
			array('title'=>"Chrome 12.0.712.0 (Win 7 64)","value"=>"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/534.27 (KHTML, like Gecko) Chrome/12.0.712.0 Safari/534.27"),
			array('title'=>"Chrome 13.0.782.24 (Win 7 64)","value"=>"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.24 Safari/535.1"),
			array('title'=>"Firefox 3.0.2pre (Win XP 64)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.0 x64; en-US; rv:1.9pre) Gecko/2008072421 Minefield/3.0.2pre"),
			array('title'=>"Firefox 3.0.10 (Win XP)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.10) Gecko/2009042316 Firefox/3.0.10"),
			array('title'=>"Firefox 3.0.11 (Vista) + .NET","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.0; en-GB; rv:1.9.0.11) Gecko/2009060215 Firefox/3.0.11 (.NET CLR 3.5.30729)"),
			array('title'=>"Firefox 3.5.6 (Vista)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6 GTB5"),
			array('title'=>"Firefox 3.6.8 (XP)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 5.1; tr; rv:1.9.2.8) Gecko/20100722 Firefox/3.6.8 ( .NET CLR 3.5.30729; .NET4.0E)"),
			array('title'=>"Firefox 4.01 (Win 7 32)","value"=>"Mozilla/5.0 (Windows NT 6.1; rv:2.0.1) Gecko/20100101 Firefox/4.0.1"),
			array('title'=>"Firefox 4.01 (Win 7 64)","value"=>"Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1"),
			array('title'=>"Opera 7.5 (Win XP)","value"=>"Opera/7.50 (Windows XP; U)"),
			array('title'=>"Opera 7.5 (Win ME)","value"=>"Opera/7.50 (Windows ME; U) [en]"),
			array('title'=>"Opera 7.51 (Win XP)","value"=>"Opera/7.51 (Windows NT 5.1; U) [en]"),
			array('title'=>"Opera 8.0 (Win 2000)","value"=>"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; en) Opera 8.0"),
			array('title'=>"Avant Browser - MSIE 7 (Win XP)","value"=>"Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Avant Browser; Avant Browser; .NET CLR 1.0.3705; .NET CLR 1.1.4322; Media Center PC 4.0; .NET CLR 2.0.50727; .NET CLR 3.0.04506.30)"),
			array('title'=>"Chrome 15.0.874.120 (Vista)","value"=>"Mozilla/5.0 (Windows NT 6.0) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.120 Safari/535.2"),
			array('title'=>"Chrome 16.0.912.36 (Win 7 64)","value"=>"Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.36 Safari/535.7"),
			array('title'=>"Chrome 18.6.872.0 (Win 7)","value"=>"Mozilla/5.0 (Windows NT 6.1) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/18.6.872.0 Safari/535.2 UNTRUSTED/1.0 3gpp-gba UNTRUSTED/1.0"),
			array('title'=>"Firefox 5.0 (XP)","value"=>"Mozilla/5.0 (Windows NT 5.1; rv:5.0) Gecko/20100101 Firefox/5.0"),
			array('title'=>"Firefox 6.0a2 (Win 7 64)","value"=>"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:6.0a2) Gecko/20110622 Firefox/6.0a2"),
			array('title'=>"Firefox 7.0.1 (Win 7 64)","value"=>"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:7.0.1) Gecko/20100101 Firefox/7.0.1"),
			array('title'=>"Firefox 10.0.1 (Win 7 64)","value"=>"Mozilla/5.0 (Windows NT 6.1; WOW64; rv:10.0.1) Gecko/20100101 Firefox/10.0.1"),
			array('title'=>"Opera 9.25 - (Vista)","value"=>"Opera/9.25 (Windows NT 6.0; U; en)"),
			array('title'=>"Opera 10.10 (id as 9.8) (Server 2003)","value"=>"Opera/9.80 (Windows NT 5.2; U; en) Presto/2.2.15 Version/10.10"),
			array('title'=>"Opera 11.00 (id as 9.8) (Win XP)","value"=>"Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.7.39 Version/11.00"),
			array('title'=>"Opera 11.01 (id as 9.8) (Win 7)","value"=>"Opera/9.80 (Windows NT 6.1; U; en) Presto/2.7.62 Version/11.01"),
			array('title'=>"Opera 11.10 (id as 9.8) (Win XP)","value"=>"Opera/9.80 (Windows NT 5.1; U; zh-tw) Presto/2.8.131 Version/11.10"),
			array('title'=>"Opera 12.00 (id as 9.8) (Win 7)","value"=>"Opera/9.80 (Windows NT 6.1; U; es-ES) Presto/2.9.181 Version/12.00"),
			array('title'=>"Safari 531.21.10 (Win XP)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/531.21.8 (KHTML, like Gecko) Version/4.0.4 Safari/531.21.10"),
			array('title'=>"Safari 533.17.8 (Server 2003/64 bit)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 5.2; en-US) AppleWebKit/533.17.8 (KHTML, like Gecko) Version/5.0.1 Safari/533.17.8"),
			array('title'=>"Safari 533.19.4 (Win 7)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US) AppleWebKit/533.19.4 (KHTML, like Gecko) Version/5.0.2 Safari/533.18.5"),
			array('title'=>"SeaMonkey (Mozilla) 2.0.12 (Win 7)","value"=>"Mozilla/5.0 (Windows; U; Windows NT 6.1; en-GB; rv:1.9.1.17) Gecko/20110123 (like Firefox/3.x) SeaMonkey/2.0.12")),
		
		1=>Array(
			array('title'=>"Chrome 4.0.302.2 (OS X 10_5_8 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_5_8; en-US) AppleWebKit/532.8 (KHTML, like Gecko) Chrome/4.0.302.2 Safari/532.8"),
			array('title'=>"Chrome 6.0.464 (OS X 10_6_4 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_4; en-US) AppleWebKit/534.3 (KHTML, like Gecko) Chrome/6.0.464.0 Safari/534.3"),
			array('title'=>"Chrome 9.0.597.15 (OS X 10_6_5 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; en-US) AppleWebKit/534.13 (KHTML, like Gecko) Chrome/9.0.597.15 Safari/534.13"),
			array('title'=>"Firefox 0.9 (OS X Mach)","value"=>"Mozilla/5.0 (Macintosh; U; Mac OS X Mach-O; en-US; rv:2.0a) Gecko/20040614 Firefox/3.0.0+"),
			array('title'=>"Firefox 3.0.3 (OS X PPC)","value"=>"Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10.5; en-US; rv:1.9.0.3) Gecko/2008092414 Firefox/3.0.3"),
			array('title'=>"Firefox 3.5 (OS X 10.5 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.5; en-US; rv:1.9.1) Gecko/20090624 Firefox/3.5"),
			array('title'=>"Firefox 3.6 (OS X 10.6 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.14) Gecko/20110218 AlexaToolbar/alxf-2.0 Firefox/3.6.14"),
			array('title'=>"Firefox 3.6 (OS X 10.5 PPC)","value"=>"Mozilla/5.0 (Macintosh; U; PPC Mac OS X 10.5; en-US; rv:1.9.2.15) Gecko/20110303 Firefox/3.6.15"),
			array('title'=>"Firefox 4.0.1 (OS X 10.6 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1"),
			array('title'=>"MSIE 5.15 (OS 9)","value"=>"Mozilla/4.0 (compatible; MSIE 5.15; Mac_PowerPC)"),
			array('title'=>"Omniweb 563.15 (OS X PPC)","value"=>"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en-US) AppleWebKit/125.4 (KHTML, like Gecko, Safari) OmniWeb/v563.15"),
			array('title'=>"Opera 9.0 (OS X PPC)","value"=>"Opera/9.0 (Macintosh; PPC Mac OS X; U; en)"),
			array('title'=>"Safari 85 (OS X PPC)","value"=>"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/125.2 (KHTML, like Gecko) Safari/85.8"),
			array('title'=>"Safari 125.8 (OS X PPC)","value"=>"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/125.2 (KHTML, like Gecko) Safari/125.8"),
			array('title'=>"Safari 312.3 (OS X PPC)","value"=>"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; fr-fr) AppleWebKit/312.5 (KHTML, like Gecko) Safari/312.3"),
			array('title'=>"Safari 419.3 (OS X PPC)","value"=>"Mozilla/5.0 (Macintosh; U; PPC Mac OS X; en) AppleWebKit/418.8 (KHTML, like Gecko) Safari/419.3"),
			array('title'=>"Camino 2.2.1 (Firefox 4.0.1) (OS X 10.6 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1 Camino/2.2.1"),
			array('title'=>"Camino 2.2a1pre (Firefox 4.0.1) (OS X 10.6 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0b6pre) Gecko/20100907 Firefox/4.0b6pre Camino/2.2a1pre"),
			array('title'=>"Chrome 14.0.835.186 (OS X 10_7_2 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/14.0.835.186 Safari/535.1"),
			array('title'=>"Chrome 15.0.874.54 (OS X 10_6_8 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.2 (KHTML, like Gecko) Chrome/15.0.874.54 Safari/535.2"),
			array('title'=>"Chrome 16.0.912.36 (OS X 10_6_8 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.7 (KHTML, like Gecko) Chrome/16.0.912.36 Safari/535.7"),
			array('title'=>"Firefox 5.0 (OS X 10.6 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:5.0) Gecko/20100101 Firefox/5.0"),
			array('title'=>"Firefox 9.0 (OS X 10.6 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:9.0) Gecko/20100101 Firefox/9.0"),
			array('title'=>"Firefox 10.0.1 (OS X 10.6 Intel)","value"=>"Mozilla/5.0 (Macintosh; Intel Mac OS X 10_7_2; rv:10.0.1) Gecko/20100101 Firefox/10.0.1"),
			array('title'=>"Opera 9.20 (OS X Intel)","value"=>"Opera/9.20 (Macintosh; Intel Mac OS X; U; en)"),
			array('title'=>"Opera 9.64 (OS X PPC)","value"=>"Opera/9.64 (Macintosh; PPC Mac OS X; U; en) Presto/2.1.1"),
			array('title'=>"Opera 10.61 (id as 9.8) (OS X Intel)","value"=>"Opera/9.80 (Macintosh; Intel Mac OS X; U; en) Presto/2.6.30 Version/10.61"),
			array('title'=>"Opera 11.00 (id as 9.8) (OS X Intel)","value"=>"Opera/9.80 (Macintosh; Intel Mac OS X 10.4.11; U; en) Presto/2.7.62 Version/11.00"),
			array('title'=>"Opera 11.52 (id as 9.8) (OS X Intel)","value"=>"Opera/9.80 (Macintosh; Intel Mac OS X 10.6.8; U; fr) Presto/2.9.168 Version/11.52"),
			array('title'=>"Safari 531.21.10 (OS X 10_6_2 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_2; en-us) AppleWebKit/531.21.8 (KHTML, like Gecko) Version/4.0.4 Safari/531.21.10"),
			array('title'=>"Safari 533.19.4 (OS X 10_6_5 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_5; de-de) AppleWebKit/534.15+ (KHTML, like Gecko) Version/5.0.3 Safari/533.19.4"),
			array('title'=>"Safari 533.20.27 (OS X 10_6_6 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_6; en-us) AppleWebKit/533.20.25 (KHTML, like Gecko) Version/5.0.4 Safari/533.20.27"),
			array('title'=>"Safari 534.20.8 (OS X 10_7 Intel)","value"=>"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_7; en-us) AppleWebKit/534.20.8 (KHTML, like Gecko) Version/5.1 Safari/534.20.8")),
			
		2=>Array(
			array('title'=>"Chrome 4.0.237.0 (Debian)","value"=>"Mozilla/5.0 (X11; U; Linux i686; en-US) AppleWebKit/532.4 (KHTML, like Gecko) Chrome/4.0.237.0 Safari/532.4 Debian"),
			array('title'=>"Chrome 4.0.277.0","value"=>"Mozilla/5.0 (X11; U; Linux i686; en-US) AppleWebKit/532.8 (KHTML, like Gecko) Chrome/4.0.277.0 Safari/532.8"),
			array('title'=>"Chrome 5.0.309.0","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/532.9 (KHTML, like Gecko) Chrome/5.0.309.0 Safari/532.9"),
			array('title'=>"Chrome 7.0.514 (64 bit)","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/534.7 (KHTML, like Gecko) Chrome/7.0.514.0 Safari/534.7"),
			array('title'=>"Chrome 9.1.0.0 (Ubuntu 64 bit)","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/540.0 (KHTML, like Gecko) Ubuntu/10.10 Chrome/9.1.0.0 Safari/540.0"),
			array('title'=>"Chrome 10.0.613.0 (64 bit)","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US) AppleWebKit/534.15 (KHTML, like Gecko) Chrome/10.0.613.0 Safari/534.15"),
			array('title'=>"Chrome 10.0.613.0 (Ubuntu 32 bit)","value"=>"Mozilla/5.0 (X11; U; Linux i686; en-US) AppleWebKit/534.15 (KHTML, like Gecko) Ubuntu/10.10 Chromium/10.0.613.0 Chrome/10.0.613.0 Safari/534.15"),
			array('title'=>"Chrome 12.0.703.0 (Ubuntu 64 bit)","value"=>"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/534.24 (KHTML, like Gecko) Ubuntu/10.10 Chromium/12.0.703.0 Chrome/12.0.703.0 Safari/534.24"),
			array('title'=>"Chrome 13.0.782.20 (64 bit)","value"=>"Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.20 Safari/535.1"),
			array('title'=>"Epiphany 1.2 - Gecko","value"=>"Mozilla/5.0 (X11; U; Linux; i686; en-US; rv:1.6) Gecko Epiphany/1.2.5"),
			array('title'=>"Epiphany 1.4 - Gecko (Ubuntu)","value"=>"Mozilla/5.0 (X11; U; Linux i586; en-US; rv:1.7.3) Gecko/20040924 Epiphany/1.4.4 (Ubuntu)"),
			array('title'=>"Firefox 0.8","value"=>"Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.6) Gecko/20040614 Firefox/0.8"),
			array('title'=>"Firefox 2.0.0.12 (Ubuntu 7.10)","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; sv-SE; rv:1.8.1.12) Gecko/20080207 Ubuntu/7.10 (gutsy) Firefox/2.0.0.12"),
			array('title'=>"Firefox 3.0.12 - (Ubuntu karmic 9.10)","value"=>"Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.0.11) Gecko/2009060309 Ubuntu/9.10 (karmic) Firefox/3.0.11"),
			array('title'=>"Firefox 3.5.2 - Shiretoko (Ubuntu 9.04)","value"=>"Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1.2) Gecko/20090803 Ubuntu/9.04 (jaunty) Shiretoko/3.5.2"),
			array('title'=>"Firefox 3.5.5","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1.5) Gecko/20091107 Firefox/3.5.5"),
			array('title'=>"Firefox 3.5.3 (Mint 8)","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.1.3) Gecko/20091020 Linux Mint/8 (Helena) Firefox/3.5.3"),
			array('title'=>"Firefox 3.6.9 (Gentoo 64 bit)","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.2.9) Gecko/20100915 Gentoo Firefox/3.6.9"),
			array('title'=>"Firefox 3.8 (Ubuntu/9.25)","value"=>"Mozilla/5.0 (X11; U; Linux i686; pl-PL; rv:1.9.0.2) Gecko/20121223 Ubuntu/9.25 (jaunty) Firefox/3.8"),
			array('title'=>"Firefox 4.0b6pre (32 bit)","value"=>"Mozilla/5.0 (X11; Linux i686; rv:2.0b6pre) Gecko/20100907 Firefox/4.0b6pre"),
			array('title'=>"Firefox 4.0.1 (32 on 64 bit)","value"=>"Mozilla/5.0 (X11; Linux i686 on x86_64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1"),
			array('title'=>"Firefox 4.0.1 (32 bit)","value"=>"Mozilla/5.0 (X11; Linux i686; rv:2.0.1) Gecko/20100101 Firefox/4.0.1"),
			array('title'=>"Firefox 4.0.1 (64 bit)","value"=>"Mozilla/5.0 (X11; Linux x86_64; rv:2.0.1) Gecko/20100101 Firefox/4.0.1"),
			array('title'=>"Firefox 4.2a1pre (64 bit)","value"=>"Mozilla/5.0 (X11; Linux x86_64; rv:2.2a1pre) Gecko/20100101 Firefox/4.2a1pre"),
			array('title'=>"Konqueror 3 rc4 - khtml","value"=>"Konqueror/3.0-rc4; (Konqueror/3.0-rc4; i686 Linux;;datecode)"),
			array('title'=>"Konqueror 3.3 - khtml (Gentoo)","value"=>"Mozilla/5.0 (compatible; Konqueror/3.3; Linux 2.6.8-gentoo-r3; X11;"),
			array('title'=>"Konqueror 3.5 - khtml (Debian)","value"=>"Mozilla/5.0 (compatible; Konqueror/3.5; Linux 2.6.30-7.dmz.1-liquorix-686; X11) KHTML/3.5.10 (like Gecko) (Debian package 4:3.5.10.dfsg.1-1+b1)"),
			array('title'=>"Konqueror 3.5.6 - khtml (Kubuntu)","value"=>"Mozilla/5.0 (compatible; Konqueror/3.5; Linux; en_US) KHTML/3.5.6 (like Gecko) (Kubuntu)"),
			array('title'=>"Mozilla 1.6 (Debian)","value"=>"Mozilla/5.0 (X11; U; Linux; i686; en-US; rv:1.6) Gecko Debian/1.6-7"),
			array('title'=>"Opera 7.23","value"=>"MSIE (MSIE 6.0; X11; Linux; i686) Opera 7.23"),
			array('title'=>"Chrome 13.0.782.41 (Slackware 13.37 64 bit)","value"=>"Mozilla/5.0 Slackware/13.37 (X11; U; Linux x86_64; en-US) AppleWebKit/535.1 (KHTML, like Gecko) Chrome/13.0.782.41"),
			array('title'=>"Chrome 14.0.825.0 (Ubuntu 11.04)","value"=>"Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.1 (KHTML, like Gecko) Ubuntu/11.04 Chromium/14.0.825.0 Chrome/14.0.825.0 Safari/535.1"),
			array('title'=>"Chrome 15.0.874.120 (Ubuntu 11.10)","value"=>"Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.2 (KHTML, like Gecko) Ubuntu/11.10 Chromium/15.0.874.120 Chrome/15.0.874.120 Safari/535.2"),
			array('title'=>"Firefox 5.0 (32 bit)","value"=>"Mozilla/5.0 (X11; Linux i686; rv:5.0) Gecko/20100101 Firefox/5.0"),
			array('title'=>"Firefox 6.0 (32 bit)","value"=>"Mozilla/5.0 (X11; Linux i686; rv:6.0) Gecko/20100101 Firefox/6.0"),
			array('title'=>"Firefox 7.0a1 (64 bit)","value"=>"Mozilla/5.0 (X11; Linux x86_64; rv:7.0a1) Gecko/20110623 Firefox/7.0a1"),
			array('title'=>"Firefox 8.0 (32 bit)","value"=>"Mozilla/5.0 (X11; Linux i686; rv:8.0) Gecko/20100101 Firefox/8.0"),
			array('title'=>"Firefox 10.0.1 (64 bit)","value"=>"Mozilla/5.0 (X11; Linux x86_64; rv:10.0.1) Gecko/20100101 Firefox/10.0.1"),
			array('title'=>"Konqueror 4.3 - khtml (Slackware 13)","value"=>"Mozilla/5.0 (compatible; Konqueror/4.2; Linux) KHTML/4.2.4 (like Gecko) Slackware/13.0"),
			array('title'=>"Konqueror 4.3.1 - khtml (Fedora 11)","value"=>"Mozilla/5.0 (compatible; Konqueror/4.3; Linux) KHTML/4.3.1 (like Gecko) Fedora/4.3.1-3.fc11"),
			array('title'=>"Konqueror 4.4.3 - khtml (Fedora 12)","value"=>"Mozilla/5.0 (compatible; Konqueror/4.4; Linux) KHTML/4.4.1 (like Gecko) Fedora/4.4.1-1.fc12"),
			array('title'=>"Konqueror 4.4.3 - khtml (Kubuntu)","value"=>"Mozilla/5.0 (compatible; Konqueror/4.4; Linux 2.6.32-22-generic; X11; en_US) KHTML/4.4.3 (like Gecko) Kubuntu"),
			array('title'=>"Mozilla 1.9.0 (Debian)","value"=>"Mozilla/5.0 (X11; U; Linux x86_64; en-US; rv:1.9.0.3) Gecko/2008092814 (Debian-3.0.1-1)"),
			array('title'=>"Mozilla 1.9a3pre","value"=>"Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9a3pre) Gecko/20070330"),
			array('title'=>"Opera 9.64 (Linux Mint)","value"=>"Opera/9.64 (X11; Linux i686; U; Linux Mint; nb) Presto/2.1.1"),
			array('title'=>"Opera 10.10 (id as 9.8)","value"=>"Opera/9.80 (X11; Linux i686; U; en) Presto/2.2.15 Version/10.10"),
			array('title'=>"Opera 11.00 (id as 9.8)","value"=>"Opera/9.80 (X11; Linux x86_64; U; pl) Presto/2.7.62 Version/11.00")));
		
		$os=rand(0,2);
		$type=rand(0,count($list[$os])-1);
		$useragent=$list[$os][$type]['value'];
		//$agentBrowser = array('Firefox','Safari','Opera','Flock','Internet Explorer','Ephifany','AOL Explorer','Seamonkey','Konqueror','GoogleBot');
		//$agentOS = array('Windows 2000','Windows NT','Windows XP','Windows Vista','Redhat Linux','Ubuntu','Fedora','FreeBSD','OpenBSD','OS 10.5');
		//$useragent=$agentBrowser[rand(0,7)].'/'.rand(1,8).'.'.rand(0,9).' (' .$agentOS[rand(0,9)].' '.rand(1,7).'.'.rand(0,9).'; en-US;)';
		
		curl_setopt($this->curl, CURLOPT_USERAGENT, $useragent);
	}
	
	/**
	 * Установка cookie в cURL
	 * 
	 * @param string $cookie вида "параметр=значение"
	 */
	public function setcook($cookie){
		curl_setopt($this->curl, CURLOPT_COOKIE, $cookie);
	}
	
	/**
	 * Установка дополнительных параметров для cURL
	 * 
	 * @param string $opt название параметра
	 * @param string $value значение параметра 
	 */
	public function setopt($opt,$value){
		curl_setopt($this->curl, $opt, $value);
	}
	
	/**
	 * Поиск файла с кешем страницы
	 * 
	 * @param string $url закодированая строка url_encode
	 * 
	 * @return string содержимое кеша 
	 */
	private function getcache($url){
		if (strlen($url)>250)$url=substr(-250, 250);
		if (!file_exists($this->cache."/".$url)) return false;
		return file_get_contents($this->cache."/".$url);
	}
	
	/**
	 * Запись файла с кешем страницы
	 * 
	 * @param string $text содержание кеша
	 * @param string $url закодированая строка url_encode
	 * 
	 * @return bool
	 */
	private function putcache($text,$url){
		if (strlen($url)>250)$url=substr(-250, 250);
		if(file_put_contents($this->cache."/".$url, $text)) return true;
		else return false;
	}
	
	/**
	 * Закрытие handle cURL
	 * 
	 * @return bool
	 */
	public function curlclose(){
		curl_close($this->curl);
		return true;
	}
	
	/**
	 * Получение информации о последнем запросе
	 */
	public function info(){
		return curl_getinfo($this->curl);
	}
	
	/**
	 * Получение ошибки в последнем запросе
	 */
	public function error(){
		return curl_error($this->curl);
	}
	
	/**
	 * Инициализация PageFinder
	 * 
	 * @param string $proxy_ver
	 * @param string $cache
	 * @param string $cookie
	 * 
	 */
	function __construct($proxy_ver = null,$cache = null, $cookie = null) {
		// сохранение исходных параметров
		$this->proxy_src=$proxy_ver;
		$this->cache_src=$cache;
		$this->cookie_src=$cookie;
		// инициализация объекта для работы с прокси
		if ($this->proxy_src!==null) {
			switch ($this->proxy_src) {
				case 'dynamic':
					$this->proxy=new IpDynamic; 
					if ($this->proxy->status==TRUE) break;
				case 'static':
					$this->proxy=new IpStatic;
					if ($this->proxy->status==TRUE) break;
				case 'real':
					$this->proxy=new IpReal;
					if ($this->proxy->status==TRUE) break;
				default:
					return FALSE;
			}
		}
		// путь к папке с кешем
		$this->cache = 'pages/'.$this->cache_src.'/';
		// если такой нет то создаем
		if (!is_dir($this->cache) && $this->cache!==null) mkdir($this->cache, 0777);
		// определения cookie ID
		if($this->cookie_src===null)$this->cookie_src = Registry::get('user_id');
		// инициализация класса cURL
		$this->curl = curl_init();
		// инициализация cookie, если надо
		if(isset($this->cookie_src) && $this->cookie_src!==false){
			curl_setopt($this->curl, CURLOPT_COOKIEFILE, 'cookies/cookie_'.$this->cookie_src.'.txt');
			curl_setopt($this->curl, CURLOPT_COOKIEJAR, 'cookies/cookie_'.$this->cookie_src.'.txt');
		}
		// задание новой сессии для кук
		elseif ($this->cookie_src===false) curl_setopt($this->curl, CURLOPT_COOKIESESSION, 1);
		// отключение тунелирования при использовании настоящего ip адреса
		if($this->proxy_src!='real'){
			curl_setopt($this->curl, CURLOPT_HTTPPROXYTUNNEL, 0);
			curl_setopt($this->curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
		}
		curl_setopt($this->curl, CURLOPT_HEADER, 0);
		curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, WAITING_TIME);
		curl_setopt($this->curl, CURLOPT_TIMEOUT, WAITING_TIME);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_AUTOREFERER, 1);
		curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->curl, CURLOPT_MAXREDIRS, 30);
		// необходимо, чтобы cURL не высылал заголовок на ожидание
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Expect:')); 
		// выбор юзерагента
		$this->curl_useragent();
		// отключение проверки шифрования
		$this->curl_ssl(0);
	}

	/**
	 * Получение полученных куков
	 * 
	 * @param string $data строка с текстом страницы
	 * 
	 * @return bool
	 */
	public function getCookie($data){
		$header=substr($data,0,curl_getinfo($this->curl,CURLINFO_HEADER_SIZE));
		$body=substr($data,curl_getinfo($this->curl,CURLINFO_HEADER_SIZE));
		preg_match_all("/Set-Cookie: (.*?)=(.*?);/i",$header,$res);
		$cookie='';
		echo "<br/>#####Cookie_SET!#####<br/>";
		if(isset($res[1]))foreach ($res[1] as $key => $value) echo ($value.'='.$res[2][$key].'; ');
		echo "<br/>#####END Cookie_SET!#####";
		return true;
	}
	
	/**
	 * Получение страницы
	 * 
	 * @param string $url адрес страницы
	 * @param bool $cache_set надо ли использовать кеш для запроса
	 * @param string $referer откуда происходит переход
	 * @param bool $ip_change флаг смены адреса
	 * 
	 * @return string текст страницы
	 */
	public function getpage($url,$cache_set = true,$referer = null,$ip_change = null){
		// проверка на наличие кеша
		if($cache_set===true && $this->cache!==null){
			$cache=$this->getcache(urlencode($url));
			if($cache!=FALSE) return $cache;
		}
		// установка адреса
		curl_setopt($this->curl, CURLOPT_URL, $url);
		// установка рефера
		if(!is_null($referer))curl_setopt ($this->curl, CURLOPT_REFERER, $referer);
		$num=0;
		// старт попыток
		while(1){
			$num++;
			// после 10 попыток завершить метод
			if ($num==10) {
				Registry::get('db')->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",Registry::get('parse_id'),time(),0,$url,'10 empty');
				return false;
			}
			// установка настроек прокси
			if ($this->proxy!==null && $this->proxy->name!=='real') {
				if($ip_change==null) $this->proxy->getproxy();
				curl_setopt($this->curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
				curl_setopt($this->curl, CURLOPT_PROXY, $this->proxy->ip);
				curl_setopt($this->curl, CURLOPT_PROXYUSERPWD, $this->proxy->login);
			}
			// запрос
			$res = curl_exec($this->curl);
			// отметка об использовании
			$this->proxy->usage($url);
			/*var_dump($res);			
			echo "<br/>IP: ".$this->proxy->ip;
			echo "<br/>#####CURL INFO#####<br/>";
			var_dump(curl_getinfo($this->curl));
			echo "<br/>#####CURL INFO END#####<br/>";*/
			file_put_contents(LOGS_PATH.'lastpage_'.Registry::get('shops_id').'_'.Registry::get('user_id').'.html', $res);
			if(!$res){
				// удаление ip если запрос не прошёл
				if ($this->proxy->name=='dynamic'){$this->proxy->delete();continue;}
				//проверяем, если ошибка, то получаем номер и сообщение
				$error = curl_error($this->curl).'('.curl_errno($this->curl).')';
				if ($error!='(0)') file_put_contents(LOGS_PATH."curl.".date("d_m_Y",time()).".log", Registry::get('pid')." (".time()."): Ошибка при запросе страници. ".$error."\r\n", FILE_APPEND);
			}
			// елси появилась каптча
			elseif ((strpos($res, '/captcha/check-captcha.xml')!==false || strpos($res,'class="b-captcha"') !== false)&& $this->proxy!==null){
				Registry::get('db')->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",Registry::get('parse_id'),time(), Registry::get('pid'),$url,"Captcha");
				//file_put_contents(LOGS_PATH.'captcha_'.$this->proxy->ip, $res);
				if(isset($_GET['debug']) && $_GET['debug']==true){echo "<br>Captcha!: ".$this->proxy->ip." / ".$url."<br />".$res;exit;}
				$this->proxy->setbanned();
				$this->__construct($this->proxy_src,$this->cache_src,$this->cookie_src);
				if(Registry::get('region_id')==213 || Registry::get('region_id')==2 || Registry::get('region_id')==65)$this->setcook('yandex_gid='.Registry::get('region_id'));
				curl_setopt($this->curl, CURLOPT_URL, $url);
			}
			elseif (strpos($res, '404 - Not Found')!==false) Registry::get('db')->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",Registry::get('parse_id'),time(), Registry::get('pid'),$url,"404");
			elseif (strpos($res, 'Сервис перегружен')!==false || strpos($res, '502 Bad Gateway')!==false) {
				Registry::get('db')->query("INSERT INTO `log_errors` (`parse_id`,`time`,`title`,`url`,`value`) VALUES (?,?,?,?,?)",Registry::get('parse_id'),time(), Registry::get('pid'),$url,"server is overloaded");
				sleep(2);
			}
			elseif (strlen($res)>50){
				if($cache_set===true)$this->putcache($res, urlencode($url));
				break;
			}
		}
		//$this->getCookie($res);
		return $res;
	}	
}

?>