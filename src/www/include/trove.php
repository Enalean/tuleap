<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

// ################################## Trove Globals

$TROVE_MAXPERROOT = 3;
$TROVE_BROWSELIMIT = 20;
$TROVE_HARDQUERYLIMIT = 300;

// regenerates full path entries for $node and all subnodes
function trove_genfullpaths($mynode,$myfullpath,$myfullpathids) {
	// first generate own path
	$res_update = db_query('UPDATE trove_cat SET fullpath=\''
		.db_es($myfullpath).'\',fullpath_ids=\''
		.db_es($myfullpathids).'\' WHERE trove_cat_id='.db_ei($mynode));
	// now generate paths for all children by recursive call
	{
		$res_child = db_query('SELECT trove_cat_id,fullname FROM '
			.'trove_cat WHERE parent='.db_ei($mynode));
		while ($row_child = db_fetch_array($res_child)) {
		  //for the root node everything works a bit different ...
		  if (!$mynode) {
		    trove_genfullpaths($row_child['trove_cat_id'],
				$row_child['fullname'],
				$row_child['trove_cat_id']);
		  } else {
			trove_genfullpaths($row_child['trove_cat_id'],
				$myfullpath.' :: '.$row_child['fullname'],
				$myfullpathids.' :: '.$row_child['trove_cat_id']);
		  }
		}
	}
}

// #########################################

// adds a group to a trove node
function trove_setnode($group_id,$trove_cat_id,$rootnode=0) {
	// verify we were passed information
	if ((!$group_id) || (!$trove_cat_id)) return 1;

	// verify trove category exists
	$res_verifycat = db_query('SELECT trove_cat_id,fullpath_ids FROM trove_cat WHERE '
		.'trove_cat_id='.db_ei($trove_cat_id));
	if (db_numrows($res_verifycat) != 1) return 1;
	$row_verifycat = db_fetch_array($res_verifycat);

	// if we didnt get a rootnode, find it
	if (!$rootnode) $rootnode = trove_getrootcat($trove_cat_id);

	// must first make sure that this is not a subnode of anything current
	$res_topnodes = db_query('SELECT trove_cat.trove_cat_id AS trove_cat_id,'
		.'trove_cat.fullpath_ids AS fullpath_ids FROM trove_cat,trove_group_link '
		.'WHERE trove_cat.trove_cat_id=trove_group_link.trove_cat_id AND '
		.'trove_group_link.group_id='.db_ei($group_id).' AND '
		.'trove_cat.root_parent='.db_ei($rootnode));
	while($row_topnodes = db_fetch_array($res_topnodes)) {
		$pathids = explode(' :: ',$row_topnodes['fullpath_ids']);
		for ($i=0;$i<count($pathids);$i++) {
			// anything here will invalidate this setnode
			if ($pathids[$i] == $trove_cat_id) {
				return 1;
			}
		}
	}

	// need to see if this one is more specific than another
	// if so, delete the other and proceed with this insertion
	$subnodeids = explode(' :: ',$row_verifycat['fullpath_ids']);
	$res_checksubs = db_query('SELECT trove_cat_id FROM trove_group_link WHERE '
		.'group_id='.db_ei($group_id).' AND trove_cat_root='.db_ei($rootnode));
	while ($row_checksubs = db_fetch_array($res_checksubs)) {
		// check against all subnodeids
		for ($i=0;$i<count($subnodeids);$i++) {
			if ($subnodeids[$i] == $row_checksubs['trove_cat_id']) {
				// then delete subnode
				db_query('DELETE FROM trove_group_link WHERE '
					.'group_id='.db_ei($group_id).' AND trove_cat_id='
					.db_ei($subnodeids[$i]));
			}
		}
	}

	// if we got this far, must be ok
	db_query('INSERT INTO trove_group_link (trove_cat_id,trove_cat_version,'
		.'group_id,trove_cat_root) VALUES ('.db_ei($trove_cat_id).','
		.time().','.db_ei($group_id).','.db_ei($rootnode).')');
	return 0;
}

