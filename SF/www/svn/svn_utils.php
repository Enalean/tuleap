<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// $Id$
//
//      Originally written by Laurent Julliard 2004 CodeX Team, Xerox
//


function svn_header($params) {
	global $group_id,$DOCUMENT_ROOT;

	$params['toptab']='svn';
	$params['group']=$group_id;

	//only projects can use cvs, and only if they have it turned on
	$project=project_get_object($group_id);

	if (!$project->isProject()) {
		exit_error('Error','Only Projects Can Use The Subversion Manager');
	}
	if (!$project->usesService('svn')) {
	    exit_error('Error','This Project Has Turned Subversion Off');
	}
	echo site_project_header($params);

	echo '<P><B><A HREF="/svn/?func=info&group_id='.$group_id.'">Subversion Info</A>';

	if ($project->isPublic() || user_isloggedin()) {
	  echo ' | <A HREF="/cgi-bin/viewcvs.cgi/?roottype=svn&root='.$project->getUnixName().'">Browse SVN Tree</A>';
	}
	if (user_isloggedin()) {
	  echo ' | <A HREF="/svn/?func=browse&group_id='.$group_id.'&set=my">My SVN Commits</A>';
	}
	echo ' | <A HREF="/svn/?func=browse&group_id='.$group_id.'">SVN Query</A>';
	echo ' | <A HREF="/svn/admin/?group_id='.$group_id.'">SVN Admin</A>';	
	if (!$params['help']) { $params['help'] = "VersionControlWithSubversion.html";}
	echo ' | '.help_button($params['help'],false,'Help');

	echo '</B>';
	echo ' <hr width="300" size="1" align="left" noshade>';
}

function svn_header_admin($params) {
    global $group_id,$DOCUMENT_ROOT;
    
    //required params for site_project_header();
    $params['group']=$group_id;
    $params['toptab']='svn';
    
    $project=project_get_object($group_id);
    
    //only projects can use the svn manager, and only if they have it turned on
    if (!$project->isProject()) {
	exit_error('Error','Only Projects Can Use The Commits Browser');
    }
    if (!$project->usesService('svn')) {
	exit_error('Error','This Project Has Turned Off The Subversion Browser');
    }
    echo site_project_header($params);
    echo '<P><B><A HREF="/svn/admin/?group_id='.$group_id.'">Admin</A></B>';
    echo ' | <B><A HREF="/svn/admin/?func=general_settings&group_id='.$group_id.'">General Settings</A></B>';
    echo ' | <b><A HREF="/svn/admin/?func=access_control&group_id='.$group_id.'">Access Control</A></b>';
    echo ' | <B><A HREF="/svn/admin/?func=notification&group_id='.$group_id.'">Email Notification</A></B>';    

    if (!$params['help']) { $params['help'] = "SubversionAdministrationInterface.html";}
    echo ' | <b>'.help_button($params['help'],false,'Help').'</b>';
    echo ' <hr width="300" size="1" align="left" noshade>';
}


function svn_footer($params) {
    site_project_footer($params);
}


function svn_utils_technician_box($group_id,$name='_commiter',$checked='xzxz',$text_100='None') {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=svn_data_get_technicians($group_id);
		return html_build_select_box($result,$name,$checked,true,$text_100);
	}
}


