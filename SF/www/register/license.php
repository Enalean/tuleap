<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require "pre.php";    // Initial db and session library, opens session
require "vars.php";
require('account.php');
session_require(array('isloggedin'=>'1'));

if ($insert_group_name && $group_id && $rand_hash && $form_full_name && $form_unix_name) {
	/*
		check for valid group name
	*/
	if (!account_groupnamevalid($form_unix_name)) {
		exit_error("Invalid Group Name",$register_error);
	}
	/*
		See if it's taken already
	*/
	if (db_numrows(db_query("SELECT group_id FROM groups WHERE unix_group_name LIKE '$form_unix_name'")) > 0) {
		exit_error("Group Name Taken","That group name already exists.");
	}
	/*
		Hash prevents them from updating a live, existing group account
	*/
	$sql="UPDATE groups SET unix_group_name='". strtolower($form_unix_name) ."', group_name='$form_full_name', ".
		"http_domain='$form_unix_name.$GLOBALS[sys_default_domain]', homepage='$form_unix_name.$GLOBALS[sys_default_domain]' ".
		"WHERE group_id='$group_id' AND rand_hash='__$rand_hash'";
	$result=db_query($sql);

} else {
	exit_error('Error','Missing Info Or Invalid State. Some form variables were missing. 
		If you are certain you entered everything, <B>PLEASE</B> report to codex-admin@'. $GLOBALS['sys_default_domain'].' and
		include info on your browser and platform configuration');
}

$HTML->header(array('title'=>'License'));
?>

<H2>Step 5: Software Policy</H2>



<P><?php print $GLOBALS['sys_name']; ?> was created to promote code
sharing of Xerox internally developed software and to have one single
place where to shop for pieces of source code.

<p>To keep things simple, the Xerox Open Source Committee and the
Xerox Intellectual Property Law Dept have put together a default <a
href="/docs/licenses/xrx.php" target="side_window">Xerox Code eXchange
Policy</a> that defines the terms and conditions under which source
code can be shared inside Xerox.

<P>We think that this policy serves well the purpose of code sharing
while offering a good level of protection. However if this default
policy doesn't fit your needs select the item "Other" in the menu
below and let us know why you wish to use another policy. Your new
policy will be reviewed by the Xerox Intellectual Property Law Dept
and the Xerox Open Source Committee for approval.

<P>Choosing a new policy is a serious decision. Please take some time
to read the <a href="/docs/licenses/xrx.php"
target="side_window">default policy</a> before making a choice about
your project. <?php print $GLOBALS['sys_name']; ?> is not responsible
for legal discrepencies regarding your own software policy.

<P>
Two important remarks:
<ul>

<li>If you are applying for a website hosting-only project and do not
expect to host any source code (why ? :-) on <?php print
$GLOBALS['sys_name']; ?>, please select "website-only" from the
choices below and proceed.</LI>

<LI>If your software has already been approved by the COMIP for
external Open Source then choose the Open Source license that covers
your Xerox code. It will help other Xerox teams to decide whether they
can/want to re-use your code in there own Xerox owned software.</li>

</UL>
<!-- LJ
<P>For many legal reasons, you may not change a project's license once it has been set. If you
feel that you have a special case and legal capability to do this,
we will work with you on a case-by-case basis.
-->

<!-- LJ
<P><B>Licenses</B>

<UL>
<LI><A href="http://www.opensource.org/licenses/gpl-license.html" target="_blank">GNU General Public License</A>
<LI><A href="http://www.opensource.org/licenses/lgpl-license.html" target="_blank">GNU Library or 'Lesser' Public License</A>
<LI><A href="http://www.opensource.org/licenses/bsd-license.html" target="_blank">BSD License</A>
<LI><A href="http://www.opensource.org/licenses/mit-license.html" target="_blank">MIT License</A>
<LI><A href="http://www.opensource.org/licenses/artistic-license.html" target="_blank">Artistic License</A>
<LI><A href="http://www.mozilla.org/MPL/MPL-1.0.html" target="_blank">Mozilla Public License 1.0</A>
<LI><A href="http://www.troll.no/qpl" target="_blank">Q Public License</A>
<LI><A href="http://www.research.ibm.com/jikes/license/license3.htm" target="_blank">IBM Public License 1.0</A>
<LI><A href="http://cvw.mitre.org/cvw/licenses/source/license.html" target="_blank">Collaborative Virtual Workspace License</A>
<LI><A href="http://www.risource.org/RPL/RPL-1.0A.shtml" target="_blank">Ricoh Source Code Public License 1.0</A>
<LI><A href="http://www.python.org/doc/Copyright.html" target="_blank">Python License</A>
<LI><A href="http://www.opensource.org/licenses/zlib-license.html" target="_blank">zlib/libpng License</A>
<LI><A href="http://www.sourceforge.net/register/publicdomain.txt" target="_blank">Public Domain</A>
</UL>
-->

<P><B>Policy for This Project</B>

<FONT size=-1>
<FORM action="category.php" method="post">
<INPUT TYPE="HIDDEN" NAME="insert_license" VALUE="y">
<INPUT TYPE="HIDDEN" NAME="group_id" VALUE="<?php echo $group_id; ?>">
<INPUT TYPE="HIDDEN" NAME="rand_hash" VALUE="<?php echo $rand_hash; ?>">
<!-- LJ <B>Your License:</B><BR> -->
<?php
	echo '<SELECT NAME="form_license">';
	while (list($k,$v) = each($LICENSE)) {
		print "<OPTION value=\"$k\"";
		print ">$v\n";
	}
	echo '</SELECT>';

?>
<P>
If you selected "other", please provide an explanation along
with a description of your license. Realize that other licenses may
not be approved. 
<BR><TEXTAREA name="form_license_other" wrap=virtual cols=60 rows=10></TEXTAREA>
<P>
<H2><FONT COLOR="RED">Do Not Back Arrow After This Point</FONT></H2> 
<P>
<INPUT type=submit name="Submit" value="Step 6: Category">
</FORM>
</FONT>

<?php
$HTML->footer(array());

?>

