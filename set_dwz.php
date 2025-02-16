<?php
$title='用户设置';
include './inc_header.php'; 
$url = dirname(dirname(current(explode('?','http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'])).'#'));
$url .= '/index.html';
if(!empty($_POST['ud']['token'])){
	$url = $_POST['ud']['token'];
}
?>
<form action="?m=dwz" method="post" enctype="application/x-www-form-urlencoded" style="margin:0;" >
	<table class="right_table" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<td class="table_left">原始地址</td>
			<td><input class="table_text" id="url" name="ud[url]" value="<?php echo($url); ?>" >
				<span class="table_info" >原始地址</span></td>
		</tr>
		<tr>
			<td class="table_left">原始地址</td>
			<td><input class="table_text" id="token" name="ud[token]" value="<?php echo(empty($_POST['ud']['token'])?'bc266cf09f7dd35102a36ebb74a62eaa':$_POST['ud']['token']); ?>" >
				<span class="table_info" >原始地址</span></td>
		</tr>
		<tr class="qqShare">
			<td class="table_left">生成类型</td>
			<td >
				<select class="table_text" id="type" name="ud[type]">
					<option value="tcn1">t.cn-微博</option>
					<option value="sinaurl1" selected>sinaurl.cn-微博</option>
					<option value="baidu">mr.baidu.com-百度</option>
					<option value="dd.ma">ddma</option>
					<option value="rmbaidu">r.m.baidu.com</option>
					<option value="mibaidu">QQ0/微信团</option>
					<option value="mybaidu">mi.mbd.baidu.com</option>
					<option value="mobaidu">my.mbd.baidu.com</option>
					<option value="httx">httx.ink企备稳定短链</option>
				</select>
				<span class="table_info" >选择生成类型</span>
			</td>
		</tr>
		<tr class="qqShare">
			<td class="table_left">生成模式</td>
			<td >
				<select class="table_text" id="pattern" name="ud[pattern]">
					<option value="1">普通</option>
					<option value="2" selected>防红</option>
					<option value="3">直链</option>
					<option value="4">缩短</option>
				</select>
				<span class="table_info" >选择生成类型</span>
			</td>
		</tr>
		<?php if(!empty($_POST['ud'])){ ?>
			<tr class="qqShare">
				<td class="table_left">短网址</td>
				<td >
					<?php
						$ud = curlJson("https://s.011.run/api/url.php?type={$_POST['ud']['type']}&pattern={$_POST['ud']['pattern']}&token={$_POST['ud']['token']}&url=".urlencode($_POST['ud']['url']));
						if(!empty($ud['dwz'])){
							echo '<textarea class="table_text" id="dwz" placeholder="生短度昂网址" >'.$ud['dwz'].'</textarea>';
						}else{
							echo '生成短网址失败！';
						}
						// var_dump($_POST,$ud );
					?>
					<span class="table_info" ></span>
				</td>
			</tr>
		<?php }	?>
	</table>
	<div class="button_save" >
		<input class="butt chenksave" name="save" type="submit" style="background-color:#43A7FF;" value="生成短网址">
		<input class="butt" type="button" onclick="copy();" value="复制短网址">
	</div>
	<script>
		function copy() {
			tip('复制成功！');
			var range = document.createRange();
			range.selectNode(document.getElementById('dwz'));
			var selection = window.getSelection();
			if (selection.rangeCount > 0) selection.removeAllRanges();
			selection.addRange(range);
			document.execCommand('copy');
			//selection.empty();
		}
	</script>
</form>
<?php 
	include './inc_footer.php';  
//获取JSON
function curlJson($url,$post=null){
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_TIMEOUT,5);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Linux;Android) Baiduspider Mobile/Chrome');
	curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: application/x-www-form-urlencoded'));
	if($post){curl_setopt($ch, CURLOPT_POST, true);curl_setopt($ch, CURLOPT_POSTFIELDS, $post);}
	$ret = curl_exec($ch);
	curl_close($ch);
	file_put_contents(dirname(__FILE__).'/~CurlJson-'.date('Y-m').'.log',"\r\n###### ".date('Y-m-d H:i:s')."\t{$_SERVER['REMOTE_ADDR']}\t{$url}\t{$ret}\t{$_SERVER['HTTP_REFERER']}\thttp://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}\t######",FILE_APPEND);
	if(!empty($ret)&&$json=@json_decode($ret,true))return $json;
	return $ret;
}