<?php
/*
 *  util.file_mime_file.php
 *  gitphp: A PHP git repository browser
 *  Component: Utility - file mimetype using file command
 *
 *  Copyright (C) 2009 Christopher Han <xiphux@gmail.com>
 *
 *  Based on work by 3v1n0
 */

function file_mime_file($buffer)
{
	global $gitphp_conf;

	if ($buffer && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')) {
		$descspec = array(
			0 => array("pipe", "r"),
			1 => array("pipe", "w")
		);
		$proc = proc_open('file -b --mime -', $descspec, $pipes);
		if (is_resource($proc)) {
			fwrite($pipes[0], $buffer);
			fclose($pipes[0]);
			$mime = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);
			if ($mime && strpos($mime,"/")) {
				if (strpos($mime,";"))
					$mime = strtok($mime,";");
				return $mime;
			}
		}
	}

	return FALSE;
}

?>
