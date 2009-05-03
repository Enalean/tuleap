<?php
/*
 *  display.git_blob.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_head.php');
 require_once('gitutil.git_get_hash_by_path.php');
 require_once('gitutil.git_cat_file.php');
 require_once('gitutil.git_read_commit.php');
 require_once('util.file_mime.php');

function git_blob($projectroot, $project, $hash, $file, $hashbase)
{
	global $gitphp_conf,$tpl;
	if (!isset($hash) && isset($file)) {
		$base = $hashbase ? $hashbase : git_read_head($projectroot . $project);
		$hash = git_get_hash_by_path($projectroot . $project, $base,$file,"blob");
	}
	$catout = git_cat_file($projectroot . $project, $hash);
	if (isset($hashbase) && ($co = git_read_commit($projectroot . $project, $hashbase))) {
		$tpl->clear_all_assign();
		$tpl->assign("project",$project);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("tree",$co['tree']);
		$tpl->assign("hash",$hash);
		if (isset($file))
			$tpl->assign("file",$file);
		$tpl->assign("title",$co['title']);
		$tpl->display("blob_nav.tpl");
	} else {
		$tpl->clear_all_assign();
		$tpl->assign("hash",$hash);
		$tpl->display("blob_emptynav.tpl");
	}
	$tpl->clear_all_assign();
	if (isset($file))
		$tpl->assign("file",$file);
	$tpl->display("blob_header.tpl");

	if ($gitphp_conf['filemimetype']) {
		$mime = file_mime($catout,$file);
		if ($mime)
			$mimetype = strtok($mime, "/");
	}

	if ($mimetype == "image") {
		$tpl->clear_all_assign();
		$tpl->assign("mime", $mime);
		$tpl->assign("data", base64_encode($catout));
		$tpl->display("blob_image.tpl");
	} else {
		$usedgeshi = $gitphp_conf['geshi'];
		if ($usedgeshi) {
			$usedgeshi = FALSE;
			include_once($gitphp_conf['geshiroot'] . "geshi.php");
			if (class_exists("GeSHi")) {
				$geshi = new GeSHi("",'php');
				if ($geshi) {
					$lang = "";
					if (isset($file))
						$lang = $geshi->get_language_name_from_extension(substr(strrchr($file,'.'),1));
					if (isset($lang) && (strlen($lang) > 0)) {
						$geshi->set_source($catout);
						$geshi->set_language($lang);
						$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
						echo $geshi->parse_code();
						$usedgeshi = TRUE;
					}
				}
			}
		}

		if (!$usedgeshi) {
			$lines = explode("\n",$catout);
			foreach ($lines as $i => $line) {
				/*
				 * TODO: Convert tabs to spaces
				 */
				$tpl->clear_all_assign();
				$tpl->assign("nr",$i+1);
				$tpl->assign("line",htmlentities($line));
				$tpl->display("blob_line.tpl");
			}
		}
	}

	$tpl->clear_all_assign();
	$tpl->display("blob_footer.tpl");
}

?>
