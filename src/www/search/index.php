<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');
require_once('www/tracker/include/ArtifactTypeHtml.class.php');
require_once('www/tracker/include/ArtifactHtml.class.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/Artifact.class.php');
require_once('common/tracker/ArtifactFieldFactory.class.php');


$em =& EventManager::instance();   
$plugins_powered_search = false;   
$em->processEvent('plugins_powered_search', array('type_of_search' => $type_of_search,
                                                'plugins_powered_search' => &$plugins_powered_search)); 


if ($type_of_search !== "tracker" && $type_of_search !== "wiki" && !$plugins_powered_search) {
    if (!$request->isAjax()) {
        $HTML->header(array('title'=>$Language->getText('search_index','search')));
        echo "<P><CENTER>";
        $HTML->bodySearchBox();
    }
}
$hp = Codendi_HTMLPurifier::instance();
/*
	Force them to enter at least three characters
*/
if ($words && (strlen($words) < 3)) {
	if ($type_of_search == "tracker" || $type_of_search == "wiki" || $plugins_powered_search) {
        $HTML->header(array('title'=>$Language->getText('search_index','search')));
    }
    echo '<H2>'.$Language->getText('search_index','at_least_3_ch').'</H2>';
	$HTML->footer(array());
	exit;
}

if (!$words) {
    if ($type_of_search == "tracker" || $type_of_search == "wiki" || $plugins_powered_search) {
        $HTML->header(array('title'=>$Language->getText('search_index','search')));
    }
	echo '<BR>'.$Language->getText('search_index','enter_s_words');
	$HTML->footer(array());
	exit;
}

if (!$request->isAjax()) {
    echo '</CENTER><P>';
}

	
$words = trim($words);
$no_rows = 0;

if (isset($_REQUEST['exact']) && $_REQUEST['exact']) {
	$crit='AND';
} else {
	$crit='OR';
}

if (!isset($offset) || !$offset || $offset < 0) {
	$offset = 0;
}

$pagination_handled = false;

