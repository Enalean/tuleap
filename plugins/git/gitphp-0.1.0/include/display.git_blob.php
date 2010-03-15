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
 require_once('gitutil.git_path_trees.php');
 require_once('gitutil.read_info_ref.php');
 require_once('util.file_mime.php');

function git_blob($projectroot, $project, $hash, $file, $hashbase)
{
	global $gitphp_conf,$tpl;

	$cachekey = sha1($project) . "|" . $hashbase . "|" . $hash . "|" . sha1($file);

	if (!$tpl->is_cached('blob.tpl',$cachekey)) {
		$head = git_read_head($projectroot . $project);
		if (!isset($hashbase))
			$hashbase = $head;
		if (!isset($hash) && isset($file))
			$hash = git_get_hash_by_path($projectroot . $project, $hashbase,$file,"blob");
		$catout = git_cat_file($projectroot . $project, $hash);
		$tpl->assign("hash",$hash);
		$tpl->assign("hashbase",$hashbase);
		$tpl->assign("head", $head);
		if ($co = git_read_commit($projectroot . $project, $hashbase)) {
			$tpl->assign("fullnav",TRUE);
			$refs = read_info_ref($projectroot . $project);
			$tpl->assign("tree",$co['tree']);
			$tpl->assign("title",$co['title']);
			if (isset($file))
				$tpl->assign("file",$file);
			if ($hashbase == "HEAD") {
				if (isset($refs[$head]))
					$tpl->assign("hashbaseref",$refs[$head]);
			} else {
				if (isset($refs[$hashbase]))
					$tpl->assign("hashbaseref",$refs[$hashbase]);
			}
		}
		$paths = git_path_trees($projectroot . $project, $hashbase, $file);
		$tpl->assign("paths",$paths);

		if ($gitphp_conf['filemimetype']) {
			$mime = file_mime($catout,$file);
			if ($mime)
				$mimetype = strtok($mime, "/");
		}

		if ($mimetype == "image") {
			$tpl->assign("mime", $mime);
			$tpl->assign("data", base64_encode($catout));
		} else {
			$usedgeshi = $gitphp_conf['geshi'];
			if ($usedgeshi) {
				$usedgeshi = FALSE;
				include_once($gitphp_conf['geshiroot'] . "geshi.php");
				if (class_exists("GeSHi")) {
					$geshi = new GeSHi($catout, $lang = Geshi::get_language_name_from_extension(substr(strrchr($file,'.'),1)));
					if ($geshi) {
						$lang = "";
						if (isset($file))
							$lang = $geshi->get_language_name_from_extension(substr(strrchr($file,'.'),1));
						if (isset($lang) && (strlen($lang) > 0)) {
							#$geshi->set_source($catout);
							#$geshi->set_language($lang);
							#$geshi->set_header_type(GESHI_HEADER_DIV);
							#$geshi->enable_line_numbers(GESHI_FANCY_LINE_NUMBERS);
							$tpl->assign("geshiout",$geshi->parse_code());
							$usedgeshi = TRUE;
						}
					}
				}
			}

			if (!$usedgeshi) {
				$lines = explode("\n",$catout);
				$tpl->assign("lines",$lines);
			}
		}
	}

	$tpl->display('blob.tpl', $cachekey);
}

?>
