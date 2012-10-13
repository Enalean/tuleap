<?php
//
// Codendi
// Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
// http://www.codendi.com
//
// 
//
// Originally written by Nicolas Guerin 2004, Codendi Team, Xerox
//

// Provide various functions for file manager
require_once('common/frs/FRSPackageFactory.class.php');
require_once('common/frs/FRSReleaseFactory.class.php');
require_once('common/reference/ReferenceManager.class.php');
require_once('www/news/news_utils.php');

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

// Workaround for the 2GB limitation
function file_utils_get_size($file) {
    //Uncomment when limitation is fixed
    //return filesize($file);

    if (!(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')) {
        //if filename containing spaces
        $filename = escapeshellarg($file);
        $size =trim(`stat -c%s $filename`);
        //$size = filesize($file);   
    } else {
        // Not tested...
        $fsobj = new COM("Scripting.FileSystemObject");
        $f = $fsobj->GetFile($file);
        $size = $file->Size;
    }
    return $size;
}

function file_utils_admin_header($params) {
  global $group_id,$Language;

    $params['toptab']='file';
    $params['group']=$group_id;
    
    site_project_header($params);

    if (user_ismember($group_id,"R2")) {
        $pm = ProjectManager::instance();
        $p = $pm->getProject($group_id);
        
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
	
    $frspf = new FRSPackageFactory();
	$arr_id = array($frspf->STATUS_ACTIVE,$frspf->STATUS_HIDDEN);
    $arr_status = array("STATUS_ACTIVE","STATUS_HIDDEN");
    
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
		$FRS_PROCESSOR_RES=db_query("SELECT * FROM frs_processor WHERE group_id=100 OR group_id=".db_ei($group_id)." ORDER BY rank");
	}
	return html_build_select_box ($FRS_PROCESSOR_RES,$name,$checked_val,true,$Language->getText('file_file_utils','must_choose_one'),true, '', false, '', false, '', CODENDI_PURIFIER_CONVERT_HTML);
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
        $hp =& Codendi_HTMLPurifier::instance();
        $res = $frsrf->getFRSReleasesInfoListFromDb($group_id);
        $p = array();
        foreach($res as $release){
            $p[$release['package_name']][$release['release_id']] = $release['release_name'];
		}

		$select = '<select name="'. $name .'">';
        foreach($p as $package_name => $releases) {
            $select .= '<optgroup label="'. $package_name .'">';
            foreach($releases as $id => $name) {
                $select .= '<option value="'. $id .'" '. ($id == $checked_val ? 'selected="selected"' : '') .'>'. $hp->purify($name) .'</option>';
            }
            $select .= '</optgroup>';
        }
        $select .= '</select>';
        return $select;
	}
}

function file_utils_show_processors ($result) {
    global $group_id,$Language;
    $hp =& Codendi_HTMLPurifier::instance();
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
        echo '<td>'.$hp->purify($proc_name).'</td>';
    } else {
	    echo '<td><A HREF="/file/admin/editproc.php?group_id='.$group_id.'&proc_id='.$proc_id.'" title="'.$hp->purify($proc_id.' - '.$proc_name).'">'.$hp->purify($proc_name).'</td>';
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
		   db_es($pname), db_ei($group_id), db_ei($prank));
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
		   db_es($pname), db_ei($prank), db_ei($pid), db_ei($group_id));
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
		   db_ei($group_id), db_ei($pid));
    $result = db_query($sql);

    if ($result) {
        $GLOBALS['Response']->addFeedback('info', $Language->getText('file_file_utils','delete_proc_success'));
    } else {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('file_file_utils','delete_proc_fail'));
    }

}

