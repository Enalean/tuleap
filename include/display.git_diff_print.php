<?php
/*
 *  display.git_diff_print.php
 *  gitphp: A PHP git repository browser
 *  Component: Display - print a diff
 *
 *  Copyright (C) 2008 Christopher Han <xiphux@gmail.com>
 */

 require_once('gitutil.git_diff.php');

function git_diff_print($proj,$from,$from_name,$to,$to_name,$format = "html")
{
	global $tpl;
	$diffout = git_diff($proj, $from, $from_name, $to, $to_name);
	if ($format == "plain")
		echo $diffout;
	else {
		$line = strtok($diffout,"\n");
		while ($line !== false) {
			$start = substr($line,0,1);
			unset($class);
			if ($start == "+")
				$class = "diffplus";
			else if ($start == "-")
				$class = "diffminus";
			else if ($start == "@")
				$class = "diffat";
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
				if (isset($class))
					$tpl->assign("class",$class);
				$tpl->display("diff_line.tpl");
			}
			$line = strtok("\n");
		}
	}
}

?>
