<?php
/*
Plugin Name: EM附件管理
Version: 2.41
Plugin URL: http://www.2baby.me
Description: 增加多项功能，美化后台导航...
Author: 2baby
Author Email: 
Author URL: http://www.2baby.me
*/
!defined('EMLOG_ROOT') && exit('access deined!');

require_once (EMLOG_ROOT . "/content/plugins/download/config.php");

# 如果存在附件操作则自动更新附件缓存
if ( isset($_GET['action']) ){
	$actionPost = addslashes($_GET['action']);
	if( $actionPost == 'del_attach' || $actionPost == 'upload' || $actionPost == 'upload_multi' ){
		DownloadMe_Up();
	}
}
# 前台下载反映
if( isset($_GET['downloadMe']) ){
	$Gid = addslashes($_GET['downloadMe']);
	session_start();
	DownloadMe_Radio();
	$ifQuery = DownloadMe_DownLog($Gid);
	$DownData = DownloadMe_Ret();
	$DownInc = DownloadMe_Ret("Inc");
	$Data = $DownData['File'][$Gid];
	$Set = $Data['download'] ? unserialize($Data['download']) : array("statis" => 0, "hide" => false, "web" => false);
	$Path = EMLOG_ROOT .str_replace(array('..','thum-'), array(''), $Data['filepath']);
	if( $DownInc["rob"] == true && empty($_SESSION['DownloadMe']) ){
		$Echo_data = 'rob';
		$Url = BLOG_URL."?post=".$Data['blogid'];
		include_once (EMLOG_ROOT . "/content/plugins/download/download_echo.php");
		exit();
	}else{
		if( $DownInc["key"] == true ){
			if( !isset($_POST['download_key']) || strcasecmp($_SESSION['code'], $_POST['download_key']) != 0 ){
				$Echo_data = 'key';
				include_once (EMLOG_ROOT . "/content/plugins/download/download_echo.php");
				exit();
			}
		}
		/////////////////////////
		if( isset($Set['web']) && $Set['web'] === true ){
			header("Location: " . $Data['filepath']);
			exit();
		}
		if(	file_exists($Path) ){
			ob_end_clean();#清除缓冲区
			header('Cache-control: max-age=31536000');#禁止缓冲
			header('Expires: -1');#过期时间
			header('Content-Encoding: none');#页面压缩
			header('Content-type: ' . DownloadMe_Type($Path));#输出格式
			header('Content-Length: '.filesize($Path));#设置内容长度
			if( DownloadMe_FileIF($Path) === false || $DownInc["img"] == true ){
				header('Content-Disposition: attachment; filename='.$Data['filename']);#输出文件名
				if($fp = @fopen($Path, 'rb') ) {
					@fseek($fp, 0);
					if(function_exists('fpassthru')) { @fpassthru($fp); } else { echo @fread($fp, filesize($Path)); }
				}
				@fclose($fp);
			}elseif( DownloadMe_FileIF($Path) === true && $DownInc["img"] == false ){
				readfile($Path);
			}
		}else{
			echo "附件不存在";
		}
		/////////////////////
	}
		exit();
}

# 后台附件修改操作
if( isset($_POST['DownloadMeGov']) && $_POST['DownloadMeGov'] == true ){
	$Echo_Inc['file'] = ($_POST['echo_file']) ? ($_POST['echo_file']) : ECHO_FILE_INC;
	$Echo_Inc['wrap'] = ($_POST['echo_wrap']) ? ($_POST['echo_wrap']) : '';
	$Echo_Inc['mov'] = intval($_POST['echo_mov']) ? intval($_POST['echo_mov']) : 0 ;
	$Echo_Inc['down'] = intval($_POST['echo_down']) ? intval($_POST['echo_down']) : 0 ;
	$Echo_Inc['img'] = intval($_POST['echo_img']) ? intval($_POST['echo_img']) : 0 ;
	$Echo_Inc['key'] = intval($_POST['echo_key']) ? intval($_POST['echo_key']) : 0 ;
	$Echo_Inc['rob'] = intval($_POST['echo_rob']) ? intval($_POST['echo_rob']) : 0 ;
	DownloadMe_Up($Echo_Inc);
}

