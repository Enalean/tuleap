<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

/*

	Commits Manager 
	By Tim Perdue, Sourceforge, Feb 2000
	Heavy Rewrite Tim Perdue, April, 2000

*/

function uniformat_date($format, $date) {

  if (ereg("([0-9]{4})-?([0-9]{2})-?([0-9]{2}) ?([0-9]{2}):?([0-9]{2}):?([0-9]{2})", $date, $gp)) {
    list(,$y, $m, $d, $h, $min, $s) = $gp;
    $time = mktime($h, $min, $s, $m, $d, $y);
    $date = date($format, $time);
  }
  return $date;
}

function commits_header($params) {
	global $group_id,$DOCUMENT_ROOT;

	$params['toptab']='commits';
	$params['group']=$group_id;

	//only projects can use cvs, and only if they have it turned on
	$project=project_get_object($group_id);

	if (!$project->isProject()) {
		exit_error('Error','Only Projects Can Use The Commits Manager');
	}
	if (!$project->usesCVS()) {
	    exit_error('Error','This Project Has Turned CVS Off');
	}
	echo site_project_header($params);

	echo '<P><B><A HREF="/cvs/?func=info&group_id='.$group_id.'">CVS Info</A>';

	$sys_cvs_host = $GLOBALS['sys_cvs_host'];

	if ($project->isPublic() || user_isloggedin()) {
	  echo ' | <A HREF="http'.(session_issecure() ? 's':'').'://'.$sys_cvs_host.'/cgi-bin/cvsweb.cgi/?cvsroot='.$project->getUnixName().'">Browse CVS Tree</A>';
	}
	if (user_isloggedin()) {
	  echo ' | <A HREF="/cvs/?func=browse&group_id='.$group_id.'&set=my">My CVS Commits</A>';
	}
	echo ' | <A HREF="/cvs/?func=browse&group_id='.$group_id.'">CVS Query</A>';
	echo ' | <A HREF="/cvs/?func=admin&group_id='.$group_id.'">CVS Admin</A>';	
	if (!$params['help']) { $params['help'] = "VersionControlWithCVS.html";}
	echo ' | '.help_button($params['help'],false,'Help');

	echo '</B>';
	echo ' <hr width="300" size="1" align="left" noshade>';
}

function commits_header_admin($params) {
    global $group_id,$DOCUMENT_ROOT;
    
    //required params for site_project_header();
    $params['group']=$group_id;
    $params['toptab']='commits';
    
    $project=project_get_object($group_id);
    
    //only projects can use the commits manager, and only if they have it turned on
    if (!$project->isProject()) {
	exit_error('Error','Only Projects Can Use The Commits Browser');
    }
    if (!$project->usesBugs()) {
	exit_error('Error','This Project Has Turned Off The Commits Browser');
    }
    echo site_project_header($params);
    //echo '<A HREF="http'.(session_issecure() ? 's':'').'://'.$sys_default_domain.'/cgi-bin/cvsweb.cgi/?cvsroot='
    //.$row_grp['unix_group_name'].'">Browse CVS Repository</A>';
    //  echo ' | <B><A HREF="/commits/admin/index.php?commits_cat=1&group_id='.$group_id.'">Manage Categories</A></B>';
    //  echo ' | <b><A HREF="/commits/admin/index.php?other_settings=1&group_id='.$group_id.'">Other Settings</A></b>';
    if ($params['help']) {
	echo ' | <b>'.help_button($params['help'],false,'Help').'</b>';
    }
     echo ' <hr width="300" size="1" align="left" noshade>';
}


function commits_footer($params) {
	site_project_footer($params);
}

function commits_branches_box($group_id,$name='branch',$checked='xzxz', $text_100='None') {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
	  $sql = "SELECT unix_group_name from groups where group_id=$group_id";

	  $result = db_query($sql);
	  $projectname = db_result($result, 0, 'unix_group_name');
		/*
			List of possible commits_categories set up for the project
		*/
		$sql="select distinct cvs_branches.* FROM cvs_branches, cvs_checkins, cvs_repositories  where ".
		  "cvs_checkins.repositoryid=cvs_repositories.id AND cvs_repositories.repository='/cvsroot/".$projectname."' ".
		  "AND cvs_checkins.branchid=cvs_branches.id";
		$result=db_query($sql);

		return html_build_select_box($result,$name,$checked,true,$text_100);
	}
}

