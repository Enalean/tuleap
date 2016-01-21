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
$limit = 50;

$group_name_search = "";
$vGroupNameSearch = new Valid_String('group_name_search');
if($request->valid($vGroupNameSearch)) {
    if ($request->exist('group_name_search')) {
        $group_name_search = $request->get('group_name_search');
    }
}

$status        = array();
$anySelect     = "selected";
if($request->exist('status')) {
    $status = $request->get('status');
    if(! is_array($status)) {
        $status = explode(",", $status);
    }
    if(in_array('ANY', $status)) {
        $status = array();
    } else {
        $anySelect = "";
    }
}

function getSelectedFromStatus($selected_status, $status) {
    if(in_array($selected_status, $status)) {
        return "selected";
    }
}

//return projects matching given parameters
$res = $dao->returnAllProjects($offset, $limit, $status, $group_name_search);
if ($res['numrows'] == 1) {
    $row = $res['projects']->getRow();
    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id='.$row['group_id']);
}

$purifier = Codendi_HTMLPurifier::instance();

site_admin_header(array('title'=>$Language->getText('admin_grouplist','title')));

print "<p>".$Language->getText('admin_grouplist','for_categ').": ";
if ($group_name_search !="") {
    print "<b>".$Language->getText('admin_grouplist', 'begins_with', array($purifier->purify($group_name_search)))."</b>\n";
} else {
    print "<b>".$Language->getText('admin_grouplist','all_categ')."</b>\n";
}

/*
 * Add search field
 */
$purifier                   = Codendi_HTMLPurifier::instance();
$group_name_search_purify   = $purifier->purify($group_name_search);
$search_purify              = $purifier->purify($Language->getText('admin_main', 'search'));
echo '<form name="groupsrch" action="grouplist.php" method="get" class="form-horizontal">
       <table>
        <tr>
         <td valign=top>
           <label> <strong>'.$Language->getText("admin_userlist","status").'</strong> </label>
             <select name="status[]" size=7 multiple="multiple">
               <option value="ANY" '.$anySelect.'>Any</option>
               <option value="'.Project::STATUS_ACTIVE.'" '.getSelectedFromStatus(Project::STATUS_ACTIVE, $status).'>'.$Language->getText('admin_groupedit','status_A').'</option>
               <option value="S" '.getSelectedFromStatus("S", $status).'>'.$Language->getText('admin_groupedit','status_s').'</option>
               <option value="'.Project::STATUS_INCOMPLETE.'" '.getSelectedFromStatus(Project::STATUS_INCOMPLETE, $status).'>'.$Language->getText('admin_groupedit','status_I').'</option>
               <option value="'.Project::STATUS_PENDING.'" '.getSelectedFromStatus(Project::STATUS_PENDING, $status).'>'.$Language->getText('admin_groupedit','status_P').'</option>
               <option value="'.Project::STATUS_HOLDING.'" '.getSelectedFromStatus(Project::STATUS_HOLDING, $status).'>'.$Language->getText('admin_groupedit','status_H').'</option>
               <option value="D" '.getSelectedFromStatus("D", $status).'>'.$Language->getText('admin_groupedit','status_D').'</option>
             </select>
         </td>
         <td valign=top>
           <p>
             <label> <strong>'.$Language->getText('admin_main', 'search_group').'</strong> </label>
           </p>
           <input type="text" name="group_name_search" class="project_name_search" placeholder="'.$search_purify.'" value="'.$group_name_search_purify.'" />
         </td>
        </tr>
       </table>
       <div align="center">
         <button type="submit" class="btn btn-primary">'.$search_purify.'
           <i class="icon-search"></i>
         </button>
       </div>
      </form>';
?>

<TABLE class="table table-bordered table-striped table-hover">
<thead>
<TR>
<TH><b><?php echo $Language->getText('admin_groupedit','grp_name')." ".$Language->getText('admin_grouplist','click');?></b></TH>
<TH><b><?php echo $Language->getText('admin_groupedit','unix_grp'); ?></b></TH>
<TH><b><?php echo $Language->getText('global','status'); ?></b></TH>
<TH><B><?php echo $Language->getText('admin_groupedit','group_type'); ?></B></TH>
<TH><b><?php echo $Language->getText('admin_groupedit','public'); ?></b></TH>
<TH><B><?php echo $Language->getText('admin_grouplist','members'); ?></B></TH>
</TR>
</thead>
<tbody>
<?php
$i = 0;
// Get project type label
$template = TemplateSingleton::instance();

if ($group_name_search != "0") {
    $group_name_param="&group_name_search=" . urlencode($group_name_search);
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
        print "<tr>";
        print '<td><a href="groupedit.php?group_id='.$grp['group_id'].'">'.$grp['group_name'].'</a></td>';
        print '<td>'.$grp['unix_group_name'].'</td>';
        print '<td><span class="site_admin_project_status_'.$grp['status'].'">&nbsp;</span>'.$Language->getText('admin_groupedit', 'status_'.$grp['status']).'</td>';
        // group type
        print "<td>".$template->getLabel($grp['type'])."</td>";

        print '<td>'.$grp['access'].'</td>';

        // members
        print '<td>'.$daoUsers->returnUsersNumberByGroupId($grp['group_id']).'</td>';

        print "</tr>\n";
    }

    echo '<tbody></TABLE>'.PHP_EOL;

    echo '<div style="text-align:center">';

    if ($offset > 0) {
        echo  '<a href="?offset='.($offset-$limit).$group_name_param.$status_param.'">[ '.$Language->getText('project_admin_utils', 'previous').'  ]</a>';
        echo '&nbsp;';
    }
    
    echo ($offset+count($res['projects'])).'/'.$res['numrows'];
    
    if (($offset + $limit) < $res['numrows']) {
        echo '&nbsp;';
        echo '<a href="?offset='.($offset+$limit).$group_name_param.$status_param.'">[ '.$Language->getText('project_admin_utils', 'next').' ]</a>';
    }
    echo '</div>';
} else {
    echo '<tbody></TABLE>'.PHP_EOL;
}

site_admin_footer(array());

?>
