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


require('../doc_utils.php');
require('pre.php');

if (!($group_id)) {
	exit_no_group();
}

if (!(user_ismember($group_id,"D1"))) {
	exit_permission_denied();
}

function main_page($group_id) {
		docman_header('Document Admin Page','Document Manager Admin','admin');
		echo '<p><b>Pending Submissions:</b>  <p>';
		display_docs('3',$group_id);
		// doc_group 3 == pending
		echo '<p>';
		echo '<b>Active Submissions:</b>  <p>';
		display_docs('1',$group_id);
		//doc_group 1 == active
		docman_footer($params);

}//end function main_page($group_id);

//begin to seek out what this page has been called to do.

	if (strstr($mode,"docedit")) {
		$query = "select * from doc_data,doc_groups "
			."where docid='$docid' "
			."and doc_groups.doc_group = doc_data.doc_group "
			."and doc_groups.group_id = '$group_id'";
		$result = db_query($query);
		$row = db_fetch_array($result);
	
		docman_header('Edit Document','Edit Document','admin');

		echo '
	
			<form name="editdata" action="index.php?mode=docdoedit&group_id='.$group_id.'" method="POST">

			<table border="0" width="75%">

			<tr>
			        <th>Document Title:</th>
			        <td><input type="text" name="title" size="60" maxlength="255" value="'.$row['title'].'"></td>
			        <td class="example">(e.g. How to use the download server)</td>

			</tr>
			<tr>
			</tr>
			<tr>
			        <th>Short Description:</td>
			        <!-- td><input type="text" name="description" size="20" maxlength="255" value="'.$row['description'].'"></td -->
				<td><textarea cols="60" rows="4"  wrap="virtual" name="description">'.$row['description'].'</textarea></td>			        <td class="example">(e.g. Instructions on how to download files for newbies)</td>

			</tr>

			<tr>
			        <th>Document Information (in html format):</th>
			        <td><textarea cols="60" rows="10" wrap="virtual" name="data">'.$row['data'].'</textarea></td>
			</tr>

			<tr>
			        <th>Group doc belongs in:</th>
        			<td>';

		display_groups_option($group_id,$row['doc_group']);

		echo '			</td>
				</tr>

				<tr>
				        <th>State:</th>
				        <td>';

		$res_states=db_query("select * from doc_states;");
		echo html_build_select_box ($res_states, 'stateid', $row['stateid']);

		echo '
       				</td>
			</tr>



		</table>

		<input type="hidden" name="docid" value="'.$row['docid'].'">
		<input type="submit" value="Submit Edit">

		</form>';

		docman_footer($params);
	} elseif (strstr($mode,"groupdelete")) {
		$query = "select docid "
			."from doc_data "
			."where doc_group = ".$doc_group."";
		$result = db_query($query);
		if (db_numrows($result) < 1) {
			$query = "delete from doc_groups "
				."where doc_group = '$doc_group' "
				."and group_id = $group_id";
			db_query($query);
			docman_header("Group Delete","Group Delete",'admin');
			print "<p><b>Group deleted. (GroupID : ".$doc_group.")</b>";	
			docman_footer($params);	

		} else {
		
			docman_header("Group Delete","Group Delete Failed",'admin');
			print "Group was not deleted.  Cannot delete groups that still have documents grouped under them."; 
			docman_footer($params);
		}
		
	} elseif (strstr($mode,"groupedit")) {
			docman_header('Group Edit','Group Edit','admin');
			$query = "select * "
				."from doc_groups "
				."where doc_group = '$doc_group' "
				."and group_id=$group_id";
			$result = db_query($query);
			$row = db_fetch_array($result);
			echo '
			<b> Edit a group:</b>

			<form name="editgroup" action="index.php?mode=groupdoedit&group_id='.$group_id.'" method="POST">
			<table>
			<tr><th>Name:</th>  <td><input type="text" name="groupname" value="'.$row['groupname'].'"></td></tr>
			<input type="hidden" name="doc_group" value="'.$row['doc_group'].'">
			<tr><td> <input type="submit"></td></tr></table>	
			</form>	
			';
			docman_footer($params);

	} elseif (strstr($mode,"groupdoedit")) {
		$query = "update doc_groups "
			."set groupname='".htmlspecialchars($groupname)."' "
			."where doc_group='$doc_group' "
			."and group_id = '$group_id'";
		db_query($query);
		$feedback .= "Document Group Edited.";
		main_page($group_id);

	} elseif (strstr($mode,"docdoedit")) {
		//Page security - checks someone isnt updating a doc
		//that isnt theirs.

		$query = "select dd.docid "
			."from doc_data dd, doc_groups dg "
			."where dd.doc_group = dg.doc_group "
			."and dg.group_id = ".$group_id." "
			."and dd.docid = '".$docid."'"; 
		
		$result = db_query($query);
	
		if (db_numrows($result) == 1) {	

			$query = "update doc_data "
				."set title = '".htmlspecialchars($title)."', "
				."data = '".htmlspecialchars($data)."', "
				."updatedate = '".time()."', "
				."doc_group = '".$doc_group."', "
				."stateid = '".$stateid."', " 
				."description = '".htmlspecialchars($description)."' "
				."where docid = '".$docid."'"; 
		
			db_query($query);
			$feedback .= "Document \" ".htmlspecialchars($title)." \" updated";
			main_page($group_id);

		} else {

			exit_error("Error","Unable to update - Document does not exist, or document's group not the same as that to which your account belongs.");

		}

	} elseif (strstr($mode,"groupadd")) {
		$query = "insert into doc_groups(groupname,group_id) " 
			."values ('"
			."".htmlspecialchars($groupname)."',"
			."'$group_id')";
		
		db_query($query);
		$feedback .= "Group ".htmlspecialchars($groupname)." added.";
		main_page($group_id);
	
	} elseif (strstr($mode,"editgroups")) {
		docman_header('Group Edit', 'Group Edit','admin');
		echo '
			<p><b> Add a group:</b>
			<form name="addgroup" action="index.php?mode=groupadd&group_id='.$group_id.'" method="POST">
			<table>
			<tr><th>New Group Name:</th>  <td><input type="text" name="groupname"></td><td><input type="submit" value="Add"></td></tr></table>	
			</form>	
		';
		display_groups($group_id);

	} elseif (strstr($mode,"editdocs")) {

		docman_header('Edit documents list','Edit documents','admin');
		
		print "<p><b>Active Documents:</b><p>";	
		display_docs('1',$group_id);
		print "<p><b>Pending Documents:</b><p>";	
		display_docs('3',$group_id);
		print "<p><b>Hidden Documents:</b><p>";	
		display_docs('4',$group_id);
		print "<p><b>Deleted Documents:</b><p>";	
		display_docs('2',$group_id);
		print "<p><b>Private Documents:</b><p>";	
		display_docs('5',$group_id);
		docman_footer($params);	

	} else {
		main_page($group_id);
	} //end else

?>
