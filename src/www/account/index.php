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


$em = EventManager::instance();
$um = UserManager::instance();

my_header(array('title'=>$Language->getText('account_options', 'title')));

$purifier =& Codendi_HTMLPurifier::instance();

// get global user vars
$user = $um->getCurrentUser();

?>
<p><?php echo $Language->getText('account_options', 'welcome'); ?>,
    <b><?php echo  $purifier->purify(user_getrealname(user_getid()), CODENDI_PURIFIER_CONVERT_HTML) ; ?></b>

<p><?php echo $Language->getText('account_options', 'welcome_intro'); ?>
<?php
echo '<fieldset><legend>'. $Language->getText('account_options', 'title') .'</legend>';
?>

<UL>
<LI><A href="/users/<?php echo $purifier->purify($user->getUserName()); ?>/">
<B><?php echo $Language->getText('account_options', 'view_developer_profile'); ?></B></A>
<LI><A HREF="/people/editprofile.php"><B><?php echo $Language->getText('account_options', 'edit_skills_profile'); ?></B></A>
</UL>

&nbsp;<BR>

<?php if (Config::get('sys_enable_avatars')) {
    echo $user->fetchHtmlAvatar();
    echo '<a href="/account/change_avatar.php">[ '. $GLOBALS['Language']->getText('account_change_avatar', 'link') .' ]</a>';
}
?>

<TABLE width=100% border=0>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'member_since'); ?>: </TD>
<TD colspan="2"><B><?php print format_date($GLOBALS['Language']->getText('system', 'datefmt'),$user->getAddDate()); ?></B></TD>
</TR>
<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'user_id'); ?>: </TD>
<TD colspan="2"><B><?php print $user->getId(); ?></B></TD>
</TR>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'login_name', $GLOBALS['sys_name']); ?>: </TD>
<TD><B><?php echo $purifier->purify($user->getUserName()); ?></B></td>
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
<TD><B><?php print $user->getTimezone(); ?></B></td>
<td><A href="change_timezone.php">[<?php echo $Language->getText('account_options', 'change_timezone'); ?>]</A></TD>
</TR>

<TR valign=top>
<TD><?php echo $Language->getText('account_options', 'real_name'); ?>: </TD>
<TD><B><?php echo $purifier->purify($user->getRealName(), CODENDI_PURIFIER_CONVERT_HTML); ?></B></td>
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
<TD><B><?php print $user->getEmail(); ?></B></td>
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

$eParams = array('user_id'      => $user->getId(),
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
// Shell Account
$keys = $user->getAuthorizedKeys(true);

echo '<fieldset><legend>'. $Language->getText('account_options', 'shell_account_title').' '.help_button('OtherServices.html#ShellAccount') .'</legend>';
echo $Language->getText('account_options', 'shell_shared_keys').': <strong>'.count($keys).'</strong><ol>';
foreach ($keys as $key) {
    echo '<li>'.substr($key, 0, 20).'...'.substr($key, -20).'</li>';
}
echo '</ol><a href="editsshkeys.php">['.$Language->getText('account_options', 'shell_edit_keys').']</a>';


//get plugin manager
require_once('common/plugin/PluginManager.class.php');
$plugin_manager =& PluginManager::instance();
$git_plugin =& $plugin_manager->getPluginByName('git');

if ($git_plugin && $plugin_manager->isPluginAvailable($git_plugin)) {
    $remote_servers = json_decode($git_plugin->getRemoteServersForUser($user));
    if ($remote_servers) {
        echo'<br />
            <br />
            <hr />
            <br />'.
            $GLOBALS['Language']->getText('plugin_git', 'push_ssh_keys_info').
            '<ul>';

        foreach ($remote_servers as $remote_server) {
            echo '<li>'.$remote_server->host_name.'</li>';
        }
        echo '</ul>
            <form action="" method="post">
                <input type="submit"
                    title="'.$GLOBALS['Language']->getText('plugin_git', 'push_ssh_keys_button_title').'"
                    value="'.$GLOBALS['Language']->getText('plugin_git', 'push_ssh_keys_button_value').'"
                    name="ssh_key_push"/>
            </form>';
    }

    if (isset($_POST['ssh_key_push'])) {
        $git_plugin->pushSSHKeysToRemoteServers($user);
        $GLOBALS['Response']->displayFeedback();
    }
}
echo '</fieldset>';


// Authentication attempts

$accessInfo = $um->getUserAccessInfo($user);

echo '<fieldset><legend>'. $Language->getText('account_options', 'auth_attempt_title').'</legend>';
echo '<table>';
echo '<tr>';
echo '<td>'.$Language->getText('account_options', 'auth_attempt_last_success').'</td>';
echo '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $accessInfo['last_auth_success']).'</td>';
echo '</tr>';

echo '<tr>';
echo '<td>'.$Language->getText('account_options', 'auth_attempt_last_failure').'</td>';
echo '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $accessInfo['last_auth_failure']).'</td>';
echo '</tr>';

echo '<tr>';
echo '<td>'.$Language->getText('account_options', 'auth_attempt_nb_failure').'</td>';
echo '<td>'.$accessInfo['nb_auth_failure'].'</td>';
echo '</tr>';

echo '<tr>';
echo '<td>'.$Language->getText('account_options', 'auth_attempt_prev_success').'</td>';
echo '<td>'.format_date($GLOBALS['Language']->getText('system', 'datefmt'), $accessInfo['prev_auth_success']).'</td>';
echo '</tr>';

echo '</table>';
echo '</fieldset>';

include $Language->getContent('account/user_legal');

$HTML->footer(array());
?>
