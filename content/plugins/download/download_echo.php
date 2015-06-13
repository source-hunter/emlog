<?php
$Echo_title = array(
	'rob' => '您盗链啦！',
	'key' => '附件下载验证'
); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $Echo_title[$Echo_data]?></title>
</head>
<body>

<!-- 下载验证页内容 开始 -->
<?php
if( $Echo_data == 'key' ) { #附件下载验证页内容 ?>
<style>
<!--
*{font:12px /120% "Trebuchet MS", Arial, Helvetica, sans-serif; margin:0 auto;}
p{margin:15px 0;}
form{width:300px; border:15px solid #F5F5F5; margin-top:20%; padding:10px; text-align:center;}
-->
</style>
<form action="" method="post" enctype="application/x-www-form-urlencoded">
<p style="color:red;">请输入下面的验证码，以继续下载！</p>
<p><img src="<?php echo BLOG_URL; ?>/include/lib/checkcode.php" onclick="this.src=this.src" title="看不清点击刷新" align="absbottom"/>
<input name="download_key" type="text" size="12" />
<input name="" type="submit" value="继续操作..." />
</p>
<font style="color:#CCC;">(不区分大小写)</font>
<p>当前资源: <?php echo $Data['filename'] ?> - <?php echo ($Data['filesize']<=0 ? "未知大小" : changeFileSize($Data['filesize']))?></p>
</form>
<?php } ?>
<!-- 下载验证页内容 结束 -->

<!-- 防盗链页内容 开始 -->
<?php if( $Echo_data == 'rob' ){ #附件下载防盗链页内容 ?>
<style>
<!--
*{font:12px /120% "Trebuchet MS", Arial, Helvetica, sans-serif; margin:0 auto;}
p{margin:15px 0;}
div{width:300px; border:15px solid #F5F5F5; margin-top:20%; padding:10px; text-align:center;}
-->
</style>
<div>
<p>请不要盗链本站资源！</p>
<p style="color:red;">
你可以直接 <a href="<?php echo $Url ?>">访问本站</a>
下载该资源 : <a href="<?php echo $Url ?>" title="<?php echo changeFileSize($Data['filesize'])?>"><?php echo $Data['filename']?></a>
</p>
</div>
<?php } ?>
<!-- 防盗链页内容 结束 -->
</body>
</html>
