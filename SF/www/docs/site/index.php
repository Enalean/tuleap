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

require($DOCUMENT_ROOT.'/include/pre.php');

$LANG->loadLanguageMsg('docman/docman');

$group_id = 1;

$usermem = user_ismember($group_id);

echo $HTML->header(array('title'=> $LANG->getText('docs_site_index','title')));

echo '<H2>'.$LANG->getText('docs_site_index','title').'</H2>';

//get a list of group numbers that this project owns
$query = "select * "
	."from doc_groups "
	."where group_id = $group_id "
	."order by groupname";
$result = db_query($query); 

//otherwise, throw up an error
if (db_numrows($result) < 1) {
	print "<b>".$LANG->getText('docs_site_index','nodoc')."</b><p>";
} else { 
	// get the groupings and display them with their members.
	while ($row = db_fetch_array($result)) {
		$query = "select description, docid, title, doc_group "
			."from doc_data "
			."where doc_group = '".$row['doc_group']."' "
			."and stateid ='1'";
			//state 1 == 'active'
			
		$subresult = db_query($query); 

		if (!(db_numrows($subresult) < 1)) {
			print "<p><H3>".$row['groupname']."</H3>\n<ul>\n";
			while ($subrow = db_fetch_array($subresult)) {
				print "<li><a href=\"../../docman/display_doc.php?docid=".$subrow['docid']."&group_id=".$group_id."\">";
				eval('?>'.util_unconvert_htmlspecialchars($subrow['title']));
				print "</a>";
				print "<BR><i>".$LANG->getText('docman_index','description').":</i> ";
				eval('?>'.util_unconvert_htmlspecialchars($subrow['description'])); 

			}
			print "</ul>\n\n";

		}
	}
}

$HTML->footer(array());

?>