function commits_data_get_technicians($group_id) {
	$sql="SELECT distinct user.user_name, user.user_name ".
		"FROM user, user_group, cvs_checkins, cvs_repositories ".
		"WHERE (user_group.group_id='$group_id' ".
		"AND user.user_id=user_group.user_id) ".
	  //"OR (cvs_repositories.repository='/cvsroot/".$projectname."' ".
	  //	"AND cvs_checkins.repositoryid=cvs_repositories.id ".
	  //"AND user.user_id=cvs_checkins.whoid) ". 
		"ORDER BY user.user_name ASC";

	return db_query($sql);
}

function commits_technician_box($group_id,$name='_commiter',$checked='xzxz',$text_100='None') {
	if (!$group_id) {
		return 'ERROR - no group_id';
	} else {
		$result=commits_data_get_technicians($group_id);
		return html_build_select_box($result,$name,$checked,true,$text_100);
	}
}

function commits_tags_box($group_id, $name='_tag',$checked='xzxz',$text_100='None' ) {
  $sql = "SELECT unix_group_name from groups where group_id=$group_id";

  $result = db_query($sql);
  $projectname = db_result($result, 0, 'unix_group_name');
  
  $sql="select distinct stickytag, stickytag from cvs_checkins, cvs_repositories where cvs_checkins.repositoryid=cvs_repositories.id AND cvs_repositories.repository='/cvsroot/".$projectname."'";
  $result=db_query($sql);
  return html_build_select_box($result,$name,$checked,true,$text_100);
}

function show_commitslist ($result,$offset,$total_rows,$set='any', $commiter='100', $tag='100', $branch='100', $chunksz=15, $morder='', $msort=0) {
	global $sys_datefmt,$group_id;
	/*
		Accepts a result set from the commits table. Should include all columns from
		the table, and it should be joined to USER to get the user_name.
	*/
    $url = $PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&msort='.$msort;

    if ($set == 'custom')
     $url .= $pref_stg;

    $url_nomorder = $url;
    $url .= "&morder=$morder";

	if ($morder != '') {
	  $orderstr = ' sorted by '.commit_criteria_list_to_text($morder, $url_nomorder);
	} else {
	  $orderstr = '';
	}
	echo '<A name="results"></A>';  
	echo '<h3>'.$total_rows.' matching commit'.($totalrows>1 ? 's':'').$orderstr.'</h3>';

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
	if ($tag != '100') {
	  $filter_str = $filter_str."&tag='$tag'";
	}
	if ($branch != '100') {
	  $filter_str = $filter_str."&branch='$branch'";
	}
	

	$rows=db_numrows($result);
	$url .= "&order=";
	$title_arr=array();
	$title_arr[]='ID';
	$title_arr[]='Description';
	$title_arr[]='Date';
	$title_arr[]='Submitted By';

	$links_arr=array();
	$links_arr[]=$url.'id#results';
	$links_arr[]=$url.'description#results';
	$links_arr[]=$url.'f_when#results';
	$links_arr[]=$url.'user_name#results';

	$url_nomorder = $url;
	$url .= "&morder=$morder";

	echo html_build_list_table_top ($title_arr,$links_arr);

	for ($i=0; $i < $rows; $i++) {

	    $filename = db_result($result, $i, 'filename');
	    if (!$filename) {
		$filename = '';
	    }
	    ##$commits_url = '<A HREF="/commits/download.php/Commits'.$commit_id.'.txt?commit_id='.$id.'">'.$filename.'</a>';

	    ## if (commits.id == '0', will fetch on desc id, else on commit_id
	    $id_str = db_result($result, $i, 'id');
	    $id_link = '&commit_id='.$id_str;
	    $id_sublink = '';
	    if ($id_str == '0') {
	      $id_str = ' ? ';
	      $id_link = "&checkin_id=".db_result($result, $i, 'did').
		  "&when=".db_result($result, $i, 'c_when').$filter_string;
	      ##$id_sublink =" <br><A HREF=\"".$PHP_SELF."?func=detailcommit&group_id=".$group_id."&checkin_id=".db_result($result, $i, 'did').$filter_string."&desc_id=".db_result($result, $i, 'did')."\">no date on this log</A>";
	    } else {
	      ##$id_sublink =" <br><A HREF=\"".$PHP_SELF."?func=detailcommit&group_id=".$group_id.$id_link.$filter_string."&desc_id=".db_result($result, $i, 'did')."\">".$id_str." on this log</A>";
	      
	    }
	    
	    echo '
			<TR class="'. util_get_alt_row_color($i) .'">'.
			'<TD class="small"><b><A HREF="'.$PHP_SELF.'?func=detailcommit&group_id='.$group_id.$id_link.$filter_string.'">'.$id_str.
		  '</b></A></TD>'.
			'<TD class="small">'.util_make_links(join('<br>', split("\n",db_result($result, $i, 'description'))),$group_id).$id_sublink.'</TD>'.
			##'<TD class="small">'.$commits_url.'</TD>'.
			'<TD class="small">'.uniformat_date($sys_datefmt, db_result($result, $i, 'c_when')).'</TD>'.
			## '<TD class="small">'.util_user_link(db_result($result,$i,'assigned_to_user')).'</TD>'.
			'<TD class="small">'.util_user_link(db_result($result,$i,'who')).'</TD></TR>';

	}

	/*
		Show extra rows for <-- Prev / Next -->
	*/
	echo '</TD></TR></TABLE>';
	echo $nav_bar;
}