function trove_getrootcat($trove_cat_id) {
	$parent = 1;
	$current_cat = $trove_cat_id;

	while ($parent > 0) {
		$res_par = db_query("SELECT parent FROM trove_cat WHERE "
       		."trove_cat_id=".db_ei($current_cat));
		$row_par = db_fetch_array($res_par);
		$parent = $row_par["parent"];
		if ($parent == 0) return $current_cat;
		$current_cat = $parent;
	}

	return 0;
}

// returns an associative array of all project roots
function trove_getallroots() {
	$res = db_query('SELECT trove_cat_id,fullname FROM trove_cat '
		.'WHERE parent=0');
	while ($row = db_fetch_array($res)) {
		$tmpcatid = $row["trove_cat_id"];
		$CATROOTS[$tmpcatid] = $row["fullname"];
	}
	return $CATROOTS;
}

// returns HTML code for full select output for a particular root
function trove_get_html_cat_selectfull($node,$selected,$name) {
    global $Language;
	$html = "";
    $html .= '<BR><SELECT name="'. $name .'">';
	$html .= '  <OPTION value="0">'.$Language->getText('include_trove','none_selected')."\n";
	$res_cat = db_query('SELECT trove_cat_id,fullpath FROM trove_cat WHERE '
		.'root_parent='.db_ei($node).' ORDER BY fullpath');
	while ($row_cat = db_fetch_array($res_cat)) {
		$html .= '  <OPTION value="'.$row_cat['trove_cat_id'].'"';
		if ($selected == $row_cat['trove_cat_id']) $html .= (' selected');
		$html .= '>'.$row_cat['fullpath']."\n";
	}
	$html .= "</SELECT>\n";
    return $html;
}

function trove_get_html_cat_select_parent($selected = 0, $ignore_fullpath = false) {
    global $Language;
	$html = "";
    $html .= '<BR><SELECT name="form_parent">';
	$html .= '  <OPTION value="0">'.$Language->getText('admin_trove_cat_edit','root')."\n";
    $sql = "SELECT trove_cat_id,fullpath FROM trove_cat ";
    if ($ignore_fullpath) {
        $sql .= " WHERE fullpath NOT LIKE '".db_escape_string($ignore_fullpath)." ::%' AND fullpath NOT LIKE '".db_escape_string($ignore_fullpath)."' ";
    }
    $sql .= " ORDER BY fullpath";
	$res_cat = db_query($sql);
	while ($row_cat = db_fetch_array($res_cat)) {
		$html .= '  <OPTION value="'.$row_cat['trove_cat_id'].'"';
		if ($selected == $row_cat['trove_cat_id']) $html .= (' selected');
		$html .= '>'.$row_cat['fullpath']."\n";
	}
	$html .= "</SELECT>\n";
    return $html;
}

/**
 * returns the html code for the full select 
 * for all categories, for a specific group
 *
 * @param int group_id the group for categorization
 * @return string html code for the full select
 */
function trove_get_html_allcat_selectfull($group_id) {
    $html = "";
    $CATROOTS = trove_getallroots();
    while (list($catroot,$fullname) = each($CATROOTS)) {
        $html .= "\n<HR>\n<P><B>$fullname</B> ".help_button('trove_cat',$catroot)."\n";
    
        $res_grpcat = db_query('SELECT trove_cat_id FROM trove_group_link WHERE '.
                               'group_id='.db_ei($group_id).' AND trove_cat_root='.db_ei($catroot).
                               ' ORDER BY trove_group_id');
        for ($i=1;$i<=$GLOBALS['TROVE_MAXPERROOT'];$i++) {
            // each drop down, consisting of all cats in each root
            $name= "root$i"."[$catroot]";
            // see if we have one for selection
            if ($row_grpcat = db_fetch_array($res_grpcat)) {
                $selected = $row_grpcat["trove_cat_id"];	
            } else {
                $selected = 0;
            }
            $html .= trove_get_html_cat_selectfull($catroot,$selected,$name);
        }
    }
    return $html;
}


