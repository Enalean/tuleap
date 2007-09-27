<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require_once('pre.php');    
require_once('vars.php');
require_once('trove.php');

$Language->loadLanguageMsg('softwaremap/softwaremap');

// assign default. 18 is 'topic'
if (!isset($form_cat)) {
    if (isset($GLOBALS['sys_default_trove_cat'])) $form_cat = $GLOBALS['sys_default_trove_cat'];
    else $form_cat = 18;
 }

$form_cat = intval($form_cat);

// get info about current folder
$res_trove_cat = db_query('SELECT * FROM trove_cat WHERE trove_cat_id='.$form_cat);
if (db_numrows($res_trove_cat) < 1) {
	echo db_error();
	exit_error($Language->getText('softwaremap_trove_list','invalid_cat'),$Language->getText('softwaremap_trove_list','cat_not_exist'));
}
$row_trove_cat = db_fetch_array($res_trove_cat);


$HTML->header(array('title'=>$Language->getText('softwaremap_trove_list','map')));
echo'
	<h2>'.$Language->getText('softwaremap_trove_list','map').' '.help_button('TheCodeXMainMenu.html#SoftwareMap').'</h2>
	<HR NoShade>
';


// #####################################
// this section limits search and requeries if there are discrim elements

unset ($discrim_url);
unset ($discrim_desc);

if (isset($discrim)) {
        $discrim_queryalias='';
        $discrim_queryand='';
        $discrim_url_b='';

	// commas are ANDs
	$expl_discrim = explode(',',$discrim);

	// need one link for each "get out of this limit" links
	$discrim_url = '&discrim=';

	$lims=sizeof($expl_discrim);
	if ($lims > 2) {
		$lims=2;
	}

	// one per argument	
	for ($i=0;$i<$lims;$i++) {
		// make sure these are all ints, no url trickery
		$expl_discrim[$i] = intval($expl_discrim[$i]);

		// need one aliased table for everything
		$discrim_queryalias .= ',trove_group_link trove_group_link_'.$i.' ';
		
		// need additional AND entries for aliased tables
		$discrim_queryand .= 'AND trove_group_link_'.$i.'.trove_cat_id='
			.$expl_discrim[$i].' AND trove_group_link_'.$i.'.group_id='
			.'groups.group_id ';

		// must build query string for all urls
		if ($i==0) {
			$discrim_url .= $expl_discrim[$i];
		} else {
			$discrim_url .= ','.$expl_discrim[$i];
		}
		// must also do this for EACH "get out of this limit" links
		// convoluted logic to build urls for these, but works quickly
		for ($j=0;$j<sizeof($expl_discrim);$j++) {
			if ($i!=$j) {
				if (!$discrim_url_b[$j]) {
					$discrim_url_b[$j] = '&discrim='.$expl_discrim[$i];
				} else {
					$discrim_url_b[$j] .= ','.$expl_discrim[$i];
				}
			}
		}

	}

	// build text for top of page on what viewier is seeing
	$discrim_desc = '<FONT size="-1">
<span class="highlight">
'.$Language->getText('softwaremap_trove_list','limit_view').'
</span>';
	
	for ($i=0;$i<sizeof($expl_discrim);$i++) {
		$discrim_desc .= '<BR> &nbsp; &nbsp; &nbsp; '
			.trove_getfullpath($expl_discrim[$i])
			.' <A href="/softwaremap/trove_list.php?form_cat='.$form_cat
			.$discrim_url_b[$i].'">['.$Language->getText('softwaremap_trove_list','remove_view').']'
			.'</A>';
	}
	$discrim_desc .= "<HR></FONT>\n";
} 

// #######################################

if (!isset($discrim_desc)) $discrim_desc="";
print '<P>'.$discrim_desc;

