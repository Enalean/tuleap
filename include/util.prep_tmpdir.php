<?php
/*
 *  util.prep_tmpdir.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - Prepare temporary directory
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

function prep_tmpdir()
{
	$tmpdir = GitPHP_Config::GetInstance()->GetValue('gittmp', '/tmp/gitphp/');
	if (file_exists($tmpdir)) {
		if (is_dir($tmpdir)) {
			if (!is_writeable($tmpdir))
				return "Specified tmpdir is not writeable!";
		} else
			return "Specified tmpdir is not a directory";
	} else
		if (!mkdir($tmpdir, 0700))
			return "Could not create tmpdir";
	return TRUE;
}

?>
