<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('www/admin/admin_utils.php');


session_require(array('group'=>'1','admin_flags'=>'A'));

$dao = new ProjectDao(CodendiDataAccess::instance());
$offset = $request->getValidated('offset', 'uint', 0);
if ( !$offset || $offset < 0 ) {
    $offset = 0;
}
$limit  = 50;

$group_name_search = "0";
$vGroupNameSearch = new Valid_String('group_name_search');
if($request->valid($vGroupNameSearch)) {
    if ($request->exist('group_name_search')) {
        $group_name_search = $request->get('group_name_search');
    }
}

$status ="";
$vStatus = new Valid_WhiteList('status', array('I','D'));
if($request->valid($vStatus)) {
    if ($request->exist('status')) {
        $status = $request->get('status');
    }
}

//return projects matching given parameters
$res = $dao->returnAllProjects($offset, $limit, $status, $group_name_search);
if ($res['numrows'] == 1) {
    $row = $res['projects']->getRow();
    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id='.$row['group_id']);
}

site_admin_header(array('title'=>$Language->getText('admin_grouplist','title')));

print "<p>".$Language->getText('admin_grouplist','for_categ').": ";
if ($group_name_search !="0") {
    print "<b>".$Language->getText('admin_grouplist','begins_with',array($group_name_search))."</b>\n";
} else {
    print "<b>".$Language->getText('admin_grouplist','all_categ')."</b>\n";
}


?>

<TABLE width="100%" border="1">
<TR>
<TD><b><?php echo $Language->getText('admin_groupedit','grp_name')." ".$Language->getText('admin_grouplist','click');?></b></TD>
<TD><b><?php echo $Language->getText('admin_groupedit','unix_grp'); ?></b></TD>
<TD><b><?php echo $Language->getText('global','status'); ?></b></TD>
<TD><B><?php echo $Language->getText('admin_groupedit','group_type'); ?></B></TD>
<TD><b><?php echo $Language->getText('admin_groupedit','public'); ?></b></TD>
<TD><b><?php echo $Language->getText('admin_groupedit','license'); ?></b></TD>
<TD><B><?php echo $Language->getText('admin_grouplist','members'); ?></B></TD>
</TR>

<?php
$odd_even = array('boxitem', 'boxitemalt');
$i = 0;
// Get project type label
$template = TemplateSingleton::instance();

if ($group_name_search != "0") {
    $group_name_param="&group_name_search=$group_name_search";
} else {
    $group_name_param="";
}

if ($status !="") {
    $status_param = "&status=$status";
} else  {
    $status_param ="";
}

if ($res['numrows'] > 0) {
    $daoUsers = new UserGroupDao(CodendiDataAccess::instance());
    foreach ($res['projects'] as $grp) {
        print "<tr class=\"". $odd_even[$i++ % count($odd_even)] ."\">";
        print '<td><a href="groupedit.php?group_id='.$grp['group_id'].'">'.$grp['group_name'].'</a></td>';
        print '<td>'.$grp['unix_group_name'].'</td>';
        print '<td><span class="site_admin_project_status_'.$grp['status'].'">&nbsp;</span>'.$Language->getText('admin_groupedit', 'status_'.$grp['status']).'</td>';
        // group type
        print "<td>".$template->getLabel($grp['type'])."</td>";

        print '<td>'.$grp['is_public'].'</td>';
        print '<td>'.$grp['license'].'</td>';

        // members
        print '<td>'.$daoUsers->returnUsersNumberByGroupId($grp['group_id']).'</td>';

        print "</tr>\n";
    }

    echo '</TABLE>'.PHP_EOL;
 
    echo '<div style="text-align:center">';

    if ($offset > 0) {
        echo  '<a href="?offset='.($offset-$limit).$group_name_param.$status_param.'">[ '.$Language->getText('project_admin_utils', 'previous').'  ]</a>';
        echo '&nbsp;';
    }
    
    echo ($offset+$i).'/'.$res['numrows'];
    
    if (($offset + $limit) < $res['numrows']) {
        echo '&nbsp;';
        echo '<a href="?offset='.($offset+$limit).$group_name_param.$status_param.'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
    }
    echo '</div>';
} else {
    echo '</TABLE>'.PHP_EOL;
}

site_admin_footer(array());

?>
