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

site_admin_header(array('title'=>$Language->getText('admin_main', 'title')));

$abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');

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



if($GLOBALS['sys_user_approval'] == 1){
    $pending_users = $realpending_users; 
    
}else{
    $pending_users = $realpending_users + $validated_users ;
}



db_query("SELECT count(*) AS count FROM user WHERE status='V' OR status='W'");
$row = db_fetch_array();
$validated_users = $row['count'];

// Start output

echo "<p><i>".$Language->getText('admin_main', 'message')."</i></p>";

// Documentation
$wDoc = new Widget_Static($Language->getText('admin_main', 'documentation'));
$wDoc->setContent('
<ul>
  <li><a href="/documentation/installation_guide/html/Codendi_Installation_Guide.html">'.$Language->getText('admin_main', 'install_guide').'</a></li>
  <li><a href="/documentation/administration_guide/html/Codendi_Administration_Guide.html">'.$Language->getText('admin_main', 'admin_guide').'</a></li>
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
    </ul>
  </li>
  <li>'.$Language->getText('admin_main', 'stat_projects').'
    <ul>
      <li>'.$Language->getText('admin_main', 'sstat_reg_g').': <strong>'.$total_groups.'</strong></li>
      <li>'.$Language->getText('admin_main', 'sstat_reg_act_g').': <strong>'.$active_groups.'</strong></li>
      <li>'.$Language->getText('admin_main', 'sstat_pend_g').': <strong>'.$pending_projects.'</strong></li>
    </ul>
  </li>
</ul>');

// User administration

// Letter Links
$letter_links = '';
foreach ($abc_array as $l) {
    $letter_links .= '<a href="userlist.php?user_name_search='.$l.'">&nbsp;'.$l.'&nbsp;</a>';
}

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
  <li>'.$Language->getText('admin_main', 'display_user').$letter_links.'</li>
  <li>'.$Language->getText('admin_main', 'search_user').'
    <form name="usersrch" action="search.php" method="post" style="display: inline;">
      <input type="text" name="search">
      <input type="hidden" name="usersearch" value="1">
      <input type="submit" value="'.$Language->getText('admin_main', 'search').'">
    </form>
  </li>
  <li>'.$Language->getText('admin_main', 'pending_user',array("approve_pending_users.php?page=pending")).' '.$user_approval.'</li>
  '.$user_validated.'
  <li><a href="/people/admin">'.$Language->getText('admin_main', 'skills').'</a></li>
  <li><a href="register_admin.php?page=admin_creation">'.$Language->getText('admin_main', 'new_user').'</a></li>
</ul>');


// Project administration
// Letter Links
$letter_links = '';
foreach ($abc_array as $l) {
    $letter_links .= '<a href="grouplist.php?group_name_search='. $l .'">&nbsp;'. $l .'&nbsp;</a>';
}

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
  <li>'.$Language->getText('admin_main', 'display_group').$letter_links.'</li>
  <li>'.$Language->getText('admin_main', 'search_group').'
    <form name="gpsrch" action="search.php" method="post" style="display: inline;">
      <input type="text" name="search">
      <input type="hidden" name="groupsearch" value="1">
      <input type="submit" value="'.$Language->getText('admin_main', 'search').'">
    </form>
  </li>
  <li>'.$Language->getText('admin_main', 'incomplete_group', array("grouplist.php?status=I")).'</li>
  <li>'.$Language->getText('admin_main', 'pending_group', array("approve-pending.php")).' '.$groups_pending.'</li>
  <li>'.$Language->getText('admin_main', 'deleted_group',array("grouplist.php?status=D")).'</li>
</ul>');

$wDoc->display();
$wStats->display();
$wUser->display();
$wProject->display();
?>

<h3><?php echo $Language->getText('admin_main', 'site_news'); ?></h3>
<ul>
<li><a href="/news/admin"><?php echo $Language->getText('admin_main', 'site_news_approval'); ?></A>
</ul>

<h3><?php echo $Language->getText('admin_main', 'system_event'); ?></h3>
<ul>
<li><a href="/admin/system_events/"><?php echo $Language->getText('admin_main', 'sysevent_monitor'); ?></A>
</ul>

<h3><?php echo $Language->getText('admin_main', 'desc_fields'); ?></h3>
<ul>
<li><a href="/admin/descfields/desc_fields_edit.php"><?php echo $Language->getText('admin_main', 'desc_fields_edit'); ?></A>
</ul>


<h3><?php         
if ($GLOBALS['sys_use_trove'] != 0) {
    echo $Language->getText('admin_main', 'trove_cat'); ?></h3>
<ul>
<li><a href="/admin/trove/trove_cat_list.php"><?php echo $Language->getText('admin_main', 'trove_cat_list'); ?></A>
<li><a href="/admin/trove/trove_cat_add.php"><?php echo $Language->getText('admin_main', 'trove_cat_add'); ?></A>
</ul>

<h3><?php
} // if trove
 echo $Language->getText('admin_main', 'header_svc'); ?></h3>
<ul>
<li><a href="/project/admin/servicebar.php?group_id=100"><?php echo $Language->getText('admin_main', 'configure_svc'); ?></A>
</ul>

<h3><?php echo $Language->getText('admin_main', 'servers'); ?></h3>
<ul>
<li><a href="/admin/servers/"><?php echo $Language->getText('admin_main', 'servers_admin'); ?></A>
</ul>

<h3><?php echo $Language->getText('admin_main', 'header_ref'); ?></h3>
<ul>
<li><a href="/project/admin/reference.php?group_id=100"><?php echo $Language->getText('admin_main', 'configure_ref'); ?></A>
</ul>

<h3><?php echo $Language->getText('admin_main', 'header_tracker'); ?></h3>
<ul>
<li><a href="/tracker/admin/restore.php"><?php echo $Language->getText('admin_main', 'tracker_remove'); ?></A>
<li><a href="/tracker/admin/?group_id=100"><?php echo $Language->getText('admin_main', 'tracker_template'); ?></A>
</ul>

<h3><?php echo $Language->getText('admin_main', 'header_stat'); ?></h3>
<ul>
<li><a href="lastlogins.php"><?php echo $Language->getText('admin_main', 'stat_login'); ?></A>
<li><a href="/stats/"><?php echo $Language->getText('admin_main', 'stat_spu'); ?></A>
</ul>

<h3><?php echo $Language->getText('admin_main', 'header_utils'); ?></h3>
<UL>
<LI><A href="massmail.php"><?php echo $Language->getText('admin_main', 'mail_engine'); ?></A>
<LI><A href="externaltools.php?tool=phpMyAdmin">phpMyAdmin</A>
<LI><A href="externaltools.php?tool=munin">munin</A>
<LI><A href="externaltools.php?tool=info">PHP info</A>
<LI><A href="externaltools.php?tool=APC">APC - PHP Cache</A>
<?php
    $em =& EventManager::instance();
    $em->processEvent('site_admin_external_tool_hook', null);
?>
</ul>

<h3><?php echo $Language->getText('admin_main', 'header_plugins'); ?></h3>
<ul>
<?php
    $em =& EventManager::instance();
    $em->processEvent('site_admin_option_hook', null);
?>
</ul>


<?php
site_admin_footer(array());
?>