// ######## two column table for key on right
// first print all parent cats and current cat
print '<TABLE width=100% border="0" cellspacing="0" cellpadding="0">
<TR valign="top"><TD>';
$folders = explode(" :: ",$row_trove_cat['fullpath']);
$folders_ids = explode(" :: ",$row_trove_cat['fullpath_ids']);
$folders_len = count($folders);
for ($i=0;$i<$folders_len;$i++) {
	for ($sp=0;$sp<($i*2);$sp++) {
		print " &nbsp; ";
	}
	html_image("ic/ofolder15.png",array());
	print "&nbsp; ";
	// no anchor for current cat
	if ($folders_ids[$i] != $form_cat) {
            if (!isset($discrim_url)) $discrim_url="";
            print '<A href="/softwaremap/trove_list.php?form_cat='
			.$folders_ids[$i].$discrim_url.'">';
	} else {
		print '<B>';
	}
	print $folders[$i];
	if ($folders_ids[$i] != $form_cat) {
		print '</A>';
	} else {
		print '</B>';
	}
	print "<BR>\n";
}

// print subcategories
/*
t3 = SELECT t.trove_cat_id AS trove_cat_id, count(t.group_id) AS nb
FROM trove_group_link AS t INNER JOIN groups AS g USING(group_id)
WHERE g.is_public = 1
  AND g.status = 'A'
  AND g.type = 1
GROUP BY trove_cat_id

=> number of projects under each category (no sublevels)

*************

t = SELECT t.trove_cat_id FROM trove_cat AS t WHERE t.parent = 18

=> All direct (one level) subcategories under 18

*************

t2 = SELECT ... 
FROM trove_cat AS t, trove_cat AS t2
WHERE t2.fullpath_ids LIKE CONCAT(t.trove_cat_id, ' ::%')
   OR t2.fullpath_ids LIKE CONCAT('%:: ', t.trove_cat_id, ' ::%')
   OR t2.fullpath_ids LIKE t.trove_cat_id
   OR t2.fullpath_ids LIKE CONCAT('%:: ', t.trove_cat_id)

=> All subcategories (deep) of a category

*************

=> Select direct (one level) subcategories under 18, then select their 
   subcategories (deep), retrieve the number of projects for each. group and sum.
*/$sql = "SELECT t.trove_cat_id AS trove_cat_id, t.fullname AS fullname, SUM(IFNULL(t3.nb, 0)) AS subprojects 
FROM trove_cat AS t, trove_cat AS t2 LEFT JOIN (SELECT t.trove_cat_id AS trove_cat_id, count(t.group_id) AS nb
FROM trove_group_link AS t INNER JOIN groups AS g USING(group_id)
WHERE g.is_public = 1
  AND g.status = 'A'
  AND g.type = 1
GROUP BY trove_cat_id) AS t3 USING(trove_cat_id)
WHERE t.parent = $form_cat
  AND (
      t2.fullpath_ids LIKE CONCAT(t.trove_cat_id, ' ::%')
   OR t2.fullpath_ids LIKE CONCAT('%:: ', t.trove_cat_id, ' ::%')
   OR t2.fullpath_ids LIKE t.trove_cat_id
   OR t2.fullpath_ids LIKE CONCAT('%:: ', t.trove_cat_id)
      )
GROUP BY t.trove_cat_id
ORDER BY fullname";
$res_sub = db_query($sql);
echo db_error();
$nb_listed_projects=0;
while ($row_sub = db_fetch_array($res_sub)) {
	for ($sp=0;$sp<($folders_len*2);$sp++) {
		print " &nbsp; ";
	}
        if (!isset($discrim_url)) $discrim_url="";
	print ('<a href="trove_list.php?form_cat='.$row_sub['trove_cat_id'].$discrim_url.'">');
	html_image("ic/cfolder15.png",array());
        $nb_proj_in_cat=($row_sub['subprojects']?$row_sub['subprojects']:'0');
        $nb_listed_projects+=$nb_proj_in_cat;
	print ('&nbsp; '.$row_sub['fullname'].'</a> <I>('
		.$nb_proj_in_cat
		.' '.$Language->getText('softwaremap_trove_list','projs').')</I><BR>');
}

