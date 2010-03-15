<?php
/*
 *  display.git_blob_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob (plaintext)
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_cat_file.php');
 require_once('util.file_mime.php');

function git_blob_plain($projectroot,$project,$hash,$file)
{
	global $gitphp_conf, $tpl;

	$cachekey = sha1($project) . "|" . $hash . "|" . sha1($file);

	$buffer = null;

	// XXX: Nasty hack to cache headers
	if (!$tpl->is_cached('blobheaders.tpl', $cachekey)) {
		if ($file)
			$saveas = $file;
		else
			$saveas = $hash . ".txt";

		$buffer = git_cat_file($projectroot . $project, $hash);

		if ($gitphp_conf['filemimetype'])
			$mime = file_mime($buffer, $file);

		$headers = array();

		if ($mime)
			$headers[] = "Content-type: " . $mime;
		else
			$headers[] = "Content-type: text/plain; charset=UTF-8";

		$headers[] = "Content-disposition: inline; filename=\"" . $saveas . "\"";

		$tpl->assign("blobheaders", serialize($headers));
	}
	$out = $tpl->fetch('blobheaders.tpl', $cachekey);

	$returnedheaders = unserialize($out);

	foreach ($returnedheaders as $i => $header)
		header($header);


	if (!$tpl->is_cached('blobplain.tpl', $cachekey)) {
		if (!$buffer)
			$buffer = git_cat_file($projectroot . $project, $hash);
		$tpl->assign("blob", $buffer);
	}
	$tpl->display('blobplain.tpl', $cachekey);
}

?>
