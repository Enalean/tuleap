<?php
/*
 *  display.git_commit.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - commit
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('util.file_type.php');
 require_once('util.date_str.php');
 require_once('gitutil.git_read_commit.php');
 require_once('gitutil.git_diff_tree.php');
 require_once('gitutil.read_info_ref.php');

function git_commit($projectroot,$project,$hash)
{
	global $tpl;
	$co = git_read_commit($projectroot . $project, $hash);
	$ad = date_str($co['author_epoch'],$co['author_tz']);
	$cd = date_str($co['committer_epoch'],$co['committer_tz']);
	if (isset($co['parent'])) {
		$root = "";
		$parent = $co['parent'];
	} else {
		$root = "--root";
		$parent = "";
	}
	$diffout = git_diff_tree($projectroot . $project, $root . " " . $parent . " " . $hash, TRUE);
	$difftree = explode("\n",$diffout);
	$tpl->clear_all_assign();
	$tpl->assign("project",$project);
	$tpl->assign("hash",$hash);
	$tpl->assign("tree",$co['tree']);
	if (isset($co['parent']))
		$tpl->assign("parent",$co['parent']);
	$tpl->display("commit_nav.tpl");
	$tpl->assign("title",$co['title']);
	$refs = read_info_ref($projectroot . $project);
	if (isset($refs[$co['id']]))
		$tpl->assign("commitref",$refs[$co['id']]);
	$tpl->assign("author",$co['author']);
	$tpl->assign("adrfc2822",$ad['rfc2822']);
	$tpl->assign("adhourlocal",$ad['hour_local']);
	$tpl->assign("adminutelocal",$ad['minute_local']);
	$tpl->assign("adtzlocal",$ad['tz_local']);
	$tpl->assign("committer",$co['committer']);
	$tpl->assign("cdrfc2822",$cd['rfc2822']);
	$tpl->assign("cdhourlocal",$cd['hour_local']);
	$tpl->assign("cdminutelocal",$cd['minute_local']);
	$tpl->assign("cdtzlocal",$cd['tz_local']);
	$tpl->assign("id",$co['id']);
	$tpl->assign("parents",$co['parents']);
	$tpl->assign("comment",$co['comment']);
	$tpl->assign("difftreesize",count($difftree)+1);
	$tpl->display("commit_data.tpl");
	$alternate = FALSE;
	foreach ($difftree as $i => $line) {
		$tpl->clear_all_assign();
		if (ereg("^:([0-7]{6}) ([0-7]{6}) ([0-9a-fA-F]{40}) ([0-9a-fA-F]{40}) (.)([0-9]{0,3})\t(.*)$",$line,$regs)) {
			if ($alternate)
				$tpl->assign("class","dark");
			else
				$tpl->assign("class","light");
			$alternate = !$alternate;
			$tpl->assign("project",$project);
			$tpl->assign("hash",$hash);
			$tpl->assign("from_mode",$regs[1]);
			$tpl->assign("to_mode",$regs[2]);
			$tpl->assign("from_mode_cut",substr($regs[1],-4));
			$tpl->assign("to_mode_cut",substr($regs[2],-4));
			$tpl->assign("from_id",$regs[3]);
			$tpl->assign("to_id",$regs[4]);
			$tpl->assign("status",$regs[5]);
			$tpl->assign("similarity",$regs[6]);
			$tpl->assign("file",$regs[7]);
			$tpl->assign("from_file",strtok($regs[7],"\t"));
			$tpl->assign("to_file",strtok("\t"));
			$tpl->assign("to_filetype",file_type($regs[2]));
			if ((octdec($regs[2]) & 0x8000) == 0x8000)
				$tpl->assign("isreg",TRUE);
			$modestr = "";
			if ((octdec($regs[1]) & 0x17000) != (octdec($regs[2]) & 0x17000))
				$modestr .= " from " . file_type($regs[1]) . " to " . file_type($regs[2]);
			if ((octdec($regs[1]) & 0777) != (octdec($regs[2]) & 0777)) {
				if ((octdec($regs[1]) & 0x8000) && (octdec($regs[2]) & 0x8000))
					$modestr .= " mode: " . (octdec($regs[1]) & 0777) . "->" . (octdec($regs[2]) & 0777);
				else if (octdec($regs[2]) & 0x8000)
					$modestr .= " mode: " . (octdec($regs[2]) & 0777);
			}
			$tpl->assign("modechange",$modestr);
			$simmodechg = "";
			if ($regs[1] != $regs[2])
				$simmodechg .= ", mode: " . (octdec($regs[2]) & 0777);
			$tpl->assign("simmodechg",$simmodechg);

			$tpl->display("commit_item.tpl");
		}
	}
	$tpl->clear_all_assign();
	$tpl->display("commit_footer.tpl");
}

?>
