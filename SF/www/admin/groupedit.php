<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    
require "vars.php";
require($DOCUMENT_ROOT.'/admin/admin_utils.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

// group public choice
if ($Update) {
	$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

	//audit trail
	if (db_result($res_grp,0,'status') != $form_status)
		{ group_add_history ('status',db_result($res_grp,0,'status'),$group_id);  }
	if (db_result($res_grp,0,'is_public') != $form_public)
		{ group_add_history ('is_public',db_result($res_grp,0,'is_public'),$group_id);  }
	if (db_result($res_grp,0,'type') != $group_type)
		{ group_add_history ('type',db_result($res_grp,0,'type'),$group_id);  }
	if (db_result($res_grp,0,'http_domain') != $form_domain)
		{ group_add_history ('http_domain',db_result($res_grp,0,'http_domain'),$group_id);  }
	if (db_result($res_grp,0,'unix_box') != $form_box)
		{ group_add_history ('unix_box',db_result($res_grp,0,'unix_box'),$group_id);  }
	if (db_result($res_grp,0,'project_type') != $project_type)
		{ group_add_history ('project type',db_result($res_grp,0,'project_type'),$group_id);  }

	db_query("UPDATE groups SET is_public=$form_public,status='$form_status',"
		. "license='$form_license',type='$group_type',project_type='$project_type',"
		. "unix_box='$form_box',http_domain='$form_domain' WHERE group_id=$group_id");

	$feedback .= ' Updating Project Info ';

	/*
		If this is a foundry, see if they have a preferences row, if not, create one
	*/
	if ($group_type=='2') {
		$res=db_query("SELECT * FROM foundry_data WHERE foundry_id='$group_id'");
		if (db_numrows($res) < 1) {
			group_add_history ('added foundry_data row','',$group_id);

			$feedback .= ' CREATING NEW FOUNDRY_DATA ROW ';
			$r=db_query("INSERT INTO foundry_data (foundry_id) VALUES ('$group_id')");
			if (!$r || db_affected_rows($r) < 1) {
				echo 'COULD NOT INSERT NEW FOUNDRY_DATA ROW: '.db_error();
			}
		}
	}
}

// get current information
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

if (db_numrows($res_grp) < 1) {
	exit_error("Invalid Group","Invalid group was passed in.");
}

$row_grp = db_fetch_array($res_grp);

site_admin_header(array('title'=>"Editing Group"));

echo '<H2>'.$row_grp['group_name'].'</H2>' ;?>

<p>
<?php print "<A href=\"/project/admin/?group_id=$group_id\"><H3>[Project Admin]</H3></A>"; ?></b>

<P>
<A href="userlist.php?group_id=<?php print $group_id; ?>"><H3>[View/Edit Group Members]</H3></A>

<p>
<FORM action="<?php echo $PHP_SELF; ?>" method="POST">
<B>Group Type:</B><BR>
<?php

echo show_group_type_box('group_type',$row_grp['type']);

?>

<B>Status</B>
<SELECT name="form_status">
<OPTION <?php if ($row_grp['status'] == "I") print "selected "; ?> value="I">Incomplete</OPTION>
<OPTION <?php if ($row_grp['status'] == "A") print "selected "; ?> value="A">Active
<OPTION <?php if ($row_grp['status'] == "P") print "selected "; ?> value="P">Pending
<OPTION <?php if ($row_grp['status'] == "H") print "selected "; ?> value="H">Holding
<OPTION <?php if ($row_grp['status'] == "D") print "selected "; ?> value="D">Deleted
</SELECT>

<B>Public?</B>
<SELECT name="form_public">
<OPTION <?php if ($row_grp['is_public'] == 1) print "selected "; ?> value="1">Yes
<OPTION <?php if ($row_grp['is_public'] == 0) print "selected "; ?> value="0">No
</SELECT>

<?
if ( $sys_show_project_type ) {
?>
<p><B>Project Type:</B><br>
<?php

echo show_project_type_box($row_grp['project_type']);

?>
<?
}
?>

<P><B>License</B>
<SELECT name="form_license">
<OPTION value="none">N/A
<OPTION value="other">Other
<?php
	while (list($k,$v) = each($LICENSE)) {
		print "<OPTION value=\"$k\"";
		if ($k == $row_grp['license']) print " selected";
		print ">$v\n";
	}
?>
</SELECT>


<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<BR>Home Box: <INPUT type="text" name="form_box" value="<?php print $row_grp['unix_box']; ?>">
<BR>HTTP Domain: <INPUT size=40 type="text" name="form_domain" value="<?php print $row_grp['http_domain']; ?>">
<BR><INPUT type="submit" name="Update" value="Update">
</FORM>

<P><A href="newprojectmail.php?group_id=<?php print $group_id; ?>">Send New Project Instruction Email</A>

<?php

// ########################## OTHER INFO

print "<HR><P><B>Other Information</B>";
print "<P>Unix Group Name: $row_grp[unix_group_name]";

print "<P>Submitted Description:<P> $row_grp[register_purpose]";

print "<P>License Other: <P> $row_grp[license_other]";

echo '
<P>'.show_grouphistory ($group_id);

site_admin_footer(array());

?>