function svn_utils_show_revision_list ($result,$offset,$total_rows,$set='any', $commiter='100', $path='', $chunksz=15, $morder='', $msort=0) {
	global $sys_datefmt,$group_id;
	/*
		Accepts a result set from the svn_commits table. Should include all columns from
		the table, and it should be joined to USER to get the user_name.
	*/
    $url = $PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&msort='.$msort;

    if ($set == 'custom')
     $url .= $pref_stg;

    $url_nomorder = $url;
    $url .= "&morder=$morder";

	if ($morder != '') {
	  $orderstr = ' sorted by '.svn_utils_criteria_list_to_text($morder, $url_nomorder);
	} else {
	  $orderstr = '';
	}
	echo '<A name="results"></A>';  
	echo '<h3>'.$total_rows.' matching commit'.($total_rows>1 ? 's':'').$orderstr.'</h3>';

    $nav_bar ='<table width= "100%"><tr>';
    $nav_bar .= '<td width="20%" align ="left">';


    echo '<P>Click a column heading to sort results (up or down), '.
      'or <A HREF="'.$url.'&order=#results"><b>Reset sort</b></a>. ';

    if ($msort) { 
	$url_alternate_sort = str_replace('msort=1','msort=0',$url).
	    '&order=#results';
	$text = 'Deactivate';
    } else {    
	$url_alternate_sort = str_replace('msort=0','msort=1',$url).
	    '&order=#results';
	$text = 'Activate';
    }

    echo 'You can also <a href="'.$url_alternate_sort.'"><b> '.$text.
      ' multicolumn sort</b></a>'."\n";

    // If all bugs on screen so no prev/begin pointer at all
    if ($total_rows > $chunksz) {
	if ($offset > 0) {
	    $nav_bar .=
	    '<A HREF="'.$url.'&offset=0#results"><B><< Begin</B></A>'.
	    '&nbsp;&nbsp;&nbsp;&nbsp;'.
	    '<A HREF="'.$url.'&offset='.($offset-$chunksz).
	    '#results"><B>< Previous '.$chunksz.'</B></A></td>';
	} else {
	    $nav_bar .=
		'<span class="disable">&lt;&lt; Begin&nbsp;&nbsp;&lt; Previous '.$chunksz.'</span>';
	}
    }

    $nav_bar .= '</td>';
    
    $offset_last = min($offset+$chunksz-1, $total_rows-1);
    $nav_bar .= '<td width= "60% " align = "center" class="small">Items '.($offset+1).' - '.
	($offset_last+1)."</td>\n";

    $nav_bar .= '<td width="20%" align ="right">';

    // If all bugs on screen, no next/end pointer at all
    if ($total_rows > $chunksz) {
	if ( ($offset+$chunksz) < $total_rows ) {

	    $offset_end = ($total_rows - ($total_rows % $chunksz));
	    if ($offset_end == $total_rows) { $offset_end -= $chunksz; }

	    $nav_bar .= 
		'<A HREF="'.$url.'&offset='.($offset+$chunksz).
		'#results" class="small"><B>Next '.$chunksz.' &gt;</B></A>'.
		'&nbsp;&nbsp;&nbsp;&nbsp;'.
		'<A HREF="'.$url.'&offset='.($offset_end).
		'#results" class="small"><B>End &gt;&gt;</B></A></td>';
	} else {
	    $nav_bar .= 
		'<span class="disable">Next '.$chunksz.
		' &gt;&nbsp;&nbsp;End &gt;&gt;</span>';
	}
    }
    $nav_bar .= '</td>';
    $nav_bar .="</tr></table>\n";
 
    echo $nav_bar;


	$filter_str = '';
	if ($commiter != '100') {
	  $filter_str = "&commiter='$commiter'";
	}
	if ($path != '') {
	  $filter_str = $filter_str."&path='$path'";
	}
	

	$rows=db_numrows($result);
	$url .= "&order=";
	$title_arr=array();
	$title_arr[]='Revision';
	$title_arr[]='Description';
	$title_arr[]='Date';
	$title_arr[]='Commiter';

	$links_arr=array();
	$links_arr[]=$url.'revision#results';
	$links_arr[]=$url.'description#results';
	$links_arr[]=$url.'date#results';
	$links_arr[]=$url.'who#results';

	$url_nomorder = $url;
	$url .= "&morder=$morder";

	echo html_build_list_table_top ($title_arr,$links_arr);

	for ($i=0; $i < $rows; $i++) {

	    $id_str = db_result($result, $i, 'commit_id');
	    $rev = db_result($result, $i, 'revision');
	    $id_link = '&commit_id='.$id_str;
	    $id_sublink = '';
	    
	    echo '
			<TR class="'. util_get_alt_row_color($i) .'">'.
			'<TD class="small"><b><A HREF="'.$PHP_SELF.'?func=detailrevision&group_id='.$group_id.$id_link.$filter_string.'">'.$rev.
		  '</b></A></TD>'.
			'<TD class="small">'.util_make_links(join('<br>', split("\n",db_result($result, $i, 'description'))),$group_id).$id_sublink.'</TD>'.
			'<TD class="small">'.format_date($sys_datefmt, db_result($result, $i, 'date')).'</TD>'.
			'<TD class="small">'.util_user_link(db_result($result,$i,'who')).'</TD></TR>';

	}

	/*
		Show extra rows for <-- Prev / Next -->
	*/
	echo '</TD></TR></TABLE>';
	echo $nav_bar;
}

function svn_utils_make_viewlink($group_name, $filename, $text, $view_params) {
    return '<A href="/cgi-bin/viewcvs.cgi/'.$filename.'?root='.$group_name.'&roottype=svn'.$view_params.'"><B>'.$text.'</B></A>';
}


