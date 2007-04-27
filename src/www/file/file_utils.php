<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX, 2001-2004. All Rights Reserved
// http://codex.xerox.com
//
// 
//
// Originally written by Nicolas Guerin 2004, CodeX Team, Xerox
//

// Provide various functions for file manager
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
$Language->loadLanguageMsg('file/file');

function file_utils_header($params) {
    global $group_id,$Language;

    $params['toptab']='file';
    $params['group']=$group_id;
    
    site_project_header($params);

    if (!array_key_exists('pv', $params) || !$params['pv']) {
        if (user_ismember($group_id,"R2")) {
            echo '<strong>'
                .'<a href="/file/admin/?group_id='.$group_id.'">'.$Language->getText('file_file_utils','admin').'</a>';
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
        $p = project_get_object($group_id);
        
        echo '<strong>';
        echo '<a href="/file/?group_id='.$group_id.'">'. $p->services['file']->getLabel() .'</a>';
        echo ' | <a href="/file/admin/?group_id='.$group_id.'">'.$Language->getText('file_file_utils','admin').'</a>';
	echo ' | <a href="/file/admin/manageprocessors.php?group_id='.$group_id.'">'.$Language->getText('file_file_utils','manage_proc').'</a>';
	if (!isset($params['help'])) { $params['help'] = "FileRelease.html";}
	echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));
        echo "</strong><br><hr>";
    }
}

function file_utils_footer($params) {
	site_project_footer($params);
}



function file_get_package_name_from_id($package_id) {
	$frspf = new FRSPackageFactory();	
	$res =& $frspf->getFRSPackageFromDb($package_id);
    return $res->getName();
}


function file_get_release_name_from_id($release_id) {
	$frsrf = new FRSReleaseFactory();
	$res = $frsrf->getFRSReleaseFromDb($release_id);	
    return $res->getName();
}


function file_get_package_id_from_release_id($release_id) {
	$frsrf = new FRSReleaseFactory();
	$res = $frsrf->getFRSReleaseFromDb($release_id);
    $res=db_query("SELECT package_id FROM frs_release WHERE release_id=$release_id");
    return $res->getPackageID();
}

/*

 The following functions are for the FRS (File Release System)
 They were moved here from project_admin_utils.php since they can
 now be used by non-admins (e.g. file releases admins)

*/


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

function frs_show_processor_popup ($group_id, $name='processor_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of the available processors 
	*/
	global $FRS_PROCESSOR_RES,$Language;
	if (!isset($FRS_PROCESSOR_RES)) {
		$FRS_PROCESSOR_RES=db_query("SELECT * FROM frs_processor WHERE group_id=100 OR group_id=$group_id ORDER BY rank");
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
	global $FRS_RELEASE_ID_RES,$FRS_RELEASE_NAME_RES,$Language;
	$frsrf = new FRSReleaseFactory();
	if (!$group_id) {
		return $Language->getText('file_file_utils','g_id_err');
	} else {
		if (!isset($FRS_RELEASE_ID_RES)) {
			$res = $frsrf->getFRSReleasesInfoListFromDb($group_id);
			$FRS_RELEASE_ID_RES = array();
			$FRS_RELEASE_NAME_RES = array();
			foreach($res as $release){
				$FRS_RELEASE_ID_RES[] = $release['release_id'];
				$FRS_RELEASE_NAME_RES[] = $release['package_name'].':'.$release['release_name'];
			}
		}
		return html_build_select_box_from_arrays ($FRS_RELEASE_ID_RES, $FRS_RELEASE_NAME_RES,$name,$checked_val,false);
	}
}
function frs_show_release_popup2($group_id, $name='release_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of releases for the project
	*/
	$frsrf = new FRSReleaseFactory();
	if (!$group_id) {
		return $GLOBALS['Language']->getText('file_file_utils','g_id_err');
	} else {
        $res = $frsrf->getFRSReleasesInfoListFromDb($group_id);
        $p = array();
        foreach($res as $release){
            $p[$release['package_name']][$release['release_id']] = $release['release_name'];
		}

		$select = '<select name="'. $name .'">';
        foreach($p as $package_name => $releases) {
            $select .= '<optgroup label="'. $package_name .'">';
            foreach($releases as $id => $name) {
                $select .= '<option value="'. $id .'" '. ($id == $checked_val ? 'selected="selected"' : '') .'>'. $name .'</option>';
            }
            $select .= '</optgroup>';
        }
        $select .= '</select>';
        return $select;
	}
}

/*

	pop-up box of packages for this group

*/

function frs_show_package_popup ($group_id, $name='package_id', $checked_val="xzxz") {
	/*
		return a pop-up select box of packages for this project
	*/
	global $FRS_PACKAGE_RES,$FRS_PACKAGE_NAME_RES,$Language;
	$frspf = new FRSPackageFactory();
	if (!$group_id) {
		return $Language->getText('file_file_utils','g_id_err');
	} else {
		if (!isset($FRS_PACKAGE_RES)) {
			$res =& $frspf->getFRSPackagesFromDb($group_id);
			$FRS_PACKAGE_ID_RES = array();
			$FRS_PACKAGE_NAME_RES = array();
			foreach($res as $package){
				$FRS_PACKAGE_ID_RES[] = $package->getPackageID();
				$FRS_PACKAGE_NAME_RES[] = $package->getName();
			}			
		}
		return html_build_select_box_from_arrays($FRS_PACKAGE_ID_RES, $FRS_PACKAGE_NAME_RES, $name,$checked_val,false);
	}
}

function file_utils_show_processors ($result) {
    global $group_id,$Language;

    $rows  =  db_numrows($result);

    $title_arr=array();
    $title_arr[]=$Language->getText('file_file_utils','proc_name');
    $title_arr[]=$Language->getText('file_file_utils','proc_rank');
    $title_arr[]=$Language->getText('file_file_utils','del'); 

    echo html_build_list_table_top ($title_arr);

    for($j=0; $j<$rows; $j++)  {

	$proc_id = db_result($result,$j,'processor_id');
	$proc_name = db_result($result,$j,'name');
	$proc_rank = db_result($result,$j,'rank');
	$gr_id = db_result($result,$j,'group_id');
	
	echo '<tr class="'. html_get_alt_row_color($j) .'">'. "\n";
	
    if ($gr_id == "100") {
        echo '<td>'.$proc_name.'</td>';
    } else {
	    echo '<td><A HREF="/file/admin/editproc.php?group_id='.$group_id.'&proc_id='.$proc_id.'" title="'.$proc_id.' - '.$proc_name.'">'.$proc_name.'</td>';
    }
    
    echo '<td>'.$proc_rank."</td>\n";     
	
	if ($gr_id == "100") {
	    #pre-defined processors are not manageable  
	    echo '<TD align=center>-</TD>';
	} else { 
	    echo '<TD align=center>'.
		'<a href="/file/admin/manageprocessors.php?mode=delete&group_id='. $group_id .'&proc_id='. $proc_id .'" '.
		'" onClick="return confirm(\''.$Language->getText('file_file_utils','del_proc').'\')">'.		
		'<IMG SRC="'.util_get_image_theme("ic/trash.png").'" HEIGHT="16" WIDTH="16" BORDER="0" ALT="'.$Language->getText('file_file_utils','del').'"></A></TD>';
	}
	
	echo "</tr>";
    }
    echo "</table>";
}

function file_utils_add_proc ($pname,$prank) {

    global $group_id,$Language;
    
    $sql = sprintf('INSERT INTO frs_processor'.
		   ' (name,group_id,rank)'.
		   ' VALUES'.
		   '("%s",%d,%d)',
		   $pname, $group_id, $prank);
    $result = db_query($sql);
    
    if ($result) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_file_utils','add_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_file_utils','add_proc_fail'));
    }
    
}

