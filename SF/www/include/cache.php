<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$


// #################################### function cache_display

function cache_display($name,$function,$time) {
	$filename = $GLOBALS['sf_cache_dir']."/sfcache_".$GLOBALS['sys_theme']."_$name.sf";

	while ((filesize($filename)<=1) || ((time() - filectime($filename)) > $time)) {
		// file is non-existant or expired, must redo, or wait for someone else to

		if (!file_exists($filename)) {
			@touch($filename);
		}

		// open file. If this does not work, wait one second and try cycle again
		if ($rfh=@fopen($filename,'r')) {
			// obtain a blocking write lock, else wait 1 second and try again
			if(flock($rfh,2)) { 
				// open file for writing. if this does not work, something is broken.
				if (!$wfh = @fopen($filename,'w')) {
					return "Unable to open cache file for writing after obtaining lock.";
				}
				// have successful locks and opens now
				$return=cache_get_new_data($function);
				fwrite($wfh,$return); //write the file
				fclose($wfh); //close the file
				flock($rfh,3); //release lock
				fclose($rfh); //close the lock
				return $return;
			} else { // unable to obtain flock
				sleep(1);
				clearstatcache();
			}
		} else { // unable to open for reading
			sleep(1);
			clearstatcache();
		}
	} 
		
	// file is now good, use it for return value
	if (!$rfh = fopen($filename,'r')) { //bad filename
		return cache_get_new_data($function);
	}
	while(!flock($rfh,1+4) && ($counter < 30)) { // obtained non blocking shared lock 
		usleep(250000); // wait 0.25 seconds for the lock to become available
		$counter++;
	}
	$result=stripslashes(fread($rfh,200000));
	flock($rfh,3); // cancel read lock
	fclose($rfh);
	return $result;
}

function cache_get_new_data($function) {
	$furl=fopen("http://localhost/write_cache.php?sys_themeid=".$GLOBALS['sys_themeid']."&function=".urlencode($function),'r');
	return stripslashes(fread($furl,200000));
}
?>
