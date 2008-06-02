<?php
/*
 *  gitutil.git_diff_print.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - print a diff
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 include_once('gitutil.git_cat_file.php');

function git_diff_print($proj,$from,$from_name,$to,$to_name,$format = "html")
{
	global $gitphp_conf,$tpl;
	$from_tmp = "/dev/null";
	$to_tmp = "/dev/null";
	$pid = posix_getpid();
	if (isset($from)) {
		$from_tmp = $gitphp_conf['gittmp'] . "gitphp_" . $pid . "_from";
		git_cat_file($proj,$from,$from_tmp);
	}
	if (isset($to)) {
		$to_tmp = $gitphp_conf['gittmp'] . "gitphp_" . $pid . "_to";
		git_cat_file($proj,$to,$to_tmp);
	}
	$diffout = shell_exec("diff -u -p -L '" . $from_name . "' -L '" . $to_name . "' " . $from_tmp . " " . $to_tmp);
	if ($format == "plain")
		echo $diffout;
	else {
		$line = strtok($diffout,"\n");
		while ($line !== false) {
			$start = substr($line,0,1);
			unset($color);
			if ($start == "+")
				$color = "#008800";
			else if ($start == "-")
				$color = "#cc0000";
			else if ($start == "@")
				$color = "#990099";
			if ($start != "\\") {
			/*
				while (($pos = strpos($line,"\t")) !== false) {
					if ($c = (8 - (($pos - 1) % 8))) {
						$spaces = "";
						for ($i = 0; $i < $c; $i++)
							$spaces .= " ";
						preg_replace('/\t/',$spaces,$line,1);
					}
				}
			 */
				$tpl->clear_all_assign();
				$tpl->assign("line",htmlentities($line));
				if (isset($color))
					$tpl->assign("color",$color);
				$tpl->display("diff_line.tpl");
			}
			$line = strtok("\n");
		}
	}
	if (isset($from))
		unlink($from_tmp);
	if (isset($to))
		unlink($to_tmp);
}

?>
