<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/project/admin/permissions.php');    
require($DOCUMENT_ROOT.'/file/file_utils.php');


if (!user_ismember($group_id,'R2')) {
    exit_permission_denied();
}

/*


	Relatively simple form to edit/add packages of releases


*/

if ($submit) {
    /*
		make updates to the database

    */
	if ($func=='add_package' && $package_name) {

	  //make sure that the package_name does not already exist
	  $query = "SELECT * from frs_package where name='".htmlspecialchars($package_name)."'";
	  $res = db_query($query);
	  if ($res && (db_numrows($res) > 0)) {
	    $feedback .= ' This package name already exists. Please choose another name. ';
	  } else {
		//create a new package
		db_query("INSERT INTO frs_package (group_id,name,rank,status_id) ".
			"VALUES ('$group_id','". htmlspecialchars($package_name)."','$rank','1')");
		$feedback .= ' Added Package ';
	  }
	} else if ($func=='update_package' && $package_id && $package_name && $status_id) {
		if ($status_id != 1) {
			//if hiding a package, refuse if it has releases under it
// LJ Wrong SQL statement. It should only check for the existence of
// LJ active packages. If only hidden releases are in this package
// LJ then we can safely hide it.
// LJ $res=db_query("SELECT * FROM frs_release WHERE package_id='$package_id'");
			$res=db_query("SELECT * FROM frs_release WHERE package_id='$package_id' AND status_id=1");
			if (db_numrows($res) > 0) {
				$feedback .= ' Sorry - you cannot hide a package that still contains active releases. Hide attached releases first ';
				$status_id=1;
			}
		}
		//update an existing package
		db_query("UPDATE frs_package SET name='". htmlspecialchars($package_name)  ."', status_id='$status_id', rank='$rank'".
			"WHERE package_id='$package_id' AND group_id='$group_id'");
		$feedback .= ' Updated Package ';

	} else if ($func=='update_permissions') {
            list ($return_code, $feedback) = permission_process_selection_form($_POST['group_id'], $_POST['permission_type'], $_POST['object_id'], $_POST['ugroups']);
            if (!$return_code) exit_error('Error','ERROR: could not update permissions: <p>'.$feedback);
        }
}
if ($_POST['reset']) {
    // Must reset access rights to defaults
    if (permission_clear_all($group_id, $_POST['permission_type'], $_POST['object_id'])) {
        $feedback="Permissions reset to default";
    } else {
        $feedback="Error: cannot reset permissions to default";
    }
}

file_utils_admin_header(array('title'=>'Release/Edit File Releases', 'help' => 'FileReleaseDelivery.html'));


echo '<H3>Packages</H3>
<P>
You can use packages to group different file releases together. A package is a consistent set of source files that can be put together and deliver a working part of your bigger project.
<P>For instance, assuming you work on a multi-platform Database engine you could create the following packages:
<P>
<B>DB-win</B> the sources for the Windows version of the DB engine<BR>
<B>DB-unix</B> the sources for the Unix version  of the DB engine<BR>
<B>DB-odbc</B> the ODBC driver for both platforms
<P>
You must first define your packages by naming them.
<P>
Once you have packages defined, you can start creating new <B>releases of packages.</B>
<P>
<H3>Releases of Packages</H3>
<P>
A release of a package can contain multiple files. A release is generally identified with a version number. Examples of possible release names are:<B>3.22.1</B>, <b>3.22.2</B>, <B>3.22.3</B>,...
<P>
You can create new releases of packages by clicking on <B>Add/Edit Releases</B> next to your package name.
<P>';

/*

	Show a list of existing packages
	for this project so they can
	be edited

*/

// LJ status_id field was missing from the select statement
// LJ Causing the displayed status of packages to be wrong
// LJ $res=db_query("SELECT package_id,name AS package_name FROM frs_packag
$res=db_query("SELECT status_id,package_id,name AS package_name,rank FROM frs_package WHERE group_id='$group_id' ORDER BY rank");
$rows=db_numrows($res);
if (!$res || $rows < 1) {
	echo '<h4>You Have No Packages Defined</h4>';
} else {
	$title_arr=array();
	$title_arr[]='Package Name';
	$title_arr[]='Rank On Screen';
	$title_arr[]='Status';
	$title_arr[]='Update';
	$title_arr[]='Releases';
	$title_arr[]='Permissions';

	echo html_build_list_table_top ($title_arr);

	for ($i=0; $i<$rows; $i++) {
		echo '
		<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
		<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
		<INPUT TYPE="HIDDEN" NAME="func" VALUE="update_package">
		<INPUT TYPE="HIDDEN" NAME="package_id" VALUE="'. db_result($res,$i,'package_id') .'">
		<TR class="'. util_get_alt_row_color($i) .'">
			<TD><FONT SIZE="-1"><INPUT TYPE="TEXT" NAME="package_name" VALUE="'. 
				db_result($res,$i,'package_name') .'" SIZE="20" MAXLENGTH="30"></TD>
                        <TD align="center"><INPUT TYPE="TEXT" NAME="rank" SIZE="3" MAXLENGTH="3" VALUE="'.db_result($res,$i,'rank').'"/></TD>
			<TD align="center"><FONT SIZE="-1">'. frs_show_status_popup ('status_id', db_result($res,$i,'status_id')) .'</TD>
			<TD align="center"><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="Update"></TD>
			<TD  align="center" NOWRAP><FONT SIZE="-1"><A HREF="editreleases.php?package_id='. 
				db_result($res,$i,'package_id') .'&group_id='. $group_id .'"><B>[Add/Edit Releases]</B></A></TD>
			<TD  align="center" NOWRAP><FONT SIZE="-1"><A HREF="editpackagepermissions.php?package_id='. 
				db_result($res,$i,'package_id') .'&group_id='. $group_id .'"><B>['; 
                if (permission_exist('PACKAGE_READ',db_result($res,$i,'package_id'))) {
                    echo 'Edit';
                } else echo 'Define';
                echo ' Permissions]</B></A></TD>
		</TR></FORM>';
	}
	echo '</TABLE>';

}

/*

	form to create a new package

*/

echo '<p><hr><P>
<h3>Create a New Package</h3>
<P>
<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="add_package">
<table>
<tr><th>Package Name:</th>  <td><input type="text" name="package_name" size="20" MAXLENGTH="30"></td></tr>
<tr><th>Rank on screen:</th>  <td><input type="text" name="rank" size="4" maxlength="4"></td></tr>
<tr><td> <input type="submit" NAME="submit" VALUE="Create This Package"></td></tr></table>	
</FORM>';

file_utils_footer(array());

?>
