<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

if (!$group_id) {
    exit_no_group(); // need a group_id !!!
}

commits_header(array ('title'=>'CVS Commit '.$commit_id.' - Details',
		      'help' => 'CVSWebInterface.html#QueryingCVS'));

$order_str = "";

if ($order) {
  if ($order != 'filename')
    $order_str = " ORDER BY ".$order;
  else
    $order_str = " ORDER BY dir, file";
}

$when_str = '';

$id_str = "AND cvs_checkins.descid='$checkin_id' ";
if ($commit_id) {
  $id_str = "AND cvs_checkins.commitid='$commit_id' ";
  if ($desc_id) {
    $id_str = $id_str . "AND cvs_checkins.descid='$desc_id' ";
  }
}

if ($when) {
  $when_str = "AND cvs_checkins.ci_when='$when' ";
}
if ($tag) {
  $when_str = $when_str."AND cvs_checkins.stickytag='$tag' ";
}
$sql="SELECT distinct repository, IF (cvs_checkins.commitid > 0, cvs_commits.comm_when, cvs_checkins.ci_when) as c_when, repositoryid, description, file, fileid, dir, dirid, type, branch, revision, addedlines, removedlines ".
	"FROM cvs_dirs, cvs_descs, cvs_files, cvs_checkins, cvs_branches, cvs_repositories, cvs_commits ".
	"WHERE cvs_checkins.fileid=cvs_files.id ".
	"AND cvs_checkins.dirid=cvs_dirs.id ".
	"AND (cvs_checkins.commitid='0' OR cvs_checkins.commitid=cvs_commits.id) ".
	"AND cvs_checkins.branchid=cvs_branches.id ".
        "AND cvs_checkins.descid=cvs_descs.id ".
	"AND cvs_checkins.repositoryid=cvs_repositories.id ".
	$id_str.
        $when_str.$order_str;


$result=db_query($sql);

if (db_numrows($result) > 0) {
    echo show_commit_details($result);
} else {
	echo '
		<H1>Commit details not found for '.$commit_id.' ID</H1>
        ';
}

commits_footer(array());

?>