// Check if a sort criteria is already in the list of comma
// separated criterias. If so invert the sort order, if not then
// simply add it
function svn_utils_add_sort_criteria($criteria_list, $order, $msort)
{
    //echo "<br>DBG \$criteria_list=$criteria_list,\$order=$order";
    if ($criteria_list) {
	$arr = explode(',',$criteria_list);
	$i = 0;
	while (list(,$attr) = each($arr)) {
	    preg_match("/\s*([^<>]*)([<>]*)/", $attr,$match);
	    list(,$mattr,$mdir) = $match;
	    //echo "<br><pre>DBG \$mattr=$mattr,\$mdir=$mdir</pre>";
	    if ($mattr == $order) {
		if ( ($mdir == '>') || (!isset($mdir)) ) {
		    $arr[$i] = $order.'<';
		} else {
		    $arr[$i] = $order.'>';
		}
		$found = true;
	    }
	    $i++;
	}
    }

    if (!$found) {
      if (!$msort) { unset($arr); }
      $arr[] = $order.'<';
    }
    
    //echo "<br>DBG \$arr[]=".join(',',$arr);

    return(join(',', $arr));	

}

// Transform criteria list to SQL query (+ means ascending
// - is descending)
function svn_utils_criteria_list_to_query($criteria_list)
{

    $criteria_list = str_replace('>',' ASC',$criteria_list);
    $criteria_list = str_replace('<',' DESC',$criteria_list);
    return $criteria_list;
}

// Transform criteria list to readable text statement
// $url must not contain the morder parameter
function svn_utils_criteria_list_to_text($criteria_list, $url){

    if ($criteria_list) {

	$arr = explode(',',$criteria_list);

	while (list(,$crit) = each($arr)) {

	    $morder .= ($morder ? ",".$crit : $crit);
	    $attr = str_replace('>','',$crit);
	    $attr = str_replace('<','',$attr);

	    $arr_text[] = '<a href="'.$url.'&morder='.$morder.'#results">'.
		svn_utils_field_get_label($attr).'</a><img src="'.util_get_dir_image_theme().
		((substr($crit, -1) == '<') ? 'dn' : 'up').
		'_arrow.png" border="0">';
	}
    }

    return join(' > ',$arr_text);
}

function svn_utils_field_get_label($sortField) {
  if ($sortField == "id") {
    return "Revision";
  }
  else if ($sortField == "date") {
    return "Date";
  }
  else if ($sortField == "who") {
    return "Commiter";
  }
  return $sortField;
  }


function svn_utils_show_revision_detail($result,$group_id,$group_name,$commit_id) {
    global $sys_datefmt;
    /*
      Accepts a result set from the svn_checkins table. Should include all columns from
      the table, and it should be joined to USER to get the user_name.
    */

    $rows=db_numrows($result);
    $url = "/svn/?func=detailrevision&commit_id=$commit_id&group_id=$group_id&order=";
    $list_log = '<pre>'.util_make_links(util_line_wrap(db_result($result, 0, 'description')), $group_id).'</pre>';
    $revision = db_result($result, 0, 'revision');
    $hdr = '[Revision #'.$revision.'] - ';

    echo '<h2>'.$hdr.format_date($sys_datefmt, db_result($result, 0, 'date')).'</h2></h2>';
    echo '<table WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2"><tr class="'. util_get_alt_row_color(0).'"><td>'.$list_log.'</td></tr></table>';
    echo '<h3> List of impacted files</h3>';
    $title_arr=array();
    $title_arr[]= 'File';
    $title_arr[]='Revision';
    $title_arr[]='Type';
    //$title_arr[]='AddedLines'; To be implemented
    //$title_arr[]='RemovedLines'; To be implemented

    $links_arr=array();
    $links_arr[]=$url.'filename';
    $links_arr[]=$url.'';
    $links_arr[]=$url.'type';

    echo html_build_list_table_top ($title_arr,$links_arr);

    for ($i=0; $i < $rows; $i++) {

	$type = db_result($result, $i, 'type');
	$dirname = db_result($result, $i, 'dir');
	$filename = db_result($result, $i, 'file');
	$fullpath = $dirname.$filename;

	if ($filename) {
	    // It' a file
	    $viewfile_url = svn_utils_make_viewlink($group_name, $fullpath, $fullpath,'');
	    $viewrev_url = svn_utils_make_viewlink($group_name, $fullpath, $revision, "&rev=$revision&view=markup");

	} else {
	    // It' a directory
	    $viewfile_url = svn_utils_make_viewlink($group_name, $fullpath, $fullpath,'');
	    $viewrev_url = svn_utils_make_viewlink($group_name, $fullpath, $revision, "&rev=$revision");
	}

	if ($type == 'Change') {	    

	    $viewtype_url = svn_utils_make_viewlink($group_name, $fullpath, $type,
					   "&r1=text&tr1=$revision&r2=text&tr2=".($revision-1)."&diff_format=h");

	} else if ($type == 'Add') {
	    $viewtype_url = $type;
	} else if ($type == 'Delete') {
	    $viewtype_url = $type;
	}

	echo '
	       <TR class="'. util_get_alt_row_color($i) .'">'.
	    '<TD class="small"><b>'.$viewfile_url.'</b></TD>'.
	    '<TD class="small" width="10%" align="center">'.$viewrev_url.'</TD>'.
	    '<TD class="small" width="10%" align="center">'.$viewtype_url.'</TD>';
	//'<TD class="small">'.$added.'</TD>'. // To be done
	//'<TD class="small">'.$removed.'</TD></TR>'; // To be done

    }

    echo '</TD></TR></TABLE>';
}

