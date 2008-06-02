<?php
/*
 *  display.git_blob_plain.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - blob (plaintext)
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_cat_file.php');

function git_blob_plain($projectroot,$project,$hash,$file)
{
	if ($file)
		$saveas = $file;
	else
		$saveas = $hash . ".txt";
	header("Content-type: text/plain; charset=UTF-8");
	header("Content-disposition: inline; filename=\"" . $saveas . "\"");
	echo git_cat_file($projectroot . $project, $hash);
}

?>
