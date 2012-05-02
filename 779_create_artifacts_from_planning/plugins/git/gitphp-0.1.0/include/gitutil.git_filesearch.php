<?php
/*
 *  gitutil.git_filesearch.php
 *  gitphp: A PHP git repository browser
 *  Component: Git utility - search files
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 */

require_once('gitutil.git_grep.php');
require_once('gitutil.git_ls_tree.php');

function git_filesearch($project, $hash, $search, $case = false, $skip = 0, $count = 100)
{
	$matches = array();
	
	/*
	 * Search file contents
	 */
	$grepout = git_grep($project, $hash, $search, $case, false, true);
	$lines = explode("\n",$grepout);
	foreach ($lines as $j => $line) {
		if ($case)
			$ret = preg_match("/^([^:]+):([^:]+):(.*" . quotemeta($search) . ".*)/",$line,$regs);
		else
			$ret = preg_match("/^([^:]+):([^:]+):(.*" . quotemeta($search) . ".*)/i",$line,$regs);
		if ($ret) {
			$fname = trim($regs[2]);
			if (!isset($matches[$fname])) {
				$matches[$fname] = array();
				$matches[$fname]['lines'] = array();
			}
			$matches[$fname]['lines'][] = $regs[3];
		}
	}

	/*
	 * Search filenames
	 */
	 $lsout = git_ls_tree($project, $hash, false, true);
	 $entries = explode("\n",$lsout);
	 foreach ($entries as $j => $line) {
		$ret = preg_match("/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.+)/",$line,$regs);
                //maybe < 4
                if ( $ret === 0 ) {
                    continue;
                }               
		$fname = trim($regs[4]);                
		if (isset($matches[$fname])) {
			$matches[$fname]['hash'] = $regs[3];
			$matches[$fname]['type'] = $regs[2];
		} else {

                        $exp = "/^([0-9]+) (.+) ([0-9a-fA-F]{40})\t(.*" . quotemeta($search) . "[^\/]*)$/".($case?'i':'');                        
			$ret = preg_match($exp,$line,$regs);
			if ($ret) {
				$fname = trim($regs[4]);
				$matches[$fname] = array();
				$matches[$fname]['hash'] = $regs[3];
				$matches[$fname]['type'] = $regs[2];
			}
		}
	 }
	
	if ($skip > 0) {
		foreach ($matches as $i => $val) {
			unset($matches[$i]);
			$skip--;
			if ($skip <= 0)
				break;
		}
	}

	if (count($matches) > $count) {
		$index = 1;
		foreach ($matches as $i => $val) {
			if ($index > $count)
				unset($matches[$i]);
			++$index;
		}
	}

	return $matches;
}

?>
