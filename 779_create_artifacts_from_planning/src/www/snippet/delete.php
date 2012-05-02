<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require('../snippet/snippet_utils.php');


/*
	By Tim Perdue, 2000/01/10

	Delete items from packages, package versions, and snippet versions
*/

if (user_isloggedin()) {
	snippet_header(array('title'=>$Language->getText('snippet_delete','delete_s')));

	if ($type=='frompackage' && $snippet_version_id && $snippet_package_version_id) {
		/*
			Delete an item from a package
		*/

		//Check to see if they are the creator of this package_version
		$result=db_query("SELECT * FROM snippet_package_version ".
			"WHERE submitted_by='".user_getid()."' AND ".
			"snippet_package_version_id='$snippet_package_version_id'");
		if (!$result || db_numrows($result) < 1) {
			echo '<H1>'.$Language->getText('snippet_delete','only_creator_deletes').'</H1>';
			snippet_footer(array());
			exit;
		} else {

			//Remove the item from the package
			$result=db_query("DELETE FROM snippet_package_item ".
				"WHERE snippet_version_id='$snippet_version_id' ".
				"AND snippet_package_version_id='$snippet_package_version_id'");
			if (!$result || db_affected_rows($result) < 1) {
				echo '<H1>'.$Language->getText('snippet_delete','s_not_exist_in_p').'</H1>';
				snippet_footer(array());
				exit;
			} else {
				echo '<H1>'.$Language->getText('snippet_delete','removed').'</H1>';
				snippet_footer(array());
				exit;
			}
		}

	} else  if ($type=='snippet' && $snippet_version_id) {
		/*
			Delete a snippet version
		*/

		//find this snippet id and make sure the current user created it
		$result=db_query("SELECT * FROM snippet_version ".
			"WHERE snippet_version_id='$snippet_version_id' AND submitted_by='".user_getid()."'");
		if (!$result || db_numrows($result) < 1) {
			echo '<H1>'.$Language->getText('snippet_add_snippet_to_package','error_s_not_exist').'</H1>';
			snippet_footer(array());
			exit;
		} else {
			$snippet_id=db_result($result,0,'snippet_id');

			//do the delete
			$result=db_query("DELETE FROM snippet_version ".
				"WHERE snippet_version_id='$snippet_version_id' AND submitted_by='".user_getid()."'");

			//see if any versions of this snippet are left
			$result=db_query("SELECT * FROM snippet_version WHERE snippet_id='$snippet_id'");
			if (!$result || db_numrows($result) < 1) {
				//since no version of this snippet exist, delete the main snippet entry,
				//even if this person is not the creator of the original snippet
				$result=db_query("DELETE FROM snippet WHERE snippet_id='$snippet_id'");
			}

			echo '<H1>'.$Language->getText('snippet_delete','s_removed').'</H1>';
			snippet_footer(array());
			exit;
		}

	} else  if ($type=='package' && $snippet_package_version_id) {
		/*
			Delete a package version

		*/

		//make sure they own this version of the package
		$result=db_query("SELECT * FROM snippet_package_version ".
			"WHERE submitted_by='".user_getid()."' AND ".
			"snippet_package_version_id='$snippet_package_version_id'");
		if (!$result || db_numrows($result) < 1) {
			//they don't own it or it's not found
			echo '<H1>'.$Language->getText('snippet_delete','only_creator_delete_p').'</H1>';
			snippet_footer(array());
			exit;
		} else {
			$snippet_package_id=db_result($result,0,'snippet_package_id');

			//do the version delete
			$result=db_query("DELETE FROM snippet_package_version ".
		       		"WHERE submitted_by='".user_getid()."' AND ".
				"snippet_package_version_id='$snippet_package_version_id'");

			//delete snippet_package_items
			$result=db_query("DELETE FROM snippet_package_item ".
				"WHERE snippet_package_version_id='$snippet_package_version_id'");

			//see if any versions of this package remain
			$result=db_query("SELECT * FROM snippet_package_version ".
				"WHERE snippet_package_id='$snippet_package_id'");
			if (!$result || db_numrows($result) < 1) {
				//since no versions of this package remain,
				//delete the main package even if the user didn't create it
				$result=db_query("DELETE FROM snippet_package WHERE snippet_package_id='$snippet_package_id'");
			}
			echo '<H1>'.$Language->getText('snippet_delete','p_removed').'</H1>';
			snippet_footer(array());
			exit;
		}
	} else {
		exit_error($Language->getText('global','error'),$Language->getText('snippet_delete','url_mangled'));
	}

} else {

	exit_not_logged_in();

}

?>
