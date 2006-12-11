<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
// Originally written by Nicolas Guerin 2004, CodeX Team, Xerox
//

// Provide various functions for file manager

$Language->loadLanguageMsg('file/file');

function file_utils_header($params) {
    global $group_id,$Language;

    $params['toptab']='file';
    $params['group']=$group_id;
    
    site_project_header($params);

    if (!array_key_exists('pv', $params) || !$params['pv']) {
        if (user_ismember($group_id,"R2")) {
            echo '<strong>'
                .'<a href="/file/admin/index.php?group_id='.$group_id.'">'.$Language->getText('file_file_utils','admin').'</a>';
            if (!isset($params['help'])) { $params['help'] = "FileRelease.html";}
            echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
            echo "</strong><p>";
        }
    }
}

function file_utils_admin_header($params) {
  global $group_id,$Language;

    $params['toptab']='file';
    $params['group']=$group_id;
    
    site_project_header($params);

    if (user_ismember($group_id,"R2")) {
        echo '<strong>'
            .'<a href="/file/admin/index.php?group_id='.$group_id.'">'.$Language->getText('file_file_utils','admin').'</a>'
            .' | <a href="/file/admin/editpackages.php?group_id='.$group_id.'">'.$Language->getText('file_admin_index','edit_release_files').'</a>';
        echo ' | <a href="/file/admin/qrs.php?group_id='.$group_id.'">'.$Language->getText('file_admin_index','quick_add').'</a>';
	if (!isset($params['help'])) { $params['help'] = "FileRelease.html";}
	echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
        echo "</strong><br><hr>";
    }
}

function file_utils_footer($params) {
	site_project_footer($params);
}



function file_get_package_name_from_id($package_id) {
    $res=db_query("SELECT name FROM frs_package WHERE package_id=$package_id");
    return db_result($res,0,'name');
}


function file_get_release_name_from_id($release_id) {
    $res=db_query("SELECT name FROM frs_release WHERE release_id=$release_id");
    return db_result($res,0,'name');
}


function file_get_package_id_from_release_id($release_id) {
    $res=db_query("SELECT package_id FROM frs_release WHERE release_id=$release_id");
    return db_result($res,0,'package_id');
}

/*

 The following functions are for the FRS (File Release System)
 They were moved here from project_admin_utils.php since they can
 now be used by non-admins (e.g. file releases admins)

*/


// Is the package active, so that we can display it and send notifications when it is updated?
function frs_package_is_active($status_id) {
    return (($status_id==1)?true:false);
}

/*

	pop-up box of supported frs statuses

*/

function frs_show_status_popup ($name='status_id', $checked_val="xzxz") {
    global $Language;
	/*
		return a pop-up select box of statuses
	*/
	global $FRS_STATUS_RES;
	if (!isset($FRS_STATUS_RES)) {
		$FRS_STATUS_RES=db_query("SELECT * FROM frs_status");
	}
	$arr_id = util_result_column_to_array($FRS_STATUS_RES,0);
	$arr_status = util_result_column_to_array($FRS_STATUS_RES,1);
	for ($i=0; $i<count($arr_status); $i++) {
	    $arr_status[$i] = $Language->getText('file_admin_editpackages',strtolower($arr_status[$i]));
	}
	return html_build_select_box_from_arrays($arr_id,$arr_status,$name,$checked_val,false);

}

/*

	pop-up box of supported frs filetypes

*/

function frs_show_filetype_popup ($name='type_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of the available filetypes
	*/
	global $FRS_FILETYPE_RES,$Language;
	if (!isset($FRS_FILETYPE_RES)) {
// LJ Sort by type_id added so that new extensions goes
// LJ in the right place in the menu box
		$FRS_FILETYPE_RES=db_query("SELECT * FROM frs_filetype ORDER BY type_id");
	}
	return html_build_select_box ($FRS_FILETYPE_RES,$name,$checked_val,true,$Language->getText('file_file_utils','must_choose_one'));
}

/*

	pop-up box of supported frs processor options

*/

function frs_show_processor_popup ($name='processor_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of the available processors 
	*/
	global $FRS_PROCESSOR_RES,$Language;
	if (!isset($FRS_PROCESSOR_RES)) {
		$FRS_PROCESSOR_RES=db_query("SELECT * FROM frs_processor");
	}
	return html_build_select_box ($FRS_PROCESSOR_RES,$name,$checked_val,true,$Language->getText('file_file_utils','must_choose_one'));
}

/*

	pop-up box of packages:releases for this group

*/


function frs_show_release_popup ($group_id, $name='release_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of releases for the project
	*/
	global $FRS_RELEASE_RES,$Language;
	if (!$group_id) {
		return $Language->getText('file_file_utils','g_id_err');
	} else {
		if (!isset($FRS_RELEASE_RES)) {
			$FRS_RELEASE_RES=db_query("SELECT frs_release.release_id,concat(frs_package.name,' : ',frs_release.name) ".
				"FROM frs_release,frs_package ".
				"WHERE frs_package.group_id='$group_id' ".
				"AND frs_release.package_id=frs_package.package_id");
			echo db_error();
		}
		return html_build_select_box ($FRS_RELEASE_RES,$name,$checked_val,false);
	}
}

/*

	pop-up box of packages for this group

*/

function frs_show_package_popup ($group_id, $name='package_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of packages for this project
	*/
	global $FRS_PACKAGE_RES,$Language;
	if (!$group_id) {
		return $Language->getText('file_file_utils','g_id_err');
	} else {
		if (!isset($FRS_PACKAGE_RES)) {
			$FRS_PACKAGE_RES=db_query("SELECT package_id,name FROM frs_package WHERE group_id='$group_id'");
			echo db_error();
		}
		return html_build_select_box ($FRS_PACKAGE_RES,$name,$checked_val,false);
	}
}
