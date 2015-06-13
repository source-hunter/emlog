<?php
/*
	附件管理
*/
!defined('EMLOG_ROOT') && exit('access deined!');

require_once (EMLOG_ROOT . "/content/plugins/download/config.php");

# 安装
function callback_init(){
	$Path = EMLOG_ROOT . "/content/plugins/download/";
	$InstallFile = $Path . 'install.php';
	if( is_file($InstallFile) === true ){
		include_once ($InstallFile);
		$Check = DownloadMe_Install_Check();
		if( $Check === true ){ 
			#"安装成功！";
			@rename($InstallFile, $InstallFile.'.Download');
			if( is_file($Path . DOWNLOAD_CACHE_NAME . "_Inc.php") === true ){
				@rename($Path . DOWNLOAD_CACHE_NAME . "_Inc.php", EMLOG_ROOT . "/content/cache/" . DOWNLOAD_CACHE_NAME . "_Inc.php" );
			}
			if( is_file($Path . DOWNLOAD_CACHE_NAME . "_Gip.php") === true ){
				@rename($Path . DOWNLOAD_CACHE_NAME . "_Gip.php", EMLOG_ROOT . "/content/cache/" . DOWNLOAD_CACHE_NAME . "_Gip.php" );
			}
			if( is_file($Path . "Version.php") === true ){
				@unlink( $Path . "Version.php" );
			}
		}
	}elseif( is_file($InstallFile . '.Download') === true ){
		@rename($InstallFile . '.Download', $InstallFile);
		callback_init();
	}else{
		emMsg('安装文件 ( /content/plugins/download/install.php ) 缺失，请重新下载插件安装包');
	}
	DownloadMe_Up();# 写入缓存
}
# 卸载
function callback_rm(){
	$InstallFile = EMLOG_ROOT . '/content/plugins/download/install.php';
	$Path = EMLOG_ROOT . "/content/cache/" . DOWNLOAD_CACHE_NAME;
	@unlink( $Path . ".php" );
	if( is_file($Path . "_Inc.php") === true ){
		@rename( $Path . "_Inc.php", EMLOG_ROOT . "/content/plugins/download/" . DOWNLOAD_CACHE_NAME . "_Inc.php" );
	}
	if( is_file($Path . "_Gip.php") === true ){
		@rename( $Path . "_Gip.php", EMLOG_ROOT . "/content/plugins/download/" . DOWNLOAD_CACHE_NAME . "_Gip.php" );
	}
	if( is_file(EMLOG_ROOT . "/content/plugins/download/att.csv") === true ){
		@unlink( EMLOG_ROOT . "/content/plugins/download/att.csv" );
	}
	@rename( $InstallFile.".Download", $InstallFile );
	global $CACHE;
	$CACHE->updateCache("logatts");
}