<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

// ################################## Trove Globals

$TROVE_MAXPERROOT = 3;
$TROVE_BROWSELIMIT = 20;
$TROVE_HARDQUERYLIMIT = 300;

// ##################################

// regenerates full path entries for $node and all subnodes
function trove_genfullpaths($mynode,$myfullpath,$myfullpathids) {
	// first generate own path
	$res_update = db_query('UPDATE trove_cat SET fullpath=\''
		.$myfullpath.'\',fullpath_ids=\''
		.$myfullpathids.'\' WHERE trove_cat_id='.$mynode);
	// now generate paths for all children by recursive call
	{
		$res_child = db_query('SELECT trove_cat_id,fullname FROM '
			.'trove_cat WHERE parent='.$mynode);
		while ($row_child = db_fetch_array($res_child)) {
			trove_genfullpaths($row_child['trove_cat_id'],
				$myfullpath.' :: '.$row_child['fullname'],
				$myfullpathids.' :: '.$row_child['trove_cat_id']);
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
		.'trove_cat_id='.$trove_cat_id);
	if (db_numrows($res_verifycat) != 1) return 1;
	$row_verifycat = db_fetch_array($res_verifycat);

	// if we didnt get a rootnode, find it
	if (!$rootnode) $rootnode = trove_getrootcat($trove_cat_id);

	// must first make sure that this is not a subnode of anything current
	$res_topnodes = db_query('SELECT trove_cat.trove_cat_id AS trove_cat_id,'
		.'trove_cat.fullpath_ids AS fullpath_ids FROM trove_cat,trove_group_link '
		.'WHERE trove_cat.trove_cat_id=trove_group_link.trove_cat_id AND '
		.'trove_group_link.group_id='.$group_id.' AND '
		.'trove_cat.root_parent='.$rootnode);
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
		.'group_id='.$group_id.' AND trove_cat_root='.$rootnode);
	while ($row_checksubs = db_fetch_array($res_checksubs)) {
		// check against all subnodeids
		for ($i=0;$i<count($subnodeids);$i++) {
			if ($subnodeids[$i] == $row_checksubs['trove_cat_id']) {
				// then delete subnode
				db_query('DELETE FROM trove_group_link WHERE '
					.'group_id='.$group_id.' AND trove_cat_id='
					.$subnodeids[$i]);
			}
		}
	}

	// if we got this far, must be ok
	db_query('INSERT INTO trove_group_link (trove_cat_id,trove_cat_version,'
		.'group_id,trove_cat_root) VALUES ('.$trove_cat_id.','
		.time().','.$group_id.','.$rootnode.')');
	return 0;
}

function trove_getrootcat($trove_cat_id) {
	$parent = 1;
	$current_cat = $trove_cat_id;

	while ($parent > 0) {
		$res_par = db_query("SELECT parent FROM trove_cat WHERE "
			."trove_cat_id=$current_cat");
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

// returns full select output for a particular root
function trove_catselectfull($node,$selected,$name) {
	print "<BR><SELECT name=\"$name\">";
	print '  <OPTION value="0">None Selected'."\n";
	$res_cat = db_query('SELECT trove_cat_id,fullpath FROM trove_cat WHERE '
		.'root_parent='.$node.' ORDER BY fullpath');
	while ($row_cat = db_fetch_array($res_cat)) {
		print '  <OPTION value="'.$row_cat['trove_cat_id'].'"';
		if ($selected == $row_cat['trove_cat_id']) print (' selected');
		print '>'.$row_cat['fullpath']."\n";
	}
	print "</SELECT>\n";
}

// ###############################################################
// gets discriminator listing for a group

function trove_getcatlisting($group_id,$a_filter,$a_cats) {
	global $discrim_url;
	global $expl_discrim;
	global $form_cat;

	$res_trovecat = db_query('SELECT trove_cat.fullpath AS fullpath,'
		.'trove_cat.fullpath_ids AS fullpath_ids,'
		.'trove_cat.trove_cat_id AS trove_cat_id '
		.'FROM trove_cat,trove_group_link WHERE trove_cat.trove_cat_id='
		.'trove_group_link.trove_cat_id AND trove_group_link.group_id='
		.$group_id.' '
		.'ORDER BY trove_cat.fullpath');

	if (db_numrows($res_trovecat) < 1) {
		print 'This project has not yet categorized itself in the '
			.'<A href="/softwaremap/trove_list.php">Trove '
			.'Software Map</A>.';
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
		if (!$proj_discrim_used[$folders_ids[0]]) {
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
			if ($proj_discrim_used[$folders_ids[0]]) print ', ';

			if ($a_cats) print '<A href="/softwaremap/trove_list.php?form_cat='
				.$folders_ids[$folders_len-1].$discrim_url.'">';
			print ($folders[$folders_len-1]);
			if ($a_cats) print '</A>';

			if ($a_filter) {
				if ($filterisalreadyapplied) {
					print ' (Now Filtering) ';
				} else {
					print ' <A href="/softwaremap/trove_list.php?form_cat='
						.$form_cat;
					if ($discrim_url) {
						print $discrim_url.','.$folders_ids[$folders_len-1];
					} else {
						print '&discrim='.$folders_ids[$folders_len-1];
					}
					print '">[Filter]</A> ';
				}
			}
		$proj_discrim_used[$folders_ids[0]] = 1;
		$isfirstdiscrim = 0;
	}
	echo '</UL>';
}

// returns cat fullname
function trove_getfullname($node) {
	$res = db_query('SELECT fullname FROM trove_cat WHERE trove_cat_id='.$node);
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
			.'WHERE trove_cat_id='.$currentcat);
		$row = db_fetch_array($res);
		$return = $row["fullname"] . ($first?"":" :: ") . $return;
		$currentcat = $row["parent"];
		$first = 0;
	}
	return $return;
}

?>
