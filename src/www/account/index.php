<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('common/event/EventManager.class.php');
require_once('www/my/my_utils.php');

session_require(array('isloggedin'=>'1'));

$Language->loadLanguageMsg('account/account');

$em =& EventManager::instance();

my_header(array('title'=>$Language->getText('account_options', 'title')));

$purifier =& CodeX_HTMLPurifier::instance();

// get global user vars
$res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
$row_user = db_fetch_array($res_user);

?>
<p><?php echo $Language->getText('account_options', 'welcome'); ?>,
    <b><?php echo $purifier->purify(user_getrealname(user_getid())); ?></b>

<p><?php echo $Language->getText('account_options', 'welcome_intro'); ?>
<?php
echo '<fieldset><legend>'. $Language->getText('account_options', 'title') .'</legend>';
?>

<UL>
<LI><A href="/users/<?php echo $purifier->purify($row_user['user_name']); ?>/">
<B><?php echo $Language->getText('account_options', 'view_developer_profile'); ?></B></A>
<LI><A HREF="/people/editprofile.php"><B><?php echo $Language->getText('account_options', 'edit_skills_profile'); ?></B></A>
</UL>

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
<TD><B><?php echo $purifier->purify($row_user['user_name']); ?></B></td>
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
<TD><B><?php echo $purifier->purify($row_user['realname']); ?></B></td>
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
</TABLE>
</fieldset>

<?php
// ############################### Shell Account

if ($row_user['unix_status'] == 'A') {
	echo '<fieldset><legend>'. $Language->getText('account_options', 'shell_account_title').' '.help_button('OtherServices.html#ShellAccount') .'</legend>'; 
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
	echo '</fieldset>';
} 

$HTML->footer(array());
?>
