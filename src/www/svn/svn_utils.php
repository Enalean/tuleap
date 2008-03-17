<?php
//
// CodeX: Breaking Down the Barriers to Source Code Sharing inside Xerox
// Copyright (c) Xerox Corporation, CodeX / CodeX Team, 2004. All Rights Reserved
// http://codex.xerox.com
//
// 
//
//      Originally written by Laurent Julliard 2004 CodeX Team, Xerox
//

$GLOBALS['Language']->loadLanguageMsg('svn/svn');

function svn_header($params) {
        global $group_id, $Language, $there_are_specific_permissions;

	$params['toptab']='svn';
	$params['group']=$group_id;

	//only projects can use cvs, and only if they have it turned on
	$project=project_get_object($group_id);

	if ($project->isFoundry()) {
		exit_error($Language->getText('global','error'),$Language->getText('svn_utils','proj_err'));
	}
	if (!$project->usesService('svn')) {
	    exit_error($Language->getText('global','error'),$Language->getText('svn_utils','svn_off'));
	}
	echo site_project_header($params);

	echo '<P><B><A HREF="/svn/?func=info&group_id='.$group_id.'">'.$Language->getText('svn_utils','svn_info').'</A>';

	if ($project->isPublic() || user_isloggedin()) {
	  echo ' | <A HREF="/svn/viewvc.php/?roottype=svn&root='.$project->getUnixName(false).'">'.$Language->getText('svn_utils','browse_tree').'</A>';
	}
	
    if (user_isloggedin()) {
        echo ' | <A HREF="/svn/?func=browse&group_id='.$group_id.'&set=my">'.$Language->getText('svn_utils','my_ci').'</A>';
        echo ' | <A HREF="/svn/?func=browse&group_id='.$group_id.'">'.$Language->getText('svn_utils','svn_query').'</A>';
    }
    if (user_ismember($group_id, 'A')||user_ismember($group_id,'SVN_ADMIN') ) {
        echo ' | <A HREF="/svn/admin/?group_id='.$group_id.'">'.$Language->getText('svn_utils','svn_admin').'</A>';
    }
    if (!isset($params['help']) || !$params['help']) { $params['help'] = "VersionControlWithSubversion.html";}
	echo ' | '.help_button($params['help'],false,$Language->getText('global','help'));

	echo '</B>';
	echo ' <hr width="300" size="1" align="left" noshade>';
}

function svn_header_admin($params) {
    global $group_id,$Language;
    
    //required params for site_project_header();
    $params['group']=$group_id;
    $params['toptab']='svn';
    
    $project=project_get_object($group_id);
    
    //only projects can use the svn manager, and only if they have it turned on
    if ($project->isFoundry()) {
	exit_error($Language->getText('global','error'),$Language->getText('svn_utils','proj_err'));
    }
    if (!$project->usesService('svn')) {
	exit_error($Language->getText('global','error'),$Language->getText('svn_utils','browse_off'));
    }
    echo site_project_header($params);
    echo '<P><B><A HREF="/svn/admin/?group_id='.$group_id.'">'.$Language->getText('svn_utils','admin').'</A></B>';
    echo ' | <B><A HREF="/svn/admin/?func=general_settings&group_id='.$group_id.'">'.$Language->getText('svn_admin_index','gen_sett').'</A></B>';
    echo ' | <b><A HREF="/svn/admin/?func=access_control&group_id='.$group_id.'">'.$Language->getText('svn_admin_index','access').'</A></b>';
    echo ' | <B><A HREF="/svn/admin/?func=notification&group_id='.$group_id.'">'.$Language->getText('svn_utils','notif').'</A></B>';    

    if (!$params['help']) { $params['help'] = "SubversionAdministrationInterface.html";}
    echo ' | <b>'.help_button($params['help'],false,$Language->getText('global','help')).'</b>';
    echo ' <hr width="300" size="1" align="left" noshade>';
}


function svn_footer($params) {
    site_project_footer($params);
}


function svn_utils_technician_box($projectname,$name='_commiter',$checked='xzxz',$text_100='None') {
  global $Language;
	if (!$projectname) {
		return $Language->getText('svn_utils','g_id_err');
	} else {
		$result=svn_data_get_technicians($projectname);
                if (!in_array($checked,util_result_column_to_array($result))) {
                    // Selected 'my commits' but never commited
                    $checked='xzxz';
                }
		return html_build_select_box($result,$name,$checked,true,$text_100);
	}
}


