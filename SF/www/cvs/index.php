<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    

//only projects can use the bug tracker, and only if they have it turned on
$project=project_get_object($group_id);

if (!$project->isProject()) {
	exit_error('Error','Only Projects Can Use CVS');
}
if (!$project->usesCVS()) {
	exit_error('Error','This Project Has Turned Off CVS');
}


site_project_header(array('title'=>'CVS Repository','group'=>$group_id,'toptab'=>'cvs'));

$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

$row_grp = db_fetch_array($res_grp);

// ######################## table for summary info

print '<TABLE width="100%"><TR valign="top"><TD width="65%">'."\n";

// ######################## anonymous CVS instructions

// LJ No anonymous access anymore on CodeX
// LJ if ($row_grp['is_public']) {
if (0) {
	print '<h2>CVS Access '.help_button('VersionControlWithCVS.html').'</h2>
<P>The CVS repository of this project can be checked out through anonymous
(pserver) CVS with the following instruction set. The module you wish
to check out must be specified as the <I>modulename</I>. When prompted
for a password for <I>anonymous</I>, simply press the Enter key.

<P><FONT size="-1" face="courier">cvs -d:pserver:anonymous@cvs.'.$row_grp['http_domain'].':/cvsroot/'.$row_grp['unix_group_name'].' login
<BR>&nbsp;<BR>cvs -d:pserver:anonymous@cvs.'.$row_grp['http_domain'].':/cvsroot/'.$row_grp['unix_group_name'].' co <I>modulename</I>
</FONT>

<P>Updates from within the module\'s directory do not need the -d parameter.';
}

// ############################ developer access

print '<h2>CVS Access '.help_button('VersionControlWithCVS.html').'</h2>
<P>CVS read-only  access is granted to all '.$GLOBALS['sys_name'].' registered users. Anonymous users do not have access to the CVS tree, because many '.$GLOBALS['sys_name'].' projects want to know who is in their community of users. Users desiring read access to project 
CVS trees should register on '.$GLOBALS['sys_name'].'. 
<P>Any registered and logged-in '.$GLOBALS['sys_name'].' user has read access to the CVS tree.  Project members (the developers who are part of the core team) are granted read (checkout) and write (commit) access to the CVS tree. Below are the typical commands you would use to login into the CVS server and checkout the source code of this project. In the command below substitute <I>modulename</I> and
<I>username</I> with the proper values. Enter your site password when
prompted.

<P><FONT size="-1" face="courier">cvs -d:pserver:username@cvs.'.$row_grp['http_domain'].':/cvsroot/'.$row_grp['unix_group_name'].' login
<BR>&nbsp;<BR>cvs -d:pserver:username@cvs.'.$row_grp['http_domain'].':/cvsroot/'.$row_grp['unix_group_name'].' co <I>modulename</I>

<!-- LJ no ssh access here
</FONT><P><FONT size="-1" face="courier">export CVS_RSH=ssh
<BR>&nbsp;<BR>cvs -z3 -d<I>developername</I>@cvs.'.$row_grp['http_domain'].':/cvsroot/'.$row_grp['unix_group_name'].' co <I>modulename</I>
</FONT>-->';

print '<P>'.help_button('VersionControlWithCVS.html',false,'[More on how to use CVS&hellip;]');

// ################## summary info

print '</TD><TD width="35%">';
print $HTML->box1_top("Repository History");

// ################ is there commit info?

$res_cvshist = db_query("SELECT * FROM group_cvs_history WHERE group_id='$group_id'");
if (db_numrows($res_cvshist) < 1) {
	print '<P>This project has no CVS history.';
} else {

// LJ Change formatting and it is not 30 but 7 day
print '<P><b>Developer (Commits) (Adds) 7day/Total</b><BR>&nbsp;';

while ($row_cvshist = db_fetch_array($res_cvshist)) {
	print '<BR>'.$row_cvshist['user_name'].' ('.$row_cvshist['cvs_commits_wk'].'/'
		.$row_cvshist['cvs_commits'].') ('.$row_cvshist['cvs_adds_wk'].'/'
		.$row_cvshist['cvs_adds'].')';
}

} // ### else no cvs history

// ############################## CVS Browsing

if ($row_grp['is_public']) {
	print '<HR><B>Browse the CVS Tree</B>
<P>Browsing the CVS tree gives you a great view into the current status
of this project\'s code. You may also view the complete histories of any
file in the repository.
<UL>
<LI><A href="http://'.$sys_cvs_host.'/cgi-bin/cvsweb.cgi?cvsroot='
.$row_grp['unix_group_name'].'"><B>Browse CVS Repository</B>';
}


print $HTML->box1_bottom();

print '</TD></TR></TABLE>';

site_project_footer(array());

?>