function file_utils_update_proc ($pid,$pname,$prank) {
    
    global $group_id,$Language;
    
    $sql = sprintf('UPDATE frs_processor'.
		   ' SET name = "%s",rank = %d'.
		   ' WHERE processor_id=%d'.
		   ' AND group_id=%d',
		   $pname, $prank, $pid, $group_id);
    $result = db_query($sql);
    
    if ($result) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_file_utils','update_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_file_utils','update_proc_fail'));
    }
    
}

function file_utils_delete_proc ($pid) {
    
    global $group_id,$Language;
    
    $sql = sprintf('DELETE FROM frs_processor'.
		   ' WHERE group_id=%d'.
		   ' AND processor_id=%d',
		   $group_id, $pid);
    $result = db_query($sql);

    if ($result) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_file_utils','delete_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_file_utils','delete_proc_fail'));
    }

}

function file_utils_convert_bytes_to_kbytes($size_in_bytes, $decimals_precision = 0) {
    global $Language;
    
    $size_in_kbytes = $size_in_bytes / 1024;
    
    $decimal_separator = $Language->getText('system','decimal_separator');
    $thousand_separator = $Language->getText('system','thousand_separator'); 
    // because I don't know how to specify a space in a .tab file
    if ($thousand_separator == "' '") {
        $thousand_separator = ' ';  
    }
    return number_format($size_in_kbytes, $decimals_precision, $decimal_separator, $thousand_separator); 
}

function frs_display_package_form(&$package, $title, $url, $siblings) {
    $group_id = $package->getGroupId();
    file_utils_admin_header(array('title'=>$GLOBALS['Language']->getText('file_admin_editpackages','edit_package'), 'help' => 'FileReleaseDelivery.html'));
    echo '<h3>'. $title .'</h3>
    <P>
    <form action="'. $url .'" method="post">
    <table>
    <tr><th>'.$GLOBALS['Language']->getText('file_admin_editpackages','p_name').':</th>  <td><input type="text" name="package[name]" CLASS="textfield_small" value="'. $package->getName() .'">';
    //{{{ Rank
    $nb_siblings = count($siblings);
    if ($nb_siblings && ($nb_siblings > 1 || $siblings[0] != $package->getPackageId())) {
        echo '</td></tr>';
        echo '<tr><th>'.$GLOBALS['Language']->getText('file_admin_editpackages','rank_on_screen').':</th><td>';
        $GLOBALS['HTML']->selectRank($package->getPackageId(), $package->getRank(), $siblings, array('name' => 'package[rank]'));
    } else {
        echo '<input type="hidden" name="package[rank]" value="0" />';
    }
    echo '</td></tr>';
    //}}}
    echo '<tr><th>'.$GLOBALS['Language']->getText('global','status').':</th>  <td>'. frs_show_status_popup('package[status_id]', $package->getStatusID()) .'</td></tr>';
    if (isset($GLOBALS['sys_frs_license_mandatory']) && !$GLOBALS['sys_frs_license_mandatory']) {
        $approve_license = $package->getApproveLicense();
        echo '<tr><th>'.$GLOBALS['Language']->getText('file_admin_editpackages','license').':</th>  <td><SELECT name="package[approve_license]">
                        <OPTION VALUE="1"'.(($approve_license == '1') ? ' SELECTED':'').'>'.$GLOBALS['Language']->getText('global','yes').'</OPTION>
                        <OPTION VALUE="0"'.(($approve_license == '1') ? ' SELECTED':'').'>'.$GLOBALS['Language']->getText('global','no').'</OPTION></SELECT></td></tr>';
     } else {
        echo '<INPUT TYPE="HIDDEN" NAME="package[approve_license]" VALUE="1">';
     }
     
     //We cannot set permission on creation for now
     if ($package->getPackageID()) {
         echo '<tr style="vertical-align:top"><th>' .'Permissions'. ':</th><td>';
         permission_display_selection_frs('PACKAGE_READ', $package->getPackageID(), $package->getGroupID());
         echo '</td></tr>';
     }
     echo '<tr><td> <input type="submit" NAME="submit" VALUE="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" /><input type="submit" name="cancel" value="'. $GLOBALS['Language']->getText('global','btn_cancel') .'" /></td></tr></table>	
     </FORM>';
     
     file_utils_footer(array());
}

