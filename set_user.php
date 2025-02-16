<?php

$title='用户设置';

include './inc_header.php'; 
?>
<form action="?act=set_pass" method="post" enctype="application/x-www-form-urlencoded" style="margin:0;" >
	<table class="right_table" border="0" cellpadding="5" cellspacing="0">
		<tr>
			<td class="table_left" >温馨提示</td>
			<td>
				<ol>
					<li>当用户名为 admin 密码为 123456 时，不用登陆就可以进入</li>
					<li>当密码错误不能登录时，请删除或查看根目录下的 web-config.php 文件</li>
				</ol>
			</td>
		</tr>
		<tr>
			<td class="table_left">用 户 名</td>
			<td><input class="table_text" id="user" name="user" value="<?php echo($conf['user']); ?>" >
				<span class="table_info" >填写本后台的管理用户名</span></td>
		</tr>
		<tr>
			<td class="table_left">管理密码</td>
			<td><input class="table_text" id="pass" name="pass" value="<?php echo($conf['pass']); ?>" >
				<span class="table_info" >填写本后台的管理密码</span></td>
		</tr>

	</table>
	<div class="button_save" >
		<input class="butt chenksave" name="save" type="submit" value="保存设置">
	</div>
</form>
<?php 
	include './inc_footer.php';  
?>