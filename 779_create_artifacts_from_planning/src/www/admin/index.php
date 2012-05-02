<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

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
  <li><a href="/documentation/installation_guide/html/Installation_Guide.html">'.$Language->getText('admin_main', 'install_guide').'</a></li>
  <li><a href="/documentation/administration_guide/html/Administration_Guide.html">'.$Language->getText('admin_main', 'admin_guide').'</a></li>
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
    <form name="usersrch" action="userlist.php" method="get" style="display: inline;">
      <input type="text" name="user_name_search">
      <input type="submit" value="'.$Language->getText('admin_main', 'search').'">
    </form>
  </li>
  <li>'.$Language->getText('admin_main', 'pending_user',array("approve_pending_users.php?page=pending")).' '.$user_approval.'</li>
  '.$user_validated.'
  <li><a href="/people/admin">'.$Language->getText('admin_main', 'skills').'</a></li>
  <li><a href="register_admin.php?page=admin_creation">'.$Language->getText('admin_main', 'new_user').'</a></li>
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
    <form name="gpsrch" action="grouplist.php" method="get" style="display: inline;">
      <input type="text" name="group_name_search">
      <input type="submit" value="'.$Language->getText('admin_main', 'search').'">
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
$wConf->setContent('
<ul>
  <li>'.$Language->getText('admin_main', 'conf_project').': 
    <ul>
      <li><a href="/admin/descfields/desc_fields_edit.php">'.$Language->getText('admin_main', 'desc_fields_edit').'</a></li>
      <li><a href="/project/admin/servicebar.php?group_id=100">'.$Language->getText('admin_main', 'configure_svc').'</a></li>
      <li><a href="/project/admin/reference.php?group_id=100">'.$Language->getText('admin_main', 'configure_ref').'</a></li>
    </ul>
  </li>
  <li>'.$Language->getText('admin_main', 'header_tracker').':
    <ul>
      <li><a href="/tracker/admin/restore.php">'.$Language->getText('admin_main', 'tracker_remove').'</a></li>
      <li><a href="/tracker/admin/?group_id=100">'.$Language->getText('admin_main', 'tracker_template').'</a></li>
    </ul>
  </li>
  '.$trov_conf.'
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
      <li><a href="externaltools.php?tool=phpMyAdmin">phpMyAdmin</a></li>
      <li><a href="externaltools.php?tool=munin">munin</a></li>
      <li><a href="externaltools.php?tool=info">PHP info</a></li>
      <li><a href="externaltools.php?tool=APC">APC - PHP Cache</a></li>
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

echo "<p><i>".$Language->getText('admin_main', 'message')."</i></p>";

echo '<table id="site_admin_main_table"><tr>';

echo '<td>';
$wUser->display();
$wProject->display();
echo "</td>";

echo '<td>';
$wUtils->display();
$wConf->display();
echo "</td>";

echo '<td>';
$wDoc->display();
$wStats->display();
$wPlugins->display();
echo "</td>";

echo "</tr></table>";

site_admin_footer(array());
?>
