<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// $Id: index.php 5631 2007-04-02 09:33:11 +0000 (Mon, 02 Apr 2007) nterray $

require_once('pre.php');    
require_once('common/event/EventManager.class.php');

session_require(array('isloggedin'=>'1'));

$Language->loadLanguageMsg('account/account');

$em =& EventManager::instance();

$HTML->header(array('title'=>$Language->getText('account_options', 'title')));
echo '<a href="/my/"><b>'. $Language->getText('menu','my_personal_page') .'</b></a> | <a href="/account/"><b>'. $Language->getText('include_menu','account_maintenance') .'</b></a>';
// get global user vars
$res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
$row_user = db_fetch_array($res_user);

$HTML->box1_top($Language->getText('account_options', 'title').": ".user_getrealname(user_getid()));
?>

<p><?php echo $Language->getText('account_options', 'welcome'); ?>,
    <b><?php echo user_getrealname(user_getid()); ?></b>

<p><?php echo $Language->getText('account_options', 'welcome_intro'); ?>

<UL>
<LI><A href="/users/<?php print $row_user['user_name']; ?>/">
<B><?php echo $Language->getText('account_options', 'view_developer_profile'); ?></B></A>
<LI><A HREF="/people/editprofile.php"><B><?php echo $Language->getText('account_options', 'edit_skills_profile'); ?></B></A>
</UL>
<?php $HTML->box1_bottom(); ?>

&nbsp;<BR>
<TABLE width=100% border=0>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'member_since'); ?>: </TD>
<TD colspan="2"><B><?php print format_date($sys_datefmt,$row_user['add_date']); ?></B></TD>
</TR>
<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'user_id'); ?>: </TD>
<TD colspan="2"><B><?php print $row_user['user_id']; ?></B></TD>
</TR>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'login_name'); ?>: </TD>
<TD><B><?php print $row_user['user_name']; ?></B></td>
<td>
<?php
$display_change_password = true;
$params = array('allow' => &$display_change_password);
$em->processEvent('display_change_password', $params);
if ($display_change_password) {
    echo '<A href="change_pw.php">['.$Language->getText('account_options', 'change_password').']</A>';
 }
?>
</TD>
</TR>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'timezone'); ?>: </TD>
<TD><B><?php print $row_user['timezone']; ?></B></td>
<td><A href="change_timezone.php">[<?php echo $Language->getText('account_options', 'change_timezone'); ?>]</A></TD>
</TR>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'real_name'); ?>: </TD>
<TD><B><?php print $row_user['realname']; ?></B></td>
<td>
<?php
$display_change_realname = true;
$params = array('allow' => &$display_change_realname);
$em->processEvent('display_change_realname', $params);
if ($display_change_realname) {
    echo '<A href="change_realname.php">['.$Language->getText('account_options', 'change_real_name').']</A>';
 }
?>
</TD>
</TR>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'email_address'); ?>: </TD>
<TD><B><?php print $row_user['email']; ?></B></td>
<td>
<?php
$display_change_email = true;
$params = array('allow' => &$display_change_email);
$em->processEvent('display_change_email', $params);
if ($display_change_email) {
    echo '<A href="change_email.php">['.$Language->getText('account_options', 'change_email_address').']</A>';
 }
?>
</TD>
</TR>

<?php
$entry_label  = array();
$entry_value  = array();
$entry_change = array();

$eParams = array('user_id'      => db_result($res_user,0,'user_id'),
                 'entry_label'  => &$entry_label,
                 'entry_value'  => &$entry_value,
                 'entry_change' => &$entry_change);
$em->processEvent('account_pi_entry', $eParams);
foreach($entry_label as $key => $label) {
    $value  = $entry_value[$key];
    $change = $entry_change[$key];
    print '
<TR valign=top>
<TD>'.$label.'</TD>
<TD><B>'.$value.'</B></td>
<TD>'.$change.'</TD>
</TR>
';
}
?>

<TR>
<TD COLSPAN=3>&nbsp;<BR></td>
</tr>

<TR>
<TD COLSPAN=3>
<?php 
// ############################# Preferences
$HTML->box1_top($Language->getText('account_options', 'preferences')); ?>
<FORM action="updateprefs.php" method="post">

<INPUT type="checkbox" name="form_mail_site" value="1" 
<?php 
if ($row_user['mail_siteupdates']) print " checked"; 
echo '>'.$Language->getText('account_register', 'siteupdate');
?>

<P><INPUT type="checkbox"  name="form_mail_va" value="1" 
<?php
if ($row_user['mail_va']) print " checked";
echo '>'.$Language->getText('account_register', 'communitymail');
?>