function makeCvsLink($group_id, $filename='', $text, $rev='', $displayfunc='') {
  $res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

  //  $view_str='&content-type=text/vnd.viewcvs-markup';
  $view_str=$displayfunc;
  if ($rev) {
    $view_str=$view_str.'&rev='.$rev;
  }
    

  $row_grp = db_fetch_array($res_grp);
    return '<A href="http'.(session_issecure() ? 's':'').'://'.$GLOBALS['sys_cvs_host'].'/cgi-bin/cvsweb.cgi/'.$filename.'?cvsroot='.$row_grp['unix_group_name'].$view_str.'"><B>'.$text.'</B></A>';
}

function makeCvsDirLink($group_id, $filename='', $text, $dir='') {
  $res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

  //  $view_str='&content-type=text/vnd.viewcvs-markup';
   

  $row_grp = db_fetch_array($res_grp);

  return  '<A href="http'.(session_issecure() ? 's':'').'://'.$GLOBALS['sys_cvs_host'].'/cgi-bin/cvsweb.cgi/'.$dir."/Attic/".$filename.'?cvsroot='.$row_grp['unix_group_name'].'"><B>'.$text.'</B></A>';

}

// Check is a sort criteria is already in the list of comma
// separated criterias. If so invert the sort order, if not then
// simply add it
function commit_add_sort_criteria($criteria_list, $order, $msort)
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
function commit_criteria_list_to_query($criteria_list)
{

    $criteria_list = str_replace('>',' ASC',$criteria_list);
    $criteria_list = str_replace('<',' DESC',$criteria_list);
    return $criteria_list;
}

// Transform criteria list to readable text statement
// $url must not contain the morder parameter
function commit_criteria_list_to_text($criteria_list, $url){

    if ($criteria_list) {

	$arr = explode(',',$criteria_list);

	while (list(,$crit) = each($arr)) {

	    $morder .= ($morder ? ",".$crit : $crit);
	    $attr = str_replace('>','',$crit);
	    $attr = str_replace('<','',$attr);

	    $arr_text[] = '<a href="'.$url.'&morder='.$morder.'#results">'.
		commit_field_get_label($attr).'</a><img src="'.util_get_dir_image_theme().
		((substr($crit, -1) == '<') ? 'dn' : 'up').
		'_arrow.png" border="0">';
	}
    }

    return join(' > ',$arr_text);
}

function commit_field_get_label($sortField) {
  if ($sortField == "id") {
    return "ID";
  }
  if ($sortField == "f_when") {
    return "Date";
  }
  return $sortField;
  }


