<?php
//调试信息
error_reporting(E_ALL ^ E_NOTICE);
ini_set("display_errors", "On");
//定义常量
define('APP_DEBUG', isOpenDebug());
define('APP_PATH', __DIR__ . '/');
define('LOG_PATH', __DIR__ .'/cache/');
define('WWW_PATH', realpath( __DIR__ .'/../') . '/');
define('SERVER_HOST','http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);
define('SERVER_PATH','http://'.$_SERVER['HTTP_HOST'].preg_replace('/[\\\\\/][^\\\\\/]*$/','',$_SERVER['REQUEST_URI']));
//header( "Content-Type: charset=utf-8");
//定义参数
$GLOBALS['CONFIG']	= array(
	'database'		=> array(
		'host'		=>	'127.0.0.1',
		'port'		=>	'3306',
		'table'		=>	'table',
		'user'		=>	'root',
		'pass'		=>	'root',
	),
	'template'		=> array(
		'path'		=>	'./template/',	//默认目录
		'miss'		=>	'error.html',	//错误页面
		'replece'	=>	array(),		//替换内容
	)
);
//载入参数
if(is_file($file=APP_PATH.'conn.php')||is_file($file=APP_PATH.'config.php')){
	$GLOBALS['CONFIG']=array_merge($GLOBALS['CONFIG'],require($file)); 
}
//默认时区设置
date_default_timezone_set('PRC');
//类库载入配置
function __autoload($name){
	if (is_file($path = APP_PATH.'/lib/'.$name.'.php') || is_file($path = APP_PATH . $name.'.php') || is_file($path = APP_PATH .'_'. $name.'.php') || is_file($path = './'. $name.'.php') ){
		require_once $path;
	}
}
//数据库
function db($name=''){
	static $db;
	if(is_null($db))$db = new Query();
	if(strstr($name,' ')){
		return $db->parm( func_get_args() , 1 )->query($name);
	}else{
		return $db->table($name);
	}
}
//重定向
function redirect($url, $obj=null, $deep=false, $time='0'){
	header("Refresh: $time; url=".(isset($url) ? url($url, $obj, $deep) : $_SERVER['REQUEST_URI']));
}
//写入函数
function write($dir, $con = ''){
	$info = pathinfo($dir);
	if (!is_dir($info['dirname'])) {
		$arr = explode('/', $info['dirname']);
		foreach ($arr as $str) {
			$aimDir .= $str.'/';
			if (!file_exists($aimDir)) {
				mkdir($aimDir);
			}
		}
	}
	return file_put_contents($dir, $con);
}
//COOKIE
function cookie($name, $val = '', $date = 25920000){
	if ($val === null)
		$date = -1;
	if ($val === ''){
		if(substr($_COOKIE[$name], 0, 1) == '{'){
			return json_decode($_COOKIE[$name], true);
		}else{
			return isset($_COOKIE[$name])?$_COOKIE[$name]:null;
		}
	}
	return setcookie($name, is_array($val) ? json_encode($val) : $val, is_numeric($date) ?$date + $_SERVER['REQUEST_TIME'] : 0, '/');
}
//SESSION
function session($name=null, $val = ''){
	if(!isset($_SESSION)){
		session_name('SID');
		session_start();
		if($_SESSION['token']!==$_SERVER['HTTP_USER_AGENT']){
			$_SESSION=array();
		}
	}
	if(is_string($name)){
		if (strpos($name, '.')) {
			$n = explode('.', $name);
			if ('' === $val){
				return $_SESSION[$n[0]][$n[1]];
			}else{
				$_SESSION[$n[0]][$n[1]] = $val;
			}
		} else {
			if ('' === $val){
				return $_SESSION[$name];
			}else{
				$_SESSION[$name] = $val;
			}
		}
	}
	$_SESSION['token']=$_SERVER['HTTP_USER_AGENT'];
	return $_SESSION;
}
//生成URL
function url($url, $obj, $deep=false){
	if($url===true){
		$url = current(explode('?',$_SERVER['REQUEST_URI']));
	}
	if(is_array($url)){
		$deep = $obj;
		$obj = $url;
		$url = '';
	}
	if(is_array($obj)){
		if($deep)$obj = array_merge($_GET,$obj);
		$an='?';
		foreach ($obj as $k => $v){
			if(is_string($v)||is_numeric($v)){
				$url .= $an .$k.'='.urlencode($v);
				$an='&';
			}
		}
	}
	return $url;
}
//文件缓存控制
function cache($name, $val = '', $expire = 0){
	$path = LOG_PATH.'logs/'.md5($name). '.log';
	if (is_null($val)) {
		if (is_file($path))unlink($path);
	} elseif ($val === '' && is_file($path) && is_array($con = unserialize(file_get_contents($path))) && ($con['expire'] === 0 || $con['expire'] > $_SERVER['REQUEST_TIME']) ) {
		return $con['data'];
	}
	if(!is_dir(LOG_PATH.'logs/'))mkdir(LOG_PATH.'logs/');
	if(!is_array($val) && is_object($val))$val = $val($name);
	write($path, serialize(array('expire' => $expire?$_SERVER['REQUEST_TIME'] + $expire:0 , 'data'=> $val )));
	return $val;
}
//数据操作类
class Query{
	//配置
	public $config=array(
		'type'		=> 'mysql',
		'host'		=> '127.0.0.1',
		'port'		=> '3306',
		'user'		=> 'root',
		'pass'		=> 'root',
		'table'		=> 'table',
		'prefix'	=> '',
		'charset'	=> 'utf8',
	);
	public $conn;
	public $sqls	= array();
	public $data	= array();
	public $clear	= true;
	public $debug	= true;
	public $options	= array();
	public function __construct($config=array()){
		$this->connect($this->config=array_merge($this->config,$GLOBALS['CONFIG']['database'],$config));
		define('DBPREFIX',$this->config['prefix']);
	}
	public function connect($config){
		try {
			$this->conn = new PDO($config['type'].':host='.$config['host'].':'.$config['port'].';dbname='.$config['table'],$config['user'],$config['pass']);
			$this->conn->exec('SET character_set_connection='.$config['charset'].', character_set_results='.$config['charset'].', character_set_client=binary');
		} catch (PDOException $e){
			trigger_error('数据库连接错误: '.$e->getMessage()." on line ".$e->getLine(),E_USER_ERROR);
		}
		return $this;
	}
	public function query($sql){
		try{
			if(APP_DEBUG){
				$queryStart=microtime(true);
			}
			if(isset($this->template[$sql])){
				$sql=$this->template[$sql];
			}
			$sql=$this->buildSql($sql);
			if($this->clear){
				$this->options=array();
			}else{
				$this->clear=true;
			}
			if(preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE|VALUE|FIND)(.+)$/is',$sql,$match)){
				$action=strtoupper($match[1]);
				if(in_array($action,array('SELECT','FIND','VALUE'))){
					$sql='SELECT'.$match[2];
					$sth=$this->conn->query($sql);
					if($sth){
						$result=$sth->fetchAll(PDO::FETCH_ASSOC);
						if(is_array($result)){
							if(isset($result[0]['methodback'])){
								$result=$result[0]['methodback'];
							}elseif($action=='FIND'){
								$result=$result[0];
							}elseif($action=='VALUE'){
								$result=array_shift($result[0]);
							}
						}
					}else{
						$result=null;
					}
				}else{
					$result=$this->conn->exec($sql);
					if('INSERT'==$action){
						$result=$this->conn->lastInsertId();
					}
				}
			}elseif($sth=$this->conn->query($sql)){
				$result=$sth->fetchAll(PDO::FETCH_ASSOC);
			}
			if(APP_DEBUG){
				$GLOBALS['querySqls'][]='['.sprintf("%.9f",(microtime(true)-$queryStart)*1000 ).':'.(is_numeric($result)?$result:count($result)).'] '.$sql;
				if ($this->conn->errorCode() != '00000'){
					$err=$this->conn->errorInfo();
					$err[]=$sql;
					dump($err);
					trigger_error('数据库运行错误 : '.$sql.$err[2],E_USER_ERROR);
				}
			}else{
				if ($this->conn->errorCode() != '00000' && function_exists('errorBar')){
					$err=$this->conn->errorInfo();
					errorBar(3306,$err[2],$sql,$err[0],$err);
				}
			}
			return $result;
		}catch (PDOException $e) {
			trigger_error('数据库操作错误: '.$e->getMessage()."行号: ".$e->getLine(),E_USER_ERROR);
		}
	}
	public function table($table){
		$this->options['table'] = $this->config['prefix'].$table;
		return $this;
	}
	public function field($field=null){
		if($field)$this->options['field']=$field;
		return $this;
	}
	public function limit($limit=null,$value=null){
		if(is_null($value)){
			$this->options['limit']=$limit;
		}else{
			$this->options['limit']=$limit.','.$value;
		}
		return $this;
	}
	public function order($order=null,$value=null){
		if(strpbrk($order,'( )')){
			$this->options['order']= $order ;
		}elseif(is_null($value)){
			$this->options['order']='`'.$order.'`';
		}else{
			$this->options['order']='`'.$order.'` '.$value;
		}
		return $this;
	}
	public function page($sub=10,&$page,$size=10){
		$this->clear=false;
		$page= self::pagelist($sub,$this->count(),$size);
		$this->limit($page['limit']);
		return $this;
	}
	public function save($data=null,$key='id'){
		$this->options['data']=$data;
		return $this->query('save');
	}
	public function data($data=null){
		if(!empty($data))$this->options['data']=$data;
		return $this;
	}
	public function insert($data=null,$filter=false){
		if($filter)$this->$filter();
		if(!empty($data))$this->options['data']=$data;
		return $this->query('insert');
	}
	public function update($data=null,$field=null,$filter=false){
		if($filter === true||$field===true)$this->$filter();
		if(!empty($data)){
			$field=is_string($field)?$field:'id';
			if(isset($data[$field])&&empty($this->options['where'])){
				$this->where($data[$field]);
				unset($data[$field]);
			}
			$this->options['data']=$data;
		}
		return $this->query('update');
	}
	public function count($field=null){
		return $this->method('COUNT',$field);
	}
	public function max($field=null){
		return $this->method('MAX',$field);
	}
	public function min($field=null){
		return $this->method('MIN',$field);
	}
	public function avg($field=null){
		return $this->method('AVG',$field);
	}
	public function sum($field=null){
		return $this->method('SUM',$field);
	}
	public function method($method,$field=null){
		if($field)$this->options['field']=$field;
		$this->options['method']=$method;
		return $this->query('method');
	}
	public function value($field=null,$name=null,$action=null,$value=null){
		if($field)$this->options['field']=$field;
		if($name)$this->where($name,$action,$value);
		$result=$this->query('find');
		return array_shift($result[0]);
	}
	public function find($name=null,$action=null,$value=null){
		if($name)$this->where($name,$action,$value);
		$result=$this->query('find');
		return $result[0];
	}
	public function select($name=null,$action=null,$value=null){
		if($name)$this->where($name,$action,$value);
		return $this->query('select');
	}
	public function delete($name=null,$action=null,$value=null){
		if($name)$this->where($name,$action,$value);
		if($this->options['where'])return $this->query('delete');
	}
	public function parm($arr,$index=0){
		for($index ; $index < count($arr);$index++){
			$this->options['parms'][]=$arr[$index];
		}
		return $this;
	}
	public function filter($list=null){
		if(is_array($list)){
			$this->options['filter']=$list;
		}else{
			$this->clear=false;
			$filter=$this->query("SHOW COLUMNS FROM `{$this->options['table']}`;");
			$this->options['filter']=array();
			foreach($filter as $key=>$val){
				$this->options['filter'][]=$val['Field'];
			}			
		}
		return $this;
	}
	public function whereOr($name=null,$action=null,$value=null){
		$this->where('OR',$name,$action,$value);
		return $this;
	}
	public function where($logic=null,$name=null,$action=null,$value=null){
		$arg=is_array($name)?$name:func_get_args();
		if(!(is_string($arg[0])&&in_array($arg[0],$this->operator))){
			array_unshift($arg,'AND');
		}
		if(is_numeric($arg[1])){
			$str= '`id` = '.$arg[1];
		}elseif(is_string($arg[1])){
			if(is_string($arg[2])||is_numeric($arg[2])){
				if(isset($arg[3])&&in_array($arg[2],array('not in','not like','NOT IN','NOT LIKE','in','like','IN','LIKE','<','>','<>','!=','='))){
					$arg[2]=strtoupper($arg[2]);
					if('IN'==$arg[2]){
						$str='`'.$arg[1].'` IN ('.$this->joinEscape($arg[3]).')';
					}elseif('LIKE'==$arg[2]){
						$str='`'.$arg[1].'` LIKE '.$this->escape($arg[3]);
					}else{
						$str='`'.$arg[1].'` '.$arg[2].' '.$this->escape($arg[3]);
					}
				}elseif(strpos($arg[1],'|')){
					$arg2=$this->escape($arg[2]);
					$str= '(`'.str_replace('|','` = '.$arg2.' OR `',$arg[1]).'` = '.$arg2.')';
				}else{
					$str= '`'.$arg[1].'` = '.$this->escape($arg[2]).'';
				}
			}elseif(is_null($arg[2])){
				if(strpos($arg[1],'OR')||strpos($arg[1],'AND')){
					$str= '('.$arg[1].')';
				}else{
					$str= $arg[1];
				}
			}
		}
		if(!isset($str)){
			$arr=array();
			foreach($arg as $k=>$v){
				if(is_array($v)){
					$arr[]=$this->where('CALLBACK',$v);
				}elseif(!is_int($k)){
					$arr[]=$this->where('CALLBACK',array($arg[0],$k,$v));
				}
			}
			if(count($arr)>0){
				$str=''.implode($arr,' '.strtoupper($arg[0]).' ').'';
				if($this->options['where']&&count($arr)>1){
					$str='('.$str.')';
				}
			}
		}
		if('CALLBACK'==$logic){
			return $str;
		}
		if($str){
			$logic=is_string($logic)&&in_array($logic,$this->operator)?strtoupper($logic):'AND';
			if(isset($this->options['where'])){
				if(strpos($this->options['where'],' OR ')){
					$this->options['where']='('.$this->options['where'].') '.$logic.' '.$str;
				}elseif($this->options['where']){
					$this->options['where'].=' '.$logic.' '.$str;
				}
			}else{
				$this->options['where'] = $str;
			}
		}
		return $this;
	}
	static public function escape($str){
		if(is_int($str)||(is_numeric($str)&&intval($str)==$str)){
			return $str;
		}elseif(is_string($str)){
			return "'".@mysql_escape_string($str)."'";
		}elseif(is_bool($str)){
			return @mysql_escape_string($str);
		}elseif(is_null($str)){
			return 'NULL';
		}else{
			return "'".@mysql_escape_string($str)."'";
		}
	}
	protected function joinEscape($arr){
		if(is_string($arr)){
			$arr=explode(',',$arr);
		}
		foreach($arr as &$v)$v=$this->escape($v);
		return implode(',',$arr);
	}
	protected function buildSql($sql=''){
		if(!empty($this->options['parms'])){
			$sql=preg_replace_callback('/\?/',array($this,'parmReplacePattern'),$sql);
		}
		$sql=preg_replace_callback('/(\s)?%([a-z]+?)%/',array($this,'buildPattern'),$sql);
		$sql=str_replace('DBPREFIX_',$this->config['prefix'],$sql);
		$this->sqls[]=$sql;
		return $sql;
	}
	protected function parmReplacePattern($a){
		if(!empty($this->options['parms'])){
			return $this->escape(array_shift($this->options['parms']));
		}
		return $a[0];
	}
	protected function buildPattern($a){
		switch($a[2]){
			case 'where' :
				if(isset($this->options['where'])){
					return $a[1].'WHERE '.$this->options['where'];
				}
				break;
			case 'table' :
				if(isset($this->options['table'])){
					return $a[1].$this->options['table'];
				}
				break;
			case 'value':
				if(is_array($this->options['data'])){
					$keyStr=$valstr=array();
					foreach($this->options['data'] as $key=>$val){
						if(isset($this->options['filter'])&&!in_array($key,$this->options['filter']))continue;
						array_push($keyStr,'`'.$key.'`');
						array_push($valstr,$this->escape($val));
					}
					return $a[1].'('.implode(',',$keyStr).')VALUE('.implode(',',$valstr).')';
				}elseif(is_string($this->options['data'])){
					return $a[1].$this->options['data'];
				}
				break;
			case 'set':
				if(is_array($this->options['data'])){
					$valstr=array();
					foreach($this->options['data'] as $key=>$val){
						if(isset($this->options['filter'])&&!in_array($key,$this->options['filter']))continue;
						$valstr[]= '`'.$key.'` = '.$this->escape($val);
					}
					return $a[1].implode(' , ',$valstr).'';
				}elseif(is_string($this->options['data'])){
					return $a[1].$this->options['data'];
				}
				break;
			case 'field':
				if(isset($this->options['field']) && '*'!==$this->options['field']){
					return $a[1].$this->options['field'];
				}else{
					return $a[1].'*';
				}
				break;
			case 'limit':
				if(isset($this->options['limit'])){
					return $a[1].'LIMIT '.$this->options['limit'];
				}
				break;
			case 'order':
				if(isset($this->options['order'])){
					return $a[1].'ORDER BY '.$this->options['order'];
				}
				break;
			case 'method':
				if(isset($this->options['method'])){
					return $a[1].$this->options['method'];
				}
				break;
		}
		return '';
	}
	//生成翻页
	static public function pagelist($arr, $count, $size){
		if(!is_array($arr))$arr=array('sub'=>$arr,'size'=>10);
		$arr['count'] = $count; //共多少条
		if(isset($_GET['page'])){
			$arr['index'] = $_GET['page'];
		}else{
			$arr['index'] = 1;
		}
		$div = ceil($arr['count'] / $arr['sub']);
		if($arr['index']<0){
			$arr['index']=$div+$arr['index']+1;
		}
		$star = $arr['index'] - floor($arr['size'] / 2);
		if ($div < 1) {
			$div = 1;
		}
		$sa = explode('PAGEINDEX', url(array('page' => 'PAGEINDEX'), true));
		$ha = array('<a title="第', '页" target="_self" href="'.$sa[0], $sa[1].'"', '>', "</a>\r\n");
		if ($star > $div - $arr['size']) {
			$star = $div - $arr['size'] + 1;
		}
		$arr['limit'] = (($arr['index'] - 1) * $arr['sub']).','.$arr['sub'];
		$arr['html'] = "\r\n".$ha[0].'1'. $ha[1]  .'1'. $ha[2].$ha[3].(($star < 1 && $star =  1) ? '首页' : '首页…').$ha[4];
		for ($i = $star; $i < $star + $arr['size'] && $i <= $div ; $i++) {
			$arr['html'] .= $ha[0].$i .$ha[1].$i.$ha[2].($i == $arr['index'] ? ' class="pagelist_checked"' : '').$ha[3].$i.$ha[4];
		}
		$arr['html'] .= $ha[0].$div.$ha[1].$div.$ha[2].$ha[3].($i <= $div ?'…尾页' : '尾页').$ha[4];
		return $arr;
	}
	//debug
	public function sql(){
		return $this->sqls;
	}
	//清理机制
	public function __destruct(){
		$this->conn=null;
	}
	//运算符
	protected $operator = array('or','and','not','OR','AND','NOT');
	//模版
	protected $template =	array(
		'save'		=>	"INSERT INTO %table% %value% ON DUPLICATE KEY UPDATE %set%;",
		'find'		=>	"SELECT %field% FROM %table% %where% %order% LIMIT 1;",
		'method'	=>	"SELECT %method%(%field%) AS methodback FROM %table% %where% %limit%;",
		'select'	=>	"SELECT %field% FROM %table% %where% %order% %limit%;",
		'insert'	=>	"INSERT INTO %table% %value% %comment%;",
		'update'	=>	"UPDATE %table% SET %set% %where%;",
		'delete'	=>	"DELETE FROM %table% %where% %order% %limit%;",
	);
}
class Template{
	public $path	= './template/';
	public $miss	= 'error.html';
	public $data	= array();
	public $tpls	= array();
	public $incPic	= 0;
    public $litObj	= array();
	public $replace	= array();
	public $top='<?php if (!defined("APP_PATH")) exit(); ?>';
    public function __construct(){
		foreach($GLOBALS['CONFIG']['template'] as $k => $v)$this->{$k} = $v;
	}
	static public function view($name=null,$value=array()){
		$temp = new self;
		if(!empty($value)){
			$temp->data=array_merge($temp->data,$value);
		}
		echo $temp->display($name);
	}
	public function display($name){
		$this->tmp = $this->getTemplateDir($name);
		if(is_array($this->data))extract($this->data);
		ob_start();
		include $this->tmp;
		$this->html = ob_get_clean();			
		if(!empty($this->replace)){
			$this->html = str_replace(array_keys($this->replace), $this->replace, $this->html);
		}
		if (isset($GLOBALS['shtml'])){
			$this->write($GLOBALS['shtml'], $this->html);
		}
		return $this->html;
    }
	public function assign($name,$value=null){
		if(is_array($name)){
			$this->data=array_merge($this->data,$name);
		}else{
			$this->data[$name]=$value;
		}
		return $this;
	}
	public function getTemplateDir($name){
		$this->tmp = LOG_PATH . 'temp/' . md5( $this->path . $name ) . '.php'; 
		if ($this->isRenew($this->tmp)){
			$tpl=$this->getTemplate($name);
			//生成更新时间
			$topIncText='';
			foreach($this->tpls as $v)$topIncText.= $v.'|'.filemtime($v).'|';
			$this->top.='<?php /*['.$topIncText.date('YmdHis',$_SERVER["REQUEST_TIME"]).']*/ ?>';
			//解析数据
			$tpl['con']=$this->parse($tpl['con']);
			$this->write($this->tmp,$tpl['con']);			
		}
		return $this->tmp;
	}
	protected function isRenew($tmp){
		if(APP_DEBUG)return true;
		if(!is_file($tmp))return true;
		$f= fopen($tmp,"r");
		$line = fgets($f);
		fclose($f);
		if(preg_match('/\/\*\[(.+?)\]\*\//',$line,$reg)){
			$li=explode('|',$reg[1]);
			for($i=0 ; $i < count($li) ; $i+=2){
				if(isset($li[$i+1])&&(!is_file($li[$i])||filemtime($li[$i])!=$li[$i+1]))return true;
			}
		}
		return false;
    }
	public function getTemplate($tpl){
		if($this->incPic++ > 100){
			trigger_error('存在死循环',E_USER_ERROR);
		}
		$tpl = parse_url($tpl);
		$tpl['dir'] = $this->getPath($tpl);
		$GLOBALS['templates'][]=$tpl['dir'];
		$this->tpls[]=$tpl['dir'];
		if(empty($tpl['con'])){
			$tpl['con']=file_get_contents($tpl['dir']);
		}
		$tpl['con']=preg_replace_callback('/\{(inc)\b(.*?)(\/?)\}/',function($a){
			if('inc'==$a[1]){
				$tpl=$this->getTemplate(trim($a[2]));
				return $tpl['con'];
			}
			return $a[0];
		},$tpl['con']);
		$tpl['con']=preg_replace('/^[\W\w]+?return.*view\W*__FILE__\W*?;/','<?php',$tpl['con']);
		/**** query ****/
		if(isset($tpl['query'])){
			parse_str($tpl['query'],$tpl['item']);			
			foreach ($tpl['item'] as $key=>$val) {
				if('children'==$key){
					$tpl['con']=preg_replace_callback('/\{__(\w+)__\}/',function($a){
						if('CONTENT'==$a[1]){
							return $this->getTemplate($tpl['item']['children'])['con'];
						}elseif(preg_match('/<'.$a[1].'.*?>([\W\w]*?)<\/'.$a[1].'>/',$this->getTemplate($tpl['item']['children'])['con'],$li)){
							return $li[1];
						}
					},$tpl['con']);					
				}elseif('tag'==$key){
					if(preg_match('/<'.$val.'.*?>([\W\w]*?)<\/'.$val.'>/',$tpl['con'],$li)){
						$tpl['con']=$li[1];
					}				
				}else{
					$tpl['con']=str_replace($key,$val,$tpl['con']);
				}
			}
		}
		/**** query ****/		
        return $tpl;
    }
	public function getPath($tpl){
		if(is_file($tpl['path'])){
			return $tpl['path'];
		}elseif(substr($tpl['path'], 0, 1 )=='/'){
			$tpl['path'] = WWW_PATH . substr ($tpl['path'],1);
		}else{
			$tpl['path'] = $this->path . $tpl['path'];
		}
        if (is_file($file = $tpl['path'] . '.html')|| is_file($file = $tpl['path'] . '.php')|| is_file($file = $tpl['path'])) {
            return $file;
        } elseif (is_file($file =  $this->path . $this->miss)){
			$this->tmp=LOG_PATH . 'temp/error.php';
			return $file;
		} else {
			header("HTTP/1.1 501 Not Implemented"); 
            trigger_error('模版( ' . $tpl['path'] . ' )不存在',E_USER_ERROR  );
        }
    }
    public function parse($content){
		//literal 
		$content=preg_replace_callback('/\{(literal)\s*?\}([\W\w]*?)\{\/\1\s*?\}|<\?php[\W\w]*?\?>|<(script|style)\b.*?>([\W\w]*?)<\/\4\s*>/',function($a){
			if(isset($a[4])&&empty($a[5]))return $a[0];
			$index=count($this->litObj);
			$this->litObj[$index]=empty($a[1])?$a[0]:$a[2];
			return 'literalStart'.$index.'literalEnd';
		},$content);
		//tag
		$content=preg_replace_callback('/\{(\/?\w+)\b(.*?)(\/?)\}/',function($a){
				if(in_array($a[1],['/if','/for','/volist','/empty','/switch']))return '<?php } ?>';
				$a[2]=trim($a[2]);
				switch($a[1]){	
					case 'volist':
						return '<?php foreach('.$a[2].'){ ?>';
					case 'for':
						return '<?php '.(stripos( $a[2] ,' as ')?'foreach':'for').'('.$a[2].'){ ?>';
					case 'if':
						return '<?php if('.$a[2].'){ ?>';
					case 'elseif':
						return '<?php }elseif('.$a[2].'){ ?>';
					case 'else':
						return '<?php }else{ ?>';
					case 'case':
						return '<?php case '.$a[2].': ?>';
					case 'break':
						return '<?php break; ?>';
					case 'default':
						return '<?php default: ?>';
					case 'switch':
						return '<?php switch('.$a[2].'){ ?>';
					case 'empty':
						return preg_replace('/^(\!?)(.+)/','<?php if($1empty($2))'.('/'==$a[3]?'':'{').' ?>',$a[2]);
					default:
						return $a[0];
				}
			},$content);
		//echo
		$content=preg_replace_callback('/\{(\$|\:|\w+\(|\/\/|[A-Z0-9_]+)([\W\w]*?)\}/',function($a){
				if('$'==$a[1]){
					if(strpos($a[2],'|')){
						if(preg_match('/^(.*?)(\|+)(.*?)(\=(.+))?$/',$a[2],$li)){
							if('||'==$li[2]){
								return '<?php echo(empty('.$a[1].$li[1].')?'.$li[3].':'.$a[1].$li[1].'); ?>';
							}elseif($li[4]){
								return '<?php echo('.$li[3].'('.str_replace('###',$a[1].$li[1],(strpos($li[4],'###')?$li[5]:'###,'.$li[5])).')); ?>';
							}elseif('date'==$li[3]){
								return '<?php echo('.$li[3].'("Y-m-d h:i:s",'.$a[1].$li[1].')); ?>';
							}else{
								return '<?php echo('.$li[3].'('.$a[1].$li[1].')); ?>';
							}
						}
					}else{
						return '<?php echo('.$a[1].$a[2].'); ?>';
					}
				}elseif(':'==$a[1]){
					return '<?php echo('.$a[2].'); ?>';
				}elseif(substr($a[1],-1)=='('){
					return '<?php echo('.$a[1].$a[2].'); ?>';
				}elseif(empty($a[2])){
					return '<?php echo(defined("'.$a[1].$a[2].'")?'.$a[1].$a[2].':"'.$a[0].'"); ?>';
				}elseif('//'==$a[1]){
					return '';
				}
				return $a[0];
			},$content);
		//str
		$content=preg_replace_callback('/\{str(.*?)\}([\W\w]+?)\{\/str\}/',function($a){
				return '<?php '.trim($a[1]).'="'.addslashes(trim($a[2])).'"; ?>';
			},$content);
		// /**/
		$content=preg_replace_callback('/\{\/\*[\W\w]+\*\/\}/',function($a){
				return '';
			},$content);
		//shtml
		$content=preg_replace_callback('/\{shtml(.*?)\}/',function($a){
				$this->top.='<?php shtml('.trim($a[1]).'); ?>';
				return '';
			},$content);
		//$.
		$content=preg_replace_callback('/(\$\w+)\.([\w\.]*\w)/',function($a){
				return $a[1]."['".str_replace('.',"']['",$a[2])."']";
			},$content);
		//原样输出
		$content=preg_replace_callback('/literalStart(\d{1,3})literalEnd/',function($a){
				return isset($this->litObj[$a[1]])?$this->litObj[$a[1]]:$a[0];
			},$content);
		//清多行
		$content=preg_replace('/(\n\s*<\?php.+?\?>)\s*\n\s*/','$1',$this->top.$content);
		//去多行
		//$content=preg_replace('/[\r\n]\s+/',"\r\n",$content);
		//去标签
		$content=str_replace('?><?php ','',$content);
		$content=trim($content);
		return $content;
    }
	static public function shtml($time = 0, $path = null){
		if(APP_DEBUG)return;
		$GLOBALS['shtml'] =  LOG_PATH . 'html/' . md5($_SERVER['SERVER_NAME'] . ( is_string($path) ? $path : $_SERVER['REQUEST_URI'] )) . '.html';
		if (is_file($GLOBALS['shtml'])){
			if(!is_null($time)&&($time == 0 || filemtime($GLOBALS['shtml']) + $time > $_SERVER['REQUEST_TIME'] ) ) {
				exit(file_get_contents($GLOBALS['shtml']));
			}else{
				unlink($GLOBALS['shtml']);
			}
		}
    }
	public function write($dir, $con = '', $type = "w"){
        $path = pathinfo($dir)['dirname'];
        if (!is_dir($path)) {
            $arr = explode('/', $path);
            foreach ($arr as $str) {
                $aimDir .= $str . '/';
                if (!file_exists($aimDir)) {
                    mkdir($aimDir);
                }
            }
        }
        return file_put_contents($dir, $con);
    }
}
//应用架构
class Route{
	public $mca;
	public $config = array(					
		'miss'   	=>	'miss',							//空控制器
		'spacer'   	=>	'-',							//分类结构
		'modules'  	=>	array('web', 'm' , 'admin'),	//允许的模块
		'denies'	=>	array('app','pubilc','static'),	//禁止模快
		'indexs' 	=>	array('web','index','index'),	//默认控制器
	);
	//初始化
	public function init(){
		//获取目录
		if(isset($_SERVER['PATH_INFO'])){
			$this->mca = explode('/',strtolower(preg_replace('/\..*/', '',trim($_SERVER['PATH_INFO'],'/'))));
			//默认模块补全
			if (!in_array($this->mca[0], $this->config['modules'])) {
				array_unshift($this->mca, $this->config['indexs'][0]);
			}
		}else{
			$this->mca = $this->config['indexs'];
		}
		//采集请求
		if(isset($_GET['m']))$this->mca[0]=$_GET['m'];
		if(isset($_GET['c']))$this->mca[1]=$_GET['c'];
		if(isset($_GET['a']))$this->mca[2]=$_GET['a'];
		//联想默认
		if(empty($this->mca[0]))$this->mca[0] = $this->config['indexs'][0];
		if(empty($this->mca[1]))$this->mca[1] = $this->config['indexs'][1];
		if(empty($this->mca[2]))$this->mca[2] = $this->config['indexs'][2];
	}
	//生成参数
	function getRequest(){
		$i=count($this->mca);
		$GLOBALS['request']=$this->mca;
		while($i>2&&isset($this->mca[--$i])&&isset($this->mca[--$i])){
			$GLOBALS['request'][$this->mca[$i]]=$this->mca[$i+1];
		}
		return $GLOBALS['request'];
	}
	function display(){
		//禁止访问目录设置||防止SSX攻击
		if(in_array($this->mca[1],$this->config['denies'])||preg_match('/[^\w\-]/',implode($this->mca))){
			header("HTTP/1.1 501 Not Implemented");  
			trigger_error('非法输入',E_USER_ERROR);
			return '';
		}
		//控制器
		if((class_exists($control =  $this->mca[0] . '\\' . ucfirst($this->mca[1]))&&$shx=$this->mca[2]) || (class_exists($control =  $this->mca[0] . '\\'.$this->config['miss'])&&$shx=$this->mca[1])) {
			$class = new $control($message);
			if (method_exists($class, $shx) || method_exists($class, $shx = $this->config['miss'])) {
				//执行控制器					
				$class->{$shx}(isset($GLOBALS['request'])?$GLOBALS['request']:$this->mca[0]);
				return '';
			}
		}
		$fileArr=array(
			$this->mca[0].$this->config['spacer'].$this->mca[1].$this->config['spacer'].$this->mca[2],
			$this->mca[0].$this->config['spacer'].$this->mca[1],
			$this->mca[1].$this->config['spacer'].$this->mca[2],
			$this->mca[1],
			$this->mca[0]
		);
		//初始化模板引擎
		$template = new Template;
		foreach($fileArr as $v){
			if(is_file($file=$template->path.$v.'.php')||is_file($file=$template->path.$v.'.html')){
				//返回内容
				return $template->display($file);
			}
		}
		//返回错误页
		return $template->display($template->miss);
	}
}
/*
*
*
*
***************  以下是常用函数  ****************
*
*
*
*/
/*****************  系统调试  ********************/
//错误记录
set_error_handler('Mistake::errorBar');
//错误打印
register_shutdown_function('Mistake::fatalBar');
//错误处理机制
class Mistake{
	static function errorBar($errno,$errstr,$errfile,$errline,$varg){
		if(!in_array($errno,[ E_NOTICE , 8192 ])){
			$path = __DIR__ .'/log/errorLog '. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log';
			$info = pathinfo($path);
			if (!is_dir($info['dirname'])) {
				$aimDir='';
				$arr = explode('/', $info['dirname']);
				foreach ($arr as $str)if (!file_exists($aimDir.= $str.'/'))mkdir($aimDir);
			}
			$date = date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);
			$type = self::getType($errno);
			$text = "# {$date} {$type} {$errstr} in {$errfile} on line {$errline} by http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} ;\r\n";
			$hand = fopen($path,'a+');
			fwrite($hand,$text);
			fclose($hand);
		}
		return false;
	}
	static function fatalBar(){
		$e = error_get_last();
		if(!empty($e)&&is_file($e['file'])&&in_array($e['type'], array ( E_ERROR,E_PARSE,E_CORE_ERROR,E_CORE_WARNING,E_COMPILE_ERROR,E_COMPILE_WARNING)) ){
			$txt = '<ol class="debug-fatal" style="margin:10px auto;padding:5px 5px 5px 50px;border:#666 solid 2px;white-space:pre-wrap;background:#eee;color:#666;list-style-type:decimal;line-height:1.6em;" start="'.($e['line']-5).'">';
			$bar = fopen($e['file'], "r");
			for($i = 1;!feof($bar) && $i < $e['line']+6 && $row=fgets($bar);$i++){
				if( $i+6 > $e['line'])$txt.='<li style="background:#fcfcfc;'.($i==$e['line']?'color:#f00;':'').'">'.htmlspecialchars($row).'</li>';
			}
			$txt.='</ol>';
			fclose($bar);
			echo $txt;		
		}
	}
	static function getType($errno){
		$errortype 	= array ( E_ERROR => '错误(Error)', E_WARNING => '警告(Warning)', E_NOTICE => '通知(Notice)', E_PARSE => '解析错误(Parsing Error)', E_CORE_ERROR => '核心错误(Core Error)', E_CORE_WARNING => '核心警告(Core Warning)', E_COMPILE_ERROR => '编译错误(Compile Error)', E_COMPILE_WARNING => '编译警告(Compile Warning)', E_USER_ERROR => '用户错误(User Error)', E_USER_WARNING => '用户警告(User Warning)', E_USER_NOTICE => '用户通知(User Notice)', E_STRICT => '运行时通知(Runtime Notice)', E_RECOVERABLE_ERROR => '开捕的致命(Catchable Fatal)' );
		return isset($errortype[$errno])?$errortype[$errno]:$errno;
	}
}
//计算毫秒数
function consuming($name='window',$round=null){
	static $db=array();
	if(is_int($name)){
		$round=$name;
		$name='window';
	}
	$mt=microtime(true);
	if('window'==$name){
		$time=$mt-$_SERVER['REQUEST_TIME_FLOAT'];
	}else{
		$time=$mt-(isset($db[$name])?$db[$name]:$_SERVER['REQUEST_TIME_FLOAT']);
		$db[$name]=$mt;
	}
	return is_int($round)?round($time*1000,$round).' ms':$time;
}
//获取调试信息
function getTrace($arg=array()){
	$trace=array();
	foreach($arg as $k=>$v)$trace['VAR'.$k]=$v;
	$trace['PATH'] = $_SERVER['REQUEST_URI'];
	$trace['FROM'] = $_SERVER['HTTP_REFERER'];
	$trace['SQLS'] = $GLOBALS['querySqls'];
	$trace['TPLS'] = $GLOBALS['templates'];
	$trace['DATA'] = dejson();
	$trace['POST'] = $_REQUEST;
	$trace['HOST'] = $_SERVER;
	$trace['TIME'] = consuming('window',3);
	foreach($trace as $k => $v)if(empty($trace[$k]))unset($trace[$k]);
	return $trace;
}
//计算运行时间
function runTime(){
	echo "\r\n\r\n".'<div style="position:fixed;bottom:4px;right:4px;font-size:10px;z-index:1000;color:red;background-color:#fff;border-radius:7px;padding:1px 6px;">'.consuming('window',3).'</div>';
}
//调试开启机制
function isOpenDebug($password='on',$path='www\\test'){
	if(stristr(__DIR__,$path)){
		return true;
	}elseif(empty($_GET['openDebug'])){
		return isset($_COOKIE['openDebug'])&&$_COOKIE['openDebug']==$password;
	}elseif($_GET['openDebug']==$password){
		setcookie('openDebug',$password,384000+$_SERVER['REQUEST_TIME'],'/');
		return true;
	}elseif($_GET['openDebug']=='off'){
		setcookie('openDebug',null,$_SERVER['REQUEST_TIME'],'/');
		return false;
	}
}
//写入LOG FWRITE
function logs(){
	static $name;
	$arg = func_get_args();
	$backtrace=debug_backtrace();
	$actionflie=pathinfo($backtrace[0]['file']);
	$filename=str_replace('.php','',$actionflie['basename']);
	if(is_string($arg[0]) && preg_match( '/\/.+\.(log|txt)$/' , $arg[0] )){
		$path = iconv("gbk", "utf-8",$arg[0]);
		unset($arg[0]);
	}else{
		$path = $actionflie['dirname'].'/'.$filename .' '. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log';
	} 
	$info = pathinfo($path);
	if (!is_dir($info['dirname'])) {
		$aimDir='';
		$arr = explode('/', $info['dirname']);
		foreach ($arr as $str)if (!file_exists($aimDir.= $str.'/'))mkdir($aimDir);
	}
	foreach ($arg as $k=>$v){
		$arr[$filename.':'.$backtrace[0]['line'].'.'.$k]=$v;
	}
	$text = @var_export ($arr,true);
	$text = preg_replace('/^array \(|\s*\)$/', '', trim($text));
	$text = preg_replace('/\=\>\s+array/m', ' => array', $text);
	$text = str_replace('\\\\', '/', $text);
	if($name != $path)$text = "\r\n\r\n###### ".date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME'])." ( http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']." ) ######".$text."";
	$name = $path;
	$hand = fopen($path,'a+');
	fwrite($hand,$text);
	fclose($hand);
}
//写入LOG 写一行
function logln($path){
	$arg = func_get_args();
	$backtrace=debug_backtrace();
	$actionflie=pathinfo($backtrace[0]['file']);
	$filename=str_replace('.php','',$actionflie['basename']);
	if(is_string($arg[0]) && preg_match( '/\/.+\.(log|txt)$/' , $arg[0] )){
		$path = iconv("gbk", "utf-8",$arg[0]);
		unset($arg[0]);
	}else{
		$path = $actionflie['dirname'].'/'.$filename .' '. date('Y-m-d',$_SERVER['REQUEST_TIME']) . '.log';
	} 
	$date=date('Y-m-d H:i:s',$_SERVER['REQUEST_TIME']);
	$text = "# ".implode($arg," ")." [ {$date} http://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']} ];\r\n";
	$info = pathinfo($path);
	if (!is_dir($info['dirname'])) {
		$aimDir='';
		$arr = explode('/', $info['dirname']);
		foreach ($arr as $str)if (!file_exists($aimDir.= $str.'/'))mkdir($aimDir);
	}
	$hand = fopen($path,'a+');
	fwrite($hand,$text);
	fclose($hand);
}
//打印参数 VAR_DUMP
function dumpc($arg){
	$backtrace=debug_backtrace();
	$actionflie=pathinfo($backtrace[1]['file']);
	foreach ($arg as $k=>$v){
		$arr[str_replace('.php','',$actionflie['basename'].':'.$backtrace[1]['line'].'.'.$k)]=$v;
	}
	ob_start();
	var_dump($arr);
	$output = ob_get_clean();
	$output = preg_replace('/<\/font>\s+(<b)/m', '</font> $1', $output);
	$output = preg_replace('/<b>array<\/b>\s<i>\(size\=(\d+)\)<\/i>/', 'array($1)', $output);
	$output = str_replace('></font> <small>string</small> <font', "></font> <font", $output);
	echo '<pre style="background-color:#eef;color:#009;clear:both;padding:10px;overflow:scroll;max-width:100%;">'.$output.'</pre>';
}
//打印参数
function dump(){
	dumpc(func_get_args());
}
//TRACE
function trace(){
	dumpc(getTrace(func_get_args()));
}
/*****************  系统调试  ********************/
/*****************  系统备用  ********************/
//字符长度
function len($str){
	return mb_strlen(trim($str), 'utf8');
}
//截取等宽度字符
function sub($str, $len = 180){
	return mb_substr(preg_replace('/[\"\'\s]+/u', ' ', strip_tags($str)), 0, $len,'utf8');
}
//MD6加密
function md6($str, $s = 6){
	return substr(crc32(md5($str)), -$s);
}
//截取等宽度字符
function enjson($data){
	if(APP_DEBUG)$data['TRACE']=getTrace(array_slice(func_get_args(),1));
	header('Content-Type:application/json; charset=utf-8');
	exit(json_encode($data));
}
//获取JSON
function dejson($v = 'data'){
	return @json_decode($_REQUEST[$v], true);
}
//获取字段
function field($arr, $s){
	if (is_string($s))$s = explode(',', $s);
	foreach ($arr as $k => &$v)if (in_array($k, $s))$brr[$k] = $v;
	return $brr;
}
//获取REQUEST
function input($key = null, $default = ''){
	$request=isset($GLOBALS['request'])?$GLOBALS['request']+$_REQUEST:$_REQUEST;
	if(is_null($key))return $request;
	return empty($request[$key])?$default:$request[$key];
}
/*****************  系统备用  ********************/
/*****************  备份函数  ********************/
//获取当前链接
function pageUrl($state=false){
	$url = 'http';
	if ($_SERVER['HTTPS'] == 'on')$url .= 's';
	$url .= '://'.$_SERVER['SERVER_NAME'];
	if ($_SERVER['SERVER_PORT'] != '80') $url .= ':'.$_SERVER['SERVER_PORT'];
	$url .= $state && isset($_SERVER['PHP_SELF']) ? dirname($_SERVER['PHP_SELF']) : $_SERVER['REQUEST_URI'];
	return $url;
}
//检查手机浏览器
function isMobile(){
	return preg_match('/mobile|phone|Android|iPhone|iPod|ios|iPad/i',$_SERVER['HTTP_USER_AGENT']);
}
//时间简化时间
function dateStr($time=null,$temp='Y-m-d H:i:s') {
	return date($temp,is_numeric($time)?$time:$_SERVER['REQUEST_TIME']);
}
//时间差问题
function dateAgo($tc) {
	if(empty($tc))return '------';
	$tc = $tc - $_SERVER['REQUEST_TIME'];
	$ti = abs($tc);
	$arr = array('天'=> 86400,'小时'=> 3600,'分钟'=> 60,'秒'=> 1,);
	foreach ($arr as $k=>$v) if ($ti > $v) return floor($ti / $v). $k .($tc < 0 ? '前': '后');
	return '现在';
}
//UTF8
function utf8($str) {
	return iconv("gbk", "utf-8", $str);
}
//获取真实IP
function getIp(){
	if (getenv("HTTP_CLIENT_IP") && strcasecmp(getenv("HTTP_CLIENT_IP"), "unknown")){
		return getenv("HTTP_CLIENT_IP");
	}else if (getenv("HTTP_X_FORWARDED_FOR") && strcasecmp(getenv("HTTP_X_FORWARDED_FOR"), "unknown")){
		return getenv("HTTP_X_FORWARDED_FOR");
	}else if (getenv("REMOTE_ADDR") && strcasecmp(getenv("REMOTE_ADDR"), "unknown")){
		return getenv("REMOTE_ADDR");
	}else if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'], "unknown")){
		return $_SERVER['REMOTE_ADDR'];
	}else{
		return '0.0.0.0';
	}
}
//远程请求  get_curl(url,post,referer,cookie,agent,nobody,header);
function get_curl($url,$post=NULL,$referer=NULL,$cookie=NULL,$agent=NULL,$nobody=NULL,$header=NULL){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array( 'Accept:*/*','Accept-Encoding:gzip,deflate,sdch','Accept-Language:zh-CN,zh;q=0.8','Connection:close'));/**/
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	if($post){
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	if($header)curl_setopt($ch, CURLOPT_HEADER ,TRUE);
	if($cookie)curl_setopt($ch, CURLOPT_COOKIE ,$cookie);
	if($referer)curl_setopt($ch, CURLOPT_REFERER ,1==$referer ? $_SERVER['HTTP_REFERER'] : $referer);
	if($nobody)curl_setopt($ch, CURLOPT_NOBODY ,TRUE);
	curl_setopt($ch, CURLOPT_USERAGENT ,$agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
	curl_setopt($ch, CURLOPT_ENCODING ,"gzip");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER ,TRUE);
	$ret = curl_exec($ch);
	curl_close($ch);
	return $ret;
}
//安全链接
function checkLogin($user='admin',$pass='848586',$timeout=864000){
	if(preg_match('/^(192\.168|127\.0)\.\d+\.\d+$/',$_SERVER["SERVER_NAME"]))return;
	$useragent=md5($user.$pass.$_SERVER['SERVER_NAME']);
	if(isset($_POST['checkUser'])&&isset($_POST['checkPass'])&&$_POST['checkUser']==$user&&$_POST['checkPass']==$pass){
		return setcookie('CHECKDOG',$useragent,$_SERVER['REQUEST_TIME']+$timeout,'/');
	}
	if(empty($_COOKIE['CHECKDOG'])||$_COOKIE['CHECKDOG']!=$useragent){
		exit('<!doctype html><html><head><meta http-equiv="content-type" content="text/html; charset=utf-8"><meta name="viewport"content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"><title>请输入密码</title><style>*{margin:0;padding:0;font-family:Microsoft Yahei;font-weight:bold;text-align:center;border-radius:15px;border:none;outline:none}div{margin:auto;width:80%;min-width:250px;max-width:380px;border:1px solid#e1cfb6;background:#eee;margin-top:200px;box-shadow:0 0 40px#9aa}input{height:34px;width:60%}p{margin:20px 0}h2{background:#f50;color:#fff;line-height:60px;border-bottom-right-radius:0;border-bottom-left-radius:0}b{color:#a97c50;font-size:14px;width:100px}button{background:#f50;color:#fff;cursor:pointer;font-size:18px;padding:10px;width:60%;box-shadow:0 0 10px#ffc2a3}</style></head><body><div><h2>请输入密码</h2><form method="post"action="">'.(isset($_POST['checkUser'])?'<p style="color:red">用户名或密码不正确！</p>':'').'<p><b>帐号：</b><input name="checkUser"required></p><p><b>密码：</b><input name="checkPass"type="password"required></p><p><button type="submit">登　录</button></p></form></div></body></html>');
	}	
}
/*****************  备份函数  ********************/
/*****************  调试函数  ********************/
//打印参数 PRINT_R
function dumpr(){
	$arg = func_get_args();
	$backtrace=debug_backtrace();
	$actionflie=pathinfo($backtrace[0]['file']);
	foreach ($arg as $k=>$v){
		$arr[str_replace('.php','',$actionflie['basename'].':'.$backtrace[0]['line'].'.'.$k)]=$v;
	}
	$text = @var_export ($arr,true);
	$text = preg_replace('/^array \(|,\s*\)$/', '', trim($text));
	$text = preg_replace('/^array \(|,\s*\)$/', '', trim($text));
	$text = preg_replace('/\=\>\s+array/m', ' => array', $text);
	$text = str_replace('\\\\', '/', $text);
	echo '<pre style="background-color:#eef;color:#000;font-family:Microsoft YaHei;font-size:14px;line-height:15px;padding:14px 0;overflow:scroll;max-width:100%;">';
	print_r($arr);
	echo '</pre>';
}
//打印参数 VAR_EXPORT
function dumpv(){
	$arr=array();
	$arg=func_get_args();
	$backe=debug_backtrace();
	$pfile=pathinfo($backe[0]['file']);
	foreach ($arg as $k=>$v)$arr[str_replace('.php','',$actionflie['basename'].':'.$backtrace[0]['line'].'.'.$k)]=$v;
	$str=@var_export($arr,true);
	$str=preg_replace('/(\n(.{4})+) \=> /',"$1\t=> ",$str);
	$str=preg_replace('/(\n(.{4})+.{1,4}) \=> /',"$1\t\t=> ",$str);
	$str=preg_replace('/\s+\=>\s+array \(/'," => array (",$str);
	$str=preg_replace('/^array \(|,\s+\)$/',"",$str);
	echo '<pre style="background-color:#eef;color:#000;font-family:Microsoft YaHei;font-size:14px;line-height:15px;padding:14px 0;overflow:scroll;max-width:100%;">';
	echo $str;
	echo '</pre>';
}
/*****************  调试函数  ********************/