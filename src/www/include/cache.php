<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: cache.php 3641 2006-09-11 09:12:04Z guerin $


// #################################### function cache_display

function cache_display($name,$function,$time) {
  global $Language;

  if (!file_exists($GLOBALS['codex_cache_dir'])) {
      // This directory must be world reachable, but writable only by the web-server
      mkdir($GLOBALS['codex_cache_dir'], 0755);
  }

  $filename = $GLOBALS['codex_cache_dir']."/codex_cache_".$GLOBALS['sys_user_theme']."_".$name."_".$Language->getLanguageCode().".sf";

	while ((@filesize($filename)<=1) || ((time() - filectime($filename)) > $time)) {
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
					return $Language->getText('include_cache','unable_open_cache');
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
    global $Language;
    
    $furl=fopen(make_local_url("write_cache.php?sys_theme=".$GLOBALS['sys_user_theme']."&lang_code=".urlencode($Language->getLanguageCode())."&function=".urlencode($function)),'r');
	return stripslashes(fread($furl,200000));
}
?>
