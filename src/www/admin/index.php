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

session_require(array('group'=>'1','admin_flags'=>'A'));


site_admin_header(array('title'=>$Language->getText('admin_main', 'title')));

$abc_array = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','0','1','2','3','4','5','6','7','8','9');

// Get various number of users and projects from status
db_query("SELECT count(*) AS count FROM groups WHERE status='P'");
$row = db_fetch_array();
$pending_projects = $row['count'];


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

$version = trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));

?>
 
<h2><?php echo $Language->getText('admin_main', 'header').' ('.$version.')'; ?></h2>
<p><i><?php echo $Language->getText('admin_main', 'message'); ?></i>


<h3><?php // Documentation
echo $Language->getText('admin_main', 'documentation'); ?></h3>
<ul>
<li><a href="/documentation/installation_guide/html/Codendi_Installation_Guide.html">
    <?php echo $Language->getText('admin_main', 'install_guide'); ?></a></li>
<li><a href="/documentation/administration_guide/html/Codendi_Administration_Guide.html">
    <?php echo $Language->getText('admin_main', 'admin_guide'); ?></a></li>
</ul>

<h3><?php 

//Display of Site Statistics
echo $Language->getText('admin_main', 'header_sstat'); ?></h3>
<ul>
<?php
		print "<li>".$Language->getText('admin_main', 'stat_users')." :" ;
        db_query("SELECT count(*) AS count FROM user WHERE status='A' or status='R'");
        $row = db_fetch_array();
        print "<ul><li>".$Language->getText('admin_main', 'sstat_reg_u')." : <B>$row[count]</B></li>";
		print "<li>".$Language->getText('admin_main', 'status_user')." : " ;
		print "<B>$actif_users</B> ".$Language->getText('admin_main', 'statusactif_user');
		print ", <B>".$restricted_users."</B> ".$Language->getText('admin_main', 'statusrestricted_user');
		print ", <B>".$hold_users."</B> ".$Language->getText('admin_main', 'statushold_user');
		print ", <B>".$deleted_users."</B> ".$Language->getText('admin_main', 'statusdeleted_user');
		print ", <B>".$validated_users."</B> ".$Language->getText('admin_main', 'statusvalidated_user');
		print ", <B>".$realpending_users."</B> ".$Language->getText('admin_main', 'statuspending_user');
		print ", ".$Language->getText('admin_main', 'statustotal_user')." : <B>".($realpending_users + $validated_users + $deleted_users + $hold_users + $restricted_users + $actif_users)."</B></li>";
		
			
		print "<li>".$Language->getText('admin_main','active_users').' :';
		print "<ul><li>".$Language->getText('admin_main','lastday_users').' : <B>'.number_format(stats_getactiveusers(84600)).'</B></li>';
		print "<li>".$Language->getText('admin_main','lastweek_users').' : <B>'.number_format(stats_getactiveusers(592200)).'</B></li>';
		print "<li>".$Language->getText('admin_main','lastmonth_users').' : <B>'.number_format(stats_getactiveusers(2678400)).'</B></li>';
		print "<li>".$Language->getText('admin_main','last3months_users').' : <B>'.number_format(stats_getactiveusers(8031600)).'</B></li></ul></li>';
  
		print "</ul></li><li>".$Language->getText('admin_main', 'stat_projects')." :" ;
        db_query("SELECT count(*) AS count FROM groups");
        $row = db_fetch_array();
        print "<ul><li>".$Language->getText('admin_main', 'sstat_reg_g')." : <B>$row[count]</B></li>";

        db_query("SELECT count(*) AS count FROM groups WHERE status='A'");
        $row = db_fetch_array();
        print "<li>".$Language->getText('admin_main', 'sstat_reg_act_g')." : <B>$row[count]</B></li>";

		print "<li>".$Language->getText('admin_main', 'sstat_pend_g')." : <B>$pending_projects</B></li></ul>";

        
		print "</li>";
?>
</ul>

<h3><?php echo $Language->getText('admin_main', 'header_user'); ?></h3>
<ul>
<li><?php echo $Language->getText('admin_main', 'display_user');

	for ($i=0; $i < count($abc_array); $i++) {
        echo '<a href="userlist.php?user_name_search='. $abc_array[$i] .'">&nbsp;'. $abc_array[$i] .'&nbsp;</a>';
	}
?>
<br>
<?php echo $Language->getText('admin_main', 'search_user'); ?>
<br>
<form name="usersrch" action="search.php" method="POST">
  <input type="text" name="search">
  <input type="hidden" name="usersearch" value="1">
  <input type="submit" value="<?php echo $Language->getText('admin_main', 'search'); ?>">
</form>
<ul>
<li><?php echo $Language->getText('admin_main', 'all_users',array("userlist.php")); ?></a></li>
<LI><?php echo $Language->getText('admin_main', 'pending_user',array("approve_pending_users.php?page=pending")); ?>
<?php echo " <b>($pending_users";
if ($GLOBALS['sys_user_approval'] == 1 && $pending_users != 0) {
    print "&nbsp;-&nbsp; <a href=\"approve_pending_users.php?page=pending\">".$Language->getText('admin_main', 'need_validation')."</a>";
}
echo ")</b>";
?></li>
<?php if ($GLOBALS['sys_user_approval'] == 1) { ?>
<LI><?php echo $Language->getText('admin_main', 'validated_user',array("approve_pending_users.php?page=validated")); ?>
<?php echo " <b>($validated_users)</b>";
}
?>
</li>
</ul>
<li><a href="/people/admin"><?php echo $Language->getText('admin_main', 'skills'); ?></a></li>
<li><a href="register_admin.php?page=admin_creation"><?php echo $Language->getText('admin_main', 'new_user'); ?></a></li>
</ul>

<h3><?php echo $Language->getText('admin_main', 'header_group'); ?></h3>
<ul>

<li><?php echo $Language->getText('admin_main', 'display_group');
	for ($i=0; $i < count($abc_array); $i++) {
		echo '<a href="grouplist.php?group_name_search='. $abc_array[$i] .'">&nbsp;'. $abc_array[$i] .'&nbsp;</a>';
	}
?>
<br>
<?php echo $Language->getText('admin_main', 'search_group'); ?>
<br>
<form name="gpsrch" action="search.php" method="POST">
  <input type="text" name="search">
  <input type="hidden" name="groupsearch" value="1">
  <input type="submit" value="<?php echo $Language->getText('admin_main', 'search'); ?>">
</form>

<p>

<ul>
<li><?php echo $Language->getText('admin_main', 'all_groups',array("grouplist.php")); ?></a></li>
<LI><?php echo $Language->getText('admin_main', 'incomplete_group',array("grouplist.php?status=I")); ?>
<LI><?php echo $Language->getText('admin_main', 'pending_group',array("approve-pending.php")); ?>
<?php echo " <b>($pending_projects";
if ($pending_projects != 0) {
    print "&nbsp;-&nbsp; <a href=\"approve-pending.php\">".$Language->getText('admin_main', 'need_approval')."</a>";
}
echo ")</b>";?>
<LI><?php echo $Language->getText('admin_main', 'deleted_group',array("grouplist.php?status=D")); ?>
</ul>
</ul>

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