function show_commit_details ($result) {
	global $sys_datefmt,$group_id,$commit_id;
	/*
		Accepts a result set from the commits table. Should include all columns from
		the table, and it should be joined to USER to get the user_name.
	*/

	$rows=db_numrows($result);
	$url = "/cvs/?func=detailcommit&commit_id=$commit_id&group_id=$group_id&order=";
	$list_log = '<pre>'.util_make_links(util_line_wrap(db_result($result, 0, 'description')), $group_id).'</pre>';

	if ($commit_id) {
	  $hdr = '[Commit #'.$commit_id.'] - ';
	} else {
	  $hdr = 'Checkin - ';
	}
	echo '<h2>'.$hdr.uniformat_date($sys_datefmt, db_result($result, 0, 'c_when')).'</h2></h2>';
	echo '<table WIDTH="100%" BORDER="0" CELLSPACING="1" CELLPADDING="2"><tr class="'. util_get_alt_row_color(0).'"><td>'.$list_log.'</td></tr></table>';
	echo '<h3> List of impacted files</h3>';
	$title_arr=array();
	$title_arr[]= 'File';
	$title_arr[]='Revision';
	$title_arr[]='Branch';
	$title_arr[]='Type';
	$title_arr[]='AddedLines';
	$title_arr[]='RemovedLines';

	$links_arr=array();
	$links_arr[]=$url.'filename';
	$links_arr[]=$url.'revision';
	$links_arr[]=$url.'branch';
	$links_arr[]=$url.'type';
	$links_arr[]=$url.'addedlines';
	$links_arr[]=$url.'removedlines';

	echo html_build_list_table_top ($title_arr,$links_arr);

	for ($i=0; $i < $rows; $i++) {

	    $commit_id = db_result($result, $i, 'id');
	    $type = db_result($result, $i, 'type');
	    $added = db_result($result, $i, 'addedlines');
	    $removed = db_result($result,$i,'removedlines');
	    $revision = db_result($result,$i,'revision');
	    $filename = db_result($result, $i, 'dir').'/'.
	      db_result($result, $i, 'file');
	    if (($type == "Change") &&
		($added == 999) && 
		($removed == 999)) { // the default values
	      // back to rcs to complete
	      $repo = db_result($result,$i,'repository');
	      $command = "rlog -r".$revision." ".$repo."/".$filename;
	      $output = array();
	      exec($command, $output, $ret);
	      $added = 0;
	      $removed = 0;
	      $l =0;
	      while ($l < count($output)) { // parse the rlog result till getting "state: Exp;  lines:" 
		$line = $output[$l];
		$l++;
		if (ereg ('state: +Exp; +lines: +\+([0-9]*) +\-([0-9]*)$', $line, $na)) {
		  $added = $na[1];
		  $removed = $na[2];
		  $sql_up = "UPDATE cvs_checkins SET addedlines=".$added.", removedlines=".$removed." WHERE repositoryid=".db_result($result,$i,'repositoryid')." AND dirid=".db_result($result,$i,'dirid')." AND fileid=".db_result($result,$i,'fileid')." AND revision=".$revision;
		  $res=db_query($sql_up);
		  break;
		}
		
	      }
		  

	    }
	      

	    if (!$filename) {
		$filename = '';
	    } else {
	      if ($type == 'Remove') {
	      $filename = makeCvsDirLink($group_id, db_result($result, $i, 'file'), $filename, db_result($result, $i, 'dir'));
	      $rev_text = '';
	      } else {
		if ($type == 'Change') {
		  // horrible hack to 'guess previous revision' to diff with
		  $prev = explode(".", $revision);

		  $lastIndex = sizeof($prev);
		  $lastIndex = $lastIndex - 1;
		  if ($prev[$lastIndex] != '1') {
		    $prev[$lastIndex] = $prev[$lastIndex] - 1;
		    $previous = join(".", $prev);
		  } else {
		    $index = 0;
		    $new_prev = array();
		    while ($index < $lastIndex - 2) {
		      $new_prev[$index] = $prev[$index];
		      $index++;
		    }
		    $previous = $new_prev;
		  }
		  $previous = join('.', $prev);
		  $type = makeCvsLink($group_id, $filename.'.diff', 'Change', '', '&r1='.$previous.'&r2='.$revision);
		}
		$rev_text = makeCvsLink($group_id, $filename, $revision, $revision, '&content-type=text/x-cvsweb-markup');
		$filename = makeCvsLink($group_id, $filename, $filename);
	      }
	    }
	    ##$commits_url = '<A HREF="/commits/download.php/Commits'.$commit_id.'.txt?commit_id='.$id.'">'.$filename.'</a>';
	    
	    echo '
			<TR class="'. util_get_alt_row_color($i) .'">'.
			'<TD class="small"><b>'.$filename.'</b></TD>'.
			'<TD class="small">'.$rev_text.'</TD>'.
			'<TD class="small">'.db_result($result, $i, 'branch').'</TD>'.
			'<TD class="small">'.$type.'</TD>'.
			'<TD class="small">'.$added.'</TD>'.
			'<TD class="small">'.$removed.'</TD></TR>';


	}

	/*
		Show extra rows for <-- Prev / Next -->
	*/
	echo '
		<TR><TD COLSPAN="2" class="small">';
	if ($offset > 0) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&offset='.($offset-50).'"><B><-- Previous 50</B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD><TD>&nbsp;</TD><TD COLSPAN="2" class="small">';
	
	if ($rows==50) {
		echo '<A HREF="'.$PHP_SELF.'?func=browse&group_id='.$group_id.'&set='.$set.'&offset='.($offset+50).'"><B>Next 50 --></B></A>';
	} else {
		echo '&nbsp;';
	}
	echo '</TD></TR></TABLE>';
}


?>
