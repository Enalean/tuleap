<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//

/*
	Docmentation Manager
	by Quentin Cregan, SourceForge 06/2000
*/


function display_groups_option($group_id=false,$checkedval='xyxy') {

    if (!$group_id) {
	exit_no_group();
    } else {
	$query = "select doc_group, groupname "
	    ."from doc_groups "
	    ."where group_id = $group_id "
	    ."order by groupname";
	$result = db_query($query);

	echo html_build_select_box ($result,'doc_group',$checkedval);

    } //end else

} //end display_groups_option


function display_groups($group_id) {
	// show list of groups to edit.
	$query = "select * "
		."from doc_groups "
		."where group_id = '$group_id'";
	$result = db_query($query);
	
	if (db_numrows($result) < 1) {
		print "<p>No groups currently exist.";
	} else {

		$title_arr=array();
		$title_arr[]='Group ID';
		$title_arr[]='Group Name';
		$title_arr[]='Controls';

		echo html_build_list_table_top ($title_arr);

		$i = 0;
		while ($row = db_fetch_array($result)) {
			$output = "<tr class=\"".util_get_alt_row_color($i)."\">".
				"<td>".$row['doc_group']."</td>\n".
				"<td>".$row['groupname']."</td>\n".
				"<td>[ <a href=\"index.php?mode=groupdelete&doc_group=".$row['doc_group']."&group_id=".$group_id."\">Delete</A> ] [ <a href=\"index.php?mode=groupedit&doc_group=".$row['doc_group']."&group_id=".$group_id."\">Change Name</a> ]\n</td>".
				"</tr>\n";

			print "$output";
			$i++;
		}
		echo '</table>';
	}
		
	docman_footer($params);

}

function display_docs($style,$group_id) {
	global $sys_datefmt;

	$query = "select * "
		."from doc_data as d1, doc_groups as d2 "
		."where d1.stateid = '".$style."' "
		."and d2.group_id = '".$group_id."' " 
		."and d1.doc_group = d2.doc_group"; 
	$result = db_query($query);

	if (db_numrows($result) < 1) {
		
		$query = "select name"
			."from doc_states "
			."where stateid = ".$style."";
			$result = db_query($query);
		$row = db_fetch_array($result);
		echo 'No '.$row['name'].' docs available <p>';

	} else {

		$title_arr=array();
		$title_arr[]='Document ID';
		$title_arr[]='Name';
		$title_arr[]='Create Date';

		echo html_build_list_table_top ($title_arr);

		$i = 0;
		while ($row = db_fetch_array($result)) {
			print 	"<tr class=\"".util_get_alt_row_color($i)."\">"
				."<td>".$row['docid']."</td>"
				."<td><a href=\"index.php?docid=".$row['docid']."&mode=docedit&group_id=".$group_id."\">".$row['title']."</a></td>"
				."<td>".format_date($sys_datefmt,$row['createdate'])."</td></tr>";
			$i++;
		}	
		echo '</table>';
	}//end else

} //end function display_docs($style)

function docman_header($params) {

	global $group_id;

	$project=project_get_object($group_id);
	
	if (!$project->isProject()) {
		exit_error('Error','Only Projects Can Use The Doc Manager');
	}
	if (!$project->usesDocman()) {
		exit_error('Error','This Project Has Turned Off The Doc Manager');
	}
        // There might be encoded HTML tags in the title
	site_project_header(array('title'=>strip_tags(util_unconvert_htmlspecialchars($params['title'])),'group'=>$group_id,'toptab'=>'doc'));

	print "<p><b><a href=\"/docman/new.php?group_id=".$group_id."\">Submit new documentation</a> | ".
		"<a href=\"/docman/index.php?group_id=".$group_id."\">View Documentation</a> | ".
		"<a href=\"/docman/admin/index.php?group_id=".$group_id."\">Admin</a></b>"; 
	
	if ($param['style'] == 'admin') {
		print "<b>  | <a href=\"/docman/admin/index.php?mode=editdocs&group_id=".$group_id."\">Edit Documents</a> | ".
		"<a href=\"/docman/admin/index.php?mode=editgroups&group_id=".$group_id." \">Edit Document Groups</a></b>";

	} 
	if ($params['help']) {
	    echo ' | <b>  '.help_button($params['help'],false,'Help').'</b>';
	}
}

function docman_header_admin($params) {

    global $group_id;

    $project=project_get_object($group_id);
    
    if (!$project->isProject()) {
	exit_error('Error','Only Projects Can Use The Doc Manager');
    }
    if (!$project->usesDocman()) {
	exit_error('Error','This Project Has Turned Off The Doc Manager');
    }
    
    site_project_header(array('title'=>$params['title'],'group'=>$group_id,'toptab'=>'doc'));
    
    print "<b><a href=\"/docman/admin/index.php?group_id=".$group_id."\">Admin</a>"; 
    print "<b>  | <a href=\"/docman/admin/index.php?mode=editdocs&group_id=".$group_id."\">Edit Documents</a> | ".
	"<a href=\"/docman/admin/index.php?mode=editgroups&group_id=".$group_id." \">Edit Document Groups</a></b>";
    
    if ($params['help']) {
	echo ' | <b>  '.help_button($params['help'],false,'Help').'</b>';
    }
}

function doc_get_state_box() {
	$res_states=db_query("select * from doc_states;");
	echo html_build_select_box ($res_states, 'stateid', $row['stateid']);

}

function docman_footer($params) {
	site_project_footer($params);

}

?>
