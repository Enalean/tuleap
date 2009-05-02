<?php
/*
 *  display.git_blob_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob (plaintext)
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_cat_file.php');
 include_once('util.file_mime.php');

function git_blob_plain($projectroot,$project,$hash,$file)
{
	global $gitphp_conf;

	if ($file)
		$saveas = $file;
	else
		$saveas = $hash . ".txt";

	$buffer = git_cat_file($projectroot . $project, $hash);

	if ($gitphp_conf['filemimetype'])
		$mime = file_mime($buffer, $file);

	if ($mime)
		header("Content-type: " . $mime);
	else
		header("Content-type: text/plain; charset=UTF-8");

	header("Content-disposition: inline; filename=\"" . $saveas . "\"");

	echo $buffer;
}

?>
