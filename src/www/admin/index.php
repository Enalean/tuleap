<?php
/**
  * Copyright 1999-2000 (c) The SourceForge Crew
  * Copyright (c) Enalean, 2011-2015. All Rights Reserved.
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

require_once('pre.php');
require_once('www/admin/admin_utils.php');
require_once('www/stats/site_stats_utils.php');
require_once('common/widget/Widget_Static.class.php');

session_require(array('group'=>'1','admin_flags'=>'A'));

$abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');

$em = EventManager::instance();

// Get various number of users and projects from status
$res = db_query("SELECT count(*) AS count FROM groups");
$row = db_fetch_array($res);
$total_groups = $row['count'];

db_query("SELECT count(*) AS count FROM groups WHERE status='P'");
$row = db_fetch_array();
$pending_projects = $row['count'];

$res = db_query("SELECT count(*) AS count FROM groups WHERE status='A'");
$row = db_fetch_array($res);
$active_groups = $row['count'];

db_query("SELECT count(*) AS count FROM user WHERE status='P'");
$row = db_fetch_array();
$realpending_users = $row['count'];
    
db_query("SELECT count(*) AS count FROM user WHERE status='V' OR status='W'");
$row = db_fetch_array();
$validated_users = $row['count'];
    
db_query("SELECT count(*) AS count FROM user WHERE status='R'");
$row = db_fetch_array();
$restricted_users = $row['count'];
    
db_query("SELECT count(*) AS count FROM user WHERE status='A'");
$row = db_fetch_array();
$actif_users = $row['count'];
    
db_query("SELECT count(*) AS count FROM user WHERE status='S'");
$row = db_fetch_array();
$hold_users = $row['count'];

db_query("SELECT count(*) AS count FROM user WHERE status='D'");
$row = db_fetch_array();
$deleted_users = $row['count'];

db_query("SELECT COUNT(DISTINCT(p.user_id)) AS count
          FROM user_preferences p
          JOIN user u USING (user_id)
          WHERE preference_name = 'use_lab_features'
            AND preference_value = 1
            AND (status = 'A'
              OR status = 'R')");
$row = db_fetch_array();
$mode_lab = $row['count'];

if($GLOBALS['sys_user_approval'] == 1){
    $pending_users = $realpending_users; 
    
}else{
    $pending_users = $realpending_users + $validated_users ;
}



db_query("SELECT count(*) AS count FROM user WHERE status='V' OR status='W'");
$row = db_fetch_array();
$validated_users = $row['count'];

// Documentation
$wDoc = new Widget_Static($Language->getText('admin_main', 'documentation'));
$wDoc->setContent('
<ul>
  <li><a href="/doc/en/index.html#installation-guide">'.$Language->getText('admin_main', 'install_guide').'</a></li>
  <li><a href="/doc/en/index.html#administration-guide">'.$Language->getText('admin_main', 'admin_guide').'</a></li>
</ul>');

// Site Statistics
$wStats = new Widget_Static($Language->getText('admin_main', 'header_sstat'));
$wStats->setContent('
<ul>
  <li>'.$Language->getText('admin_main', 'stat_users').':
    <ul>
      <li>'.$Language->getText('admin_main', 'sstat_reg_u').': <strong>'.($actif_users+$restricted_users).'</strong></li>
      <li>'.$Language->getText('admin_main', 'status_user').': 
        <strong>'.$actif_users.'</strong> '.$Language->getText('admin_main', 'statusactif_user').',
        <strong>'.$restricted_users.'</strong> '.$Language->getText('admin_main', 'statusrestricted_user').',
        <strong>'.$hold_users.'</strong> '.$Language->getText('admin_main', 'statushold_user').',
        <strong>'.$deleted_users.'</strong> '.$Language->getText('admin_main', 'statusdeleted_user').',
        <strong>'.$validated_users.'</strong> '.$Language->getText('admin_main', 'statusvalidated_user').',
        <strong>'.$realpending_users.'</strong> '.$Language->getText('admin_main', 'statuspending_user').', '.
        $Language->getText('admin_main', 'statustotal_user').' : <strong>'.($realpending_users + $validated_users + $deleted_users + $hold_users + $restricted_users + $actif_users).'</strong>
      </li>
      <li>'.$Language->getText('admin_main','active_users').':
        <ul><li>'.$Language->getText('admin_main','lastday_users').': <strong>'.number_format(stats_getactiveusers(84600)).'</strong></li>
            <li>'.$Language->getText('admin_main','lastweek_users').': <strong>'.number_format(stats_getactiveusers(592200)).'</strong></li>
            <li>'.$Language->getText('admin_main','lastmonth_users').': <strong>'.number_format(stats_getactiveusers(2678400)).'</strong></li>
            <li>'.$Language->getText('admin_main','last3months_users').': <strong>'.number_format(stats_getactiveusers(8031600)).'</strong></li>
        </ul>
      </li>
      <li><a href="lastlogins.php">'.$Language->getText('admin_main', 'stat_login').'</a></li>
      <li>'.$Language->getText('admin_main','mode_lab_users').': <strong>'.$mode_lab.'</strong></li>
    </ul>
  </li>
  <li>'.$Language->getText('admin_main', 'stat_projects').'
    <ul>
      <li>'.$Language->getText('admin_main', 'sstat_reg_g').': <strong>'.$total_groups.'</strong></li>
      <li>'.$Language->getText('admin_main', 'sstat_reg_act_g').': <strong>'.$active_groups.'</strong></li>
      <li>'.$Language->getText('admin_main', 'sstat_pend_g').': <strong>'.$pending_projects.'</strong></li>
    </ul>
  </li>
  <li><a href="/stats/">'.$Language->getText('admin_main', 'stat_spu').'</a>
</ul>');

// User administration

// Letter Links

// Pending users
if ($GLOBALS['sys_user_approval'] == 1 && $pending_users != 0) {
    $user_approval =  '<strong>('.$pending_users.' - <a href="approve_pending_users.php?page=pending">'.$Language->getText('admin_main', 'need_validation').'</a>)</strong>';
} else {
    $user_approval = '(0)';
}

// Validated
if ($GLOBALS['sys_user_approval'] == 1) {
    $user_validated = '<li>'.$Language->getText('admin_main', 'validated_user',array("approve_pending_users.php?page=validated")).'<strong>('.$validated_users.')</strong></li>';
} else {
    $user_validated = '';
}

$wUser = new Widget_Static($Language->getText('admin_main', 'header_user'));
$wUser->setContent('
<ul>
  <li>'.$Language->getText('admin_main', 'all_users',array("userlist.php")).'</li>
  <li>'.$Language->getText('admin_main', 'search_user').'
    <form name="usersrch" action="userlist.php" method="get" class="form-inline">
      <input type="text" name="user_name_search" class="user_name_search" />
      <input type="submit" class="tlp-button-secondary" value="'.$Language->getText('admin_main', 'search').'">
    </form>
  </li>
  <li>'.$Language->getText('admin_main', 'pending_user',array("approve_pending_users.php?page=pending")).' '.$user_approval.'</li>
  '.$user_validated.'
  <li><a href="register_admin.php?page=admin_creation">'.$Language->getText('admin_main', 'new_user').'</a></li>
  <li><a href="permission_delegation.php">'.$Language->getText('admin_main', 'permission_delegation').'</a></li>
</ul>');


// Project administration

// Pending
if ($pending_projects != 0) {
    $groups_pending = '<strong>('.$pending_projects.' - <a href="approve-pending.php">'.$Language->getText('admin_main', 'need_approval').'</a>)</strong>';
} else {
    $groups_pending = '(0)';
}

$wProject = new Widget_Static($Language->getText('admin_main', 'header_group'));
$wProject->setContent('
<ul>
  <li>'.$Language->getText('admin_main', 'all_groups', array("grouplist.php")).'</li>
  <li>'.$Language->getText('admin_main', 'search_group').'
    <form name="gpsrch" action="grouplist.php" method="get" class="form-inline">
      <input type="text" name="group_name_search" class="group_name_search" />
      <input type="submit" class="tlp-button-secondary" value="'.$Language->getText('admin_main', 'search').'">
    </form>
  </li>
  <li>'.$Language->getText('admin_main', 'incomplete_group', array("grouplist.php?status=I")).'</li>
  <li>'.$Language->getText('admin_main', 'pending_group', array("approve-pending.php")).' '.$groups_pending.'</li>
  <li>'.$Language->getText('admin_main', 'deleted_group',array("grouplist.php?status=D")).'</li>
</ul>');

// Configuration

if ($GLOBALS['sys_use_trove'] != 0) {
    $trov_conf = '<li>'.$Language->getText('admin_main', 'trove_cat').': 
                    <ul>
                      <li><a href="/admin/trove/trove_cat_list.php">'.$Language->getText('admin_main', 'trove_cat_list').'</a></li>
                      <li><a href="/admin/trove/trove_cat_add.php">'.$Language->getText('admin_main', 'trove_cat_add').'</a></li>
                    </ul>
                  </li>';
} else {
    $trove_conf = '';
}

$wConf = new Widget_Static("Configuration");

$additional_tracker_entries = array();
if (TrackerV3::instance()->available()) {
    $additional_tracker_entries[] = '<li><a href="/tracker/admin/?group_id=100">'.$Language->getText('admin_main', 'tracker_template').'</a></li>';
}
$em->processEvent(
    Event::SITE_ADMIN_CONFIGURATION_TRACKER,
    array(
        'additional_entries' => &$additional_tracker_entries
    )
);
$tracker_links = '';
if (count($additional_tracker_entries) > 0) {
    $tracker_links = '<li>'.$Language->getText('admin_main', 'header_tracker').':
        <ul>
            <li><a href="/tracker/admin/restore.php">'.$Language->getText('admin_main', 'tracker_remove').'</a></li>
            '. implode('', $additional_tracker_entries) .'
        </ul>
    </li>';
}

$svn_links = '';
if (ForgeConfig::get(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY) !== SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL) {
    $svn_links = '<li>'.$Language->getText('admin_main', 'header_svn').':
        <ul>
            <li><a href="/admin/svn/svn_tokens.php?action=index">'.$Language->getText('admin_main', 'svn_token_config').'</a></li>
        </ul>
    </li>';
}

$wConf->setContent('
<ul>
  <li><a href="/admin/forgeaccess.php">'. $Language->getText('admin_main', 'configure_access_controls') .'</a></li>
  <li><a href="/admin/homepage.php">'. $Language->getText('admin_main', 'configure_homepage') .'</a></li>
  <li>'.$Language->getText('admin_main', 'conf_project').': 
    <ul>
      <li><a href="/admin/descfields/desc_fields_edit.php">'.$Language->getText('admin_main', 'desc_fields_edit').'</a></li>
      <li><a href="/project/admin/servicebar.php?group_id=100">'.$Language->getText('admin_main', 'configure_svc').'</a></li>
      <li><a href="/project/admin/reference.php?group_id=100">'.$Language->getText('admin_main', 'configure_ref').'</a></li>
      <li><a href="/admin/generic_user.php">'.$Language->getText('admin_main', 'configure_generic_user').'</a></li>
    </ul>
  </li>
  '. $tracker_links .'
  '. $trov_conf .'
  '. $svn_links .'
</ul>');

// Site utils
ob_start();
$em->processEvent('site_admin_external_tool_hook', null);
$pluginsContent = ob_get_contents();
ob_end_clean();

$wUtils = new Widget_Static($Language->getText('admin_main', 'header_utils'));
$wUtils->setContent('
<ul>
  <li>'.$Language->getText('admin_main', 'tool_internal').':
    <ul>
      <li><a href="/admin/system_events/">'.$Language->getText('admin_main', 'sysevent_monitor').'</a></li>
      <li><a href="/news/admin">'.$Language->getText('admin_main', 'site_news_approval').'</a></li>
      <li><a href="massmail.php">'.$Language->getText('admin_main', 'mail_engine').'</a></li>
    </ul>
  </li>
  <li>'.$Language->getText('admin_main', 'tool_external').':
    <ul>
      <li><a href="/munin/">munin</a></li>
      <li><a href="/info.php">PHP info</a></li>
      <li><a href="/admin/apc.php">APC - PHP Cache</a></li>
      '.$pluginsContent.'
    </ul>
  </li>
</ul>');

// Plugins
ob_start();
$em->processEvent('site_admin_option_hook', null);
$pluginsContent = ob_get_contents();
ob_end_clean();

$wPlugins = new Widget_Static($Language->getText('admin_main', 'header_plugins'));
$wPlugins->setContent('<ul>'.$pluginsContent.'</ul>');


// Start output
site_admin_header(array('title'=>$Language->getText('admin_main', 'title')));
echo site_admin_warnings();

echo '<div class="container-fluid site_admin">';

echo "<p><i>".$Language->getText('admin_main', 'message')."</i></p>";

echo '<div class="row-fluid">';

echo '<div class="span4">';
$wUser->display();
$wProject->display();
$em->processEvent('site_admin_disk_widget_hook', array());
echo '</div>';

echo '<div class="span4">';
$wUtils->display();
$wConf->display();
echo '</div>';

echo '<div class="span4">';
$wDoc->display();
$wStats->display();
$wPlugins->display();
echo '</div>';

echo '</div>';

echo '</div>';

site_admin_footer(array());
?>
