<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require($DOCUMENT_ROOT.'/include/pre.php');    
require($DOCUMENT_ROOT.'/include/vars.php');
require($DOCUMENT_ROOT.'/admin/admin_utils.php');
require($DOCUMENT_ROOT.'/project/admin/project_admin_utils.php');

$Language->loadLanguageMsg('admin/admin');

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

	$feedback .= $Language->getText('admin_groupedit','feedback_info');

	/*
		If this is a foundry, see if they have a preferences row, if not, create one
	*/
	if ($group_type=='2') {
		$res=db_query("SELECT * FROM foundry_data WHERE foundry_id='$group_id'");
		if (db_numrows($res) < 1) {
			group_add_history ($Language->getText('admin_groupedit','feedback_history'),'',$group_id);

			$feedback .= $Language->getText('admin_groupedit','feedback_foundry');
			$r=db_query("INSERT INTO foundry_data (foundry_id) VALUES ('$group_id')");
			if (!$r || db_affected_rows($r) < 1) {
				echo $Language->getText('admin_groupedit','feedback_insert').': '.db_error();
			}
		}
	}
}

// get current information
$res_grp = db_query("SELECT * FROM groups WHERE group_id=$group_id");

if (db_numrows($res_grp) < 1) {
	exit_error("ERROR",$Language->getText('admin_groupedit','error_group'));
}

$row_grp = db_fetch_array($res_grp);

site_admin_header(array('title'=>$Language->getText('admin_groupedit','title')));

echo '<H2>'.$row_grp['group_name'].'</H2>' ;?>

<p>
<A href="/project/admin/?group_id=<?php print $group_id; ?>"><H3>[<?php echo $Language->getText('admin_groupedit','proj_admin'); ?>]</H3></A>

<P>
<A href="userlist.php?group_id=<?php print $group_id; ?>"><H3>[<?php echo $Language->getText('admin_groupedit','proj_member'); ?>]</H3></A>

<p>
<FORM action="<?php echo $PHP_SELF; ?>" method="POST">
<B><?php echo $Language->getText('admin_groupedit','group_type'); ?>:</B>
<?php

echo show_group_type_box('group_type',$row_grp['type']);

?>

<B><?php echo $Language->getText('admin_groupedit','status'); ?></B>
<SELECT name="form_status">
<OPTION <?php if ($row_grp['status'] == "I") print "selected "; ?> value="I">
<?php echo $Language->getText('admin_groupedit','incomplete'); ?></OPTION>
<OPTION <?php if ($row_grp['status'] == "A") print "selected "; ?> value="A">
<?php echo $Language->getText('admin_groupedit','active'); ?>
<OPTION <?php if ($row_grp['status'] == "P") print "selected "; ?> value="P">
<?php echo $Language->getText('admin_groupedit','pending'); ?>
<OPTION <?php if ($row_grp['status'] == "H") print "selected "; ?> value="H">
<?php echo $Language->getText('admin_groupedit','holding'); ?>
<OPTION <?php if ($row_grp['status'] == "D") print "selected "; ?> value="D">
<?php echo $Language->getText('admin_groupedit','deleted'); ?>
</SELECT>

<B><?php echo $Language->getText('admin_groupedit','public'); ?></B>
<SELECT name="form_public">
<OPTION <?php if ($row_grp['is_public'] == 1) print "selected "; ?> value="1">
<?php echo $Language->getText('global','yes'); ?>
<OPTION <?php if ($row_grp['is_public'] == 0) print "selected "; ?> value="0">
<?php echo $Language->getText('global','no'); ?>
</SELECT>

<?
if ( $sys_show_project_type ) {
?>
<p><B><?php echo $Language->getText('admin_groupedit','project_type'); ?>:</B><br>
<?php

echo show_project_type_box($row_grp['project_type']);

?>
<?
}
?>

<P><B><?php echo $Language->getText('admin_groupedit','license'); ?></B>
<SELECT name="form_license">
<OPTION value="none"><?php echo $Language->getText('admin_groupedit','license_na'); ?>
<OPTION value="other"><?php echo $Language->getText('admin_groupedit','license_other'); ?>
<?php
	while (list($k,$v) = each($LICENSE)) {
		print "<OPTION value=\"$k\"";
		if ($k == $row_grp['license']) print " selected";
		print ">$v\n";
	}
?>
</SELECT>


<INPUT type="hidden" name="group_id" value="<?php print $group_id; ?>">
<BR><?php echo $Language->getText('admin_groupedit','home_box'); ?>:
<INPUT type="text" name="form_box" value="<?php print $row_grp['unix_box']; ?>">
<BR><?php echo $Language->getText('admin_groupedit','http_domain'); ?>:
<INPUT size=40 type="text" name="form_domain" value="<?php print $row_grp['http_domain']; ?>">
<BR><INPUT type="submit" name="Update" value="<?php echo $Language->getText('global','btn_update'); ?>">
</FORM>

<P><A href="newprojectmail.php?group_id=<?php print $group_id; ?>">
<?php echo $Language->getText('admin_groupedit','send_email'); ?></A>

<?php

// ########################## OTHER INFO

print "<P><B>".$Language->getText('admin_groupedit','other_info')."</B>";
print "<br><u>".$Language->getText('admin_groupedit','unix_grp')."</u>: $row_grp[unix_group_name]";

print "<br><u>".$Language->getText('admin_groupedit','description')."</u>:<br> $row_grp[register_purpose]";

print "<br><u>".$Language->getText('admin_groupedit','license_other')."</u>: <br> $row_grp[license_other]";
	
if ( $GLOBALS['sys_show_project_type'] ) {
    print "<br><u>".$Language->getText('admin_groupedit','project_type')."</u>: ";
    $res_type = db_query("SELECT * FROM project_type WHERE project_type_id = ". $row_grp[project_type]);
    $row_type = db_fetch_array($res_type);
    print $row_type[description];
 }	

	echo "<P><HR><P>";

echo '
<P>'.show_grouphistory ($group_id);

site_admin_footer(array());

?>
