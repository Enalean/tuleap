<?php
/*
 *  gitutil.git_read_packed_refs.php
 *  gitphp: a PHP git repository browser
 *  Component: Git utility - read packed refs
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_read_hash.php');

function git_read_packed_refs($projectroot, $project, $refdir)
{
	if (!is_file($projectroot . $project . '/packed-refs'))
		return null;

	$refs = array();
        $refs = explode("\n",git_read_hash($projectroot . $project . "/" . 'packed-refs'));
	$reflist = array();

	$dirlen = strlen($refdir);

	foreach ($refs as $i) {
		if (preg_match('/^([0-9a-f]{40}) (.+)$/i', trim($i), $regs)) {
			if (strncmp($refdir, $regs[2], $dirlen) === 0) {
				$regs[2] = substr($regs[2], $dirlen+1);
				$refobj = git_read_ref($projectroot, $project, $regs[1], $regs[2]);
				if (isset($refobj))
					$reflist[] = $refobj;
			}
		}
        }

	return $reflist;
}

?>
