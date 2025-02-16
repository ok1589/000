<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta http-equiv="Cache-Control" content="public">
<title><?php echo($title); ?> - 数据管理后台</title>
<link rel="stylesheet" type="text/css" href="./static/bootstrap.min.css">
<link rel="stylesheet" type="text/css" href="./static/layer.css">
<link rel="stylesheet" type="text/css" href="./static/admin.css?4">
<link rel="shortcut icon" type="image/x-icon" href="./static/favicon.ico">
</head>
<body>
<nav class="navbar navbar-fixed-top navbar-default">
	<div class=" main_content">
		<div class="navbar-header">
			<a class="navbar-brand" href="?m=list">
				数据管理后台
			</a>
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
				<span class="sr-only">导航按钮</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
		</div>
		<div id="navbar" class="collapse navbar-collapse">
			<ul class="nav navbar-nav navbar-right">
				<li><a href="../" target="_blank"><span class="glyphicon glyphicon-home"></span> 打开首页 </a></li>
				<li><a href="?m=list"><span class="glyphicon glyphicon-calendar"></span> 数据操作</a></li>
				<li>
					<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span class="glyphicon glyphicon-cog"></span> 系统设置<b class="caret"></b></a>
					<ul class="dropdown-menu">
						<li><a href="?m=setting"><span class="glyphicon glyphicon glyphicon-th-large"></span> 参数配置</a></li>
						<li><a href="?m=user"><span class="glyphicon glyphicon-user"></span> 用户设置</a></li>
						<li><a href="?m=home"><span class="glyphicon glyphicon-home"></span> 数据详情</a></li>
						<li><a href="?act=logout"><span class="glyphicon glyphicon-log-out"></span> 退出登陆</a></li>
					</ul>
				</li>
			</ul>
		</div>
	</div>
</nav>
<div class="main_row main_content">