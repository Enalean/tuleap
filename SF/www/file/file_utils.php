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


function file_utils_header($params) {
    global $group_id;

    $params['toptab']='file';
    $params['group']=$group_id;
    
    site_project_header($params);

    if (!$params['pv']) {
        if (user_ismember($group_id,"R2")) {
            echo '<strong>'
                .'<a href="/file/admin/index.php?group_id='.$group_id.'">Admin</a>';
            if (!$params['help']) { $params['help'] = "FileRelease.html";}
            echo ' | '.help_button($params['help'],false,'Help');
            echo "</strong><p>";
        }
    }
}

function file_utils_admin_header($params) {
    global $group_id;

    $params['toptab']='file';
    $params['group']=$group_id;
    
    site_project_header($params);

    if (user_ismember($group_id,"R2")) {
        echo '<strong>'
            .'<a href="/file/admin/index.php?group_id='.$group_id.'">Admin</a>'
            .' | <a href="/file/admin/editpackages.php?group_id='.$group_id.'">Edit/Release Files</a>';
        echo ' | <a href="/file/admin/qrs.php?group_id='.$group_id.'">Quick Add File Release</a>';
	if (!$params['help']) { $params['help'] = "FileRelease.html";}
	echo ' | '.help_button($params['help'],false,'Help');
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
	/*
		return a pop-up select box of statuses
	*/
	global $FRS_STATUS_RES;
	if (!isset($FRS_STATUS_RES)) {
		$FRS_STATUS_RES=db_query("SELECT * FROM frs_status");
	}
	return html_build_select_box ($FRS_STATUS_RES,$name,$checked_val,false);

}

/*

	pop-up box of supported frs filetypes

*/

function frs_show_filetype_popup ($name='type_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of the available filetypes
	*/
	global $FRS_FILETYPE_RES;
	if (!isset($FRS_FILETYPE_RES)) {
// LJ Sort by type_id added so that new extensions goes
// LJ in the right place in the menu box
		$FRS_FILETYPE_RES=db_query("SELECT * FROM frs_filetype ORDER BY type_id");
	}
	return html_build_select_box ($FRS_FILETYPE_RES,$name,$checked_val,true,'Must Choose One');
}

/*

	pop-up box of supported frs processor options

*/

function frs_show_processor_popup ($name='processor_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of the available processors 
	*/
	global $FRS_PROCESSOR_RES;
	if (!isset($FRS_PROCESSOR_RES)) {
		$FRS_PROCESSOR_RES=db_query("SELECT * FROM frs_processor");
	}
	return html_build_select_box ($FRS_PROCESSOR_RES,$name,$checked_val,true,'Must Choose One');
}

/*

	pop-up box of packages:releases for this group

*/


function frs_show_release_popup ($group_id, $name='release_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of releases for the project
	*/
	global $FRS_RELEASE_RES;
	if (!$group_id) {
		return 'ERROR - GROUP ID REQUIRED';
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
	global $FRS_PACKAGE_RES;
	if (!$group_id) {
		return 'ERROR - GROUP ID REQUIRED';
	} else {
		if (!isset($FRS_PACKAGE_RES)) {
			$FRS_PACKAGE_RES=db_query("SELECT package_id,name FROM frs_package WHERE group_id='$group_id'");
			echo db_error();
		}
		return html_build_select_box ($FRS_PACKAGE_RES,$name,$checked_val,false);
	}
}