// Is there anything in the svn history table ?
function svn_utils_format_svn_history($group_id) {

    $res_svnfullhist = svn_data_get_svn_history($group_id);

    if (!$res_svnfullhist || db_numrows($res_svnfullhist) < 1) {
        print '<P>This project has no Subversion history.';
    } else {
	$svnhist = array();
	while ($row_svnfullhist = db_fetch_array($res_svnfullhist)) {
	    $svnhist[$row_svnfullhist['user_name']]['full'] = $row_svnfullhist['commits'];
	    $svnhist[$row_svnfullhist['user_name']]['last'] = 0;
	}

	// Now over the last 7 days
	$res_svnlasthist = svn_data_get_svn_history($group_id,7*24*3600);
	
	while ($row_svnlasthist = db_fetch_array($res_svnlasthist)) {
	    $svnhist[$row_svnlasthist['user_name']]['last'] = $row_svnlasthist['commits'];
	}
    

        // Format output 
        $output = '<P><b>Developer Commits (Last 7 days/Total)</b><BR>&nbsp;';
        reset($svnhist);
        while (list($user, ) = each($svnhist)) {
            $output .= '<BR>'.$user.' ('.$svnhist[$user]['last'].'/'
                .$svnhist[$user]['full'].')';
        }
    }
    return $output;
}

// read permission access file. The default settings part.
function svn_utils_read_svn_access_file_defaults($gname) {
    global $feedback;

    $filename = "/svnroot/$gname/.SVNAccessFile";

    $fd = @fopen("$filename", "r");
    $in_settings = false;
    $buffer = '';
    while (!feof($fd)) {
	$line = fgets($fd, 4096);
	if (strpos($line,'# BEGIN CODEX DEFAULT') !== false) { $in_settings = true; }
	if ($in_settings) { $buffer .= $line; }
	if (strpos($line,'# END CODEX DEFAULT') !== false) { $in_settings = false; break; }
    }
    fclose($fd);
    return $buffer;

}

// read permission access file. The project specific part.
function svn_utils_read_svn_access_file($gname) {

    global $feedback;

    $filename = "/svnroot/$gname/.SVNAccessFile";

    $fd = @fopen("$filename", "r");
    if (!$fd) {
	$feedback .= "Error: Can't open file $filename";
    }

    $in_settings = false;
    $buffer = '';
    while (!feof($fd)) {
	$line = fgets($fd, 4096);
	if (strpos($line,'# BEGIN CODEX DEFAULT') !== false) { $in_settings = true; }
	if (!$in_settings) { $buffer .= $line; }
	if (strpos($line,'# END CODEX DEFAULT') !== false) { $in_settings = false; }
    }
    fclose($fd);
    return $buffer;
}

function svn_utils_write_svn_access_file($gname, $contents) {

    global $feedback;

    $filename = "/svnroot/$gname/.SVNAccessFile";
    $fd = fopen("$filename", "w+");
    if ($fd) {
	if (fwrite($fd, $contents) === false) {
	    $feedback .= "Error: Can't write to file $filename";
	    $ret = false;
	} else {
	    $ret = true;
	}
    } else {
	$feedback .= "Error: Can't open to file $filename";
	$ret = false;
    }
    fclose($fd);
    return $ret;
}

function svn_utils_svn_repo_exists($gname) {
    return is_dir("/svnroot/$gname");
}

?>
