<?php
/*
	附件管理
*/
!defined('EMLOG_ROOT') && exit('access deined!');

define('DOWNLOAD_CACHE_NAME','downloadMeData'); # 缓存文件名称
define('ECHO_FILE_INC','<div><a href="[url]" target="_blank">[name]</a>[tong] | [size] | [time]</div>'); # 默认格式

# 添加后台设置面板
addAction('adm_sidebar_ext',
 create_function(
	'',
	"echo '<div class=\"sidebarsubmenu\"id=\"download\"><a href=\"./plugin.php?plugin=download\">附件管理</a></div>';"
 )
);

# 更新缓存
function DownloadMe_Up($Inc = false) {
	$DB = MySql::getInstance();global $CACHE;$Data = array();
	if( $Inc === false || $Inc == "SQL" ){
	$query = $DB->query("SELECT * FROM ".DB_PREFIX."attachment ORDER BY blogid DESC");
	$is_num = $DB->num_rows($query);
	if(!empty($is_num)) {
	$LogQuery = $DB->query("SELECT * FROM ".DB_PREFIX."blog ORDER BY gid DESC");
		while($row = $DB->fetch_array($LogQuery)) {
			$Id = $row['gid']; $Data['Log'][$Id] = array($row['title'],$row['hide']);
		}
		while($row = $DB->fetch_array($query)) {
			$row['filepath'] = str_replace('thum-', '', $row['filepath']);
			$Id = md5($row['blogid'] . $row['aid']); $Data['File'][$Id] = $row;
			$Lid = "Log_".$row['blogid']; $Data[$Lid][] = $Id;
		}
	}
	if( $Inc == "SQL" ){
		return $Data;
	}
		$CACHE->cacheWrite(serialize($Data), DOWNLOAD_CACHE_NAME);
		$CACHE->updateCache("logatts"); DownloadMe_Radio(true);
	}else{
		$CACHE->cacheWrite(serialize($Inc), DOWNLOAD_CACHE_NAME."_Inc");
	}
}
# 读取缓存
function DownloadMe_Ret($Inc = false ) {
	if( $Inc == "SQL" ){
		return DownloadMe_Up($Inc);
	}elseif( $Inc === false ){
		$cachefile = EMLOG_ROOT . "/content/cache/" . DOWNLOAD_CACHE_NAME . ".php";
		# 缓存不存在自动更新生成缓存
		if (!is_file($cachefile) || @filesize($cachefile) <= 0 || DownloadMe_Radio() === true ){ DownloadMe_Up(); }
	}else{
		$cachefile = EMLOG_ROOT . "/content/cache/" . DOWNLOAD_CACHE_NAME . "_".$Inc.".php";
	}
	# 打开缓存
	if ($fp = @fopen($cachefile, 'rb')) {
		$Data = @fread($fp, @filesize($cachefile)); @fclose($fp);
		$Data = unserialize(str_replace("<?php exit;//", '', $Data));
		return $Data;
	}
	return false;
}
$DownloadMe_Cache_Inc = DownloadMe_Ret("Inc");

function DownloadMe_Radio( $T = false ) {
	$Ratio = EMLOG_ROOT . '/content/plugins/download/att.csv';
	$Att = EMLOG_ROOT . '/content/cache/logatts.php';
	$Log = EMLOG_ROOT . '/content/cache/newlog.php';
	$F = @fopen($Ratio,"rb");
	$Data = @fgetcsv($F, 100, ",");
	@fclose($F);
	if ( $Data[1] != @filesize($Att) || $Data[2] != @filesize($Log) || $T === true ){
		$Fwrite = array(($Data[0]+1), @filesize($Att), @filesize($Log), ($Data[3]+1)); $File = @fopen($Ratio,"wb");
		@fputcsv($File, $Fwrite); @fclose($File); return true;
	}
	return false;
}
function DownloadMe_File($paht) {
	$paht = str_replace("..","",$paht);
	if( is_string($paht) && @file_exists(EMLOG_ROOT . $paht) ){ return true; }else{ return false; }
}
function DownloadMe_exp($Da){
	$Exp = explode('[#]',$Da);
	foreach($Exp as $Val){ $E = explode('[=]',$Val); $gid = $E[0]; $Ret[$gid] = $E[1]; } return $Ret;
}
function DownloadMe_FileIF($Path){
	$Size = end(explode('.', basename($Path)));
	$In = array("gif","jpge","jpg","png");
	if( in_array($Size,$In) ) { return true; }
	return false;
}