<P><INPUT type="checkbox"  name="form_sticky_login" value="1" 
<?php
if ($row_user['sticky_login']) print " checked";
echo '>'.$Language->getText('account_options', 'remember_me', $GLOBALS['sys_name']);

echo '
<P>'.$Language->getText('account_options', 'font_size').': <select name="user_fontsize">
<option value="0"';

if ( $row_user['fontsize'] == 0 ) print "selected";
echo '>'.$Language->getText('account_options', 'font_size_browser');
?></option>
<option value="1" <?
if ( $row_user['fontsize'] == 1 ) print "selected";
echo '>'.$Language->getText('account_options', 'font_size_small');
?></option>
<option value="2" <?
if ( $row_user['fontsize'] == 2 ) print "selected";
echo '>'.$Language->getText('account_options', 'font_size_normal');
?></option>
<option value="3" <?
if ( $row_user['fontsize'] == 3 ) print "selected";
echo '>'.$Language->getText('account_options', 'font_size_large');
?></option>
</select>
    
&nbsp;&nbsp;<?php echo $Language->getText('account_options', 'theme'); ?>: 
<?php
// see what current user them is
if ($row_user['theme'] == "" || $row_user['theme'] == "default") {
    $user_theme = $GLOBALS['sys_themedefault'];
} else {
    $user_theme = $row_user['theme'];
}

// Build the theme select box from directories in css and css/custom
//$dir = opendir($GLOBALS['sys_themeroot']);
$theme_list = array();
$theme_dirs = array($GLOBALS['sys_themeroot'], $GLOBALS['sys_custom_themeroot']);
while (list(,$dirname) = each($theme_dirs)) {
    // before scanning the directory make sure it exists to avoid warning messages
    if (is_dir($dirname)) {
        $dir = opendir($dirname);
        while ($file = readdir($dir)) {
            if (is_dir("$dirname/$file") && $file != "." && $file != ".." && $file != "CVS" && $file != "custom" && $file != ".svn") {
                if (is_file($dirname.DIRECTORY_SEPARATOR.$file.DIRECTORY_SEPARATOR.$file.'_Theme.class.php')) {
                    $theme_list[] = $file;
                }
            }
        }
        closedir($dir);
    }
}

print '<select name="user_theme">'."\n";
natcasesort($theme_list); //Sort an array using a case insensitive "natural order" algorithm
while (list(,$theme) = each($theme_list)) {
    print '<option value="'.$theme.'"';
    if ($theme==$user_theme){ print ' selected'; }
    print '>'.$theme;
    if ($theme==$GLOBALS['sys_themedefault']){ print ' ('.$Language->getText('global', 'default').')'; }
    print "</option>\n";
}
print "</select>\n";

?>

&nbsp;&nbsp;<?php echo $Language->getText('account_options', 'language'); ?>: 
<?php
// display supported languages
echo html_get_language_popup($Language,'language_id',$Language->getLanguageId());
?>


&nbsp;&nbsp;<?php echo $Language->getText('account_options', 'csv_separator').' '.help_button('AccountMaintenance'); ?>:
<?php
if ($u_separator = user_get_preference("user_csv_separator")) {
} else {
    $u_separator = DEFAULT_CSV_SEPARATOR;
}
// build the CSV separator select box
print '<select name="user_csv_separator">'."\n";
// $csv_separators is defined in /www/include/utils.php
foreach ($csv_separators as $separator) {
    print '<option value="'.$separator.'"';
    if ($u_separator == $separator) {
        print ' selected="selected"';
    }
    print '>'.$Language->getText('account_options', $separator).'</option>\n';
}
print "</select>\n";
?>


<P align=center><CENTER><INPUT type="submit" name="Submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>"></CENTER>
</FORM>
<?php $HTML->box1_bottom(); 

// ############################### Shell Account

if ($row_user['unix_status'] == 'A') {
	$HTML->box1_top($Language->getText('account_options', 'shell_account_title').' '.help_button('OtherServices.html#ShellAccount')); 
	print '&nbsp;
<BR>'.$Language->getText('account_options', 'shell_box').': <b>'.$row_user['unix_box'].'</b>
<BR>'.$Language->getText('account_options', 'shell_shared_keys').': <B>';
	// get shared key count from db
	$expl_keys = explode("###",$row_user['authorized_keys']);
	if ($expl_keys[0]) {
		print (sizeof($expl_keys));
	} else {
		print '0';
	}
	print '</B> <A href="editsshkeys.php">['.$Language->getText('account_options', 'shell_edit_keys').']</A>';
	$HTML->box1_bottom(); 
} 
?>

</TD>
</TR>

</TABLE>

<?php
$HTML->footer(array());
?>