// MV: Add a None case
if($folders_len == 1) {
    $sql = "SELECT count(DISTINCT g.group_id) AS count
FROM groups AS g
LEFT JOIN trove_group_link AS t
USING ( group_id )
WHERE is_public =1
AND STATUS = 'A'
AND TYPE =1
AND trove_cat_root = ". $form_cat;
    $res_nb = db_query($sql);
    $row_nb = db_fetch_array($res_nb);

    $res_total = db_query("SELECT count(*) as count FROM groups WHERE is_public=1 AND status='A' and type=1");
    $row_total = db_fetch_array($res_total);
    $nb_not_cat=$row_total['count']-$row_nb['count'];
    for ($sp=0;$sp<($folders_len*2);$sp++) {
        print " &nbsp; ";
    }
    html_image("ic/cfolder15.png",array());
    print "&nbsp; ";

    print '<a href="/softwaremap/trove_list.php?form_cat='.$form_cat.'&special_cat=none"><em>'.$Language->getText('softwaremap_trove_list','not_categorized').'</em></a> <I>('.$nb_not_cat.' '.$Language->getText('softwaremap_trove_list','projs').')</I><BR>';

    print "<br />";
}

// ########### right column: root level
print '</TD><TD>';
// here we print list of root level categories, and use open folder for current
$res_rootcat = db_query('SELECT trove_cat_id,fullname FROM trove_cat WHERE '
	.'parent=0 ORDER BY fullname');
echo db_error();
print $Language->getText('softwaremap_trove_list','browse_by');
while ($row_rootcat = db_fetch_array($res_rootcat)) {
	// print open folder if current, otherwise closed
	// also make anchor if not current
	print ('<BR>');
    if (!isset($discrim_url)) $discrim_url="";
	if (($row_rootcat['trove_cat_id'] == $row_trove_cat['root_parent'])
		|| ($row_rootcat['trove_cat_id'] == $row_trove_cat['trove_cat_id'])) {
		html_image('ic/ofolder15.png',array());
		print ('&nbsp; <B>'.$row_rootcat['fullname']."</B>\n");
	} else {
		print ('<A href="/softwaremap/trove_list.php?form_cat='
			.$row_rootcat['trove_cat_id'].$discrim_url.'">');
		html_image('ic/cfolder15.png',array());
		print ('&nbsp; '.$row_rootcat['fullname']."\n");
		print ('</A>');
	}
}
print '</TD></TR></TABLE>';
?>
<HR noshade>
<?php
// one listing for each project

//BAD QUERY!!!

if((isset($_GET['special_cat'])) && ($_GET['special_cat'] == 'none')) {
    $qry_root_trov = 'SELECT group_id'
        .' FROM trove_group_link'
        .' WHERE trove_cat_root='.$form_cat
        .' GROUP BY group_id';
    $res_root_trov = db_query($qry_root_trov);

    $prj_list_categorized = array();
    while($row_root_trov = db_fetch_array($res_root_trov)) {
        $prj_list_categorized[] = $row_root_trov['group_id'];
    }

    $sql_list_categorized='';
    if(count($prj_list_categorized) > 0) {
        $sql_list_categorized=' AND groups.group_id NOT IN ('.implode(',', $prj_list_categorized).') ';
    }
    $query_projlist = "SELECT groups.group_id, "
        . "groups.group_name, "
        . "groups.unix_group_name, "
        . "groups.status, "
        . "groups.register_time, "
        . "groups.short_description, "
        . "project_metric.percentile, "
        . "project_metric.ranking "
        . "FROM groups "
        . "LEFT JOIN project_metric USING (group_id) "
        . "WHERE "
        . "(groups.is_public=1) AND "
    . "(groups.type=1) AND "
        . "(groups.status='A') "
        . $sql_list_categorized
        . "GROUP BY groups.group_id ORDER BY groups.group_name "
        . "LIMIT ".$TROVE_HARDQUERYLIMIT;
}
else {
// now do limiting query
    if (!isset($discrim_queryalias)) $discrim_queryalias="";
    if (!isset($discrim_queryand)) $discrim_queryand="";

$query_projlist = "SELECT groups.group_id, "
	. "groups.group_name, "
	. "groups.unix_group_name, "
	. "groups.status, "
	. "groups.register_time, "
	. "groups.short_description, "
	. "project_metric.percentile, "
	. "project_metric.ranking "
	. "FROM groups "
	. "LEFT JOIN project_metric USING (group_id) "
	. ", trove_group_link "
	. $discrim_queryalias
	. "WHERE trove_group_link.group_id=groups.group_id AND "
	. "(groups.is_public=1) AND "
        . "(groups.type=1) AND "
	. "(groups.status='A') AND "
	. "trove_group_link.trove_cat_id=$form_cat "
	. $discrim_queryand
	. "GROUP BY groups.group_id ORDER BY groups.group_name "
	. "LIMIT ".$TROVE_HARDQUERYLIMIT;
}