// ###############################################################
// gets discriminator listing for a group

function trove_getcatlisting($group_id,$a_filter,$a_cats) {
	global $discrim_url;
	global $expl_discrim;
	global $form_cat;
	global $Language;

	$res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
		.'trove_cat.fullpath_ids AS fullpath_ids,'
		.'trove_cat.trove_cat_id AS trove_cat_id '
		.'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
		.'trove_group_link.trove_cat_id AND trove_group_link.group_id='
		.db_ei($group_id).' '
		.'ORDER BY trove_group_link.trove_group_id');

// LJ Added a link to the categorization admin page
// LJ in case the project is not yet categorized

	if (db_numrows($res_trovecat) < 1) {
	  print $Language->getText('include_trove','not_categorized_yet',array('/softwaremap/trove_list.php',"/project/admin/group_trove.php?group_id=$group_id"));
	}

	// first unset the vars were using here
	$proj_discrim_used='';
	$isfirstdiscrim = 1;
	echo '<UL>';
	while ($row_trovecat = db_fetch_array($res_trovecat)) {
		$folders = explode(" :: ",$row_trovecat['fullpath']);
		$folders_ids = explode(" :: ",$row_trovecat['fullpath_ids']);
		$folders_len = count($folders);
		// if first in discrim print root category
		if ((!isset($proj_discrim_used[$folders_ids[0]]))||(!$proj_discrim_used[$folders_ids[0]])) {
			if (!$isfirstdiscrim) print '<BR>';
				print ('<LI> '.$folders[0].': ');
		}

		// filter links, to add discriminators
		// first check to see if filter is already applied
		$filterisalreadyapplied = 0;
		for ($i=0;$i<sizeof($expl_discrim);$i++) {
			if ($folders_ids[$folders_len-1] == $expl_discrim[$i])
				$filterisalreadyapplied = 1;
			}
			// then print the stuff
                        if ((isset($proj_discrim_used[$folders_ids[0]]))&&($proj_discrim_used[$folders_ids[0]])) {
                            print ', ';
                        }

			if ($a_cats) print '<A href="/softwaremap/trove_list.php?form_cat='
				.$folders_ids[$folders_len-1].$discrim_url.'">';
			print ($folders[$folders_len-1]);
			if ($a_cats) print '</A>';

			if ($a_filter) {
				if ($filterisalreadyapplied) {
					print ' ('.$Language->getText('include_trove','now_filter').') ';
				} else {
					print ' <A href="/softwaremap/trove_list.php?form_cat='
						.$form_cat;
					if ($discrim_url) {
						print $discrim_url.','.$folders_ids[$folders_len-1];
					} else {
						print '&discrim='.$folders_ids[$folders_len-1];
					}
					print '">['.$Language->getText('include_trove','filter').']</A> ';
				}
			}
		$proj_discrim_used[$folders_ids[0]] = 1;
		$isfirstdiscrim = 0;
	}
	echo '</UL>';
}

// returns cat fullname
function trove_getfullname($node) {
	$res = db_query('SELECT fullname FROM trove_cat WHERE trove_cat_id='.db_ei($node));
	$row = db_fetch_array($res);
	return $row['fullname'];
}

// returns a full path for a trove category
function trove_getfullpath($node) {
	$currentcat = $node;
	$first = 1;
	$return = '';

	while ($currentcat > 0) {
		$res = db_query('SELECT trove_cat_id,parent,fullname FROM trove_cat '
			.'WHERE trove_cat_id='.db_ei($currentcat));
		$row = db_fetch_array($res);
		$return = $row["fullname"] . ($first?"":" :: ") . $return;
		$currentcat = $row["parent"];
		$first = 0;
	}
	return $return;
}

?>