function DownloadMe_DownLog($Gid){
	$DownInc = DownloadMe_Ret("Inc");
	session_start();
	$Sesion = $_SESSION[$Gid] ? true : false;
	if( empty($DownInc['down']) && $Sesion === false ){
		$_SESSION[$Gid] = true; DownloadMe_NewUp($Gid, false); return 0;#'重新打开博客时再次统计';
	}else{
		$Gip = getIp();
		$DownGip = DownloadMe_Ret("Gip");
		if( $DownGip === false ){
			DownloadMe_NewUp($Gid, array());
			return 1;#'不存在ip数据，创建并添加数据';
		}else{
			$ifGip = false;
			if( !isset($DownGip[$Gid]) ){ DownloadMe_NewUp($Gid, $DownGip); return 2; }#'不存在该ip，添加新统计'
			foreach($DownGip[$Gid] as $Key => $Val):
				$E = explode("#", $Val);
				if( $Gip == $E[0] ){
					if( $DownInc['down'] == 1 && abs(time() - $E[1]) >= 86400 ){
						unset($DownGip[$Gid][$Key]);
						DownloadMe_NewUp($Gid, $DownGip);
						return 3;#'24小时内第一次下载';
					}
					$ifGip = true;
				}
			endforeach;
			if( $DownInc['down'] == 2 && $ifGip === false ){ DownloadMe_NewUp($Gid, $DownGip); return 4; }#'永久性不再添加统计'
		}
	}
	return 5;#'未添加统计...';
}
function DownloadMe_NewUp($Gid, $Da = false){
	$DB = MySql::getInstance();
	$DownData = DownloadMe_Ret();
	$Data = $DownData['File'][$Gid];
	if( !empty($Data) ){
		$Aid = $Data['aid']; $Lid = $Data['blogid'];
		$Edit = unserialize($Data['download']); $Edit['statis'] = intval($Edit['statis'])+1;
		$editIF = $DB->query("UPDATE ".DB_PREFIX."attachment SET download='".serialize($Edit)."' WHERE aid={$Aid} AND blogid={$Lid}");
		DownloadMe_Up();
		if( $Da === false ){ return true; }
		global $CACHE;
		$Da[$Gid][] = getIp() . '#' . time();
		$CACHE->cacheWrite(serialize($Da), DOWNLOAD_CACHE_NAME."_Gip");
	}
	return false;
}

function DownloadMe_Type($Path){
	$T = end(explode('.', basename($Path)));
	$Types = array(
		'doc' => 'application/msword', 'bin' => 'application/octet-stream',
		'exe' => 'application/octet-stream', 'so' => 'application/octet-stream',
		'dll' => 'application/octet-stream', 'pdf' => 'application/pdf',
		'ai' => 'application/postscript',	'xls' => 'application/vnd.ms-excel',
		'ppt' => 'application/vnd.ms-powerpoint',	'dir' => 'application/x-director',
		'js' => 'application/x-javascript',	'swf' => 'application/x-shockwave-flash',
		'xhtml' => 'application/xhtml+xml',	'xht' => 'application/xhtml+xml',
		'zip' => 'application/zip',	'mid' => 'audio/midi',
		'midi' => 'audio/midi',	'mp3' => 'audio/mpeg',
		'rm' => 'audio/x-pn-realaudio',	'rpm' => 'audio/x-pn-realaudio-plugin',
		'wav' => 'audio/x-wav',	'bmp' => 'image/bmp', 'gif' => 'image/gif',	'jpeg' => 'image/jpeg',
		'jpg' => 'image/jpeg', 'png' => 'image/png', 'css' => 'text/css', 'html' => 'text/html',
		'htm' => 'text/html', 'txt' => 'text/plain', 'xsl' => 'text/xml', 'xml' => 'text/xml',
		'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg',
		'avi' => 'video/x-msvideo', 'movie' => 'video/x-sgi-movie',  
	);
	return $Types[$T] ? $Types[$T] : $Types['bin'];
}
# 文章页输出附件列表
function DownloadMe_LogAction($Log){
	session_start();
	$_SESSION['DownloadMe'] = 1;
	$DownData = DownloadMe_Ret(); global $DownloadMe_Cache_Inc;
	$Gid = "Log_" . ( empty($DownloadMe_Cache_Inc['mov']) ? $Log['logid'] : $Log );
	$Data = $DownData[$Gid]; $Show = false;
	if( !empty($Data) ){
		$ExpWrap = explode('[list]', $DownloadMe_Cache_Inc['wrap']);
		echo $ExpWrap[0];
		foreach( $Data as $V ){
			$File = $DownData['File'][$V];
			$Down = unserialize($File['download']);
			if($Down['hide'] === true ){ continue; }
			$Search = array(
				"Url" => "[url]",
				"Path" => "[path]",
				"Name" => "[name]",
				"NameSub" => "[nameSub]",
				"Size" => "[size]",
				"Stat" => "[tong]",
				"Time" => "[time]"
			);
			$Replace = array(
				"Url" => BLOG_URL.'?downloadMe='.$V,
				"Path" => BLOG_URL.str_replace('../', '', $File['filepath']),
				"Name" => $File['filename'],
				"NameSub" => subString($File['filename'], 0, 25),
				"Size" => $File['filesize']<=0 ? "未知大小" : changeFileSize($File['filesize']),
				"Stat" => ( $Down['statis'] ? $Down['statis'] : 0 ),
				"Time" => smartDate($File['addtime'])
			);
			echo str_replace($Search, $Replace, $DownloadMe_Cache_Inc['file']);
			$Show = true;
		}
		if( $Show === false ){
			echo '没有附件..';
		}
		echo $ExpWrap[1];
	}
}
$DownloadMe_addAction = ( empty($DownloadMe_Cache_Inc['mov']) ? 'log_related' : 'baby_blog_atts' );
addAction($DownloadMe_addAction, "DownloadMe_LogAction");

# 清空默认附件缓存
function DownloadMe_WwwAtt(){
	global $CACHE;$Data = array();
	$Att = $CACHE->readCache('logatts');
	if ( !empty($Att) ){
		$CACHE->cacheWrite(serialize($Data), "logatts");
	}
}
addAction('index_head','DownloadMe_WwwAtt');

