<?php

require_once('pre.php');    
require_once('common/event/EventManager.class.php');
require_once('www/my/my_utils.php');

session_require(array('isloggedin'=>'1'));

$Language->loadLanguageMsg('account/account');

$em =& EventManager::instance();

my_header(array('title'=>$Language->getText('account_options', 'preferences')));

$res_user = db_query("SELECT * FROM user WHERE user_id=" . user_getid());
$row_user = db_fetch_array($res_user);

// ############################# Preferences
echo '<h3>'. $Language->getText('account_options', 'preferences') .'</h3>';
?>
<FORM action="updateprefs.php" method="post">
<table>
    <tr><td width="50%"></td><td></td></tr>
    <tr valign="top">
        <td>
            <fieldset>
                <legend>Email settings</legend>
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
            </fieldset>
            <fieldset>
                <legend>Session</legend>
<P><INPUT type="checkbox"  name="form_sticky_login" value="1" 
<?php
if ($row_user['sticky_login']) print " checked";
echo '>'.$Language->getText('account_options', 'remember_me', $GLOBALS['sys_name']);
?>
            </fieldset>
        </td>
        <td>
            <fieldset>
                <legend>Appearance</legend>
                <table>
                    <tr>
                        <td>

<?php echo $Language->getText('account_options', 'theme'); ?>: </td><td>
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

</td></tr>
<?php
echo '<tr><td>'.$Language->getText('account_options', 'font_size').': </td><td><select name="user_fontsize">
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
                        </td>
                    </tr>
                    <tr>
                        <td>
<?php echo $Language->getText('account_options', 'language'); ?>: </td><td>
<?php
// display supported languages
echo html_get_language_popup($Language,'language_id',$Language->getLanguageId());
?>
                    </tr>
                </table>
            </fieldset>
            <fieldset>
                <legend>Import/export</legend>
<?php echo $Language->getText('account_options', 'csv_separator').' '.help_button('AccountMaintenance'); ?>: 
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

            </fieldset>
        </td>
    </tr>
</table>
<P align=center><CENTER><INPUT type="submit" name="Submit" value="<?php echo $Language->getText('global', 'btn_submit'); ?>"></CENTER>
</FORM>
<?php 
$HTML->footer(array());
?>
