<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('www/project/admin/permissions.php');    
require_once('www/file/file_utils.php');
$Language->loadLanguageMsg('file/file');

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
          $query = "SELECT * from frs_package where group_id=".$group_id." AND name='".htmlspecialchars($package_name)."'";
	  $res = db_query($query);
	  if ($res && (db_numrows($res) > 0)) {
	    $feedback .= ' '.$Language->getText('file_admin_editpackages','p_name_exists').' ';
	  } else {
		//create a new package
		db_query("INSERT INTO frs_package (group_id,name,rank,status_id) ".
			"VALUES ('$group_id','". htmlspecialchars($package_name)."','$rank','1')");
		$feedback .= ' '.$Language->getText('file_admin_editpackages','p_added').' ';
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
				$feedback .= ' '.$Language->getText('file_admin_editpackages','cannot_hide').' ';
				$status_id=1;
			}
		}
		//update an existing package
		db_query("UPDATE frs_package SET name='". htmlspecialchars($package_name)  ."', status_id='$status_id', rank='$rank'".
			"WHERE package_id='$package_id' AND group_id='$group_id'");
		$feedback .= ' '.$Language->getText('file_admin_editpackages','p_updated').' ';

	} else if ($func=='update_permissions') {
            list ($return_code, $feedback) = permission_process_selection_form($_POST['group_id'], $_POST['permission_type'], $_POST['object_id'], $_POST['ugroups']);
            if (!$return_code) exit_error($Language->getText('global','error'),$Language->getText('file_admin_editpackages','perm_update_err').': <p>'.$feedback);
        }
}
if ($_POST['reset']) {
    // Must reset access rights to defaults
    if (permission_clear_all($group_id, $_POST['permission_type'], $_POST['object_id'])) {
        $feedback=$Language->getText('file_admin_editpackages','perm_reset');
    } else {
        $feedback=$Language->getText('file_admin_editpackages','perm_reset_err');
    }
}

file_utils_admin_header(array('title'=>$Language->getText('file_admin_editpackages','release_edit_f_rel'), 'help' => 'FileReleaseDelivery.html'));


echo '<H3>'.$Language->getText('file_admin_editpackages','packages').'</H3>
<P>
'.$Language->getText('file_admin_editpackages','p_explain').'
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
	echo '<h4>'.$Language->getText('file_admin_editpackages','no_p_defined').'</h4>';
} else {
	$title_arr=array();
	$title_arr[]=$Language->getText('file_admin_editpackages','p_name');
	$title_arr[]=$Language->getText('file_admin_editpackages','rank_on_screen');
	$title_arr[]=$Language->getText('global','status');
	$title_arr[]=$Language->getText('file_admin_editpackages','update');
	$title_arr[]=$Language->getText('file_admin_editpackages','releases');
	$title_arr[]=$Language->getText('file_admin_editpackages','perms');

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
			<TD align="center"><FONT SIZE="-1"><INPUT TYPE="SUBMIT" NAME="submit" VALUE="'.$Language->getText('file_admin_editpackages','update').'"></TD>
			<TD  align="center" NOWRAP><FONT SIZE="-1"><A HREF="editreleases.php?package_id='. 
				db_result($res,$i,'package_id') .'&group_id='. $group_id .'"><B>['.$Language->getText('file_admin_editpackages','add_edit_releases').']</B></A></TD>
			<TD  align="center" NOWRAP><FONT SIZE="-1"><A HREF="editpackagepermissions.php?package_id='. 
				db_result($res,$i,'package_id') .'&group_id='. $group_id .'"><B>['; 
                if (permission_exist('PACKAGE_READ',db_result($res,$i,'package_id'))) {
                    echo $Language->getText('file_admin_editpackages','edit');
                } else echo $Language->getText('file_admin_editpackages','define');
                echo ' '.$Language->getText('file_admin_editpackages','perms').']</B></A></TD>
		</TR></FORM>';
	}
	echo '</TABLE>';

}

/*

	form to create a new package

*/

echo '<p><hr><P>
<h3>'.$Language->getText('file_admin_editpackages','create_new_p').'</h3>
<P>
<FORM ACTION="'. $PHP_SELF .'" METHOD="POST">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="'.$group_id.'">
<INPUT TYPE="HIDDEN" NAME="func" VALUE="add_package">
<table>
<tr><th>'.$Language->getText('file_admin_editpackages','p_name').':</th>  <td><input type="text" name="package_name" size="20" MAXLENGTH="30"></td></tr>
<tr><th>'.$Language->getText('file_admin_editpackages','rank_on_screen').':</th>  <td><input type="text" name="rank" size="4" maxlength="4"></td></tr>
<tr><td> <input type="submit" NAME="submit" VALUE="'.$Language->getText('file_admin_editpackages','create_this_p').'"></td></tr></table>	
</FORM>';

file_utils_footer(array());

?>