$res_grp = db_query($query_projlist);
echo db_error();
$querytotalcount = db_numrows($res_grp);
	
// #################################################################
// limit/offset display

// no funny stuff with get vars
if (!isset($page)) $page=1;
$page = intval($page);
if (!$page) {
	$page = 1;
}

// store this as a var so it can be printed later as well
$html_limit = '<SPAN><CENTER><FONT size="-1">';
if ($querytotalcount ==0) {
    $html_limit .= $Language->getText('softwaremap_trove_list','no_project_in_cat')."<br>\n";
}
else {
if ($querytotalcount == $TROVE_HARDQUERYLIMIT)
     $html_limit .= $Language->getText('softwaremap_trove_list','more_projs_in_res',$querytotalcount);
else
     $html_limit .= $Language->getText('softwaremap_trove_list','projs_in_res',$querytotalcount);

// only display pages stuff if there is more to display
if ($querytotalcount > $TROVE_BROWSELIMIT) {
	$html_limit .= ' '.$Language->getText('softwaremap_trove_list','display_per_page',$TROVE_BROWSELIMIT);

	// display all the numbers
	for ($i=1;$i<=ceil($querytotalcount/$TROVE_BROWSELIMIT);$i++) {
		$html_limit .= ' ';
		if ($page != $i) {
			$html_limit .= '<A href="/softwaremap/trove_list.php?form_cat='.$form_cat;
			$html_limit .= $discrim_url.'&page='.$i;
                        if (isset($special_cat)) $html_limit .= "&special_cat=".$special_cat;
			$html_limit .= '">';
		} else $html_limit .= '<B>';
		$html_limit .= '&lt;'.$i.'&gt;';
		if ($page != $i) {
			$html_limit .= '</A>';
		} else $html_limit .= '</B>';
		$html_limit .= ' ';
	}
}
}
$html_limit .= '</FONT></CENTER></SPAN>';

print $html_limit."<HR>\n";

// #################################################################
// print actual project listings
// note that the for loop starts at 1, not 0
for ($i_proj=1;$i_proj<=$querytotalcount;$i_proj++) { 
	$row_grp = db_fetch_array($res_grp);

	// check to see if row is in page range
	if (($i_proj > (($page-1)*$TROVE_BROWSELIMIT)) && ($i_proj <= ($page*$TROVE_BROWSELIMIT))) {
		$viewthisrow = 1;
	} else {
		$viewthisrow = 0;
	}	

	if ($row_grp && $viewthisrow) {
		print '<TABLE border="0" cellpadding="0" width="100%"><TR valign="top"><TD colspan="2">';
		print "$i_proj. <a href=\"/projects/". strtolower($row_grp['unix_group_name']) ."/\"><B>"
			.htmlspecialchars($row_grp['group_name'])."</B></a> ";
		if ($row_grp['short_description']) {
			print "- " . htmlentities($row_grp['short_description'], ENT_QUOTES);
		}

		print '<BR>&nbsp;';
		// extra description
		print '</TD></TR><TR valign="top"><TD>';
		// list all trove categories
		trove_getcatlisting($row_grp['group_id'],1,0);

		print '</TD>'."\n".'<TD align="right">'; // now the right side of the display
		print $Language->getText('softwaremap_trove_list','activity_percentile').' <B>'.$row_grp['percentile'].'</B>';
		print '<BR>'.$Language->getText('softwaremap_trove_list','activity_ranking').' <B>'.$row_grp['ranking'].'</B>';
		print '<BR>'.$Language->getText('softwaremap_trove_list','register_date').' <B>'.format_date($sys_datefmt,$row_grp['register_time']).'</B>';
		print '</TD></TR></TABLE>';
		print '<HR>';
	} // end if for row and range chacking
}

// print bottom navigation if there are more projects to display
if ($querytotalcount > $TROVE_BROWSELIMIT) {
	print $html_limit;
}

// print '<P><FONT size="-1">This listing was produced by the following query: '
//	.$query_projlist.'</FONT>';

$HTML->footer(array());

?>