if ($type_of_search == "soft") {
	/*
		If multiple words, separate them and put LIKE in between
	*/
	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit group_name LIKE '%");
	$words2=implode($array,"%' $crit short_description LIKE '%");
	$words3=implode($array,"%' $crit unix_group_name LIKE '%");

    $user = UserManager::instance()->getCurrentUser();
    if ($user->isRestricted()) {
        $from_restricted = ", user_group ";
        $where_restricted = " AND user_group.group_id = groups.group_id AND user_group.user_id = '".$user->getID()."'";
    } else {
        $from_restricted = "";
        $where_restricted = "";
    }
    
	/*
		Query to find software
	*/
	$sql = "SELECT DISTINCT group_name,unix_group_name,groups.group_id,short_description ".
		"FROM groups, group_desc_value ".$from_restricted.
		"WHERE status='A' AND is_public='1' AND (groups.group_id=group_desc_value.group_id) AND ((group_name LIKE '%$words1%') OR (short_description LIKE '%$words2%') OR (unix_group_name LIKE '%$words3%') OR (group_desc_value.value LIKE '%$words3%')) ".$where_restricted." LIMIT $offset,26";

    $result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if (!$result || $rows < 1) {
		$no_rows = 1;
		echo '<H2>'.$Language->getText('search_index','no_match_found',htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')).'</H2>';
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo '<H3>'.$Language->getText('search_index','search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned))."</H3><P>\n\n";

		$title_arr = array();
		$title_arr[] = $Language->getText('search_index','project_name');
		$title_arr[] = $Language->getText('search_index','description');

		echo html_build_list_table_top($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR class=\"". html_get_alt_row_color($i)."\"><TD><A HREF=\"/projects/".db_result($result, $i, 'unix_group_name')."/\">"
				. "<IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'group_name')."</A></TD>"
				. "<TD>". $hp->purify(util_unconvert_htmlspecialchars(db_result($result,$i,'short_description')), CODENDI_PURIFIER_LIGHT) ."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == "people") {

	/*
		If multiple words, separate them and put LIKE in between
	*/
	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit user_name LIKE '%");
	$words2=implode($array,"%' $crit realname LIKE '%");

	/*
		Query to find users
	*/
	$sql =	"SELECT user_name,user_id,realname "
		. "FROM user "
		. "WHERE ((user_name LIKE '%$words1%') OR (realname LIKE '%$words2%')) AND ((status='A') OR (status='R')) ORDER BY user_name LIMIT $offset,26";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if (!$result || $rows < 1) {
		$no_rows = 1;
		echo '<H2>'.$Language->getText('search_index','no_match_found', $hp->purify(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), CODENDI_PURIFIER_CONVERT_HTML)).'</H2>';
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo '<H3>'.$Language->getText('search_index','search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned))."</H3><P>\n\n";

		$title_arr = array();
		$title_arr[] = $Language->getText('search_index','user_n');
		$title_arr[] = $Language->getText('search_index','real_n');

		echo html_build_list_table_top ($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR class=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/users/".db_result($result, $i, 'user_name')."/\">"
				. "<IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'user_name')."</A></TD>"
				. "<TD>". $hp->purify(db_result($result,$i,'realname'), CODENDI_PURIFIER_CONVERT_HTML) ."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == 'forums') {

	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit forum.body LIKE '%");
	$words2=implode($array,"%' $crit forum.subject LIKE '%");

	$sql =	"SELECT forum.msg_id,forum.subject,forum.date,user.user_name "
		. "FROM forum,user "
		. "WHERE user.user_id=forum.posted_by AND ((forum.body LIKE '%$words1%') "
		. "OR (forum.subject LIKE '%$words2%')) AND forum.group_forum_id='$forum_id' "
		. "GROUP BY msg_id,subject,date,user_name LIMIT $offset,26";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if (!$result || $rows < 1) {
		$no_rows = 1;
		echo '<H2>'.$Language->getText('search_index','no_match_found',htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')).'</H2>';
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo '<H3>'.$Language->getText('search_index','search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned))."</H3><P>\n\n";

		$title_arr = array();
		$title_arr[] = $Language->getText('search_index','thread');
		$title_arr[] = $Language->getText('search_index','author');
		$title_arr[] = $Language->getText('search_index','date');

		echo html_build_list_table_top ($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR class=\"". html_get_alt_row_color($i) ."\"><TD><A HREF=\"/forum/message.php?msg_id="
				. db_result($result, $i, "msg_id")."\"><IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> "
				. db_result($result, $i, "subject")."</A></TD>"
				. "<TD>".db_result($result, $i, "user_name")."</TD>"
				. "<TD>".format_date($GLOBALS['Language']->getText('system', 'datefmt'),db_result($result,$i,"date"))."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}
} else if ($type_of_search == 'snippets') {

	/*
		If multiple words, separate them and put LIKE in between
	*/
	$array=explode(" ",$words);
	$words1=implode($array,"%' $crit name LIKE '%");
	$words2=implode($array,"%' $crit description LIKE '%");

	/*
		Query to find software
	*/
	$sql = "SELECT name,snippet_id,description ".
		"FROM snippet ".
		"WHERE ((name LIKE '%$words1%') OR (description LIKE '%$words2%')) LIMIT $offset,26";
	$result = db_query($sql);
	$rows = $rows_returned = db_numrows($result);

	if (!$result || $rows < 1) {
		$no_rows = 1;
		echo '<H2>'.$Language->getText('search_index','no_match_found',htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')).'</H2>';
		echo db_error();
//		echo $sql;
	} else {

		if ( $rows_returned > 25) {
			$rows = 25;
		}

		echo '<H3>'.$Language->getText('search_index','search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned))."</H3><P>\n\n";

		$title_arr = array();
		$title_arr[] = $Language->getText('search_index','snippet_name');
		$title_arr[] = $Language->getText('search_index','description');

		echo html_build_list_table_top($title_arr);

		echo "\n";

		for ( $i = 0; $i < $rows; $i++ ) {
			print	"<TR class=\"". html_get_alt_row_color($i)."\"><TD><A HREF=\"/snippet/detail.php?type=snippet&id=".db_result($result, $i, 'snippet_id')."\">"
				. "<IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> ".db_result($result, $i, 'name')."</A></TD>"
				. "<TD>".db_result($result,$i,'description')."</TD></TR>\n";
		}
		echo "</TABLE>\n";
	}

} else if ($type_of_search == 'all_trackers') {
    $pm = ProjectManager::instance();
    if ($project = $pm->getProject($group_id)) {
        
        $atf = new ArtifactTypeFactory($project);
        
        // Get the artfact type list
        $at_arr = $atf->getArtifactTypes();
        if (!$at_arr || count($at_arr) < 1) {
            $no_rows = 1;
            echo '<H2>'.$Language->getText('search_index','no_match_found',htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')).'</H2>';
        } else {
            for ($i = 0; $i < count($at_arr); $i++) {
                $atid = $at_arr[$i]->getID();
                
                $array=explode(" ",$words);
                $words1=implode($array,"%' $crit artifact.details LIKE '%");
                $words2=implode($array,"%' $crit artifact.summary LIKE '%");
                $words3=implode($array,"%' $crit artifact_history.new_value LIKE '%");
                
                $sql = "SELECT SQL_CALC_FOUND_ROWS artifact.artifact_id,
                               artifact.summary,
                               artifact.open_date,
                               user.user_name
                       FROM artifact INNER JOIN user ON user.user_id=artifact.submitted_by 
                          LEFT JOIN artifact_history ON artifact_history.artifact_id=artifact.artifact_id 
                          LEFT JOIN permissions ON (permissions.object_id = CAST(artifact.artifact_id AS CHAR) AND permissions.permission_type = 'TRACKER_ARTIFACT_ACCESS')
                       WHERE artifact.group_artifact_id='". db_ei($atid) ."' 
                         AND (
                               artifact.use_artifact_permissions = 0
                               OR 
                               (
                                   permissions.ugroup_id IN (". implode(',', $current_user->getUgroups($group_id,$atid)) .")
                               )
                         )
                         AND (
                               (artifact.details LIKE '%". db_es($words1) ."%') 
                               OR 
                               (artifact.summary LIKE '%". db_es($words2) ."%') 
                               OR 
                               (artifact_history.field_name='comment' AND (artifact_history.new_value LIKE '%". db_es($words3) ."%'))
                         ) 
                       GROUP BY open_date DESC, artifact.artifact_id DESC 
                       LIMIT ". db_ei($offset) .", 25";
                $result = db_query($sql);
                if ($rows_returned = db_result(db_query('SELECT FOUND_ROWS() as nb'), 0, 'nb')) {
                    
                    echo '<h3>'. $hp->purify(SimpleSanitizer::unsanitize($at_arr[$i]->getName()), CODENDI_PURIFIER_CONVERT_HTML) .'</h3>';
                    
                    $art_field_fact = new ArtifactFieldFactory($at_arr[$i]);
                    
                    $title_arr = array();
                    
                    $summary_field = $art_field_fact->getFieldFromName("summary");
                    if ($summary_field->userCanRead($group_id,$atid))
                        $title_arr[] = $Language->getText('search_index','artifact_summary');
                    $submitted_field = $art_field_fact->getFieldFromName("submitted_by");
                    if ($submitted_field->userCanRead($group_id,$atid))
                        $title_arr[] = $Language->getText('search_index','submitted_by');
                    $date_field = $art_field_fact->getFieldFromName("open_date");
                    if ($date_field->userCanRead($group_id,$atid))
                        $title_arr[] = $Language->getText('search_index','date');
                    $status_field = $art_field_fact->getFieldFromName("status_id");
                    if ($status_field->userCanRead($group_id,$atid))
                        $title_arr[] = $Language->getText('global','status');
    
                    echo html_build_list_table_top ($title_arr);
                    $art_displayed=0;
                    $rows=0;
                    while ($arr = db_fetch_array($result)) {
                        $rows++;
                        $curArtifact=new Artifact($at_arr[$i], $arr['artifact_id']);
                        if ($curArtifact->isStatusClosed($curArtifact->getStatusID())) {
                            $status=$Language->getText('global','closed');
                        } else {                        
                            $status=$Language->getText('global','open');
                        }
                        // Only display artifacts that the user is allowed to see
                        if ($curArtifact->userCanView()) {
                            print "\n<TR class=\"". html_get_alt_row_color($art_displayed) ."\">";
                            if ($summary_field->userCanRead($group_id,$atid)) print "<TD><A HREF=\"/tracker/?group_id=$group_id&func=detail&atid=$atid&aid="
                                . $arr['artifact_id']."\"><IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> "
                                . $arr['summary']."</A></TD>";
                            if ($submitted_field->userCanRead($group_id,$atid))
                                print "<TD>".$arr['user_name']."</TD>";
                            if ($date_field->userCanRead($group_id,$atid))
                                print "<TD>".format_date($GLOBALS['Language']->getText('system', 'datefmt'),$arr['open_date'])."</TD>";
                            if ($status_field->userCanRead($group_id,$atid))
                                print "<TD>".$status."</TD>";
                            print "</TR>";
                            $art_displayed++;
                            if ($art_displayed>24) { break; } // Only display 25 results.
                        }
                    }
                    echo "</table>\n";
                }
            }
        }
        
    } else {
        exit_no_group();
    }

} else if ($type_of_search == 'tracker') {

    //
    //      get the Group object
    //
    $pm = ProjectManager::instance();
    $group = $pm->getProject($group_id);
    if (!$group || !is_object($group) || $group->isError()) {
            exit_no_group();
    }
    //
    //      Create the ArtifactType object
    //
    $ath = new ArtifactTypeHtml($group,$atid);
    if (!$ath || !is_object($ath)) {
        exit_error($Language->getText('global','error'),$Language->getText('global','error'));
    }
    if ($ath->isError()) {
            exit_error($Language->getText('global','error'),$ath->getErrorMessage());
    }
    // Check if this tracker is valid (not deleted)
    if ( !$ath->isValid() ) {
            exit_error($Language->getText('global','error'),$Language->getText('global','error'));
    }
    
    // Create field factory
    $art_field_fact = new ArtifactFieldFactory($ath);
    
    $params=array('title'=>$group->getPublicName().': \''.$ath->getName().'\' '.$Language->getText('tracker_browse', 'search_report'),
          'titlevals'=>array($ath->getName()),
          'pagename'=>'tracker_browse',
          'atid'=>$ath->getID(),
          'sectionvals'=>array($group->getPublicName()),
          'pv'=>0,
          'help' => 'ArtifactBrowsing.html');

    $ath->header($params);
    echo '<div id="tracker_toolbar_clear"></div>';
        
    $array=explode(" ",$words);
    $words1=implode($array,"%' $crit artifact.details LIKE '%");
    $words2=implode($array,"%' $crit artifact.summary LIKE '%");
    $words3=implode($array,"%' $crit artifact_history.new_value LIKE '%");
    
    $sql = "SELECT SQL_CALC_FOUND_ROWS artifact.artifact_id,
                   artifact.summary,
                   artifact.open_date,
                   user.user_name
           FROM artifact INNER JOIN user ON user.user_id=artifact.submitted_by 
              LEFT JOIN artifact_history ON artifact_history.artifact_id=artifact.artifact_id 
              LEFT JOIN permissions ON (permissions.object_id = CAST(artifact.artifact_id AS CHAR) AND permissions.permission_type = 'TRACKER_ARTIFACT_ACCESS')
           WHERE artifact.group_artifact_id='". db_ei($atid) ."' 
             AND (
                   artifact.use_artifact_permissions = 0
                   OR 
                   (
                       permissions.ugroup_id IN (". implode(',', UserManager::instance()->getCurrentUser()->getUgroups($group_id,$atid)) .")
                   )
             )
             AND (
                   (artifact.details LIKE '%". db_es($words1) ."%') 
                   OR 
                   (artifact.summary LIKE '%". db_es($words2) ."%') 
                   OR 
                   (artifact_history.field_name='comment' AND (artifact_history.new_value LIKE '%". db_es($words3) ."%'))
             ) 
           GROUP BY open_date DESC 
           LIMIT ". db_ei($offset) .", 25";
    $result = db_query($sql);
    $rows_returned = db_result(db_query('SELECT FOUND_ROWS() as nb'), 0, 'nb');
	if ( !$result || $rows_returned < 1) {
		$no_rows = 1;
		echo '<H2>'.$Language->getText('search_index','no_match_found',htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8')).'</H2>';
		echo db_error();
	} else {

		echo '<H3>'.$Language->getText('search_index','search_res', array(htmlentities(stripslashes($words), ENT_QUOTES, 'UTF-8'), $rows_returned))."</H3><P>\n";

		$title_arr = array();
                
                $summary_field = $art_field_fact->getFieldFromName("summary");
                if ($summary_field->userCanRead($group_id,$atid))
                    $title_arr[] = $Language->getText('search_index','artifact_summary');
                $submitted_field = $art_field_fact->getFieldFromName("submitted_by");
                if ($submitted_field->userCanRead($group_id,$atid))
                    $title_arr[] = $Language->getText('search_index','submitted_by');
                $date_field = $art_field_fact->getFieldFromName("open_date");
                if ($date_field->userCanRead($group_id,$atid))
                    $title_arr[] = $Language->getText('search_index','date');
                $status_field = $art_field_fact->getFieldFromName("status_id");
                if ($status_field->userCanRead($group_id,$atid))
                    $title_arr[] = $Language->getText('global','status');

		echo html_build_list_table_top ($title_arr);

		echo "\n";


                $art_displayed=0;
                $rows=0;
                while ($arr = db_fetch_array($result)) {
                    $rows++;
                    $curArtifact=new Artifact($ath, $arr['artifact_id']);
                    if ($curArtifact->isStatusClosed($curArtifact->getStatusID())) {
                        $status=$Language->getText('global','closed');
                    } else {                        
                        $status=$Language->getText('global','open');
                    }
                    // Only display artifacts that the user is allowed to see
                    if ($curArtifact->userCanView(user_getid())) {
                        print	"\n<TR class=\"". html_get_alt_row_color($art_displayed) ."\">";
                        if ($summary_field->userCanRead($group_id,$atid)) print "<TD><A HREF=\"/tracker/?group_id=$group_id&func=detail&atid=$atid&aid="
                            . $arr['artifact_id']."\"><IMG SRC=\"".util_get_image_theme('msg.png')."\" BORDER=0 HEIGHT=12 WIDTH=10> "
                            . $arr['summary']."</A></TD>";
                        if ($submitted_field->userCanRead($group_id,$atid))
                            print "<TD>".$arr['user_name']."</TD>";
                        if ($date_field->userCanRead($group_id,$atid))
                            print "<TD>".format_date($GLOBALS['Language']->getText('system', 'datefmt'),$arr['open_date'])."</TD>";
                        if ($status_field->userCanRead($group_id,$atid))
                            print "<TD>".$status."</TD>";
                        print "</TR>";
                        $art_displayed++;
                        if ($art_displayed>24) { break; } // Only display 25 results.
                    }
                }
		echo "</TABLE>\n";
	}
} else if ($type_of_search == 'wiki') {
	//get the group-id
	$group_id = $_REQUEST['group_id'];
	//Wiki language extraction
	$sql =	"SELECT DISTINCT wiki_group_list.language_id"
		." FROM wiki_group_list"
		." WHERE wiki_group_list.group_id=".$group_id." AND"
        ."       wiki_group_list.language_id <> '0'";
	$result = db_query($sql);
    if (db_numrows($result) == 0) {
        // there is not any language specified for this wiki, default search page is english
        $search_page = 'FullTextSearch';
    } else {
        $language_id = db_fetch_array($result);
        //Build the search pagename in the wiki language
        if ($language_id[0]== 'en_US'){$search_page = 'FullTextSearch';}
        else if ($language_id[0] == 'fr_FR') {$search_page = 'RechercheEnTexteIntégral';}
    }
	$GLOBALS['sys_force_ssl'] = 1;
	util_return_to('/wiki/index.php?group_id='.$group_id.'&pagename='.$search_page.'&s='.urlencode($_REQUEST['words']));
} else {
    $matchingSearchTypeFound = false;
    $rows_returned = 0;
    $rows = 0;

    $eParams = array(
        'words'              => $_REQUEST['words'],
        'offset'             => $offset,
        'nbRows'             => 25,
        'type_of_search'     => $type_of_search,
        'search_type'        => &$matchingSearchTypeFound,
        'rows_returned'      => &$rows_returned,
        'rows'               => &$rows,
        'pagination_handled' => &$pagination_handled,
        'group_id'           => $request->get('group_id'),
    );

    $em =& EventManager::instance();
    $em->processEvent('search_type', $eParams);

    if(!$matchingSearchTypeFound) {
    	echo '<H1>'.$Language->getText('search_index','invalid_search').'</H1>';
    }
}

   // This code puts the nice next/prev.
if ( !$pagination_handled && !$no_rows && ( ($rows_returned > $rows) || ($offset != 0) ) ) {

	echo "<BR>\n";

	echo "<TABLE class=\"boxitem\" WIDTH=\"100%\" CELLPADDING=\"5\" CELLSPACING=\"0\">\n";
	echo "<TR>\n";
	echo "\t<TD ALIGN=\"left\">";
	if ($offset != 0) {
		echo "<span class=\"normal\"><B>";
		echo "<A HREF=\"javascript:history.back()\"><B><IMG SRC=\"".util_get_image_theme('t2.png')."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE> ".$Language->getText('search_index','prev_res')." </A></B></span>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n\t<TD ALIGN=\"right\">";
	if ( $rows_returned > $rows) {
		echo "<span class=\"normal\"><B>";
		echo "<A HREF=\"/search/?type_of_search=$type_of_search&words=".urlencode($words)."&offset=".($offset+$rows);
		if ( $type_of_search == 'bugs' ) {
			echo "&group_id=$group_id&is_bug_page=1";
		}
		if ( $type_of_search == 'forums' ) {
			echo "&forum_id=$forum_id&is_forum_page=1";
		}
                if ( $exact ) {
			echo "&exact=1";
		}
		if ( $type_of_search == 'tracker' ) {
			echo "&group_id=$group_id&atid=$atid";
		}
		echo "\"><B>".$Language->getText('search_index','next_res')." <IMG SRC=\"".util_get_image_theme('t.png')."\" HEIGHT=15 WIDTH=15 BORDER=0 ALIGN=MIDDLE></A></B></span>";
	} else {
		echo "&nbsp;";
	}
	echo "</TD>\n</TR>\n";
	echo "</TABLE>\n";
}


if (! $request->isAjax()) {
    if (($type_of_search !== "tracker" &&  $type_of_search !== "all_trackers" ) || !isset($ath)) {
        $HTML->footer(array());
    } else {
        $ath->footer(array());
    }
}
?>
