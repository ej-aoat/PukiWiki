<?php
/////////////////////////////////////////////////
// PukiWiki - Yet another WikiWikiWeb clone.
//
// $Id: areaedit.inc.php,v 0.18 2004/08/30 00:55:10 sha Exp $
//
/* 
*プラグイン areaedit
 指定した位置のみ編集可能にする

*Usage
 #areaedit([start|end|btn:<text>|nofreeze|noauth|collect[:<page>]])
 &areaedit([nofreeze|noauth|preview[:<num>]]){<text>};

*/
/////////////////////////////////////////////////
// プレビューで編集個所より前の部分も表示するモード
define('PREVIEW_DISP_ABOVE',TRUE);

function plugin_areaedit_init()
{
	$messages = array(
		'_areaedit_messages' => array(
			'title_error'    => 'Error in areaedit',
			'body_error'     => '予期しないエラーです。正常な呼出しではありません。',
			'no_page_error'  => '$1 のページは存在しません',
			'btn_name'       => '[編集]',
			'btn_name_collect' => '収集',
			'btn_name_inline'=> '[e]',
//			'msg_cannotedit' => '[編集禁止]',
			'msg_cannotedit' => '',
//			'msg_cannotedit_inline' => '[x]',
			'msg_cannotedit_inline' => '',
			'msg_unfreeze_inline' => '[uf]',
			'title_auth'     => '$1には編集権限がありません。',
			'title_edit'     => '\'$1\' の第$2 areaeditの編集',
			'title_preview'  => '\'$1\' の第$2 areaeditのプレビュー',
		),
	);
	set_plugin_messages($messages);
}
//========================================================
function plugin_areaedit_convert()
{
	global $script,$vars,$digest,$_areaedit_messages, $_msg_unfreeze;
	static $numbers = array();
	static $starts = array();

	$page = $vars['page'];
	if (!array_key_exists($page,$numbers))	$numbers[$page] = 0;
	$areaedit_no = $numbers[$page]++;

	$end_flag = $nofreeze = $noauth =  0;
	$collect = '';
	$btn_name = $_areaedit_messages['btn_name'];
   	if ( func_num_args() ){
        foreach ( func_get_args() as $opt ){
	      	if ( $opt == 'start' ){
    	         $end_flag = 0;
       		}
        	else if ( $opt == 'end' ){
            	$end_flag = 1;
        	}
        	else if ( $opt == 'nofreeze' ){
				$nofreeze = 1;
			}
        	else if ( $opt == 'noauth' ){
				$noauth = 1;
        	}
        	else if ( preg_match('/^collect(?::(.+))?$/',$opt,$matches) ){
				$collect = $matches[1] ? $matches[1] : $page;
			}
        	else if ( preg_match('/^btn:(.*)$/',$opt,$matches) ){
				$btn_name = $matches[1];
			}
		}
    }
    if ( $end_flag ) {
		return "<div></div>";
    }
	if (!array_key_exists($page,$starts))	$starts[$page] = 0;
	$areaedit_start_no = $starts[$page]++;

    $f_page   = rawurlencode($page);
	if ( $noauth == 0 and  ! edit_auth($page,FALSE,FALSE) ){
		return <<<EOD
<div style="margin:0px 0px 0px auto;text-align:right;" title="$areaedit_start_no">
{$_areaedit_messages['msg_cannotedit']}
</div>
EOD;
	}
	if ( $nofreeze == 0 and is_freeze($page) ){
		 return  <<<EOD
<div style="margin:0px 0px 0px auto;text-align:right;">
[<a href="$script?cmd=unfreeze&amp;page=$f_page" title="$areaedit_start_no">$_msg_unfreeze</a>]
</div>
EOD;
	}
	if ( $collect ) {
		if ( $btn_name == $_areaedit_messages['btn_name'] ) {
			$btn_name  = $_areaedit_messages['btn_name_collect'];
		}
		$s_page   = htmlspecialchars($page);
		$s_refer  = htmlspecialchars($collect);
		$s_digest = htmlspecialchars($digest);
	    return <<<EOD
<div style="margin:0px auto 0px 0px;text-align:left;" title="collect">
<form action="$script" method="post">
 <div class="edit_form">
  <input type="hidden" name="plugin" value="areaedit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="refer"  value="$s_refer" />
  <input type="hidden" name="digest" value="$s_digest" />
  <input type="hidden" name="inline_plugin" value="0" />
  <input type="hidden" name="areaedit_no"   value="$areaedit_no" />
  <input type="submit" name="collect"       value="$btn_name" />
  <a href="$script?$s_refer" name="areaedit$areaedit_no">$collect</a>
</div>
</form>
</div>
EOD;
	}
	else {
 	   return <<<EOD
<div style="margin:0px 0px 0px auto;text-align:right;">
<a href="$script?plugin=areaedit&amp;areaedit_no=$areaedit_no&amp;inline_plugin=0&amp;page=$f_page&amp;digest=$digest" title="$areaedit_start_no" name="areaedit$areaedit_no">$btn_name</a>
</div>
EOD;
	}
}
//========================================================
function plugin_areaedit_inline()
{
	global $script,$vars,$digest, $_areaedit_messages;
	static $numbers = array();

	$page = $vars['page'];
	if (!array_key_exists($page,$numbers))	$numbers[$page] = 0;
	$areaedit_no = $numbers[$page]++;

	$args = func_get_args();
	$str = array_pop($args);
	$string = "";
	if ( $str != '' ){
		$string =<<<EOD
<span onmouseover="javascript:this.style.backgroundColor='#ffe4e1';" onmouseout="javascript:this.style.backgroundColor='transparent';">$str</span>
EOD;
	}
	$ndigest = $digest;
	$nofreeze = $noauth = $inline_preview = 0;
	foreach ( $args as $opt ){
		$opt = trim($opt);
        if ( $opt == 'nofreeze' ){
			$nofreeze = 1;
		}
        else if ( $opt == 'noauth' ){
			$noauth = 1;
        }
        else if ( preg_match('/^preview(?::(\d+))?$/',$opt,$match) ){
			$num = $match[1];
			if ( $num == '' ) $num = 99;
			$inline_preview = $num;
        }
	}
	$f_page   = rawurlencode($page);
	if ( $noauth == 0 and  ! edit_auth($page,FALSE,FALSE) ){
		return $string . $_areaedit_messages['msg_cannotedit_inline'];
	}
	if ( $nofreeze == 0 and is_freeze($page) ){
		 return  <<<EOD
$string<a href="$script?cmd=unfreeze&amp;page=$f_page">{$_areaedit_messages['msg_unfreeze_inline']}</a>
EOD;
	}

	$f_digest = rawurlencode($ndigest);
	$btn_name = $_areaedit_messages['btn_name_inline'];
	
	return <<<EOD
$string<a href="$script?plugin=areaedit&amp;areaedit_no=$areaedit_no&amp;page=$f_page&amp;inline_plugin=1&amp;inline_preview=$inline_preview&amp;digest=$f_digest" title="$areaedit_no" name="areaedit$areaedit_no">$btn_name</a>
EOD;
}
//========================================================
function plugin_areaedit_action()
{
	global $vars, $_areaedit_messages;
	
	if ( ! is_page($vars['page']) ){
		$error = str_replace('$1', $vars['page'], $_areaedit_messages['no_page_error']);
		return array(
			'msg'  => $_areaedit_messages['title_error'], 
			'body' => $error,
		);
	}
	if ( array_key_exists('inline_plugin', $vars) ) {
		if ( $vars['inline_plugin'] == 1 ) {
			return plugin_areaedit_action_inline();
		}
		else {
			return plugin_areaedit_action_block();
		}
	}
	return array(
		'msg'  => $_areaedit_messages['title_error'], 
		'body' => $_areaedit_messages['body_error'],
	);
}
//========================================================
function plugin_areaedit_action_inline()
{
	global $vars,$script,$cols,$rows, $_areaedit_messages, $_msg_unfreeze;
	global $_title_collided,$_msg_collided,$_title_updated, $_title_isfreezed;
	$notimestamp = 0;
	$str_areaedit = 'areaedit';
	$len_areaedit = strlen($str_areaedit) + 1;
	$title = $body = $headdata = $targetdata = $taildata =  '';

	$areaedit_no = 0;
	if ( array_key_exists('areaedit_no', $vars) ) $areaedit_no = $vars['areaedit_no'];

	$postdata_old =	array();
	$page = $vars['page'];
	$inline_preview = array_key_exists('inline_preview',$vars) ? $vars['inline_preview'] : 0;

	if (  ! array_key_exists('preview',$vars) ) {
		if ( array_key_exists('write',$vars)) {
			$postdata_old = preg_replace('/$/',"\n", 
					explode("\n", $vars['headdata'] . $vars['taildata']));
		}
		else {
			$postdata_old = get_source($page);	
			$postdata_old = str_replace("\r",'', $postdata_old);
		}

	$ic = new InlineConverter(array('plugin'));
	$areaedit_ct = $skipflag = 0;
	$found_nofreeze = $found_noauth =  0;
	$skipflag = 0;
	$update_flag = FALSE;
	foreach($postdata_old as $line)
	{
		if ( $skipflag ) {
		    $taildata .= $line;
	    	continue;
		}
		if ( substr($line,0,1) == ' ' || substr($line,0,2) == '//' ){
		    $headdata .= $line;
	    	continue;
		}
		if ( ! preg_match("/&$str_areaedit/", $line, $match) ){
			$headdata .= $line;
			continue;
		}
		$pos = 0;
		$arr = $ic->get_objects($line,$page);
		while ( count($arr) ){
			$obj = array_shift($arr);
			if ( $obj->name != $str_areaedit ) continue;
			$pos = strpos($line, '&' . $str_areaedit, $pos);
			$pos += $len_areaedit;
			if ( $areaedit_ct++ < $areaedit_no ) continue;
			$r_line = substr($line,$pos+strlen($obj->text)-$len_areaedit-1); // };.....
			
			$add_flag = 0;
			switch ( substr($line,$pos,1) ) {
				case '(':
					$pos += strlen($obj->param) + 2;
					break;
				case ';':
				case '{':
					$add_flag = 1;
					break;
			}
			$l_line = substr($line,0,$pos);                          // ....&areaedit
			if ( $add_flag ) $l_line .= '()';
			$options = explode(',', $obj->param);
			foreach ( $options as $opt ){
				$opt = trim($opt);
       			if ( $opt == 'nofreeze' ){
					$found_nofreeze = 1;
				}
        		else if ( $opt == 'noauth' ){
					$found_noauth = 1;
        		}
        		else if ( preg_match('/^preview(?::(\d+))?$/',$opt,$match) ){
					$num = $match[1];
					if ( $num == '' ) $num = 99;
					$inline_preview = $num;
        		}
			}

//echo '/param=',$obj->param;

			$headdata .= $l_line;
			$targetdata = $obj->body;
			$taildata = $r_line;
			$skipflag = 1;
			break;
		}
		if ( ! $skipflag ) $headdata .= $line;
	}
/*
echo '/page=',$page;
echo '/nofreeze=',$found_nofreeze;
echo '/noauth=',$found_noauth;
echo '/postdata_old=',join('/',$postdata_old);
*/
	$vars['areaedit_start_no'] = $areaedit_no;
	if ( $found_noauth == 0 ){
		edit_auth($page,TURE,TURE);
	}
	if ( $found_nofreeze == 0 and is_freeze($page) ){
		$f_page = rawurlencode($page);
		$title = str_replace('$1', $page, $_title_isfreezed);
		return array(
			'msg'=> $title,
			'body' => "<h1>$title</h1>[<a href=\"$script?cmd=unfreeze&amp;page=$f_page\">$_msg_unfreeze</a>]",
		);
	}
}
	else if ( array_key_exists('headdata', $vars) and array_key_exists('taildata', $vars) ){
		$headdata = $vars['headdata'];
		$taildata = $vars['taildata'];
	}

	if ( array_key_exists('areaedit_msg', $vars) ){
		$targetdata = str_replace("\n", '&br;', str_replace("\r",'',$vars['areaedit_msg']));
	}
	if (array_key_exists('write',$vars)) {
		$nowdata = $headdata . '{' . $targetdata . '}' . $taildata;
		return plugin_areaedit_write($page, $targetdata, $nowdata,$areaedit_no);
	}
	$retval = plugin_areaedit_preview($page, $targetdata, $headdata, $taildata, $inline_preview);
	if (array_key_exists('preview',$vars) ) return $retval;
	
	$title = str_replace('$1',$page,$_areaedit_messages['title_edit']);
	$title = str_replace('$2',$vars['areaedit_start_no'],$title);
	return array(
		'msg'=> $title,
		'body'=> $retval['body'],
	);
}
//========================================================
function plugin_areaedit_action_block()
{
	global $script,$vars;
	global $_areaedit_messages, $_title_isfreezed, $_btn_freeze, $_msg_unfreeze;
	
	$page    = $vars['page'];
	$collect = array_key_exists('refer', $vars) ? $vars['refer'] : '';
	
	$headdata = $targetdata = $taildata = $para = $tailpara = '';
	if (  ! array_key_exists('preview',$vars) ) {
		if ( array_key_exists('write',$vars)) {
			$postdata_old = preg_replace('/$/',"\n", 
					explode("\n", $vars['headdata'] . "\n" . $vars['taildata']));
		}
		else {
			$postdata_old = get_source($page);	
			$postdata_old = str_replace("\r",'', $postdata_old);
		}
		$options = array();
		$areaedit_ct = $areaedit_start_no = 0;
		$areaedit_no = 0;
		if ( array_key_exists('areaedit_no', $vars) ) $areaedit_no = $vars['areaedit_no'];
	
		$flag = $para_flag = $found_end = $found_nofreeze = $found_noauth = 0;
		foreach ( $postdata_old as $line ){
			if ( $flag == 0 ) {
				$headdata .= $line;
			}
			if ( ( $flag == 0 or $flag == 1 ) and 
				preg_match('/^#areaedit(?:\(([^)]+)\))?\s*$/', $line, $matches) ){
				$options = preg_split('/\s*,\s*/', $matches[1]);
				if ( $areaedit_ct ++ == $areaedit_no ) {
					$flag = $para_flag = 1;
					foreach ( $options as $opt ){
						$opt = trim($opt);
       					if ( $opt == 'nofreeze' ){
							$found_nofreeze = 1;
						}
        				else if ( $opt == 'noauth' ){
							$found_noauth = 1;
        				}
						else if ( preg_match('/^collect(?::(.+))?$/',$opt,$mat) ){
							$collect = $mat[1] ? $mat[1] : $page;
        				}
					}
				}
				else {
					if ( in_array('end', $options) ){
						if ( $flag == 1 ){
							$found_end = 1;
						}
					}
					else if ( $flag == 0 ) {
						$areaedit_start_no ++;
					}
					if ( $flag == 1 ){
						$flag = 2;
						$taildata .= $line;
					}
					if ( $para_flag >= 1 ){
						$para_flag = 2;
						$tailpara .= $line;
					}
				}
				continue;
			}
			else {
				switch ( $flag ) {
					case 0:	break;
					case 1: $targetdata .= $line; break;
					case 2: $taildata   .= $line; break;
				}
				if ( $para_flag == 1 and  preg_match('/^\n?$/',$line, $matches)) {
					$para_flag = 2;
				}
				switch ( $para_flag ) {
					case 0:	break;
					case 1: $para     .= $line; break;
					case 2: $tailpara .= $line; break;
				}
			}
		}
		if ( $found_end == 0 ){
			$vars['block'] = 1;
			$targetdata = $para;
			$taildata   = $tailpara;
		}
		$vars['areaedit_start_no'] = $areaedit_start_no;

		if ( $found_noauth == 0 ){
			edit_auth($page,TURE,TURE);
		}
		if ( $found_nofreeze == 0 and is_freeze($page) ){
			$f_page = rawurlencode($page);
			$title = str_replace('$1', $page, $_title_isfreezed);
			return array(
				'msg'=> $title,
				'body' => "<h1>$title</h1>[<a href=\"$script?cmd=unfreeze&amp;page=$f_page\">$_msg_unfreeze</a>]",
			);
		}
	}
	else if ( array_key_exists('headdata', $vars) and array_key_exists('taildata', $vars) ){
		$headdata = $vars['headdata'];
		$taildata = $vars['taildata'];
	}
	else {
	}
	
	$update_flag = FALSE;
	if ( array_key_exists('areaedit_msg', $vars) ){
		$lines = expose("\n", str_replace("\r",'',$vars['areaedit_msg']));
		$update_flag = TRUE;
	}
	else if ( $collect ){
		if ( $collect == $page ) {
			$lines = plugin_areaedit_collect($page,$postdata_old);
			$update_flag = TRUE;
		}
		else if ( is_page($collect) ) {
			$lines = plugin_areaedit_collect($collect,get_source($collect));
			$update_flag = TRUE;
		}
	}
	if ( $update_flag ){
		$targetdata = '';
		foreach ( $lines as $line ) {
			if ( $vars['block'] == 1 ) $line = preg_replace('/^$/','//', $line);
			$targetdata .= preg_replace('/^(?=#areaedit)/', '//', $line) . "\n";
		}
		if ( $vars['block'] == 1 ) {
			$targetdata = preg_replace('/\n?\/\/\s*$/',"\n",$targetdata);
		}
		else {
			$targetdata = preg_replace('/\s+$/',"\n",$targetdata);
		}
	}
	if (array_key_exists('write',$vars)) {
		$nowdata = $headdata . $targetdata . $taildata;
		return plugin_areaedit_write($page, $targetdata, $nowdata,$areaedit_no);
	}
	$retval = plugin_areaedit_preview($page, $targetdata, $headdata, $taildata, 0);
	if (array_key_exists('preview',$vars) ) return $retval;

	$title = str_replace('$1',$page,$_areaedit_messages['title_edit']);
	$title = str_replace('$2',$vars['areaedit_start_no'],$title);
	return array(
		'msg'=> $title,
		'body'=> $retval['body'],
	);
}
//========================================================
function plugin_areaedit_collect($page,$postdata_old){
	$str_areaedit = 'areaedit';
	$len_areaedit = strlen($str_areaedit) + 1;

	$ic = new InlineConverter(array('plugin'));
	$outputs = array();
	$areaedit_ct = 0;
	foreach($postdata_old as $line)
	{
		if ( substr($line,0,1) == ' ' || substr($line,0,2) == '//' ) continue;
		if ( ! preg_match("/&$str_areaedit/", $line, $match) )	continue;
		$pos = 0;
		$arr = $ic->get_objects($line,$page);
		while ( count($arr) ){
			$obj = array_shift($arr);
			if ( $obj->name != $str_areaedit ) continue;
			$pos = strpos($line, '&' . $str_areaedit, $pos);
			$pos += $len_areaedit;
			$outputs[] = "+" .  $obj->body;
			$areaedit_ct ++;
		}
	}
	return $outputs;
}
//========================================================
// プレビュー
function plugin_areaedit_preview($refer,$targetdata,$headdata,$taildata,$inline_flag)
{
	global $script,$vars;
	global $_areaedit_messages,$_msg_preview;

	// 手書きの#freezeを削除
	$msg = $postdata_input = preg_replace('/^#freeze\s*$/m','',$targetdata);

	$preview_above = '';
	if ( $inline_flag ){
		$append = "";
		if ( preg_match('/^(.+)\n/', $taildata, $match) ) $append = $match[1];
		$head = str_replace("\r",'',$headdata);
		$ary = explode("\n", $head);
		if ( $inline_flag < count($ary) ) {
			$ary = array_splice($ary, -$inline_flag, $inline_flag);
		}
		$head = join("\n", $ary);
		if ( preg_match('/(.(?!\n\*|\n\n))+$/s',$head,$match) ) $head = $match[0];
		$preview_above = $postdata_input;
		if ( $preview_above != '' ) $preview_above = "{''" . $preview_above . "''}";
		$preview_above = $head . $preview_above . $append . "\n\n----\n\n";
		$preview_above = make_str_rules($preview_above);
		$preview_above = explode("\n",$preview_above);
		$preview_above = drop_submit(convert_html($preview_above));
		$preview_above = plugin_areaedit_strip_link($preview_above);
	}

	$body = "$_msg_preview<br />\n";
	$body .= "<br />\n" . $preview_above;

	if ($postdata_input != '')
	{
		$postdata_input = make_str_rules($postdata_input);
		$postdata_input = explode("\n",$postdata_input);
		$postdata_input = drop_submit(convert_html($postdata_input));
		
		$body .= <<<EOD
<div id="preview">
  $postdata_input
</div>
EOD;
	}
	$body .= areaedit_form($refer,$msg,$headdata,$taildata,$vars['digest']);
	
	$title = str_replace('$1',$refer,$_areaedit_messages['title_preview']);
	$title = str_replace('$2',$vars['areaedit_start_no'],$title);
	return array(
		'msg'=> $title,
		'body'=>$body,
	);
}
//========================================================
function plugin_areaedit_strip_link($str){
	$str = preg_replace('/<\/?a[^>]*>/i','',$str);
	return $str;
}
//========================================================
// 書き込み
function plugin_areaedit_write($refer, $postdata_input, $postdata,$areaedit_no)
{
	global $script,$vars;
	global $_title_collided,$_msg_collided_auto,$_msg_collided,$_title_deleted;
	
	$retvars = array();
	
// 手書きの#freezeを削除。このコメントを外してはいけない。
//	$postdata = preg_replace('/^#freeze\s*$/m','',$postdata);
	
	$oldpagesrc = join('',get_source($refer));
	$oldpagemd5 = md5($oldpagesrc);
	
	if ($oldpagemd5 != $vars['digest']) {
		$retvars['msg'] = $_title_collided;
		
		$vars['digest'] = $oldpagemd5;
		list($postdata,$auto) = do_update_diff($oldpagesrc,$postdata,$vars['original']);
		
		$retvars['body'] = ($auto ? $_msg_collided_auto : $_msg_collided)."\n";
		if (TRUE) {
			global $do_update_diff_table;
			$retvars['body'] .= $do_update_diff_table;
		}
		$retvars['body'] .= areaedit_form($refer,$postdata_input,$postdata,$oldpagemd5);
	}
	else {
		$notimestamp = !empty($vars['notimestamp']);
		if ( TRUE ){
			page_write($refer,$postdata,$notimestamp);
			if ($postdata != '') {
				header("Location: $script?".rawurlencode($refer)."#areaedit$areaedit_no");
				exit;
			}
		}
		else if ( $postdata != '' ) {
//echo 'postdata=',$postdata;
			return array('msg'=>'test view', 'body'=>convert_html($postdata));
		}

		$retvars['msg'] = $_title_deleted;
		$retvars['body'] = str_replace('$1',htmlspecialchars($refer),$_title_deleted);
		tb_delete($refer);
	}
	
	return $retvars;
}
//========================================================
// 編集フォームの表示
function areaedit_form($page, $postdata_input, $headdata, $taildata, $digest = 0)
{
	global $script,$vars,$rows,$cols,$hr,$function_freeze;
	global $_btn_preview,$_btn_repreview,$_btn_update,$_btn_freeze,$_msg_help,$_btn_notchangetimestamp;
	
	if ($digest == 0) {
		$digest = md5(join('',get_source($page)));
	}
	$checked_time = array_key_exists('notimestamp',$vars) ? ' checked="checked"' : '';
	
	$r_page   = rawurlencode($page);
	$s_page   = htmlspecialchars($page);
	$r_digest = rawurlencode($digest);
	$s_digest = htmlspecialchars($digest);
	$s_postdata_input = htmlspecialchars($postdata_input);
	$s_headdata = htmlspecialchars( $headdata );
	$s_taildata = htmlspecialchars( $taildata );
	$s_original = array_key_exists('original',$vars) ? htmlspecialchars($vars['original']) : $s_headdata . $s_postdata_input . $s_taildata;
	$b_preview = array_key_exists('preview',$vars); // プレビュー中TRUE
	$btn_preview = $b_preview ? $_btn_repreview : $_btn_preview;
	
	$body = <<<EOD
<form action="$script" method="post">
 <div class="edit_form">
  <input type="hidden" name="plugin" value="areaedit" />
  <input type="hidden" name="page"   value="$s_page" />
  <input type="hidden" name="digest" value="$s_digest" />
  <input type="hidden" name="block"  value="{$vars['block']}" />
  <input type="hidden" name="inline_plugin" value="{$vars['inline_plugin']}" />
  <input type="hidden" name="inline_preview" value="{$vars['inline_preview']}" />
  <input type="hidden" name="areaedit_no"   value="{$vars['areaedit_no']}" />
  <input type="hidden" name="areaedit_start_no" value="{$vars['areaedit_start_no']}" />
  <textarea name="areaedit_msg" rows="$rows" cols="$cols">$s_postdata_input</textarea>
  <br />
  <input type="submit" name="preview" value="$btn_preview" accesskey="p" />
  <input type="submit" name="write"   value="$_btn_update" accesskey="s" />
  <input type="checkbox" name="notimestamp" value="true"$checked_time />
  <span style="small">$_btn_notchangetimestamp</span>
  <textarea name="original" rows="1" cols="1" style="display:none">$s_original</textarea>
  <textarea name="headdata" rows="1" cols="1" style="display:none">$s_headdata</textarea>
  <textarea name="taildata" rows="1" cols="1" style="display:none">$s_taildata</textarea>
 </div>
</form>
EOD;
	
	if (array_key_exists('help',$vars)) {
		$body .= $hr.catrule();
	}
	else {
		if ( $vars['inline_plugin'] == 0 ){
			$body .= <<<EOD
<ul>
 <li>
 <a href="$script?plugin=areaedit&amp;help=true&amp;areaedit_no={$vars['areaedit_no']}&amp;inline_plugin=0&amp;page=$r_page&amp;digest=$r_digest">$_msg_help</a>
 </li>
</ul>
EOD;
		}
		else {
			$body .= <<<EOD
<ul>
 <li>
 <a href="$script?plugin=areaedit&amp;help=true&amp;areaedit_no={$vars['areaedit_no']}&amp;page=$r_page&amp;inline_plugin=1&amp;inline_preview={$vars['inline_preview']}&amp;digest=$r_digest">$_msg_help</a>
 </li>
</ul>
EOD;
		}
	}
	return $body;
}
?>