if( isset($_POST['downloadMe']) && isset($_GET['downJsonMe'])){
	function DownloadMe_rEdit($Data, $i = true){
		if( $i === true ){
		return ( $Data ? unserialize($Data) : array("statis" => 0, "hide" => false, "web" => false ) );
		}
		return md5(str_replace('-', '', $Data));
	}
	$downJsonRet = $_GET['downJsonMe'];
	$Post = DownloadMe_exp($_POST['downloadMe']);
	$Return = array('error' => "参数获取错误！");
	if( isset( $Post['Act'] ) && isset( $Post['Gid'] )){
		$DB = MySql::getInstance(); global $CACHE;
		$Return = array('error' => "数据修改失败");
		$DownData = DownloadMe_Ret("SQL");
		$expGid = array_filter( explode(",", $Post['Gid']) );
		if( $Post['Act'] == 'Switch' && isset($expGid) ){
			foreach( $expGid as $Id ) {
				$Gid = DownloadMe_rEdit($Id, false); $Data = $DownData['File'][$Gid];
				$Aid = $Data['aid']; $Lid = $Data['blogid']; $Edit = DownloadMe_rEdit( $Data['download']);
				$Edit['hide'] = ($Edit['hide'] === true) ? false : true;
				$editIF =
				$DB->query("UPDATE ".DB_PREFIX."attachment SET download='".serialize($Edit)."' WHERE aid={$Aid} AND blogid={$Lid}");
				if( $editIF ===true ){ $Return['fsucc'][($Aid.'_'.$Lid)] = $Edit['hide'];
				}else{ $Return['ferror'][($Aid.'_'.$Lid)] = $Edit['hide']; }
			}
			if( isset($Return['fsucc']) ){
				DownloadMe_Up(); $rHide = 0;
				foreach( $Return['fsucc'] as $Val){ if( $Val === true ){ $rHide++; } }
				$Return['succ'] = "成功暂停 ".$rHide." 项, 开启 ".(count($Return['fsucc'])-$rHide)." 项附件" .
				(isset($Return['ferror']) ? ", 失败 " . count($Return['ferror']) . "项" : "");
			}
		}elseif( $Post['Act'] == 'Dell' && isset($expGid) ){
			$Return = array('error' => "删除附件失败!");
			foreach( $expGid as $Id ) {
				$Gid = DownloadMe_rEdit($Id, false); $Data = $DownData['File'][$Gid];
				$Aid = $Data['aid']; $Lid = $Data['blogid'];
				if (file_exists($Data['filepath'])) {
					$fpath = str_replace('thum-', '', $Data['filepath']);
						if ($fpath != $Data['filepath']) {
							@unlink($fpath) ? "" : $Return['ferror'][($Aid.'_'.$Lid)] = ($Aid.'_'.$Lid) ;
						}
					@unlink($Data['filepath']) ? "" : $Return['ferror'][($Aid.'_'.$Lid)] = ($Aid.'_'.$Lid) ;
				}
				$DB->query("UPDATE ".DB_PREFIX."blog SET attnum=attnum-1 WHERE gid={$Lid}");
				$editIF = $DB->query("DELETE FROM ".DB_PREFIX."attachment where aid={$Aid} AND blogid={$Lid}");
				if( $editIF === true ){ $Return['fsucc'][($Aid.'_'.$Lid)] = ($Aid.'_'.$Lid);
				}else{ $Return['ferror'][($Aid.'_'.$Lid)] = ($Aid.'_'.$Lid); }
			}
			if( isset($Return['fsucc']) ){
				$CACHE->updateCache('logatts');
				DownloadMe_Up();
				$Return['succ'] = "成功删除 " . count($Return['fsucc']) . "项" .
				(isset($Return['ferror']) ? ", 失败 " . count($Return['ferror']) . "项" : "");
			}
		}elseif( $Post['Act'] == "UpCache" ){
			DownloadMe_Up();
			$Return = array('succ' => "成功更新缓存，正在刷新页面。。。");
		}elseif( $Post['Act'] == "Edit" && isset($expGid) ){
			$Gid = DownloadMe_rEdit($expGid[0], false); $Data = $DownData['File'][$Gid];
			$Aid = $Data['aid']; $Lid = $Data['blogid'];
			$Return = array('error' => "修改失败");
			if( isset($Post['Sval']) ){
				$Edit = DownloadMe_rEdit( $Data['download']);
				$Edit['statis'] = intval($Post['Sval']);
				$FileEdit[] = "download='".serialize($Edit)."'";
			}
			if( isset($Post['Nval']) ){
				$FileEdit[] = "filename='".addslashes($Post['Nval'])."'";
			}
			$FileEdit = implode(", ", $FileEdit);
			$editIF = $DB->query("UPDATE ".DB_PREFIX."attachment SET {$FileEdit} WHERE aid={$Aid} AND blogid={$Lid}");
			if( $editIF ===true ){
				DownloadMe_Up();
				$Return = array('succ' => "修改成功");
			}
		}elseif( $Post['Act'] == "addUrl" && isset($expGid) ){
			$Return = array('error' => "添加附件失败!");
			if( !empty($Post['Name']) && !empty($Post['Url']) ){
				$stat = serialize(array("statis" => 0, "hide" => false, "web" => true));
				$editIF = $DB->query("INSERT INTO ".DB_PREFIX."attachment (blogid,filename,filesize,filepath,addtime,download) values ({$expGid[0]},'".$Post['Name']."','0','".$Post['Url']."','".time()."','".$stat."')");
				$editIFs = $DB->query("UPDATE ".DB_PREFIX."blog SET attnum=attnum+1 WHERE gid={$expGid[0]}");
				if( $editIF === true && $editIFs === true ){
					DownloadMe_Up();
					$Return = array('succ' => "成功添加附件");
				}
			}
		}elseif( $Post['Act'] == "FileMove" && isset($expGid) ){
			$Nid = DownloadMe_rEdit($expGid[0], false);
			$Data = $DownData['File'][$Nid];
			$Aid = $Data['aid'];
			$Return = array('error' => "请填写正确的文章ID!");
			if( !empty($Post['Move']) ){
				$LogQuery = $DB->query("SELECT * FROM ".DB_PREFIX."blog WHERE gid=" . $Post['Move']);
				$LogNum = $DB->num_rows($LogQuery);
				$Return = array('error' => "目标文章不存在!");
				if( !empty($LogNum) ){
					$editIF = $DB->query("UPDATE ".DB_PREFIX."attachment SET blogid=".$Post['Move']." WHERE aid={$Aid}");
					$Return = array('error' => "移动附件到目标文章失败!");
					if( $editIF === true ){
						DownloadMe_Up();
						$Return = array('succ' => "修改成功");
					}
				}
			}
		}elseif( $Post['Act'] == "addSize" && isset($expGid) ){
		function getFileSize($url){  
			$url = parse_url($url); 
			if($fp = @fsockopen($url['host'],empty($url['port'])?80:$url['port'],$error)){ 
				fputs($fp,"GET ".(empty($url['path'])?'/':$url['path'])." HTTP/1.1\r\n"); 
				fputs($fp,"Host:$url[host]\r\n\r\n"); 
				while(!feof($fp)){ 
					$tmp = fgets($fp); 
					if(trim($tmp) == ''){ 
						break; 
					}else if(preg_match('/Content-Length:(.*)/si',$tmp,$arr)){ 
						return trim($arr[1]); 
					} 
				} 
				return null; 
			}else{ 
				return null; 
			} 
		} 
			$Gid = DownloadMe_rEdit($expGid[0], false);
			$Data = $DownData['File'][$Gid];
			$Aid = $Data['aid']; $Lid = $Data['blogid'];
			$Return = array('error' => "远程获取失败!");
			$addSize = getFileSize($Data['filepath']);
			if( $addSize ){
				$editIF = $DB->query("UPDATE ".DB_PREFIX."attachment SET filesize='{$addSize}' WHERE aid={$Aid} AND blogid={$Lid}");
				$Return = array('error' => "数据更新失败");
				if( $editIF ){
					DownloadMe_Up();
					$Return = array('succ' => "数据修改成功", 'data' => changeFileSize($addSize) );
				}
			}

		}
	}
	$DMsg = json_encode( array_unique($Return) );
	echo $downJsonRet.'('.$DMsg,')';
	exit();
}

if( isset($_GET['plugin']) && addslashes($_GET['plugin']) == "download" ){
	function DownloadMe_AppCss(){
		$Stat_Version_Install = true;
		echo '
		<link href="../content/plugins/download/style/main.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript">
			var
				Stat_Version_Install = '.($Stat_Version_Install ? 'true' : 'false').';
				Stat_Version_DownloadMe_Url = "http://2baby.me/?plugin=Plugin_Producer&casedata=?",
				Stat_Version_DownloadMe = {user:"'.urlencode(BLOG_URL).'", author:"2baby", belong:"downloadme", version:"2.41"};
		</script>
		<script type="text/javascript" src="../content/plugins/download/style/main.js"></script>
		';
	}
	addAction('adm_head','DownloadMe_AppCss');
}