function svn_utils_show_revision_list ($result,$offset,$total_rows,$set='any', $commiter='100', $path='', $chunksz=15, $morder='', $msort=0) {
	global $sys_datefmt,$group_id,$Language;
	/*
		Accepts a result set from the svn_commits table. Should include all columns from
		the table, and it should be joined to USER to get the user_name.
	*/
    $url = $_SERVER['PHP_SELF'].'?func=browse&group_id='.$group_id.'&set='.$set.'&msort='.$msort;

    if ($set == 'custom')
     $url .= $pref_stg;

    $url_nomorder = $url;
    $url .= "&morder=$morder";

	if ($morder != '') {
	  $orderstr = $Language->getText('svn_utils','sorted_by').' '.svn_utils_criteria_list_to_text($morder, $url_nomorder);
	} else {
	  $orderstr = '';
	}
	echo '<A name="results"></A>';  
	echo '<h3>'.$Language->getText('svn_utils','match_ci',$total_rows).' '.$orderstr.'</h3>';

    $nav_bar ='<table width= "100%"><tr>';
    $nav_bar .= '<td width="20%" align ="left">';


    echo '<P>'.$Language->getText('svn_utils','sort',$url.'&order=#results').' ';

    if ($msort) { 
	$url_alternate_sort = str_replace('msort=1','msort=0',$url).
	    '&order=#results';
	$text = $Language->getText('svn_utils','deacti');
    } else {    
	$url_alternate_sort = str_replace('msort=0','msort=1',$url).
	    '&order=#results';
	$text = $Language->getText('svn_utils','acti');
    }

    echo $Language->getText('svn_utils','multi_sort',array($url_alternate_sort,$text))."\n";

    // If all bugs on screen so no prev/begin pointer at all
    if ($total_rows > $chunksz) {
	if ($offset > 0) {
	    $nav_bar .=
	    '<A HREF="'.$url.'&offset=0#results"><B><< '.$Language->getText('global','begin').'</B></A>'.
	    '&nbsp;&nbsp;&nbsp;&nbsp;'.
	    '<A HREF="'.$url.'&offset='.($offset-$chunksz).
	    '#results"><B>< '.$Language->getText('global','prev').' '.$chunksz.'</B></A></td>';
	} else {
	    $nav_bar .=
		'<span class="disable">&lt;&lt; '.$Language->getText('global','begin').'&nbsp;&nbsp;&lt; '.$Language->getText('global','prev').' '.$chunksz.'</span>';
	}
    }

    $nav_bar .= '</td>';
    
    $offset_last = min($offset+$chunksz-1, $total_rows-1);
    $nav_bar .= '<td width= "60% " align = "center" class="small">'.$Language->getText('svn_utils','items',array(($offset+1),($offset_last+1)))."</td>\n";

    $nav_bar .= '<td width="20%" align ="right">';

    // If all bugs on screen, no next/end pointer at all
    if ($total_rows > $chunksz) {
	if ( ($offset+$chunksz) < $total_rows ) {

	    $offset_end = ($total_rows - ($total_rows % $chunksz));
	    if ($offset_end == $total_rows) { $offset_end -= $chunksz; }

	    $nav_bar .= 
		'<A HREF="'.$url.'&offset='.($offset+$chunksz).
		'#results" class="small"><B>'.$Language->getText('global','next').' '.$chunksz.' &gt;</B></A>'.
		'&nbsp;&nbsp;&nbsp;&nbsp;'.
		'<A HREF="'.$url.'&offset='.($offset_end).
		'#results" class="small"><B>'.$Language->getText('global','end').' &gt;&gt;</B></A></td>';
	} else {
	    $nav_bar .= 
		'<span class="disable">'.$Language->getText('global','next').' '.$chunksz.
		' &gt;&nbsp;&nbsp;'.$Language->getText('global','end').' &gt;&gt;</span>';
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
	$title_arr[]=$Language->getText('svn_browse_revision','rev');
	$title_arr[]=$Language->getText('svn_utils','desc');
	$title_arr[]=$Language->getText('svn_utils','date');
	$title_arr[]=$Language->getText('svn_browse_revision','commiter');

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
			'<TD class="small"><b><A HREF="'.$_SERVER['PHP_SELF'].'?func=detailrevision&group_id='.$group_id.$id_link.$filter_str.'">'.$rev.
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
    return '<A href="/svn/viewvc.php/'.$filename.'?root='.$group_name.'&roottype=svn'.$view_params.'"><B>'.$text.'</B></A>';
}


// Check if a sort criteria is already in the list of comma
// separated criterias. If so invert the sort order, if not then
// simply add it
function svn_utils_add_sort_criteria($criteria_list, $order, $msort)
{
    //echo "<br>DBG \$criteria_list=$criteria_list,\$order=$order";
    $found = false;
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
    $morder = '';
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
  global $Language;
  if ($sortField == "id") {
    return $Language->getText('svn_browse_revision','rev');
  }
  else if ($sortField == "date") {
    return $Language->getText('svn_utils','date');
  }
  else if ($sortField == "who") {
    return $Language->getText('svn_browse_revision','commiter');
  }
  return $sortField;
  }


function svn_utils_show_revision_detail($result,$group_id,$group_name,$commit_id) {
    global $sys_datefmt,$Language;
    /*
      Accepts a result set from the svn_checkins table. Should include all columns from
      the table, and it should be joined to USER to get the user_name.
    */

    $rows=db_numrows($result);
    $url = "/svn/?func=detailrevision&commit_id=$commit_id&group_id=$group_id&order=";
    $list_log = '<pre>'.util_make_links(util_line_wrap(db_result($result, 0, 'description')), $group_id).'</pre>';
    $revision = db_result($result, 0, 'revision');
    $hdr = '['.$Language->getText('svn_browse_revision','rev').' #'.$revision.'] - ';

    echo '<h2>'.$hdr.format_date($sys_datefmt, db_result($result, 0, 'date')).'</h2></h2>';
    echo '<table WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2"><tr class="'. util_get_alt_row_color(0).'"><td>'.$list_log.'</td></tr></table>';
    echo '<h3> '.$Language->getText('svn_utils','impacted_files').'</h3>';
    $title_arr=array();
    $title_arr[]= $Language->getText('svn_utils','file');
    $title_arr[]=$Language->getText('svn_browse_revision','rev');
    $title_arr[]=$Language->getText('svn_utils','type');
    //$title_arr[]='AddedLines'; To be implemented
    //$title_arr[]='RemovedLines'; To be implemented

    $links_arr=array();
    $links_arr[]=$url.'filename';
    $links_arr[]=$url.'';
    $links_arr[]=$url.'type';

    $project = group_get_object($group_id);
    $root = $project->getUnixName();

    echo html_build_list_table_top ($title_arr,$links_arr);

    for ($i=0; $i < $rows; $i++) {

	$type = db_result($result, $i, 'type');
	$dirname = db_result($result, $i, 'dir');
	$filename = db_result($result, $i, 'file');
	$fullpath = $dirname.$filename;

	if ($filename) {
	    // It' a file
	    $viewfile_url = svn_utils_make_viewlink($group_name, $fullpath, $fullpath,"&pathrev=$revision&view=log");
	    $viewrev_url = svn_utils_make_viewlink($group_name, $fullpath, $revision, "&revision=$revision&pathrev=$revision&view=markup");

	} else {
	    // It' a directory
	    $viewfile_url = svn_utils_make_viewlink($group_name, $fullpath, $fullpath,"&pathrev=$revision");
	    $viewrev_url = svn_utils_make_viewlink($group_name, $fullpath, $revision, "&pathrev=$revision&view=log");
	}

	if ($type == 'Change') {	    

	    $viewtype_url = svn_utils_make_viewlink($group_name, $fullpath, 
                            $Language->getText('svn_utils','change'),
			   "&r1=".($revision-1)."&r2=$revision&diff_format=h&pathrev=$revision");

	} else if ($type == 'Add') {
	    $viewtype_url = $Language->getText('svn_utils','add');
	} else if ($type == 'Delete') {
	    $viewtype_url = $Language->getText('svn_utils','del');
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
  global $Language;
  $output = '';
    $res_svnfullhist = svn_data_get_svn_history($group_id);

    if (!$res_svnfullhist || db_numrows($res_svnfullhist) < 1) {
        print '<P>'.$Language->getText('svn_utils','no_hist');
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
        $output = '<P><b>'.$Language->getText('svn_utils','ci_week').'</b><BR>&nbsp;';
        reset($svnhist);
        while (list($user, ) = each($svnhist)) {
            $output .= '<BR>'.$user.' ('.$svnhist[$user]['last'].'/'
                .$svnhist[$user]['full'].')';
        }
    }
    return $output;
}

// read permission access file. The default settings part.
function svn_utils_read_svn_access_file_defaults($gname,$display=false) {
    global $feedback,$svn_prefix;

    $filename = "$svn_prefix/$gname/.SVNAccessFile";

    $fd = @fopen("$filename", "r");
    $in_settings = false;
    $buffer = '';
    while (!feof($fd)) {
	$line = fgets($fd, 4096);
	//if for display: don't include comment lines 
	if ($display && strpos($line,'# END CODEX DEFAULT') !== false) { $in_settings = false; break; }
	else if (!$display && strpos($line,'# BEGIN CODEX DEFAULT') !== false) { $in_settings = true; }

	if ($in_settings) { $buffer .= $line; }

	if ($display && strpos($line,'# BEGIN CODEX DEFAULT') !== false) { $in_settings = true; }
	else if (!$display && strpos($line,'# END CODEX DEFAULT') !== false) { $in_settings = false; break; }
    }
    fclose($fd);
    return $buffer;

}

// read permission access file. The project specific part.
function svn_utils_read_svn_access_file($gname) {

    global $feedback,$Language,$svn_prefix;

    $filename = "$svn_prefix/$gname/.SVNAccessFile";
    $buffer = '';

    $fd = @fopen("$filename", "r");
    if (!$fd) {
        $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_utils','file_err',$filename));
        $buffer = false;
    } else {
        $in_settings = false;
        while (!feof($fd)) {
            $line = fgets($fd, 4096);
            if (strpos($line,'# BEGIN CODEX DEFAULT') !== false) { $in_settings = true; }
            if (!$in_settings) { $buffer .= $line; }
            if (strpos($line,'# END CODEX DEFAULT') !== false) { $in_settings = false; }
        }
        fclose($fd);
    }   
    return $buffer;
}

function svn_utils_write_svn_access_file($gname, $contents) {

    global $feedback,$Language,$svn_prefix;

    $filename = "$svn_prefix/$gname/.SVNAccessFile";
    $fd = fopen("$filename", "w+");
    if ($fd) {
	if (fwrite($fd, str_replace("\r",'',$contents)) === false) {
	    $feedback .= $Language->getText('svn_utils','write_err',$filename);
	    $ret = false;
	} else {
	    $ret = true;
	}
    } else {
	$feedback .= $Language->getText('svn_utils','file_err',$filename);
	$ret = false;
    }
    fclose($fd);
    return $ret;
}

function svn_utils_svn_repo_exists($gname) {
    global $svn_prefix;
    return is_dir("$svn_prefix/$gname");
}


$GLOBALS['SVNACCESS'] = "None";
$GLOBALS['SVNGROUPS'] = "None";

/**
 * Function svn_utils_parse_access_file : parse the .SVNAccessFile of the project $gname 
 * and populate the global arrays $SVNACCESS and $SVNGROUPS.
 * 
 * @param string $gname the unix name of the group (project) we want to parse the access file
 * @global array $SVNACCESS the array populated with the rights for each user for this project $gname
 * @global array $SVNGROUPS the array populated with the members of each ugroup of this project
 *
 * Warning:
 *    The code source of this function is writing in Python too.
 *    If you modify part of this code, thanks to check if
 *    the corresponding Python code needs to be updated too.
 *    (see src/utils/svn/svnaccess.py)
 */
function svn_utils_parse_access_file($gname) {
    global $SVNACCESS, $SVNGROUPS,$Language,$svn_prefix;
  $filename = "$svn_prefix/$gname/.SVNAccessFile";
  $SVNACCESS = array();
  $SVNGROUPS = array();


  $f = @fopen($filename, "rb");
  if ($f === false) {
    $GLOBALS['Response']->addFeedback('error', $Language->getText('svn_utils','file_err',$filename));
  } else {
    $path_pat    = '/^\s*\[(.*)\]/'; // assume no repo name 'repo:'
    $perm_pat    = '/^\s*([^ ]*)\s*=\s*(.*)$/';
    $group_pat   = '/^\s*([^ ]*)\s*=\s*(.*)$/';
    $empty_pat   = '/^\s*$/';
    $comment_pat = '/^\s*#/';

    $ST_START = 0;
    $ST_GROUP = 1;
    $ST_PATH = 2;

    $state = $ST_START;

    $content = @fread($f,filesize($filename));
    $separator = "\n\t\r\0\x0B";
    $line = strtok($content,$separator);
    while ($line) {
      //echo $line."<br>\n";
      if (preg_match($comment_pat, $line) || preg_match($empty_pat,$line)) {
        $line = strtok($separator);
        continue;
      }
      $m = preg_match($path_pat,$line,$matches);
      if ($m) {
        $path = $matches[1];
        if ($path == "groups") {
          $state = $ST_GROUP;
        } else {
          $state = $ST_PATH;
        }
      }

      if ($state == $ST_GROUP) {
        $m = preg_match($group_pat,$line,$matches);
        if ($m) {
          $group = $matches[1];
          $users = $matches[2];
          $SVNGROUPS[strtolower($group)] = split(",", str_replace(' ','',strtolower($users)));
        }
      } else if ($state == $ST_PATH) {
        $m = preg_match($perm_pat, $line, $matches);
        if ($m) {
          $who = $matches[1];
          $perm = $matches[2];


          if (strpos($who,'@') === 0) {
            if (array_key_exists(strtolower(substr($who,1)),$SVNGROUPS)) {
              reset($SVNGROUPS[strtolower(substr($who,1))]); 
	      while (list(,$user) = each($SVNGROUPS[strtolower(substr($who,1))])) {
		if (array_key_exists($user,$SVNACCESS) === false) $SVNACCESS[$user] = array();
                $SVNACCESS[$user][$path] = $perm;
                //echo "SVNACCESS[$user][$path] = $perm <br>\n";
              }
            }
          } else {
            if (array_key_exists(strtolower($who),$SVNACCESS) === false) $SVNACCESS[strtolower($who)] = array();
            $SVNACCESS[strtolower($who)][$path] = $perm;
            //echo "SVNACCESS[$who][$path] = $perm <br>\n";
          }
        }
      }

      $line = strtok($separator);
    }
    fclose($f);
  }
}


function svn_utils_get_forbidden_paths($username,$gname) {
global $SVNACCESS, $SVNGROUPS;

 if ($SVNACCESS == "None") {
    svn_utils_parse_access_file($gname);
  }

 $forbidden = array();
 if (!user_is_super_user()) {   // super user have all the rights (no forbidden paths)
     if (array_key_exists('*',$SVNACCESS)) {
       foreach ($SVNACCESS['*'] as $path => $perm) {
         if (strpos($perm,'r') === false) $forbidden[$path] = true;
       }
     }
    
     if (array_key_exists($username,$SVNACCESS)) {
       foreach ($SVNACCESS[$username] as $path => $perm) {
         if (strpos($perm,'r') === false) {
           $forbidden[$path] = true;
         } else {
           if (array_key_exists($path,$forbidden)) unset($forbidden[$path]);
         }
       }
     }
 }
 return $forbidden;
}


/**
 * Function svn_utils_check_access : check if the user $username can access the path $svnpath of the project $gname 
 * regarding the global arrays $SVNACCESS and $SVNGROUPS.
 * 
 * @param string $username the login name of the user we want to check the perms
 * @param string $gname the unix name of the group (project)
 * @param string $svnpath the subversion path to check
 * @global array $SVNACCESS the array populated with the rights for each user for this project $gname
 * @global array $SVNGROUPS the array populated with the members of each ugroup of this project
 *
 * Warning:
 *    The code source of this function is writing in Python too.
 *    If you modify part of this code, thanks to check if
 *    the corresponding Python code needs to be updated too.
 *    (see src/utils/svn/svnaccess.py)
 */

function svn_utils_check_access($username, $gname, $svnpath) {
  global $SVNACCESS, $SVNGROUPS;

  if ( (user_getname()==$username) && (user_is_super_user())) return true;

  # make sure that usernames are lowercase
  $username = strtolower($username);
  
  if ($SVNACCESS == "None") {
    svn_utils_parse_access_file($gname);
  }

  $perm = '';
  $path = '/'.$svnpath;
  while (true) {
    if (array_key_exists($username,$SVNACCESS) && array_key_exists($path, $SVNACCESS[$username])) {
      $perm = $SVNACCESS[$username][$path];
      //echo "match: SVNACCESS[$username][$path] $perm";
      break;
    } else if (array_key_exists('*',$SVNACCESS) && array_key_exists($path,$SVNACCESS['*'])) {
      $perm = $SVNACCESS['*'][$path];
      //echo "match: SVNACCESS[*][$path] $perm";
      break;
    } else {
      // see if it maches higher in the path
      if ($path == '/') break;
      $idx = strrpos($path,'/');
        if ($idx == 0) {
          $path = '/';
        } else {
          $path = substr($path,0,$idx);
        }
    }
  }
  if (strpos($perm,'r') === false) {
    return false;
  } else {
    return true;
  }
}

function svn_utils_is_there_specific_permission($gname) {
    $specifics = svn_utils_read_svn_access_file($gname);
    return !$specifics || $specifics != '';
}

function svn_get_revisions(&$project, $offset, $chunksz, $_rev_id = '', $_commiter = '', $_srch = '', $order_by = '', $pv = 0) {
    global $_path;
    global $SVNACCESS, $SVNGROUPS;
    $select = 'SELECT SQL_CALC_FOUND_ROWS DISTINCT svn_commits.revision as revision, svn_commits.id as commit_id, svn_commits.description as description, svn_commits.date as date, user.user_name as who ';
    $from = "FROM svn_commits,user ";
    $where = "WHERE svn_commits.group_id=". db_ei($project->getGroupId()) ." AND user.user_id=svn_commits.whoid ";

    //check user access rights
    $forbidden = svn_utils_get_forbidden_paths(user_getname(),$project->getUnixName(false));
    if (!empty($forbidden)) {
        $from .= ",svn_dirs,svn_checkins ";
        $join = "";
        $where_forbidden = "";
        while (list($no_access,) = each($forbidden)) {
            if ($no_access == $_path) {
                $_path = '';
            }
            $join= " AND svn_checkins.dirid=svn_dirs.id AND svn_checkins.commitid=svn_commits.id "; 
            $where_forbidden .= " AND svn_dirs.dir not like '%".db_es(substr($no_access,1))."%' ";
        }
        $where .= $join.$where_forbidden;
    }

    //if status selected, and more to where clause
    if ($_path != '') {
        $path_str = " AND svn_checkins.dirid=svn_dirs.id AND svn_checkins.commitid=svn_commits.id AND svn_dirs.dir like '%".db_es($_path)."%' ";
        if (!isset($forbidden) || empty($forbidden)) {
          $from .= ",svn_dirs,svn_checkins ";
        }
    } else {
        $path_str = "";
    }


    //if revision selected, and more to where clause
    if (isset($_rev_id) && $_rev_id != '') {
        $commit_str=" AND svn_commits.revision='".db_ei($_rev_id)."' ";
    } else {
        $commit_str='';
    }

    if (isset($_commiter) && $_commiter && ($_commiter != 100)) {
        $commiter_str=" AND user.user_name='".db_es($_commiter)."' ";
    } else {
        //no assigned to was chosen, so don't add it to where clause
        $commiter_str='';
    }

    if (isset($_srch) && $_srch != '') {
        $srch_str = "AND svn_commits.description like '%".db_es(htmlspecialchars($_srch))."%' ";
    } else {
        $srch_str = "";
    }

    $where .= $commiter_str.$commit_str.$srch_str.$path_str;

 
    if (!isset($pv) || !$pv) { $limit = " LIMIT ".db_ei($offset).",".db_ei($chunksz);}

    // SQLi Warning: no real possibility to escape $order_by here.
    // We rely on a proper filtering of user input by calling methods.
    if (!isset($order_by) || $order_by == '') {
        $order_by = " ORDER BY revision desc ";
    }

    $sql=$select.$from.$where.$order_by.$limit;

    $result=db_query($sql);

    // Compute the number of rows.
    $sql1 = 'SELECT FOUND_ROWS() as nb';
    $result1 = db_query($sql1);
    if($result1 && !db_error($result1)) {
        $row1 = db_fetch_array($result1);
        $totalrows = $row1['nb'];
    }

    return array($result, $totalrows);
}
?>
