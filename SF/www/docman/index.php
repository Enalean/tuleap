<?php

//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
//

/*
        Docmentation Manager
        by Quentin Cregan, SourceForge 06/2000
*/

require('doc_utils.php');
require('pre.php');

if ($group_id) {

	$usermem = user_ismember($group_id);
	docman_header('Project Documentation','Project Documentation');
	//get a list of group numbers that this project owns
	$query = "select * "
		."from doc_groups "
		."where group_id = $group_id "
		."order by groupname";
	$result = db_query($query); 

	//otherwise, throw up an error
	if (db_numrows($result) < 1) {
		print "<b>This project has no categorized data.</b><p>";
	} else { 
		// get the groupings and display them with their members.
		while ($row = db_fetch_array($result)) {
			$query = "select description, docid, title, doc_group "
				."from doc_data "
				."where doc_group = '".$row['doc_group']."' "
				."and stateid ='1'";
				//state 1 == 'active'
				if ($usermem == true) {
					$query .= " or stateid = '5' "
						 ." and doc_group = '".$row['doc_group']."' ";
				} //state 5 == 'private' 

			$subresult = db_query($query); 

			if (!(db_numrows($subresult) < 1)) {
				print "<p><b>".$row['groupname']."</b>\n<ul>\n";
				while ($subrow = db_fetch_array($subresult)) {
					print "<li><a href=\"display_doc.php?docid=".$subrow['docid']."&group_id=".$group_id."\">".$subrow['title']."</a>".
					"<BR><i>Description:</i> ".$subrow['description']; 
				}
				print "</ul>\n\n";

			}
		}
	}

        docman_footer($params);

} else {
	exit_no_group();
}

?>
