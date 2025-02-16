<!doctype html>
<html>
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<title>请输入密码</title>
	<style>
		* {
			margin: 0;
			padding: 0;
			font-family: Microsoft Yahei;
			font-weight: bold;
			text-align: center;
			border-radius: 15px;
			border: none;
			outline: none
		}
		div {
			margin: auto;
			width: 80%;
			min-width: 250px;
			max-width: 380px;
			border: 1px solid#e1cfb6;
			background: #eee;
			margin-top: 200px;
			box-shadow: 0 0 40px#9aa
		}
		input {
			height: 34px;
			width: 60%
		}
		p {
			margin: 20px 0
		}
		h2 {
			background: #f50;
			color: #fff;
			line-height: 60px;
			border-bottom-right-radius: 0;
			border-bottom-left-radius: 0
		}
		b {
			color: #a97c50;
			font-size: 14px;
			width: 100px
		}
		button {
			background: #f50;
			color: #fff;
			cursor: pointer;
			font-size: 18px;
			padding: 10px;
			width: 60%;
			box-shadow: 0 0 10px#ffc2a3
		}
	</style>
</head>
<body>
	<div>
		<h2>请输入密码</h2>
		<form method="post" action="">
			<?php if(isset($_POST['checkUser'])){ ?>
				<p style="color:red">用户名或密码不正确！</p>
			<?php } ?>
			<p><b>帐号：</b><input name="checkUser" required>
			</p>
			<p><b>密码：</b><input name="checkPass" type="password" required>
			</p>
			<p><button type="submit">登　录</button>
			</p>
			<?php if(isset($_POST['checkUser'])){ ?>
				<p style="color:#999;font-size:12px;font-weight:100;">忘记密码请在数据库查看或密码！</p>
			<?php } ?>
		</form>
	</div>
</body>
</html>