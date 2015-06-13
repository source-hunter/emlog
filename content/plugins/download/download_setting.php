<?php
/*
	附件管理
*/
!defined('EMLOG_ROOT') && exit('access deined!');
function plugin_setting_view(){
	$DB = MySql::getInstance();
	$ActMe = isset($_GET['downAct']) ? addslashes($_GET['downAct']) : NULL;
	$Page = isset($_GET['page']) ? intval($_GET['page']) : 1;
	$DownList = DownloadMe_Ret("SQL"); $File_Unu = 0; $File_Out = 0; $File_Web = 0; $Page_Num = 12;
	if( !empty($DownList['File']) ){
		foreach( $DownList['File'] as $Key => $Val ){
			$Set = $Val['download'] ? unserialize($Val['download']) : array("statis" => 0, "hide" => false, "web" => false);
			$Val['md5'] = $Val['blogid'].'-'.$Val['aid'];
			if( $Set['hide'] === true ){ $File_Out++; }
			if( $Set['web'] === true ){ $File_Web++; }
			if( DownloadMe_File( $Val['filepath'] ) === false && $Set['web'] !== true ){ $File_Unu++; }
			if( !empty($ActMe) && $ActMe == "web" && $Set['web'] === true ){ $ListEcho[] = $Val; }
			if( !empty($ActMe) && $ActMe == "out" && $Set['hide'] === true ){ $ListEcho[] = $Val; }
			if( !empty($ActMe) && $ActMe == "unu" && DownloadMe_File( $Val['filepath'] ) === false && $Set['web'] !== true ){ $ListEcho[] = $Val; }
			if( empty($ActMe) ){ $ListEcho[] = $Val; }
		}
	}
	$PageMe = pagination(count($ListEcho),$Page_Num,$Page,'./plugin.php?plugin=download'.($ActMe?'&downAct='.$ActMe:'').'&page=');
?>
<div class="DownloadMe">
	<div class="Title"><b>附件管理</b> - 就是这么方便！
  	<div class="navMe">
      <a title="外链附件"href="./plugin.php?plugin=download&downAct=web" class="urlimg <?php echo $ActMe == 'unu' ? 'strong' : ''?>">
      ( <font class="File_Unu"><?php echo $File_Web?></font> )</a>
      <a title="异常附件" href="./plugin.php?plugin=download&downAct=unu" class="unu <?php echo $ActMe == 'unu' ? 'strong' : ''?>">
      ( <font class="File_Unu"><?php echo $File_Unu?></font> )</a>
      <a title="暂停下载" href="./plugin.php?plugin=download&downAct=out" class="out <?php echo $ActMe == 'out' ? 'strong' : ''?>">
      ( <font class="File_Out"><?php echo $File_Out?></font> )</a>
      <a title="全部附件" href="./plugin.php?plugin=download" class="anImg start <?php echo empty($ActMe) ? 'strong' : ''?>">
      ( <font class="File_All"><?php echo count($DownList['File'])?></font> )</a>
      <a href="./plugin.php?plugin=download&downAct=gov" class="anImg config <?php echo $ActMe == 'gov' ? 'strong' : ''?>">
      设置</a>
      <a href="javascript:FileActMe('UpCache');" class="anImg cache">缓存</a>
      <a href="javascript:VersionMe();" class="anImg upload">更新</a>
    </div>
  </div>
  <form action="" method="post" enctype="application/x-www-form-urlencoded">
	<div class="List">
    <div class="notes">注：
    <?php echo empty($ActMe) ? '在删除日志时关联附件也会被删除' : ''?>
    <?php echo $ActMe == 'out' ? '文章页面输出附件列表时, 暂停下载的附件将会被剔除 (不输出) ..' : ''?>
    <?php echo $ActMe == 'unu' ? '不存在实体文件, 只存在数据库关联的附件..' : ''?>
    <?php echo $ActMe == 'gov' ? '停用此插件后配置将有可能会被清空！(取决于缓存文件 "downloadMeData_Inc.php")...' : ''?>
    <?php echo $ActMe == 'web' ? '网络上的共享附件,程序不会将附件下载到本地服务器中,只做下载点击的统计！' : ''?>
    </div>
  <?php if( $ActMe == "gov" ): $Inc = DownloadMe_Ret("Inc"); ?>
  <input name="DownloadMeGov" type="hidden" value="true" />
    <table width="100%" class="item_list">
      <thead>
        <tr>
          <th class="tdcenter"><b>设置</b></th>
          <th class="tdcenter"><b>说明</b></th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><textarea name="echo_file" cols="50" rows="5"><?php echo $Inc["file"] ? $Inc["file"] : ECHO_FILE_INC ?></textarea></td>
          <td>
          前台输出附件列表时输出的格式，( Html代码 ) 可用参数：<br>
          [url] : 隐链接。[path] : 明链接。[nameSub] : 截取后的附件名<br>
          [name] : 完整附件名。[tong] : 下载统计。[time] : 上传时间。[size] : 文件大小<br>
          注:<font class="redMe">只有<strong>[url] 隐链接</strong>才能被统计！</font>
          </td>
        </tr>
        <tr>
          <td><textarea name="echo_wrap" cols="50" rows="3"><?php echo $Inc["wrap"] ? $Inc["wrap"] : '' ?></textarea></td>
          <td>
          前台输出附件列表包裹代码，( Html代码 ) 可用参数：<br>
          [list] : 附件列表<br>
          这个参数会把附件列表包裹起来，(用于自定义CSS)。
          </td>
        </tr>
        <tr>
          <td>
          	<label><input name="echo_mov" type="radio" value="0" <?php echo empty($Inc["mov"])?'checked':''?> />钩子输出模式</label>
            <label><input name="echo_mov" type="radio" value="1" <?php echo ($Inc["mov"] == 1)?'checked':''?> />日志输出模式</label>
          </td>
          <td>
          	日志输出模式需要修改前台正在使用的模版文件"module.php"的 blog_att() 功能类<br>
            将其替换成 " <font class="redMe">function blog_att($blogid){ doAction('baby_blog_atts',$blogid); }</font> "<br>
          	如果不想修改模板文件，那么就选择钩子模式吧，<br>
          </td>
        </tr>
        <tr>
          <td>
          	<label><input name="echo_down" type="radio" value="0" <?php echo empty($Inc["down"])?'checked':''?> />重新打开博客时</label>
            <label><input name="echo_down" type="radio" value="1" <?php echo ($Inc["down"] == 1)?'checked':''?> />24小时</label>
            <label><input name="echo_down" type="radio" value="2" <?php echo ($Inc["down"] == 2)?'checked':''?> />每IP</label>
          </td>
        	<td>客户下载某个附件后设置时间段内重复下载同一附件只统计一次，(IP过滤)<br>IP缓存数据 "downloadMeData_Gip.php"</td>
        </tr>
        <tr>
          <td>
					<label><input name="echo_img" type="radio" value="0" <?php echo empty($Inc["img"])?'checked':''?> />直接输出图片</label>
					<label><input name="echo_img" type="radio" value="1" <?php echo ($Inc["img"] == 1)?'checked':''?> />仍然以附件形式输出</label>
					</td>
          <td>图片类型附件的输出方法</td>
				</tr>
        <tr>
          <td>
					<label><input name="echo_key" type="radio" value="0" <?php echo empty($Inc["key"])?'checked':''?> />关闭下载验证</label>
					<label><input name="echo_key" type="radio" value="1" <?php echo ($Inc["key"] == 1)?'checked':''?> />打开下载验证</label>
					</td>
          <td>前台下载附件是否需要输入验证码</td>
				</tr>
        <tr>
          <td>
					<label><input name="echo_rob" type="radio" value="0" <?php echo empty($Inc["rob"])?'checked':''?> />关闭防盗链</label>
					<label><input name="echo_rob" type="radio" value="1" <?php echo ($Inc["rob"] == 1)?'checked':''?> />打开防盗链</label>
					</td>
          <td>前台附件防盗链设置开关</td>
				</tr>
      </tbody>
    </table>
  <?php else: ?>
    <div class="normal">
      <table width="100%" border="0" cellpadding="0" cellspacing="0">
      	<thead>
          <tr>
            <th width="20"></th>
            <th>附件名</th>
            <th width="90">大小</th>
            <th width="70">统计</th>
            <th width="120">时间</th>
            <th width="18"></th>
            <th class="operate"></th>
          </tr>
      	</thead>
        <tbody>
        <?php
				$ListNum = 0;
				for ($i=($Page - 1)*$Page_Num; $i<$Page*$Page_Num; $i++) {
					if( !isset($ListEcho[$i]) ) { continue; }
					$Echo = $ListEcho[$i]; $ListNum++;
					$Set = $Echo['download'] ? unserialize($Echo['download']) : array("statis" => 0, "hide" => false, "web" => false);
					$Onsel = ( $Set['web'] === true )? 'weburl' : ( DownloadMe_File( $Echo['filepath'] ) === false ? "onsels" : "onsel" );
					$OnseR = array('weburl' => '外链附件', 'onsels' => '错误附件', 'onsel' => '正常附件');
					$Show = ( $Set['hide'] === true ) ? "hide" : "show";
					$BlogId = $Echo['blogid']; $BlogAr = $DownList['Log'][$BlogId]; $BolgUrl = Url::log($BlogId);
					$BlogMe = '
					<a href="'.($BlogAr[1]=='n'?$BolgUrl:'write_log.php?action=edit&gid='.$BlogId).'" target="_blank" class="BlogUrl">'.
					($BlogAr[0]?$BlogAr[0]:'无标题') . ($BlogAr[1]=='y'?'( 草稿-未发布 )':'') . '</a>';
					$CacheAid = unserialize( $_SESSION['DownloadCacheAid'] );
					if( is_array($CacheAid) === true ){
						$_SESt = (($CacheAid['aid'] != $Echo['blogid'] && $CacheAid['t'] === false) ? true : false);
					}else{ $_SESt = true; }
					if( $_SESt === true ){
						echo'<tr class="BlogTitle trFile_'.$BlogId.'"><td></td><td colspan="6">Title: '.
						$BlogMe.'ID: <font class="redMe">'.$BlogId.'</font> <a title="增加外链附件" href="javascript:AddUrl('.$BlogId.');" class="addurl"></a></td></tr>';
						$_SESt = false;
					}
					$_SESSION['DownloadCacheAid'] = serialize( array('aid' => $Echo['blogid'], 't' => $_SESt) );
				?>
          <tr class="trFile_Edit trFile_<?php echo $Echo['aid']?>_<?php echo $BlogId?>">
            <td><input name="check[]" class="check" type="checkbox" value="<?php echo $Echo['md5']?>" /></td>
            <td class="name"><input name="filename" class="edit" type="text" value="<?php echo $Echo['filename']?>" size="55" /></td>
            <td>
						<?php
						if( $Set['web'] === true && $Echo['filesize'] <= 0 ){
							echo '<span class="hSize size_'.$Echo['md5'].'" title="获取文件大小" onclick="AddUrl(\''.$Echo['md5'].'\',false)">'.changeFileSize($Echo['filesize']).'</span>'; 
						}elseif( $Set['web'] === true && $Echo['filesize'] > 0 ){
							echo '<span class="hSize upSize size_'.$Echo['md5'].'" title="更新文件大小" onclick="AddUrl(\''.$Echo['md5'].'\',false)">'.changeFileSize($Echo['filesize']).'</span>'; 
						}else{
							echo changeFileSize($Echo['filesize']);
						}
						?>
						</td>
            <td><input name="statis" class="edit" type="text" value="<?php echo $Set['statis']?>" size="3" /></td>
            <td><?php echo date('Y-m-d H:i',$Echo['addtime'])?></td>
            <td><span class="imganMe <?php echo $Onsel?>" title="<?php echo $OnseR[$Onsel] ?>"></span></td>
            <td class="operate">
            	<a href="javascript:FileMove('<?php echo $Echo['md5']?>');" class="imganMe global" title="移动"></a>
              <a href="<?php echo $Echo['filepath']?>" target="_blank" class="imganMe vlog" title="下载"></a>
              <a href="javascript:FileActMe('Switch','<?php echo $Echo['md5']?>');" class="imganMe <?php echo $Show?>" title="暂停/开启"></a>
              <a href="javascript:FileActMe('Dell','<?php echo $Echo['md5']?>');" class="imganMe dell" title="删除"></a>
            </td>
          </tr>
        <?php
				}
				if( $ListNum <= 0 ){
				?>
          <tr>
            <td width="20"></td>
            <td colspan="6" style="color:red;"> G_G"… 没有找到！。。。</td>
          </tr>
        <?php
				}
				?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  </div>
  <input name="reset" type="reset" style="display:none;" />
  </form>
  <div class="PageMe">
    <?php if( $ActMe != "gov" ): ?>
  	<div class="an">
    	<a href="javascript:checkMe(true);">全选</a>
    	<a href="javascript:checkMe(false);">反选</a>
    	<a class="EditMe" href="javascript:FileActMe('Edit');">修改</a>
      <a class="ResetMe" href="javascript:FileActMe('Reset');">重置</a>
    	<a href="javascript:FileActMe('Switch');">暂停/开启下载</a>
    	<a href="javascript:FileActMe('Dell');">删除选中</a>
    </div>
  	<?php echo $PageMe?>
    <?php else: ?>
  	<div class="an">
    	<a href="javascript:FileActMe('Gov');">修改</a>
      <a class="ResetMe" href="javascript:FileActMe('Reset');">重置</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php
}
?>