<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('vars.php');
require_once('www/news/news_utils.php');
require_once('trove.php');
require_once('common/tracker/ArtifactType.class.php');
require_once('common/tracker/ArtifactTypeFactory.class.php');
require_once('common/frs/FileModuleMonitorFactory.class.php');
require_once('common/wiki/lib/Wiki.class.php');
require_once('www/project/admin/permissions.php');
require_once('common/event/EventManager.class.php');
require_once('common/widget/WidgetLayoutManager.class.php');
require_once('common/include/CodeX_HTMLPurifier.class.php');

$hp =& CodeX_HTMLPurifier::instance();

$title = $Language->getText('include_project_home','proj_info').' - '. $project->getPublicName();

$HTML->includeJavascriptFile('/scripts/scriptaculous/scriptaculous.js');

site_project_header(array('title'=>$title,'group'=>$group_id,'toptab'=>'summary'));


// ########################################### end top area

// two column deal
?>

<TABLE WIDTH="100%" BORDER="0">
<TR><TD WIDTH="99%" VALIGN="top">
<?php 

// ########################################## top area, not in box 
$res_admin = db_query("SELECT user.user_id AS user_id,user.user_name AS user_name, user.realname as realname "
	. "FROM user,user_group "
	. "WHERE user_group.user_id=user.user_id AND user_group.group_id=".db_ei($group_id)." AND "
	. "user_group.admin_flags = 'A'");

if ($project->getStatus() == 'H') {
	print '<P>'.$Language->getText('include_project_home','not_official_site',$GLOBALS['sys_name']);
}

// LJ Pointer to more detailed description added
if ($project->getDescription()) {
	print "<P>" . $hp->purify($project->getDescription(), CODEX_PURIFIER_LIGHT, $group_id) . "</P>";
	$details_prompt = '['.$Language->getText('include_project_home','more_info').'...]';
} else {
  print '<P>'.$Language->getText('include_project_home','no_short_desc',"/project/admin/editgroupinfo.php?group_id=$group_id");
	$details_prompt = '['.$Language->getText('include_project_home','other_info').'...]';
}

print '<a href="/project/showdetails.php?group_id='.$group_id.'"> '. $details_prompt .'</a>';

// trove info
if ($GLOBALS['sys_use_trove'] != 0) {
    print '<BR>&nbsp;<BR>';
    trove_getcatlisting($group_id,0,1);
}

print '<BR>';
print $Language->getText('include_project_home','view_proj_activity',"/project/stats/?group_id=$group_id");

print '</TD><TD NoWrap VALIGN="top">';

if (! $project->hideMembers()) {
    // ########################### Developers on this project
    
    echo $HTML->box1_top($Language->getText('include_project_home','devel_info'));
    ?>
        <?php
              if (db_numrows($res_admin) > 0) {
                  
                  $user_helper = new UserHelper();
                  
                  echo '<SPAN CLASS="develtitle">'.$Language->getText('include_project_home','proj_admins').':</SPAN><BR>';
                  while ($row_admin = db_fetch_array($res_admin)) {
                      
                      $display_name = '';
                      $em->processEvent('get_user_display_name', array(
                          'user_id'           => $row_admin['user_id'],
                          'user_name'         => $row_admin['user_name'],
                          'realname'          => $row_admin['realname'],
                          'user_display_name' => &$display_name
                      ));
                      if (!$display_name) {
                          $display_name = $user_helper->getDisplayNameFromUserId($row_admin['user_id']);
                      }
                      print '<A href="/users/'.$row_admin['user_name'].'/">'. $display_name .'</A><BR>';
                  }
                  ?>
                      <HR WIDTH="100%" SIZE="1" NoShade>
                           <?php
                           
                           }


    echo '<SPAN CLASS="develtitle">'.$Language->getText('include_project_home','devels').':</SPAN><BR>';
    
    //count of developers on this project
    $res_count = db_query("SELECT user_id FROM user_group WHERE group_id=".db_ei($group_id));
    print db_numrows($res_count);


    echo ' <A HREF="/project/memberlist.php?group_id='.$group_id.'">['.$Language->getText('include_project_home','view_members').']</A>';


    echo $HTML->box1_bottom();
 } else {
    print "&nbsp;";
 }

print '
</TD></TR>
</TABLE>
';

$lm =& new WidgetLayoutManager();
$lm->displayLayout($project->getGroupId(), $lm->OWNER_TYPE_GROUP);

site_project_footer(array());

?>
