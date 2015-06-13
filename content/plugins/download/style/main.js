// JavaScript Document
$(function(){
	$(".edit:text").hover(
		function(){ $(this).css("border","1px solid #F90") },
		function(){ $(this).css("border","1px solid #FFF") }
	);
	$("#download").addClass('sidebarsubmenu1');
	if( Stat_Version_Install === true ){ VersionMe(); }
});
/* 全选、反选 */
function checkMe(T){
	if(T === true ){ $(':checkbox').each(function(){$(this).attr('checked',true)}); return; }
	$(':checkbox').each(function(){$(this).attr('checked',!this.checked)});
}
function VersionMe(){
	if( Stat_Version_DownloadMe_Url && Stat_Version_DownloadMe ){
		MsgMe('更新版本检测中...(请稍后)', 'verMe', 30);
		$.getJSON(Stat_Version_DownloadMe_Url, Stat_Version_DownloadMe, function(d){
			if( d.msg ){ MsgMe(d.msg, 'verMe', 5); }
		});
	}
}
function MsgMe(M,S,T){
	var Css = (S ? (S===true?'actived':'actived ' +S) : 'error'), Time = (T ? T : (T==0 ? 0 : 3)), Num = 0;
	if( $('.DownloadMe .List .notes').children().is(".MsgAct") === true ){
		clearInterval(MsgTimer); $('.DownloadMe .List .notes').find(".MsgAct").remove();
	}
		$('.DownloadMe .List').find(".notes").append(function(){
			return $("<span />").addClass('MsgAct ' + Css).html( M )
				.append($("<span />").addClass("redMe MsgMe").attr("title","点击关闭！").text("Exit").click(function(){
					clearInterval(MsgTimer); $('.MsgAct').remove();
				}).css("cursor","pointer"))
		});
	if( Time > 0 ){
		MsgTimer = setInterval(function (){
			if( Num > Time ){ clearInterval(MsgTimer); $('.DownloadMe .List .notes').find(".MsgAct").remove(); return; }
			$('.DownloadMe .List .notes .MsgAct').find(".redMe").html(Num+'/'+Time); Num++;
		},1000);
	}
}
function AddUrl(Gid, Add){
	if(Add === true){
		var Name = $("input[name='addName']").val();
		var Url = $("input[name='addUrl']").val();
		MsgMe('正在添加附件。。。' + Name, 'verMe', 30);
		$.post("?downJsonMe=?", {downloadMe:"Act[=]addUrl[#]Name[=]"+Name+"[#]Url[=]"+Url+"[#]Gid[=]" + Gid}, function(d){
			if( d.error ){ MsgMe(d.error,false); }else if( d.succ ){
				MsgMe(d.succ,true,2); window.location.reload();
			}
		},'json');
	}else if(Add === false){
		MsgMe('正在获取附件信息。。。', 'verMe', 30);
		$.post("?downJsonMe=?", {downloadMe:"Act[=]addSize[#]Gid[=]" + Gid}, function(d){
			if( d.error ){
				MsgMe(d.error,false);
				if( d.data ){
					$(".size_" + Gid).html(d.data);
				}
			}else if( d.succ && d.data ){
				$(".size_" + Gid).html(d.data).addClass('upSize');
				MsgMe(d.succ,true,2);
			}
		},'json');
	}else if( !Add ){
		var M = $("<span />")
		.append( Gid + ' 附件名称:')
		.append( $("<input name='addName' size='20' />") )
		.append('URL:')
		.append( $("<input name='addUrl' size='50' />").val("http://") )
		.append($("<a />").text("提交").attr({'href':'javascript:AddUrl('+Gid+',true);','title':'提交附件'}));
		MsgMe(M,'addUrl',0);
	}
}
function FileMove(Nid, Move){
	if(Move === true){
		var Mid = $("input[name='FileMove']").val();
		MsgMe('正在移动附件。。。', 'verMe', 30);
		$.post("?downJsonMe=?", {downloadMe:"Act[=]FileMove[#]Move[=]"+Mid+"[#]Gid[=]"+Nid}, function(d){
			if( d.error ){ MsgMe(d.error,false); }else if( d.succ ){
				MsgMe(d.succ,true,2); window.location.reload();
			}
		},'json');
	}else{
		var M = $("<span />")
		.attr("title","请填写文章id")
		.append('移动到目标文章下:')
		.append($("<input name='FileMove' class='FileMove' />"))
		.append($("<a />").text("提交").attr({'href':'javascript:FileMove(\''+Nid+'\',true);','title':'确认移动'}));
		MsgMe(M,'addUrl',0);
	}
}
function FileActMe(S,Id,Each){
	var Gid = '',
		File_Out = $(".DownloadMe").find('.navMe').find('font.File_Out'),
		File_All = $(".DownloadMe").find('.navMe').find('font.File_All');
	if(!Id&&S!="UpCache"&&S!="Edit"&&S!="Gov"&&S!="Reset"&&$(":checked").size()<=0){ MsgMe('请选择要操作的附件！','verMe'); return; }
	if( Id ){
		if( S == "Dell" && !Each && !confirm("你确定要删除这个附件吗？") ){
			return false;
		}else{
			$.post("?downJsonMe=?", {downloadMe:"Act[=]" + S + "[#]Gid[=]" + Id}, function(data){
				if( data.succ && data.fsucc){
					///////////////////////////////////////////////////
					if( S == "Switch" ){
						$.each(data.fsucc, function(i,n){
							var HideCss = ( n === false ? "hide" : "show" );
							$("tr.trFile_" + i).find("." + HideCss).removeClass(HideCss).addClass(function(){
								return "imganMe " + ( n === true ? "hide" : "show" );
							});
							File_Out.html(function(){
								var Text = parseInt($(this).text()); return( n === true ? Text+1 : Text-1 );
							});
						});
					///////////////////////////////////////////////////
					}else if( S == "Dell"){
						$.each(data.fsucc, function(i,n){
							$("tr.trFile_" + i).remove();
							File_All.html(function(){ return $(this).text() - 1; });
						});
					}
					MsgMe(data.succ,true,6);
				}
			},'json');
		}
	}else{
		 Gid = trFileGid();
		///////////////////////////////////////////////////
		if( S == "UpCache" ){
			MsgMe('正在更新缓存。。。',true);
			$.post("?downJsonMe=?", {downloadMe:"Act[=]" + S + "[#]Gid[=]true"}, function(d){
				if( d.succ ){ MsgMe(d.succ,true,1); window.location.reload(); } return;
			},'json');
		}else
		///////////////////////////////////////////////////
		if( S == "Gov" ){
			$('form:first').submit();
		}else
		///////////////////////////////////////////////////
		if( S == "Edit" ){
			var setTing = {};
			$("div").data("NumCache",0);
			$("tr.trFile_Edit").each(function(){
				var TrObj = SVal = NVal = TrGid = Val = Edit = '';
				TrObj = $(this).children("td");
				TrGid = TrObj.children(":checkbox").val();
				TrObj.children(":text[name='filename']").val(function(){
					Edit = $(this).data("Edit");
					Val = (( Each === true && Edit ) ? Edit : $(this).val() );
					if( Val != this.defaultValue ){
						NVal = "[#]Nval[=]" + Val; $(this).data("Edit", (Edit ? Edit : this.defaultValue) ); this.defaultValue = Val;
					} return Val;
				});
				TrObj.children(":text[name='statis']").val(function(){
					Edit = $(this).data("Edit");
					Val = (( Each === true && Edit ) ? Edit : $(this).val() );
					if( Val != this.defaultValue ){
						SVal = "[#]Sval[=]" + Val; $(this).data("Edit", (Edit ? Edit : this.defaultValue) ); this.defaultValue = Val;
					} return Val;
				});
				if( NVal || SVal ){
					setTing = {downloadMe:"Act[=]" + S + "[#]Gid[=]" + TrGid + (NVal?NVal:'') + (SVal?SVal:'')};
					$.post("?downJsonMe=?", setTing, function(d){
						if( d.error ){ MsgMe(d.error,false); }else if( d.succ ){
							$("div").data("NumCache", ( $("div").data("NumCache")+1 ));
							MsgMe((Each===true?'成功还原':d.succ) +'( '+ $("div").data("NumCache") + ' )项',true);
						}
					},'json');
					if( !Each && !$(".DownloadMe .PageMe .an").children().is(".RestoreMe") ){
						$(".DownloadMe .PageMe .an").children(".EditMe").after(function(){
							return $("<a class='RestoreMe redMe'/>").attr("href","javascript:FileActMe('Reset',false,true)").html("还原到修改前");
						});
					}
				}
			});
		}else
		///////////////////////////////////////////////////
		if( S == "Reset"){
			$('form:first').children(":[name='reset']").click();
			if( Each === true && $("div").data("NumCache") >= 1 ){
				FileActMe('Edit',false, true);
				$(".DownloadMe .PageMe .an").children(".RestoreMe").remove();
			}
		}else
		///////////////////////////////////////////////////
		if( S == "Switch" || S == "Dell" ){
			if( S == "Dell" && !confirm("你确定要批量删除附件吗？ --- 删除后将无法恢复！")){
				return false; }else{ FileActMe(S, Gid, true); return; }
		}
	}
}
function trFileGid() {
	var setTing = new Array();
	$("tr.trFile_Edit").find(":checked").each(function(i){ setTing[i] = $(this).val(); });
	return setTing.join(',');
}
