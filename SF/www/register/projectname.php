<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
session_require(array('isloggedin'=>'1'));
require "account.php";

// push received vars
if ($insert_purpose && $form_purpose) { 

	srand((double)microtime()*1000000);
	$random_num=rand(0,1000000);

	// make group entry
	$result = db_query("INSERT INTO groups (group_name,is_public,unix_group_name,http_domain,homepage,status,"
		. "unix_box,cvs_box,license,register_purpose,register_time,license_other,rand_hash) VALUES ("
		. "'__$random_num',"
		. "1," // public
		. "'__$random_num',"
		. "'__$random_num',"
		. "'__$random_num',"
		. "'I'," // status
		. "'shell1'," // unix_box
		. "'cvs1'," // cvs_box
		. "'__$random_num',"
		. "'".htmlspecialchars($form_purpose)."',"
		. time() . ","
		. "'__$random_num','__".md5($random_num)."')");

	if (!$result) {
		exit_error('ERROR','INSERT QUERY FAILED. Please notify admin@'.$GLOBALS['sys_default_domain']);
	} else {
		$group_id=db_insertid($result);
	}

} else {
	exit_error('Error','Missing Information. <B>PLEASE</B> fill in all required information.');
}

$HTML->header(array('title'=>'Project Name'));

?>

<H2>Step 4: Project Name</H2>


<P><B>Project Name</B>

<P>We now need some basic technical information for your project.
There are two types of names that will be associated with this project.

<P>The "Full Name" is descriptive, has no real name restrictions (except
a 40 character limit), and
can be changed. The "Unix Name" has several restrictions because it is
used in so many places around the site. They are:

<UL>
<LI>Cannot match the unix name of any other project
<LI>Must be between 3 and 15 characters in length
<LI>Can only contain characters, numbers, and dashes
<LI>Must be a valid unix username
<LI>Cannot match one of our reserved domains
<LI>Unix name will never change for this project
</UL>

<P>Your unix name is important, however, because it will be used for
many things, including:

<UL>
<LI>A web site at unixname.<?php echo $GLOBALS['sys_default_domain']; ?> 
<LI>Email at aliases@unixname.<?php echo $GLOBALS['sys_default_domain']; ?> 
<LI>A CVS Repository root of /cvsroot/unixname
<LI>Shell access to unixname.<?php echo $GLOBALS['sys_default_domain']; ?> 
<LI>Search engines throughout the site
</UL>

<P>Please make your selections.

<P><B>Project Name</B>
<FONT size=-1>
<FORM action="license.php" method="post">
<INPUT TYPE="HIDDEN" NAME="insert_group_name" VALUE="y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">  
<INPUT TYPE="HIDDEN" NAME="rand_hash" VALUE="<?php echo md5($random_num); ?>">
Full Name:
<BR>
<INPUT size="30" maxlength="30" type=text name="form_full_name">
<P>Unix Name:
<BR>
<INPUT type=text maxlength="15" SIZE="15" name="form_unix_name">
<P>
<H2><FONT COLOR="RED">Do Not Back Arrow After This Point</FONT></H2>
<INPUT type=submit name="Submit" value="Step 5: License">
</FORM>
</FONT>

<?php
$HTML->footer(array());

?>

