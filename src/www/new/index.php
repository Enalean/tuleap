<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');
require_once('www/project/admin/permissions.php');
require_once('www/new/new_utils.php');

$Language->loadLanguageMsg('new/new');

// By default, display releases
if (!$func) $func='releases';

switch ($func) {

 case 'releases':
$HTML->header(array("title"=>$Language->getText('new_index','new_file_release')));


if ( !$offset || $offset < 0 ) {
	$offset = 0;
}

// For expediancy, list only the filereleases in the past three days.
//LJ $start_time = time() - (7 * 86400);
$start_time = time() - (14 * 86400);
$limit = 21;
$query = new_utils_get_new_releases_long($start_time, $offset, $limit);
$res_new = db_query( $query );


// LJ In case there is less than 4 releases in the last N days
// LJ then display the last ones regardless of how old they are
// LJ We don't want an empty list when CodeX started and there
// is little activity.
if (!$res_new || db_numrows($res_new) < 4) {
	$start_time = 0;
	$query = new_utils_get_new_releases_long(0, 0, 21);
	$res_new = db_query( $query );
}

//LJ Modified by LJ. If there is exactly 0 no new
//LJ release then it's not an error
//LJ
echo '<h2>'.$Language->getText('new_index','new_releases').' '.help_button('TheCodeXMainMenu.html#NewReleases').'</h2>';
if (!$res_new || db_numrows($res_new) < 1) {
	if (!$res_new) {
		echo $query . "<BR><BR>"	;
		echo db_error();
		echo '<H2>'.$Language->getText('new_index','no_release_found').' '.$Language->getText('new_index','db_err').'</H2>';
	} else {
		echo '<H2>'.$Language->getText('new_index','no_release_found').' </H2>';
	}
} else {

	if ( db_numrows($res_new) > 20 ) {
		$rows = 20;
	} else {
		$rows = db_numrows($res_new);
	}

	print "\t<TABLE width=100% cellpadding=0 cellspacing=0 border=0>";
	for ($i=0; $i<$rows; $i++) {
	  $row_new = db_fetch_array($res_new);
	  // avoid dupulicates of different file types
	  if (!($G_RELEASE["$row_new[group_id]"])) {
	    if ((!permission_exist("PACKAGE_READ",$row_new['package_id'] ))&&
		(!permission_exist("RELEASE_READ",$row_new['release_id'] ))) {
	      print "<TR valign=top>";
	      print "<TD colspan=2>";
	      print "<A href=\"/projects/$row_new[unix_group_name]/\"><B>$row_new[group_name]</B></A>"
		. "\n</TD><TD nowrap><I>".$Language->getText('new_index','released_by').": <A href=\"/users/$row_new[user_name]/\">"
		. "$row_new[user_name]</A></I></TD></TR>\n";	
		   
	      print "<TR><TD>".$Language->getText('new_index','module').": $row_new[module_name]</TD>\n";
	      print "<TD>".$Language->getText('new_index','version').": $row_new[release_version]</TD>\n";
	      print "<TD>" . date("M d, h:iA",$row_new['release_date']) . "</TD>\n";
	      print "</TR>";
		   
	      print "<TR valign=top>";
	      print "<TD colspan=2>&nbsp;<BR>";
	      if ($row_new['short_description']) {
		print "<I>$row_new[short_description]</I>";
	      } else {
		print "<I>".$Language->getText('new_index','no_desc')."</I>";
	      }
		   
	      print "</TD>";
	      print '<TD align=center nowrap border=1>';
	      print "&nbsp;</TD>";
	      print "</TR>";

	      print '<TR><TD colspan=3>';
	      // link to whole file list for downloads
	      print "&nbsp;<BR><A href=\"/file/showfiles.php?group_id=$row_new[group_id]&release_id=$row_new[release_id]\">";
	      print $Language->getText('new_index','download')."</A> ";
	      print '('.$Language->getText('new_index','total').': '.$row_new['downloads'].') | ';
	      // notes for this release
	      print "<A href=\"/file/shownotes.php?release_id=".$row_new['release_id']."\">";
	      print $Language->getText('new_index','notes')."</A>";
	      print '<HR></TD></TR>';

	      $G_RELEASE["$row_new[group_id]"] = 1;
	    }
	  }
	}
	    
	echo "<TR class=\"newproject\"><TD>";
        if ($offset != 0) {
		echo "<B>";
        	echo "<A HREF=\"/new/?func=releases&offset=".($offset-20)."\"><B><IMG SRC=\"".util_get_image_theme("t2.png")."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE> ".$Language->getText('new_index','newer_releases')."</A></B>";
        } else {
        	echo "&nbsp;";
        }

	echo "</TD><TD COLSPAN=\"2\" ALIGN=\"RIGHT\">";
	if (db_numrows($res_new)>$rows) {
		echo "<B>";
		echo "<A HREF=\"/new/?func=releases&offset=".($offset+20)."\"><B>".$Language->getText('new_index','older_releases')." <IMG SRC=\"".util_get_image_theme("t.png")."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE></A></B>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD></TR></TABLE>";
}
	break;

 case 'projects':
$HTML->header(array("title"=>$Language->getText('new_index','new_projects')));



if ( !$offset || $offset < 0 ) {
	$offset = 0;
}

// For expediancy, list only the filereleases in the past three days.
//LJ $start_time = time() - (7 * 86400);
$start_time = strval(time()-(24*3600));

 $query = new_utils_get_new_projects($start_time,$offset,0);
$res_new = db_query( $query );


//If there is exactly 0 no new
//then it's not an error
echo '<h2>'.$Language->getText('new_index','new_projects').'</h2>';
if (!$res_new || db_numrows($res_new) < 1) {
	if (!$res_new) {
		echo $query . "<BR><BR>"	;
		echo db_error();
		echo '<H2>'.$Language->getText('new_index','no_projects_found').' '.$Language->getText('new_index','db_err').'</H2>';
	} else {
		echo '<H2>'.$Language->getText('new_index','no_projects_found').' </H2>';
	}
} else {

	if ( db_numrows($res_new) > 20 ) {
		$rows = 20;
	} else {
		$rows = db_numrows($res_new);
	}

	print "\t<TABLE width=100% cellpadding=0 cellspacing=0 border=0>";
	for ($i=0; $i<$rows; $i++) {
		$row_new = db_fetch_array($res_new);
		// avoid dupulicates of different file types
		if (!isset($G_RELEASE) || !($G_RELEASE["$row_new[group_id]"])) {

		  // Get Project admin as contacts
		  $res_admin = db_query("SELECT user.user_name AS user_name "
					. "FROM user,user_group "
					. "WHERE user_group.user_id=user.user_id AND user_group.group_id=".$row_new['group_id']." AND "
					. "user_group.admin_flags = 'A'");
		  
		  $admins = array();
		  while ($row_admin = db_fetch_array($res_admin)) {
		    $admins[] = '<A href="/users/'.$row_admin['user_name'].'/">'.$row_admin['user_name'].'</A>';
		    //echo '<A href="/users/'.$row_admin['user_name'].'/">'.$row_admin['user_name'].'</A>';
		  }

		  // Get languages, OS Runtime and Development state from trove map
		  $res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
					   .'trove_cat.fullpath_ids AS fullpath_ids,'
					   .'trove_cat.trove_cat_id AS trove_cat_id '
					   .'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
					   .'trove_group_link.trove_cat_id AND trove_group_link.group_id='
					   .$row_new['group_id'].' ORDER BY trove_cat.fullpath');
		  $lang = $os = $devstate = array();
		  
		  while ($row_trovecat = db_fetch_array($res_trovecat)) {
		    $folders = explode(" :: ",$row_trovecat['fullpath']);
		    $folders_len = count($folders);
		    
		    $pl_pattern = '"/'.$Language->getText('new_index','prog_lang').'/"';
		    $os_pattern = '"/'.$Language->getText('new_index','os').'/"';
		    $devel_status_pattern = '"/'.$Language->getText('new_index','devel_status').'/"';
		    if ( preg_match($pl_pattern, $folders[0])) {
		      $lang[] = $folders[$folders_len - 1];
		    }
		    else if ( preg_match($os_pattern, $folders[0])) {
		      $os[] = $folders[$folders_len - 1];
		    }
		    else if ( preg_match($devel_status_pattern, $folders[0])) {
		      $devstate[] = $folders[$folders_len - 1];
		    }
		  }

		  print "<TR valign=top>";
		  print "<TD colspan=2>";
		  print "<A href=\"/projects/$row_new[unix_group_name]/\"><B>$row_new[group_name]</B> (" . date("y/m/d",$row_new['register_time']) . ")</A>\n</TD>";
		  print "<TD nowrap><I>".$Language->getText('new_index','contact').": ";
		  print join(',',$admins);
		  print "</I></TD></TR>\n";	
		  
		  print "<TR valign=top>";
		  print "<TD colspan=2>&nbsp;<BR>";
		  if ($row_new['short_description']) {
		    print "<I>$row_new[short_description]</I>";
		  } else {
		    print "<I>'.$Language->getText('new_index','no_desc').'</I>";
		  }
		  print "</TD>";
		  print '<TD align=center nowrap border=1>';
		  print "&nbsp;</TD>";
		  print "</TR>";
		  print '<TR><TD colspan=3>&nbsp;<BR>';
		  if (count($lang) > 0) {
		    print $Language->getText('new_index','languages').": ";
		    print join(',',$lang);
		  }
		  if (count($os) > 0) {
		    if (count($lang) > 0) {
		      print '</TD></TR><TR><TD colspan=3>';
		    }
		    print $Language->getText('new_index','os_runtime_support').": ";
		    print join(',',$os);
		  }
		  if (count($devstate) > 0) {
		    if ((count($os) > 0) || (count($lang) > 0)) {
		      print '</TD></TR><TR><TD colspan=3>';
		    }
		    print $Language->getText('new_index','devel_status').": ";
		    print join(',',$devstate);
		  }
		  print '<HR></TD></TR>';
		    
		  $G_RELEASE["$row_new[group_id]"] = 1;
		} 
	}
	
	echo "<TR class=\"newproject\"><TD>";
        if ($offset != 0) {
		echo "<B>";
        	echo "<A HREF=\"/new/?func=projects&offset=".($offset-20)."\"><B><IMG SRC=\"".util_get_image_theme("t2.png")."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE> ".$Language->getText('new_index','newer_projects')."</A></B>";
        } else {
        	echo "&nbsp;";
        }

	echo "</TD><TD COLSPAN=\"2\" ALIGN=\"RIGHT\">";
	if (db_numrows($res_new)>$rows) {
		echo "<B>";
		echo "<A HREF=\"/new/?func=projects&offset=".($offset+20)."\"><B>".$Language->getText('new_index','older_projects')." <IMG SRC=\"".util_get_image_theme("t.png")."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE></A></B>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD></TR></TABLE>";

}
   break;
}
$HTML->footer(array());

?>
