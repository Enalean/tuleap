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
	global $gitphp_conf;
	if (file_exists($gitphp_conf['gittmp'])) {
		if (is_dir($gitphp_conf['gittmp'])) {
			if (!is_writeable($gitphp_conf['gittmp']))
				return "Specified tmpdir is not writeable!";
		} else
			return "Specified tmpdir is not a directory";
	} else
		if (!mkdir($gitphp_conf['gittmp'],0700))
			return "Could not create tmpdir";
	return TRUE;
}

?>