function frs_display_release_form($is_update, &$release, $group_id, $title, $url) {
    global $frspf, $frsrf, $frsff;
    
    if (is_array($release)) {
        if (isset($release['date'])) {
            $release_date = $release['date'];
        }
        $release =& new FRSRelease($release);
    }
    file_utils_admin_header(array (
        'title' => $GLOBALS['Language']->getText('file_admin_editreleases',
        'release_new_file_version'
    ), 'help' => 'QuickFileRelease.html'));
    echo '<H3>'.$title.'</H3>';
    $sql = "SELECT * FROM frs_processor ORDER BY rank";
    $result = db_query($sql);
    $processor_id = util_result_column_to_array($result, 0);
    $processor_name = util_result_column_to_array($result, 1);
    $sql = "SELECT * FROM frs_filetype ORDER BY type_id";
    $result1 = db_query($sql);
    $type_id = util_result_column_to_array($result1, 0);
    $type_name = util_result_column_to_array($result1, 1);
    $url_news = get_server_url() . "/file/showfiles.php?group_id=" . $group_id;
    echo '<script type="text/javascript">';
    echo "var processor_id = ['" . implode("', '", $processor_id) . "'];";
    echo "var processor_name = ['" . implode("', '", $processor_name) . "'];";
    echo "var type_id = ['" . implode("', '", $type_id) . "'];";
    echo "var type_name = ['" . implode("', '", $type_name) . "'];";
    echo "var group_id = " . $group_id . ";";
    echo "var relname = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'relname') . "';";
    echo "var choose = '" . $GLOBALS['Language']->getText('file_file_utils', 'must_choose_one') . "';";
    echo "var browse = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'browse') . "';";
    echo "var local_file = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'local_file') . "';";
    echo "var scp_ftp_files = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'scp_ftp_files') . "';";
    echo "var upload_text = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'upload') . "';";
    echo "var add_file_text = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'add_file') . "';";
    echo "var add_change_log_text = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'add_change_log') . "';";
    echo "var view_change_text = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'view_change') . "';";
    echo "var refresh_files_list = '". $GLOBALS['Language']->getText('file_admin_editreleases','refresh_file_list') . "';";
    echo "var release_mode = '". ($is_update ? 'edition' : 'creation' ) ."';";
    if ($is_update) {
        $pm = & PermissionsManager::instance();
        $ugroups_name = $pm->getUgroupNameByObjectIdAndPermissionType($release->getReleaseID(), 'RELEASE_READ');
        echo "var ugroups_name = '" . implode(", ", $ugroups_name) . "';";
        echo "var default_permissions_text = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'release_perm') . "';";
    } else {
        echo "var default_permissions_text = '" . $GLOBALS['Language']->getText('file_admin_editreleases', 'default_permissions') . "';";
    }
    echo '</script>';
    $dirhandle = @ opendir($GLOBALS['ftp_incoming_dir']);
    //set variables for news template 
    $relname = $GLOBALS['Language']->getText('file_admin_editreleases', 'relname');
    if (!$is_update) {
        echo '<P>'.$GLOBALS['Language']->getText('file_admin_editreleases','contain_multiple_files');
    }
    ?>
    
    <FORM id="frs_form" NAME="frsRelease" ENCTYPE="multipart/form-data" METHOD="POST" ACTION="<?php echo $url; ?>">
        <INPUT TYPE="hidden" name="MAX_FILE_SIZE" value="<? echo $GLOBALS['sys_max_size_upload']; ?>">
        <input type="hidden" name="postReceived" value="" />
        <?php
        if ($release->getReleaseId()) {
            echo '<input type="hidden" name="release[release_id]" value="'. $release->getReleaseId() .'" />';
        }
        ?>
        <TABLE BORDER="0" width="100%">
        <TR><TD><FIELDSET><LEGEND><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','fieldset_properties'); ?></LEGEND>
        <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
            <TR>
                <TD>
                    <B><?php echo $GLOBALS['Language']->getText('file_admin_editpackages','p_name'); ?>:</B>
                </TD>
                <TD>
    <?php
    
    $res = & $frspf->getFRSPackagesFromDb($group_id);
    $rows = count($res);
    if (!$res || $rows < 1) {
        echo '<p class="highlight">' . $GLOBALS['Language']->getText('file_admin_qrs', 'no_p_available') . '</p>';
    } else {
        echo '<SELECT NAME="release[package_id]" id="package_id">';
        for ($i = 0; $i < $rows; $i++) {
            echo '<OPTION VALUE="' . $res[$i]->getPackageID() . '"';
            if($res[$i]->getPackageID() == $release->getPackageId()) echo ' selected';
            echo '>' . $res[$i]->getName() . '</OPTION>';
        }
        echo '</SELECT>';
    }
    ?>
                </TD><td></td>
                <TD>
                    <B><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','release_name'); ?>: <span class="highlight"><strong>*</strong></span></B>
                </TD>
                <TD>
                    <INPUT TYPE="TEXT" id="release_name" name="release[name]" onBlur="update_news()" value="<?php echo $release->getName(); ?>">
                </TD>
            </TR>
            <TR>
                <TD>
                    <B><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','release_date'); ?>:</B>
                </TD>
                <TD>
                <INPUT TYPE="TEXT" id="release_date" NAME="release[date]" VALUE="<?php echo isset($release_date) ? $release_date : format_date('Y-m-d',$release->getReleaseDate());?>" SIZE="10" MAXLENGTH="10">
                    <a href="<?php echo 'javascript:show_calendar(\'document.frsRelease.release_date\', $(\'release_date\').value,\''.util_get_css_theme().'\',\''.util_get_dir_image_theme().'\');">'.
                    '<img src="'.util_get_image_theme("calendar/cal.png").'" width="16" height="16" border="0" alt="'.$GLOBALS['Language']->getText('tracker_include_field','pick_date');?> "></a>
                </TD><td></td>
                <TD>
                    <B><?php echo $GLOBALS['Language']->getText('global','status'); ?>:</B>
                </TD>
                <TD>
                    <?php
    
    
        print frs_show_status_popup($name = 'release[status_id]', $release->getStatusID()) . "<br>";
    ?>
                </TD>
            </TR></TABLE></FIELDSET>
        </TD></TR>
        <TR><TD><FIELDSET><LEGEND><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','fieldset_uploaded_files'); ?></LEGEND>
    <?php
    
    $titles = array ();
    $titles[] = $is_update ? $GLOBALS['Language']->getText('file_admin_editreleases', 'delete_col') : '';
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'filename');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'processor');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'file_type');
    if ($is_update) {
        $titles[] = $GLOBALS['Language']->getText('file_admin_editreleasepermissions', 'release');
        $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'release_date');
    }
    echo html_build_list_table_top($titles, false, false, false, 'files');
    ?>
            <tbody id="files_body">
    
    <?php
        $files = & $release->getFiles();
        for ($i = 0; $i < count($files); $i++) {
            $fname = $files[$i]->getFileName();
            $list = split('/', $fname);
            $fname = $list[sizeof($list) - 1];
            echo '<TR>';
            echo '<TD><INPUT TYPE="CHECKBOX" NAME="release_files_to_delete[]" VALUE="' . $files[$i]->getFileID() . '"</TD>';
            echo '<TD>' . $fname . '<INPUT TYPE="HIDDEN" NAME="release_files[]" VALUE="' . $files[$i]->getFileID() . '"></TD>';
            echo '<TD>' . frs_show_processor_popup($group_id,$name = 'release_file_processor[]', $files[$i]->getProcessorID()) . '</TD>';
            echo '<TD>' . frs_show_filetype_popup($name = 'release_file_type[]', $files[$i]->getTypeID()) . '</TD>';
            echo '<TD>' . frs_show_release_popup2($group_id, $name = 'new_release_id[]', $files[$i]->getReleaseID()) . '</TD>';
            echo '<TD><INPUT TYPE="TEXT" NAME="release_time[]" VALUE="' . format_date('Y-m-d', $files[$i]->getReleaseTime()) . '" SIZE="10" MAXLENGTH="10"></TD></TR>';
        }
        echo '<INPUT TYPE="HIDDEN" id="nb_files" NAME="nb_files" VALUE="' . count($files) . '">';
    ?>
                        
                        <tr id="row_0">
                            <td></td>
                            <td>
                                <input type="hidden" name="js" value="no_js"/>
                                <select name="ftp_file[]" id="ftp_file_0">
                                    <option value="-1"><?php echo $GLOBALS['Language']->getText('file_file_utils','must_choose_one'); ?></option>
    <?php
    
    //iterate and show the files in the upload directory
    $file_list = $frsff->getUploadedFileNames();
    foreach ($file_list as $file) {
        echo '<option value="' . $file . '">' . $file . '</option>';
    }
    echo '<script type="text/javascript">';
    echo "var available_ftp_files = ['" . implode("', '", $file_list) . "'];";
    echo '</script>';
    
    ?>
                                </select>
    
                                <span id="or">or</span>
                                <input type="file" name="file[]" id="file_0" />
                            </td>
                            <td>
                                <?php print frs_show_processor_popup($group_id,$name = 'file_processor'); ?>
                            </td>
                            <td>
                                <?php print frs_show_filetype_popup($name = 'file_type'); ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php
    
    
        echo '<div id=\'files_help\'><span class="smaller">';
        include ($GLOBALS['Language']->getContent('file/qrs_attach_file'));
        echo '</span></div>';
    ?>
            </FIELDSET>
            </TD></TR>
            <TR><TD><FIELDSET><LEGEND><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','fieldset_notes'); ?></LEGEND>
            <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2" WIDTH="100%">
            <TR id="notes_title">
                <TD VALIGN="TOP" width="10%">
                    <span id="release_notes"><B><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','release_notes'); ?>:  </B></span>
                </TD>
            </TR>
            <TR id="upload_notes">
                <TD>
                    <input id="uploaded_notes" type="file" name="uploaded_release_notes"  size="30">
                </TD>
            </TR>
            <TR id="release_notes_area">
                <TD width="100%">
                    <TEXTAREA NAME="release[release_notes]" rows="7" cols="70"><?php echo htmlspecialchars($release->getNotes());?></TEXTAREA>
                </TD>
            </TR>
            <TR id="change_log_title">
                <TD VALIGN="TOP" width="10%">
                    <span id="change_log"><B><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','change_log'); ?>:  </B></span>
                </TD>
            </TR>
            <TR id="upload_change_log">
                <TD>
                    <input type="file" id="uploaded_change_log" name="uploaded_change_log"  size="30">
                </TD>
            </TR>
            <TR id="change_log_area">
                <TD width="40%">
                    <TEXTAREA ID="text_area_change_log" NAME="release[change_log]" ROWS="7" COLS="70"><?php echo htmlspecialchars($release->getChanges());?></TEXTAREA>
                </TD>
            </TR>
            </TABLE></FIELDSET>
            </TD></TR>
            <TR>
                <TD>
                    <FIELDSET><LEGEND><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','fieldset_permissions'); ?></LEGEND>
                        <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
    
                            <TR id="permissions">
                                <TD>
                                    <DIV id="permissions_list">
                                        <?php 
                                        if ($is_update) {
                                            permission_display_selection_frs("RELEASE_READ", $release->getReleaseID(), $group_id);
                                        } else {
                                            permission_display_selection_frs("PACKAGE_READ", $release->getPackageID(), $group_id);
                                        }
                                        ?>
                                    </DIV>
                                </TD>
                            </TR>
                        </TABLE>
                    </FIELDSET>
                </TD>
            </TR> 
            <?php
    
    
        if (user_ismember($group_id, 'A') || user_ismember($group_id, 'N2') || user_ismember($group_id, 'N1')) {
            echo '
            <TR><TD><FIELDSET><LEGEND>' . $GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_news') . '</LEGEND>
                <TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">
                    <TR>
                        <TD VALIGN="TOP">
                            <B> ' . $GLOBALS['Language']->getText('file_admin_editreleases', 'submit_news') . ' :</B>
                        </TD>
                        <TD>
                            <INPUT ID="submit_news" TYPE="CHECKBOX" NAME="release_submit_news" VALUE="1">
                            
                        </TD>	
                    </TR>
                    <TR id="tr_subject">
                        <TD VALIGN="TOP" ALIGN="RIGHT">
                            <B> ' . $GLOBALS['Language']->getText('file_admin_editreleases', 'subject') . ' :</B>
                        </TD>
                        <TD>
                            <INPUT TYPE="TEXT" ID="release_news_subject" NAME="release_news_subject" VALUE=" ' . $GLOBALS['Language']->getText('file_admin_editreleases', 'file_news_subject', $relname) . '" SIZE="40" MAXLENGTH="60">
                        </TD>
                    </TR>	
                    <TR id="tr_details">
                        <TD VALIGN="TOP" ALIGN="RIGHT">
                            <B> ' . $GLOBALS['Language']->getText('file_admin_editreleases', 'details') . ' :</B>
                        </TD>
                        <TD>
                            <TEXTAREA ID="release_news_details" NAME="release_news_details" ROWS="7" COLS="50">' . $GLOBALS['Language']->getText('file_admin_editreleases', 'file_news_details', array (
            $relname,
            $url_news
            )) . ' </TEXTAREA>
                        </TD>
                    </TR>
                    <TR id="tr_public">
                        <TD ROWSPAN=2 VALIGN="TOP" ALIGN="RIGHT">
                            <B> ' . $GLOBALS['Language']->getText('news_submit', 'news_privacy') . ' :</B>
                        </TD>
                        <TD>
                            <INPUT TYPE="RADIO" ID="publicnews" NAME="private_news" VALUE="0" CHECKED>' . $GLOBALS['Language']->getText('news_submit', 'public_news') . '
                        </TD>
                    </TR > 
                    <TR id="tr_private">
                        <TD>
                            <INPUT TYPE="RADIO" ID="privatenews" NAME="private_news" VALUE="1">' . $GLOBALS['Language']->getText('news_submit', 'private_news') . '
                        </TD>
                    </TR></DIV>
                </TABLE></FIELDSET>
            </TD></TR>';
        }
    
        $fmmf = new FileModuleMonitorFactory();
        $count = count($fmmf->getFilesModuleMonitorFromDb($release->getPackageId()));
        if ($count > 0) {
            echo '<TR><TD><FIELDSET><LEGEND>' . $GLOBALS['Language']->getText('file_admin_editreleases', 'fieldset_notification') . '</LEGEND>';
            echo '<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="2">';
            echo '<TR><TD>' . $GLOBALS['Language']->getText('file_admin_editreleases', 'users_monitor', $count) . '</TD></TR>';
            echo '<TR><TD><B>' . $GLOBALS['Language']->getText('file_admin_editreleases', 'mail_file_rel_notice') . '</B><INPUT TYPE="CHECKBOX" NAME="notification" VALUE="1" CHECKED>';
            echo '</TD></TR>';
            echo '</TABLE></FIELDSET></TD></TR>';
        }
    ?>
            
            <TR>
                <TD ALIGN="CENTER">
                    
                    <INPUT TYPE="HIDDEN" NAME="create" VALUE="bla">
                    <INPUT TYPE="SUBMIT" ID="create_release"  VALUE="<?php echo $is_update ? $GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release') : $GLOBALS['Language']->getText('file_admin_qrs', 'release_file'); ?>" onclick="check_parameters()">
                    <input type="submit" ID="cancel_release" name="cancel" value="<?php echo  $GLOBALS['Language']->getText('global','btn_cancel');?>" />
                </TD>
            </TR>
        </TABLE>
    </FORM>
    
    <?php
    
    file_utils_footer(array ());
}