function frs_display_package_form(&$package, $title, $url, $siblings) {
	$hp =& Codendi_HTMLPurifier::instance();
    $group_id = $package->getGroupId();
    file_utils_admin_header(array('title'=>$GLOBALS['Language']->getText('file_admin_editpackages','edit_package'), 'help' => 'FileReleaseDelivery.html'));
    echo '<h3>'. $hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML) .'</h3>
    <P>
    <form action="'. $url .'" method="post">
    <table>
    <tr><th>'.$GLOBALS['Language']->getText('file_admin_editpackages','p_name').':</th>  <td><input type="text" name="package[name]" CLASS="textfield_small" value="'. $hp->purify(util_unconvert_htmlspecialchars($package->getName()), CODENDI_PURIFIER_CONVERT_HTML) .'">';
    //{{{ Rank
    $nb_siblings = count($siblings);
    if ($nb_siblings && ($nb_siblings > 1 || $siblings[0] != $package->getPackageId())) {
        echo '</td></tr>';
        echo '<tr><th>'.$GLOBALS['Language']->getText('file_admin_editpackages','rank_on_screen').':</th><td>';
        echo $GLOBALS['HTML']->selectRank($package->getPackageId(), $package->getRank(), $siblings, array('name' => 'package[rank]'));
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
                        <OPTION VALUE="0"'.(($approve_license == '0') ? ' SELECTED':'').'>'.$GLOBALS['Language']->getText('global','no').'</OPTION></SELECT></td></tr>';
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
    $hp =& Codendi_HTMLPurifier::instance();
    if (is_array($release)) {
        if (isset($release['date'])) {
            $release_date = $release['date'];
        }
        $release = new FRSRelease($release);
    }
    if ($is_update) {
        $files = $release->getFiles();
        if (count($files) > 0 ) {
            for ($i = 0; $i < count($files); $i++) {
                if (!$frsff->compareMd5Checksums($files[$i]->getComputedMd5(), $files[$i]->getReferenceMd5())) {
                    $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('file_admin_editreleases',  'md5_fail', array(basename($files[$i]->getFileName()), $files[$i]->getComputedMd5())));
                }
            }
        }
    }

    file_utils_admin_header(array (
        'title' => $GLOBALS['Language']->getText('file_admin_editreleases',
        'release_new_file_version'
    ), 'help' => 'FileReleaseDelivery.html'));
    echo '<H3>'.$hp->purify($title, CODENDI_PURIFIER_CONVERT_HTML).'</H3>';
    $sql = "SELECT * FROM frs_processor WHERE (group_id = 100 OR group_id = ".db_ei($group_id).") ORDER BY rank";
    $result = db_query($sql);
    $processor_id = util_result_column_to_array($result, 0);
    $processor_name = util_result_column_to_array($result, 1);
    foreach ($processor_name as $key => $value) {
        $processor_name[$key] = $hp->purify($value, CODENDI_PURIFIER_JS_QUOTE);
    }
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
        $pm  = PermissionsManager::instance();
        $dar = $pm->getAuthorizedUgroups($release->getReleaseID(), FRSRelease::PERM_READ);
        $ugroups_name = array();
        foreach ($dar as $row) {
            $ugroups_name[] = util_translate_name_ugroup($row['name']);
        }
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
            echo '<input type="hidden" id="release_id" name="release[release_id]" value="'. $release->getReleaseId() .'" />';
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
            echo '>' . $hp->purify(util_unconvert_htmlspecialchars($res[$i]->getName()), CODENDI_PURIFIER_CONVERT_HTML) . '</OPTION>';
        }
        echo '</SELECT>';
    }
    ?>
                </TD><td></td>
                <TD>
                    <B><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','release_name'); ?>: <span class="highlight"><strong>*</strong></span></B>
                </TD>
                <TD>
                    <INPUT TYPE="TEXT" id="release_name" name="release[name]" onBlur="update_news()" value="<?php echo $hp->purify($release->getName()); ?>">
                </TD>
            </TR>
            <TR>
                <TD>
                    <B><?php echo $GLOBALS['Language']->getText('file_admin_editreleases','release_date'); ?>:</B>
                </TD>
                <TD>
                <?php echo $GLOBALS['HTML']->getDatePicker('release_date', 'release[date]', isset($release_date) ? $hp->purify($release_date) : format_date('Y-m-d',$release->getReleaseDate())); ?>
                </TD>
                <td></td>
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
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'md5sum');
    $titles[] = $GLOBALS['Language']->getText('file_admin_editreleases', 'user');
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
            $user_id = $files[$i]->getUserID();
            $userName =(isset($user_id)) ? UserManager::instance()->getUserById($files[$i]->getUserID())->getRealName() : "";   
            echo '<TR>';
            echo '<TD><INPUT TYPE="CHECKBOX" NAME="release_files_to_delete[]" VALUE="' . $files[$i]->getFileID() . '"</TD>';
            echo '<TD>' . $hp->purify($fname, CODENDI_PURIFIER_CONVERT_HTML) . '<INPUT TYPE="HIDDEN" NAME="release_files[]" VALUE="' . $files[$i]->getFileID() . '"></TD>';
            echo '<TD>' . frs_show_processor_popup($group_id,$name = 'release_file_processor[]', $files[$i]->getProcessorID()) . '</TD>';
            echo '<TD>' . frs_show_filetype_popup($name = 'release_file_type[]', $files[$i]->getTypeID()) . '</TD>';
            //In case of difference between the inserted md5 and the computed one
            //we dispaly an editable text field to let the user insert the right value
            //to avoid the error message next time
            $value = 'value = "'.$files[$i]->getReferenceMd5().'"';
            if ($frsff->compareMd5Checksums($files[$i]->getComputedMd5(), $files[$i]->getReferenceMd5())) {
                $value = 'value = "'.$files[$i]->getComputedMd5().'" readonly="true"';
            }
            echo '<TD><INPUT TYPE="TEXT" NAME="release_reference_md5[]" '.$value.' SIZE="36" ></TD>';
            echo '<TD><INPUT TYPE="TEXT" NAME="user" value = "'.$userName.'" readonly="true"></TD>';
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
        echo '<option value="' . $file . '">' . $hp->purify($file, CODENDI_PURIFIER_CONVERT_HTML) . '</option>';
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
                            <td>
                                <input name="reference_md5" value="" size="36" type="TEXT">
                            </td>
                        </tr>
                    </tbody>
                </table>
                <?php
    
		echo '<span class="small" style="color:#666"><i>'.$GLOBALS['Language']->getText('file_admin_editreleases','upload_file_msg',formatByteToMb($GLOBALS['sys_max_size_upload'])).'</i> </span>';
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
                    <TEXTAREA NAME="release[release_notes]" rows="7" cols="70"><?php echo $hp->purify($release->getNotes(), CODENDI_PURIFIER_CONVERT_HTML);?></TEXTAREA>
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
                    <TEXTAREA ID="text_area_change_log" NAME="release[change_log]" ROWS="7" COLS="70"><?php echo $hp->purify($release->getChanges(), CODENDI_PURIFIER_CONVERT_HTML);?></TEXTAREA>
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
                    <INPUT TYPE="SUBMIT" ID="create_release"  VALUE="<?php echo $is_update ? $GLOBALS['Language']->getText('file_admin_editreleases', 'edit_release') : $GLOBALS['Language']->getText('file_admin_qrs', 'release_file'); ?>">
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
    $pm = ProjectManager::instance();
    //get and filter all inputs from $request
    $release = array();
    $res = $request->get('release');
    $vName = new Valid_String();
    $vPackage_id = new Valid_UInt();
    $vStatus_id =  new Valid_UInt();
    if ($vName->validate($res['name']) &&
        $vPackage_id->validate($res['package_id']) && 
        $vStatus_id->validate($res['status_id'])) {
        $release['status_id'] = $res['status_id'];
        $release['name'] = $res['name'];
        $release['package_id'] = $res['package_id'];
    } else {
        $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('file_admin_editreleases', 'rel_update_failed'));
        $GLOBALS['Response']->redirect('/file/showfiles.php?group_id='.$group_id);        
    }
    
    $um   = UserManager::instance();
    $user = $um->getCurrentUser();

    $vDate = new Valid_String();
    if ($vDate->validate($res['date'])) {
        $release['date'] = $res['date'];     	
    } else {
        $release['date'] = "";
    }
    
    $vRelease_notes = new Valid_Text();
    if ($vRelease_notes->validate($res['release_notes'])) {
        $release['release_notes'] = $res['release_notes'];
    } else {
        $release['release_notes'] = "";
    }
  
    $vChange_log = new Valid_Text();
    if ($vChange_log->validate($res['change_log'])) {
        $release['change_log'] = $res['change_log'];     	
    } else {
        $release['change_log'] = "";
    }
     
    if($request->valid(new Valid_String('js'))) {
        $js = $request->get('js');
    } else {
        $js = "";
    }
    
    if($request->validArray(new Valid_String('ftp_file'))) {
        $ftp_file = $request->get('ftp_file');
    } else {
        $ftp_file = array();
    }
    
    if($request->validArray(new Valid_UInt('file_processor'))) {
        $file_processor = $request->get('file_processor');
    } else {
        $file_processor = array();
    }
    
    if($request->validArray(new Valid_UInt('file_type'))) {
        $file_type = $request->get('file_type');
    } else {
        $file_type = array();
    }

    if($request->validArray(new Valid_String('reference_md5'))) {
        $reference_md5 = $request->get('reference_md5');
    } else {
        $reference_md5 = array();
    }
    
    if($request->validArray(new Valid_UInt('ftp_file_processor'))) {
        $ftp_file_processor = $request->get('ftp_file_processor');
    } else {
        $ftp_file_processor = array();
    }
    
    if($request->validArray(new Valid_UInt('ftp_file_type'))) {
        $ftp_file_type = $request->get('ftp_file_type');
    } else {
        $ftp_file_type = array();
    }

    if($request->validArray(new Valid_String('ftp_reference_md5'))) {
        $ftp_reference_md5 = $request->get('ftp_reference_md5');
    } else {
        $ftp_reference_md5 = array();
    }

    if($request->valid(new Valid_String('release_news_subject'))) {
        $release_news_subject = $request->get('release_news_subject');
    } else {
        $release_news_subject = "";
    }
    
    if($request->valid(new Valid_Text('release_news_details'))) {
        $release_news_details = $request->get('release_news_details');
    } else {
        $release_news_details = "";
    }
    
    if($request->valid(new Valid_WhiteList('private_news',array(0,1)))) {
        $private_news = $request->get('private_news');
    } else {
        $private_news = 0;
    }
    
    if($request->validArray(new Valid_UInt('ugroups'))) {
        $ugroups = $request->get('ugroups');
    } else {
        $GLOBALS['Response']->addFeedback('error',$GLOBALS['Language']->getText('file_admin_editreleases', 'rel_update_failed'));
        $GLOBALS['Response']->redirect('/file/showfiles.php?group_id='.$group_id);
    }
    
    if($request->valid(new Valid_WhiteList('release_submit_news',array(0,1)))) {
        $release_submit_news = (int) $request->get('release_submit_news');
    } else {
        $release_submit_news = 0;
    }
    
    if($request->valid(new Valid_WhiteList('notification',array(0,1)))) {
        $notification = $request->get('notification');
    } else {
        $notification = 0;
    }
    
    if ($is_update) {
        if($request->validArray(new Valid_UInt('release_files_to_delete'))) {
            $release_files_to_delete = $request->get('release_files_to_delete');
        } else {
            $release_files_to_delete = array();
        }

        if($request->validArray(new Valid_UInt('release_files'))) { 
            $release_files = $request->get('release_files');
        } else {
            $release_files = array();
        }
        
        if($request->validArray(new Valid_UInt('release_file_processor'))) {
            $release_file_processor = $request->get('release_file_processor');
        } else {
            $release_file_processor = array();
        }
        
        if($request->validArray(new Valid_UInt('release_file_type'))) {
            $release_file_type = $request->get('release_file_type');
        } else {
            $release_file_type = array();
        }

        if($request->validArray(new Valid_String('release_reference_md5'))) {
            $release_reference_md5 = $request->get('release_reference_md5');
        } else {
            $release_reference_md5 = array();
        }
        if($request->validArray(new Valid_UInt('new_release_id'))) {
            $new_release_id = $request->get('new_release_id');
        } else {
            $new_release_id = array();
        }
        
        if($request->validArray(new Valid_String('release_time'))) {
            $release_time = $request->get('release_time');
        } else {
            $release_time = array();
        }
        
        if($request->validArray(new Valid_String('reference_md5'))) {
            $reference_md5 = $request->get('reference_md5');
        } else {
            $reference_md5 = array();
        }
        
        if($request->valid(new Valid_UInt('id'))) {
            $release['release_id'] = $request->get('id');
        } else {
            exit;
        }
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
            $code = addslashes(fread(fopen($_FILES['uploaded_change_log']['tmp_name'], 'r'), file_utils_get_size($_FILES['uploaded_change_log']['tmp_name'])));
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
            $code = addslashes(fread(fopen($_FILES['uploaded_release_notes']['tmp_name'], 'r'), file_utils_get_size($_FILES['uploaded_release_notes']['tmp_name'])));
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
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'rel_updated', $release['name']));
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
            // extract cross references
            $reference_manager =& ReferenceManager::instance();
            $reference_manager->extractCrossRef($release['release_notes'],$release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);
            $reference_manager->extractCrossRef($release['change_log'],$release_id, ReferenceManager::REFERENCE_NATURE_RELEASE, $group_id);
            
            //set the release permissions
            list ($return_code, $feedbacks) = permission_process_selection_form($group_id, 'RELEASE_READ', $release_id, $ugroups);
            if (!$return_code) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editpackages', 'perm_update_err'));
                $GLOBALS['Response']->addFeedback('error', $feedbacks);
            }
            
            //submit news if requested
            if ($release_id && user_ismember($group_id, 'A') && $release_submit_news) {
            	news_submit($group_id, $release_news_subject, $release_news_details, $private_news, 3);
            }

            // Send notification
            if ($notification) {
                $rel = $frsrf->getFRSReleaseFromDb($release_id);
                $count = $frsrf->emailNotification($rel);
                if ($count === false) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'mail_failed', array (
                            $GLOBALS['sys_email_admin']
                        )));
                } else {
                    if ($count > 0) {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'email_sent', $count));
                    }
                }
            }

            $group = $pm->getProject($group_id);
            $group_unix_name = $group->getUnixName(false);
            $project_files_dir = $GLOBALS['ftp_frs_dir_prefix'] . '/' . $group_unix_name;

            if ($is_update) {
                $files =& $rel->getFiles();
                
                //remove files
                foreach ($release_files_to_delete as $rel_file) {
                    $res =& $frsff->getFRSFileFromDb($rel_file);
                    $fname = $res->getFileName();
                    $res = $frsff->delete_file($group_id, $rel_file);
                    if ($res == 0) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'f_not_yours', basename($fname)));
                    } else {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'file_deleted', basename($fname)));
                    }
                }

                //update files
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
                                    if ($release_reference_md5[$index] && $release_reference_md5[$index] != '') {
                                        $array['reference_md5'] = $release_reference_md5[$index];
                                    }
                                    $res = $frsff->update($array);
                                    if($res) {
                                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'file_updated', $fname));
                                    }
                                }
                            }
                    }
                    $index ++;
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
                // TODO : fix warnings due to array instead of string for "file_processor", "file_type" & "reference_md5"
                if ($ftp_file[0] != -1) {
                    $ftp_files_processor_type_list[] = array (
                        'name' => $ftp_file[0],
                        'processor' => $file_processor,
                        'type' => $file_type,
                        'reference_md5' => $reference_md5
                    );

                } else
                    if (trim($_FILES['file']['name'][0]) != '') {
                        $http_files_processor_type_list[] = array (
                            'error' => $_FILES['file']['error'][0],
                            'name' => $_FILES['file']['name'][0],
                            'tmp_name' => $_FILES['file']['tmp_name'][0],
                            'processor' => $file_processor,
                            'type' => $file_type,
                            'reference_md5' => $reference_md5
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
                            'type' => $file_type[$i],
                            'reference_md5' => $reference_md5[$i]
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
                            'type' => $ftp_file_type[$index],
                            'reference_md5' => $ftp_reference_md5[$index]
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
                        $filename = $file['name'];
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
                                $newFile = new FRSFile();
                                $newFile->setRelease($res1);
                                $newFile->setFileName($filename);
                                $newFile->setProcessorID($file['processor']);
                                $newFile->setTypeID($file['type']);
                                $newFile->setReferenceMd5($file['reference_md5']);
                                $newFile->setUserId($user->getId());
                                try {
                                    $frsff->createFile($newFile);
                                    $addingFiles = true;
                                }
                                catch (Exception $e) {
                                    $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                                }
                            }
                        }else{
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('file_admin_editreleases', 'not_add_file') . ": " . basename($filename));
                        }
                    }

                    //iterate and add the ftp files to the frs_file table
                    foreach ($ftp_files_processor_type_list as $file) {
                        $filename = $file['name'];

                        $newFile = new FRSFile();
                        $newFile->setRelease($res1);
                        $newFile->setFileName($filename);
                        $newFile->setProcessorID($file['processor']);
                        $newFile->setTypeID($file['type']);
                        $newFile->setReferenceMd5($file['reference_md5']);
                        $newFile->setUserId($user->getId());

                        try {
                            $frsff->createFile($newFile, ~FRSFileFactory::COMPUTE_MD5);
                            $addingFiles = true;
                            $em = EventManager::instance();
                            $em->processEvent(Event::COMPUTE_MD5SUM, array('fileId' => $newFile->getFileID()));
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('file_admin_editreleases', 'offline_md5', $filename));
                        }
                        catch (Exception $e) {
                            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
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