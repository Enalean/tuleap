<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

exit;

/*


	One time use script
	to migrate and normalize the filerelease data

	Methodology:

	1. need to update the frs_release table with the change notes

	2. need to build all the frs_file entries with proper relations to frs_release


*/

require ('squal_pre.php');

echo $REMOTE_ADDR;

if (!strstr($REMOTE_ADDR,'192.168.1.')) {
        exit_permission_denied();
}

//get all the tasks
$result=db_query("SELECT * FROM frs_release WHERE release_id > 9290 ORDER BY release_id ASC");
$rows=db_numrows($result);
echo "\n<BR>Rows: $rows\n";
flush();

for ($i=0; $i<$rows; $i++) {
	flush();
	echo "\n<BR>Release :: ".db_result($result,$i,'release_id');

	/*
		Get the files from the old system for this release
	*/
	$res2=db_query("SELECT * FROM filerelease ".
		"WHERE filemodule_id='". db_result($result,$i,'package_id') ."' AND release_version='". db_result($result,$i,'name') ."'");

	$rows2=db_numrows($res2);
	//echo db_error();
	if ($rows2 < 1) {
		/*
			no matches for this release
		*/
		echo "\n<BR>Warning - deleting release!";
		db_query ("DELETE FROM frs_release WHERE release_id='". db_result($result,$i,'release_id') ."'");
	} else {
		$release_id=db_result($result,$i,'release_id');

		/*
			set the change notes and release time for this release 
			based on any given file from the release in the old system
		*/
		db_query ("UPDATE frs_release SET notes='". addslashes(db_result($res2,0,'text_notes')) ."',".
			"changes='". addslashes(db_result($res2,0,'text_changes')) ."', ".
			"preformatted='". db_result($res2,0,'text_format') ."', ".
			"released_by='". db_result($res2,0,'user_id') ."', ".
			"release_date='". db_result($res2,0,'release_time') ."' ".
			"WHERE release_id='$release_id'");

		echo "\n<BR>Update Release: $release_id :: ".db_error();

		for ($f=0; $f<$rows2; $f++) {
			/*
				move each of the files from the old system to the new
			*/
			db_query("INSERT INTO frs_file (file_id,filename,release_id,processor_id,release_time,file_size,post_date,type_id) VALUES ".
				"('". db_result($res2,$f,'filerelease_id') ."','". db_result($res2,$f,'filename') ."','$release_id',".
				"'9999','". db_result($res2,$f,'release_time') ."','". db_result($res2,$f,'file_size') ."','". db_result($res2,$f,'post_time') ."','9999')");
			echo "\n<BR>File: ". db_result($res2,$f,'filerelease_id') ." :: ".db_error();
		}
	}
}

?>