function frs_process_release_form($is_update, $request, $group_id, $title, $url) {
    global $frspf, $frsrf, $frsff;

    //get all inputs from $request
    $release = $request->get('release');
    $js = $request->get('js');
    $ftp_file = $request->get('ftp_file') ? $request->get('ftp_file') : array();
    $file_processor = $request->get('file_processor');
    $file_type = $request->get('file_type');
    $ftp_file_processor = $request->get('ftp_file_processor');
    $ftp_file_type = $request->get('ftp_file_type');
    $release_news_subject = $request->get('release_news_subject');
    $release_news_details = $request->get('release_news_details');
    $private_news = $request->get('private_news');
    $ugroups = $request->get('ugroups');
    $release_submit_news = (int) $request->get('release_submit_news');
    $notification = $request->get('notification');
    if ($is_update) {
        $release_files_to_delete = $request->get('release_files_to_delete') ? $request->get('release_files_to_delete'):array();
        $release_files = $request->get('release_files') ? $request->get('release_files') : array();
        $release_file_processor = $request->get('release_file_processor');
        $release_file_type = $request->get('release_file_type');
        $new_release_id = $request->get('new_release_id');
        $release_time = $request->get('release_time');
        $release['release_id'] = $request->get('id');
    }

    $validator = new frsValidator();

    if ($is_update) {
        $valid = $validator->isValidForUpdate($release, $group_id);
    } else {
        $valid = $validator->isValidForCreation($release, $group_id);
    }
    if ($valid) {

        //uplaod release_notes and change_log if needed
        $data_uploaded = false;
        if (isset($_FILES['uploaded_change_log']) && !$_FILES['uploaded_change_log']['error']) {
            $code = addslashes(fread(fopen($_FILES['uploaded_change_log']['tmp_name'], 'r'), filesize($_FILES['uploaded_change_log']['tmp_name'])));
            if ((strlen($code) > 0) && (strlen($code) < $GLOBALS['sys_max_size_upload'])) {
                //size is fine
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'data_uploaded'));
                $data_uploaded = true;
                $release['change_log'] = $code;
            } else {
                //too big or small
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_admin_editreleases', 'length_err', $GLOBALS['sys_max_size_upload']));
            }
        }
        if (isset($_FILES['uploaded_release_notes']) && !$_FILES['uploaded_release_notes']['error']) {
            $code = addslashes(fread(fopen($_FILES['uploaded_release_notes']['tmp_name'], 'r'), filesize($_FILES['uploaded_release_notes']['tmp_name'])));
            if ((strlen($code) > 0) && (strlen($code) < $GLOBALS['sys_max_size_upload'])) {
                //size is fine
                if (!$data_uploaded) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'data_uploaded'));
                }
                $release['release_notes'] = $code;
            } else {
                //too big or small
                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_admin_editreleases', 'length_err', $GLOBALS['sys_max_size_upload']));
            }
        }

        if ($is_update) {
            // make sure that we don't change the date by error because of timezone reasons.
            // eg: release created in India (GMT +5:30) at 2004-06-03. 
            // MLS in Los Angeles (GMT -8) changes the release notes
            // the release_date that we showed MLS is 2004-06-02. 
            // with mktime(0,0,0,2,6,2004); we will change the unix time in the database
            // and the people in India will discover that their release has been created on 2004-06-02
            $rel = & $frsrf->getFRSReleaseFromDb($release['release_id']);
            if (format_date('Y-m-d', $rel->getReleaseDate()) == $release['date']) {
                // the date didn't change => don't update it
                $unix_release_time = $rel->getReleaseDate();
            }else{
                $date_list = split("-", $release['date'], 3);
                $unix_release_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
            }
        } else {
            //parse the date 
            $date_list = split("-", $release['date'], 3);
            $unix_release_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
        }

        //now we create or update the release
        $array = array (
            'release_date' => $unix_release_time,
            'name' => $release['name'],
            'status_id' => $release['status_id'],
            'package_id' => $release['package_id'],
            'notes' => $release['release_notes'],
            'changes' => $release['change_log']
        );
        if ($is_update) {
            $array['release_id'] = $release['release_id'];
        }
        
        if ($is_update) {
            $res = $frsrf->update($array);
            if (!$res) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_update_failed'));
                //insert failed - go back to definition screen
            } else {
                //release added - now show the detail page for this new release
                $release_id = $array['release_id'];
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_updated'));
            }
        } else {
            $res = $frsrf->create($array);
            if (!$res) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language'] > getText('file_admin_editreleases', 'add_rel_fail'));
                //insert failed - go back to definition screen
            } else {
                //release added - now show the detail page for this new release
                $release_id = $res;
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_added'));
            }
        }
        if ($res) {
            //set the release permissions
            list ($return_code, $feedbacks) = permission_process_selection_form($group_id, 'RELEASE_READ', $release_id, $ugroups);
            if (!$return_code) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'perm_update_err'));
                $GLOBALS['Response']->addFeedback('error', $feedbacks);
            }
            
            //submit news if requested
            if ($release_id && user_ismember($group_id, 'A') && $release_submit_news) {
                $new_id = forum_create_forum($GLOBALS['sys_news_group'], $release_news_subject, 1, 0);
                $sql = sprintf('INSERT INTO news_bytes' .
                '(group_id,submitted_by,is_approved,date,forum_id,summary,details)' .
                'VALUES (%d, %d, %d, %d, %d, "%s", "%s")', $group_id, user_getid(), 0, time(), $new_id, htmlspecialchars($release_news_subject), htmlspecialchars($release_news_details));
                $result = db_query($sql);

                if (!$result) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('news_submit', 'insert_err'));
                } else {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('news_submit', 'news_added'));
                    // set permissions on this piece of news
                    if ($private_news) {
                        news_insert_permissions($new_id, $group_id);
                    }
                }
            }

            //send notification
            if ($notification) {
                /*
                    Send a release notification email
                */
                $fmmf = new FileModuleMonitorFactory();
                $result = $fmmf->whoIsMonitoringPackageById($group_id, $release['package_id']);

                if ($result && count($result) > 0) {
                    //send the email
                    $array_emails = array ();
                    foreach ($result as $res) {
                        $array_emails[] = $res['email'];
                        $package_name = $res['name'];
                    }
                    $list = implode($array_emails, ', ');
                    $subject = $GLOBALS['sys_name'] . ' ' . $GLOBALS['Language']->getText('file_admin_editreleases', 'file_rel_notice') . ' ' . $GLOBALS['Language']->getText('file_admin_editreleases', 'file_rel_notice_project', group_getunixname($group_id));
                    $package_id = $release['package_id'];
                    list ($host, $port) = explode(':', $GLOBALS['sys_default_domain']);
                    $body = $GLOBALS['Language']->getText('file_admin_editreleases', 'download_explain_modified_package', $package_name) . " " . $GLOBALS['Language']->getText('file_admin_editreleases', 'download_explain', array (
                    "<" . get_server_url() . "/file/showfiles.php?group_id=$group_id&release_id=$release_id> ", $GLOBALS['sys_name'])) .
                    "\n<" . get_server_url() . "/file/filemodule_monitor.php?filemodule_id=$package_id> ";

                    $mail = & new Mail();
                    $mail->setFrom($GLOBALS['sys_noreply']);
                    $mail->setBcc($list);
                    $mail->setSubject($subject);
                    $mail->setBody($body);
                    if ($mail->send()) {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'email_sent', count($result)));
                    } else { //ERROR
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array (
                            $GLOBALS['sys_email_admin']
                        )));
                    }
                }
            }

            $group_unix_name = group_getunixname($group_id);
            $project_files_dir = $GLOBALS['ftp_frs_dir_prefix'] . '/' . $group_unix_name;

            if ($is_update) {
                //remove files
                foreach ($release_files_to_delete as $rel_file) {
                    $res =& $frsff->getFRSFileFromDb($rel_file);
                    $fname = $res->getFileName();
                    $list = split('/', $fname);
                    $fname = $list[sizeof($list) - 1];            
                    $res = $frsff->delete_file($group_id, $rel_file);
                    if ($res == 0) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'f_not_yours', $fname));
                    } else {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'file_deleted', $fname));
                    }
                }
    
                //update files
                $files =& $rel->getFiles();
                $index = 0;
                foreach ($release_files as $rel_file) {
                              
                    if (!$release_files_to_delete || !in_array($rel_file, $release_files_to_delete) ) {
                        $fname = $files[$index]->getFileName();
                        $list = split('/', $fname);
                        $fname = $list[sizeof($list) - 1];      
                        if ($new_release_id[$index] != $release_id) {
                            //changing to a different release for this file
                            //see if the new release is valid for this project
                            $res2 = & $frsrf->getFRSReleaseFromDb($new_release_id[$index], $group_id);
                            if (!$res2 || count($res2) < 1) {
                                //release not found for this project
                                $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_not_yours', $fname));
                            }
                        } 
                         if($new_release_id[$index] == $release_id || $res2) {
                                if (!ereg("[0-9]{4}-[0-9]{2}-[0-9]{2}", $release_time[$index])) {
                                    $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('file_admin_editreleases', 'data_not_parsed_file', $fname));
                                } else {
                                    $res2 = & $frsff->getFRSFileFromDb($rel_file);
                                    if (format_date('Y-m-d', $res2->getReleaseTime()) == $release_time[$index]) {
                                        $unix_release_time = $res2->getReleaseTime();
                                    } else {
                                        $date_list = split("-", $release_time[$index], 3);
                                        $unix_release_time = mktime(0, 0, 0, $date_list[1], $date_list[2], $date_list[0]);
                                    }
                                    $array = array (
                                        'release_id' => $new_release_id[$index],
                                        'release_time' => $unix_release_time,
                                        'type_id' => $release_file_type[$index],
                                        'processor_id' => $release_file_processor[$index],
                                        'file_id' => $rel_file
                                    );
                                    $res = $frsff->update($array);
                                    if($res) {
                                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'file_updated', $fname));
                                    }
                                }
                            }
                            $index ++;
                    }
                    
                }
            }
            //add new files
            //files processing
            $http_files_list = array ();
            $processor_type_list = array ();
            $file_type_list = array ();

            $http_files_processor_type_list = array ();
            $ftp_files_processor_type_list = array ();
            if (isset ($js) && $js == 'no_js') {
                //if javascript is not allowed, there is maximum one file to upload						
                if ($ftp_file[0] != -1) {
                    $ftp_files_processor_type_list[] = array (
                        'name' => $ftp_file[0],
                        'processor' => $file_processor,
                        'type' => $file_type
                    );

                } else
                    if (trim($_FILES['file']['name'][0]) != '') {
                        $http_files_processor_type_list[] = array (
                            'error' => $_FILES['file']['error'][0],
                            'name' => $_FILES['file']['name'][0],
                            'tmp_name' => $_FILES['file']['tmp_name'][0],
                            'processor' => $file_processor,
                            'type' => $file_type
                        );
                    }
            } else {
                //get http files with the associated processor type and file type in allowed javascript case
                $nb_files = isset($_FILES['file']) ? count($_FILES['file']['name']) : 0;
                for ($i = 0; $i < $nb_files; $i++) {
                    if (trim($_FILES['file']['name'][$i]) != '') {
                        $http_files_processor_type_list[] = array (
                            'error' => $_FILES['file']['error'][$i],
                            'name' => $_FILES['file']['name'][$i],
                            'tmp_name' => $_FILES['file']['tmp_name'][$i],
                            'processor' => $file_processor[$i],
                            'type' => $file_type[$i]
                        );
                    }
                }
                //remove hidden ftp_file input (if the user let the select boxe on --choose file)
                $tmp_file_list = array ();
                $index = 0;
                foreach ($ftp_file as $file) {
                    if (trim($file) != '') {
                        $ftp_files_processor_type_list[] = array (
                            'name' => $file,
                            'processor' => $ftp_file_processor[$index],
                            'type' => $ftp_file_type[$index]
                        );
                        $index++;
                    }
                }
            }

            if (count($http_files_processor_type_list) > 0 || count($ftp_files_processor_type_list) > 0) {
                //see if this release belongs to this project
                $res1 = & $frsrf->getFRSReleaseFromDb($release_id, $group_id);
                if (!$res1 || count($res1) < 1) {
                    //release not found for this project
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_not_yours'));
                } else {
                    $now = time();
                    $addingFiles = false;
                    //iterate and add the http files to the frs_file table
                    foreach ($http_files_processor_type_list as $file) {

                        //see if filename is legal before adding it
                        $filename = $file['name'];
                        if (!util_is_valid_filename($filename)) {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'illegal_file_name') . ": $filename");
                        } else {
                            if (isset($file['error'])) {
                                switch($file['error']) {
                                    case UPLOAD_ERR_OK:
                                        // all is OK
                                        break;
                                    case UPLOAD_ERR_INI_SIZE:
                                    case UPLOAD_ERR_FORM_SIZE:
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'error_upload_size', $file['error']));
                                        break;
                                    case UPLOAD_ERR_PARTIAL:
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'error_upload_partial', $file['error']));
                                        break;
                                    case UPLOAD_ERR_NO_FILE:
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'error_upload_nofile', $file['error']));
                                        break;
                                    default:
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'error_upload_unknown', $file['error']));
                                }
                            }
                            if (is_uploaded_file($file['tmp_name'])) {
                                $uploaddir = $GLOBALS['ftp_incoming_dir'];
                                $uploadfile = $uploaddir . "/" . basename($filename);
                                if (!file_exists($uploaddir) || !is_writable($uploaddir) || !move_uploaded_file($file['tmp_name'], $uploadfile)) {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": " . basename($filename));
                                } else {
                                    // get the package id and compute the upload directory
                                    $pres = & $frsrf->getFRSReleaseFromDb($release_id, $group_id, $release['package_id']);

                                    if (!$pres || count($pres) < 1) {
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'p_rel_not_yours'));
                                    }
                                    //see if they already have a file by this name
                                    $res1 = $frsff->isFileBaseNameExists($filename, $release_id, $group_id);
                                    if (!$res1) {

                                        /*
                                            move the file to the project's fileserver directory
                                        */
                                        clearstatcache();
                                        if (is_file($GLOBALS['ftp_incoming_dir'] . '/' . $filename) && file_exists($GLOBALS['ftp_incoming_dir'] . '/' . $filename)) {
                                            //move the file to a its project page using a setuid program
                                            //test if the file aldready exists in the destination directory
                                            $group = new Group($group_id);
                                            $group_unix_name = $group->getUnixName();
                                            if (!file_exists($GLOBALS['ftp_frs_dir_prefix'].'/'.$group_unix_name.'/'.$frsff->getUploadSubDirectory($release_id).'/'.$filename)){
                                                $exec_res = $frsff->moveFileForge($group_id, $filename, $frsff->getUploadSubDirectory($release_id));
                                                if (!$exec_res) {
                                                    //echo '<h3>' . $exec_res[0], $exec_res[1] . '</H3><P>';
                                                
                                                    //add the file to the database
                                                    $array = array (
                                                        'filename' => $frsff->getUploadSubDirectory($release_id
                                                    ) . '/' . $filename, 'release_id' => $release_id, 'file_size' => filesize($project_files_dir . '/' . $frsff->getUploadSubDirectory($release_id) . '/' . $filename), 'processor_id' => $file['processor'] , 'type_id' => $file['type'] );
                                                    $res = & $frsff->create($array);
        
                                                    if (!$res) {
                                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": $filename ");
                                                        echo db_error();
                                                    } else {
                                                        $addingFiles = true;
                                                    }
                                                }else{
                                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": " . basename($filename));
                                                }
                                            } else {
                                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'upload_file_deleted', basename($filename)));
                                            }
                                        } else {
                                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'filename_invalid') . ": $filename");
                                        }
                                    } else {
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'filename_exists') . ": $filename");
                                    }
                                }
                            }else{
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": " . basename($filename));
                            }
                        }
                    }

                    //iterate and add the ftp files to the frs_file table
                    foreach ($ftp_files_processor_type_list as $file) {
                        $filename = $file['name'];
                        //see if filename is legal before adding it
                        if (!util_is_valid_filename($filename)) {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'illegal_file_name') . ": $filename");
                        } else {
                            // get the package id and compute the upload directory
                            $pres = & $frsrf->getFRSReleaseFromDb($release_id, $group_id, $release['package_id']);

                            if (!$pres || count($pres) < 1) {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'p_rel_not_yours'));
                            }
                            //see if they already have a file by this name
                            $res1 = $frsff->isFileBaseNameExists($filename, $release_id, $group_id);
                            if (!$res1) {

                                /*
                                    move the file to the project's fileserver directory
                                */
                                clearstatcache();
                                if (is_file($GLOBALS['ftp_incoming_dir'] . '/' . $filename) && file_exists($GLOBALS['ftp_incoming_dir'] . '/' . $filename)) {
                                    //move the file to a its project page using a setuid program
                                    if (!file_exists($GLOBALS['ftp_frs_dir_prefix'].'/'.$group_unix_name.'/'.$frsff->getUploadSubDirectory($release_id).'/'.$filename)){
                                        $exec_res = $frsff->moveFileForge($group_id, $filename, $frsff->getUploadSubDirectory($release_id));
                                        if (!$exec_res) {
                                            //echo '<h3>' . $exec_res[0], $exec_res[1] . '</H3><P>';
                                            //add the file to the database
                                            $array = array (
                                                'filename' => $frsff->getUploadSubDirectory($release_id
                                            ) . '/' . $filename, 'release_id' => $release_id, 'file_size' => filesize($project_files_dir . '/' . $frsff->getUploadSubDirectory($release_id) . '/' . $filename), 'processor_id' => $file['processor'], 'type_id' => $file['type']);
                                            $res = & $frsff->create($array);
        
                                            if (!$res) {
                                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": $filename ");
                                                echo db_error();
                                            } else {
                                                $addingFiles = true;
                                            }
                                        }else{
                                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": $filename ");
                                        }   
                                    }else {
                                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'upload_file_deleted', basename($filename)));
                                    }
                                } else {
                                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'filename_invalid') . ": $filename");
                                }
                            } else {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'filename_exists') . ": $filename");
                            }
                        }
                    }
                }
                if ($addingFiles){
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'add_files'));
                }
            }
            //redirect to files
            $GLOBALS['Response']->redirect('/file/?group_id=' . $group_id);
        }
    } else {
        $GLOBALS['Response']->addFeedback('error', $validator->getErrors());
    }
    frs_display_release_form($is_update, $release, $group_id, $title, $url);
}
?>