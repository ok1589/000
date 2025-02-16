<?php
$title='数据详情';
$isbutton=true;
include './inc_header.php'; 

$count = db( 'ec_data' )->where( $where )->count();
?>
<div class="" style="padding:300px 0;text-align:center;font-weight:700;font-size:24px;color:red;">
共采集到数据 <?php echo $count; ?> 条
<br>
<font size="-1" color="#999">(清理重复可以获取有效数据)<font>


</div>


<?php 
	include './inc_footer.php';  
?>