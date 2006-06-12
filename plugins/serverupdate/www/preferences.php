<?php

$serverupdate = new ServerUpdate();
$svnupdate = $serverupdate->getSVNUpdate();

echo '<h3>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','preferences_title').'</h3>';
echo '<p>';
echo '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','svn_repository').'</strong> '.$svnupdate->getRepository().'<br />';
echo '</p>';
echo '<p>';
echo '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','working_directory').'</strong> '.$svnupdate->getWorkingCopyDirectory().'<br />';
echo '</p>';
echo '<p>';
echo '<strong>'.$GLOBALS['Language']->getText('plugin_serverupdate_preferences','script_directory').'</strong> '.$svnupdate->getWorkingCopyDirectory().'/'.UPGRADE_SCRIPT_PATH.'<br />';
echo '</p>';

?>
