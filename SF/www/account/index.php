<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id$

require ('pre.php');    

session_require(array('isloggedin'=>'1'));

$HTML->header(array('title'=>"Account Maintenance"));

// get global user vars
$res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
$row_user = db_fetch_array($res_user);

$HTML->box1_top("Account Maintenance: " . user_getname()); ?>

<p>Welcome, <b><?php print user_getname(); ?></b>. 
<p>You can view or change all of your account features from here. You may also wish
to view your developer/consultant profiles and ratings.

<UL>
<LI><A href="/users/<?php print $row_user['user_name']; ?>/"><B>View My Developer Profile</B></A>
<LI><A HREF="/people/editprofile.php"><B>Edit My Skills Profile</B></A>
</UL>
<?php $HTML->box1_bottom(); ?>

&nbsp;<BR>
<TABLE width=100% border=0>

<TR valign=top>
<TD>Member Since: </TD>
<TD colspan="2"><B><?php print format_date($sys_datefmt,$row_user['add_date']); ?></B></TD>
</TR>
<TR valign=top>
<TD>User ID: </TD>
<TD colspan="2"><B><?php print $row_user['user_id']; ?></B></TD>
</TR>

<TR valign=top>
<TD>Login Name: </TD>
<TD><B><?php print $row_user['user_name']; ?></B></td>
<td><A href="change_pw.php">[Change Password]</A></TD>
</TR>

<TR valign=top>
<TD>Timezone: </TD>
<TD><B><?php print $row_user['timezone']; ?></B></td>
<td><A href="change_timezone.php">[Change Timezone]</A></TD>
</TR>


<TR valign=top>
<TD>Real Name: </TD>
<TD><B><?php print $row_user['realname']; ?></B></td>
<td><A href="change_realname.php">[Change Real Name]</A></TD>
</TR>

<TR valign=top>
<TD>Email Addr: </TD>
<TD><B><?php print $row_user['email']; ?></B></td>
<td><A href="change_email.php">[Change Email Addr]</A>
</TD>
</TR>

<TR>
<TD COLSPAN=3>&nbsp;<BR></td>
</tr>

<TR>
<TD COLSPAN=3>
<?php 
// ############################# Preferences
$HTML->box1_top("Preferences"); ?>
<FORM action="updateprefs.php" method="post">

<INPUT type="checkbox" name="form_mail_site" value="1"<?php 
	if ($row_user['mail_siteupdates']) print " checked"; ?>> Receive Email for Site Updates
<I>(Low traffic. Includes security notices, major site news. Highly recommended.)</I>

<P><INPUT type="checkbox"  name="form_mail_va" value="1"<?php
	if ($row_user['mail_va']) print " checked"; ?>> Receive additional community mailings. 
<I>(Low traffic.)</I>

<P><INPUT type="checkbox"  name="form_sticky_login" value="1"<?php
	if ($row_user['sticky_login']) print " checked"; ?>> Remember my login/password <I>(<?php print $GLOBALS['sys_name']; ?> remembers your login/password. . Not recommended. <u>If you change this preference make sure to logout and login again</u>)</I>

<P>Font size: <select name="user_fontsize">
<option value="0" <?
    if ( $row_user['fontsize'] == 0 ) print "selected";
?>>Browser default</option>
<option value="1" <?
    if ( $row_user['fontsize'] == 1 ) print "selected";
?>>Small</option>
<option value="2" <?
    if ( $row_user['fontsize'] == 2 ) print "selected";
?>>Normal</option>
<option value="3" <?
    if ( $row_user['fontsize'] == 3 ) print "selected";
?>>Large</option>
</select>
    
&nbsp;&nbsp;Theme / Color scheme: 
<?php
// see what current user them is
if ($row_user['theme'] == "" || $row_user['theme'] == "default") {
    $user_theme = $GLOBALS['sys_themedefault'];
} else {
    $user_theme = $row_user['theme'];
}

// Build the theme select box from directories in css and css/custom
$dir = opendir($GLOBALS['sys_urlroot']."/css");
$theme_list = array();
$theme_dirs = array($GLOBALS['sys_urlroot']."/css", getenv('SF_LOCAL_INC_PREFIX')."/etc/codex/themes/css/");
while (list(,$dirname) = each($theme_dirs)) {
    // before scanning the directory make sure it exists to avoid warning messages
    if (is_dir($dirname)) {
	$dir = opendir($dirname);
	while ($file = readdir($dir)) {
	    if (is_dir("$dirname/$file") && $file != "." && $file != ".." && 
		$file != "CVS" && $file != "custom") {
		$theme_list[] = $file;
	    }
	}
	closedir($dir);
    }
}

print '<select name="user_theme">'."\n";
while (list(,$theme) = each($theme_list)) {
    print '<option value="'.$theme.'"';
    if ($theme==$user_theme){ print ' selected'; }
    print '>'.$theme;
    if ($theme==$GLOBALS['sys_themedefault']){ print ' (default)'; }
    print "</option>\n";
}
print "</select>\n";

?>

<P align=center><CENTER><INPUT type="submit" name="Update" value="Update"></CENTER>
</FORM>
<?php $HTML->box1_bottom(); 

// ############################### Shell Account

if ($row_user['unix_status'] == 'A') {
	$HTML->box1_top("Shell Account Information ".help_button('OtherServices.html#ShellAccount')); 
	print '&nbsp;
<BR>Shell box: <b>'.$row_user['unix_box'].'</b>
<BR>SSH Shared Keys: <B>';
	// get shared key count from db
	$expl_keys = explode("###",$row_user['authorized_keys']);
	if ($expl_keys[0]) {
		print (sizeof($expl_keys));
	} else {
		print '0';
	}
	print '</B> <A href="editsshkeys.php">[Edit Keys]</A>';
	$HTML->box1_bottom(); 
} 
?>

</TD>
</TR>

</TABLE>

<?php
$HTML->footer(array());
?>
