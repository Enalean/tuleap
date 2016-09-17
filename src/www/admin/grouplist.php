<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 * Copyright 1999-2000 (c) The SourceForge Crew
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

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
//EXPORT-CSV
if ($request->exist('export')) {
    //Validate group_name_search
    $group_name_search = "";
    $valid_group_name_search  = new Valid_String('group_name_search');
    if($request->valid($valid_group_name_search)) {
        $group_name_search = $request->get('group_name_search');
    }
    //Get status values
    if ($request->exist('status')) {
        $status_values = $request->get('status');
        if (is_array($status_values) && !empty($status_values) && (! in_array('ANY', $status_values)) ) {
            $status = explode(',', $status_values);
        }
    }
    //export user list in csv format
    $project_list_exporter = new Admin_ProjectListExporter();
    $project_list_csv      = $project_list_exporter->exportProjectList($group_name_search, $status);
    header ('Content-Type: text/csv');
    header ('Content-Disposition:attachment; filename=project_list.csv');
    header ('Content-Length:'.strlen($project_list_csv));
    echo $project_list_csv;
}
//return projects matching given parameters
$res = $dao->returnAllProjects($offset, $limit, $status, $group_name_search);
if ($res['numrows'] == 1) {
    $row = $res['projects']->getRow();
    $GLOBALS['Response']->redirect('/admin/groupedit.php?group_id='.$row['group_id']);
}

$purifier = Codendi_HTMLPurifier::instance();

site_admin_header(array('title'=>$Language->getText('admin_grouplist','title')));

print "<p>".$purifier->purify($Language->getText('admin_grouplist','for_categ')).": ";
if ($group_name_search !="") {
    print "<b>".$purifier->purify($Language->getText('admin_grouplist', 'begins_with', array($group_name_search)))."</b>\n";
} else {
    print "<b>".$purifier->purify($Language->getText('admin_grouplist','all_categ'))."</b>\n";
}

/*
 * Add search field and Export CSV button
 */
$group_status = implode(',', $status);
if ($group_status == "") {
    $group_status = "ANY";
}
$group_name_search_purify   = $purifier->purify($group_name_search);
$group_status_purify        = $purifier->purify($group_status);
$search_purify              = $purifier->purify($Language->getText('admin_main', 'search'));
$export                     = $purifier->purify($Language->getText('admin_main', 'export_csv'));
echo '<form name="groupsrch" action="grouplist.php" method="get" class="form-horizontal">
       <table>
        <tr>
         <td valign=top>
           <label> <strong>'.$Language->getText("admin_userlist","status").'</strong> </label>
             <select name="status[]" size=7 multiple="multiple">
               <option value="ANY" '.$anySelect.'>Any</option>
               <option value="'.$purifier->purify(Project::STATUS_ACTIVE).'" '.$purifier->purify(getSelectedFromStatus(Project::STATUS_ACTIVE, $status)).'>'.$purifier->purify($Language->getText('admin_groupedit','status_A')).'</option>
               <option value="'.$purifier->purify(Project::STATUS_SYSTEM).'" '.$purifier->purify(getSelectedFromStatus(Project::STATUS_SYSTEM, $status)).'>'.$purifier->purify($Language->getText('admin_groupedit','status_s')).'</option>
               <option value="'.$purifier->purify(Project::STATUS_INCOMPLETE).'" '.$purifier->purify(getSelectedFromStatus(Project::STATUS_INCOMPLETE, $status)).'>'.$purifier->purify($Language->getText('admin_groupedit','status_I')).'</option>
               <option value="'.$purifier->purify(Project::STATUS_PENDING).'" '.$purifier->purify(getSelectedFromStatus(Project::STATUS_PENDING, $status)).'>'.$purifier->purify($Language->getText('admin_groupedit','status_P')).'</option>
               <option value="'.$purifier->purify(Project::STATUS_HOLDING).'" '.$purifier->purify(getSelectedFromStatus(Project::STATUS_HOLDING, $status)).'>'.$purifier->purify($Language->getText('admin_groupedit','status_H')).'</option>
               <option value="'.$purifier->purify(Project::STATUS_DELETED).'" '.$purifier->purify(getSelectedFromStatus(Project::STATUS_DELETED, $status)).'>'.$purifier->purify($Language->getText('admin_groupedit','status_D')).'</option>
             </select>
         </td>
         <td valign=top>
           <p>
             <label> <strong>'.$purifier->purify($Language->getText('admin_main', 'search_group')).'</strong> </label>
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
echo '<form action="grouplist.php?group_name_search='.$group_name_search_purify.'&export&status='.$group_status_purify.'" method="post">';
    echo '<input type="submit" class="btn" name="exp-csv" value="'.$export.'">';
echo '</form>';
?>

<TABLE class="table table-bordered table-striped table-hover">
<thead>
<TR>
<TH><b><?php echo $purifier->purify($Language->getText('admin_groupedit','grp_name')." ".$Language->getText('admin_grouplist','click'));?></b></TH>
<TH><b><?php echo $purifier->purify($Language->getText('admin_groupedit','unix_grp')); ?></b></TH>
<TH><b><?php echo $purifier->purify($Language->getText('global','status')); ?></b></TH>
<TH><B><?php echo $purifier->purify($Language->getText('admin_groupedit','group_type')); ?></B></TH>
<TH><b><?php echo $purifier->purify($Language->getText('admin_groupedit','public')); ?></b></TH>
<TH><B><?php echo $purifier->purify($Language->getText('admin_grouplist','members')); ?></B></TH>
</TR>
</thead>
<tbody>
<?php
$i = 0;
// Get project type label
$template = TemplateSingleton::instance();

if (! empty($group_name_search)) {
    $group_name_param = "&group_name_search=" . urlencode($group_name_search);
} else {
    $group_name_param = "";
}

if (! empty ($status)) {
    $status       = implode(',', $status);
    $status_param = "&status=" . urlencode($status);
} else  {
    $status_param = "";
}

if ($res['numrows'] > 0) {
    $daoUsers = new UserGroupDao(CodendiDataAccess::instance());
    foreach ($res['projects'] as $grp) {
        print "<tr>";
        print '<td><a href="groupedit.php?group_id='.$purifier->purify(urlencode($grp['group_id'])).'">'.$purifier->purify(html_entity_decode($grp['group_name'])).'</a></td>';
        print '<td>'.$purifier->purify($grp['unix_group_name']).'</td>';
        print '<td><span class="site_admin_project_status_'.$purifier->purify($grp['status']).'">&nbsp;</span>'.$purifier->purify($Language->getText('admin_groupedit', 'status_'.$grp['status'])).'</td>';
        // group type
        print "<td>".$purifier->purify($template->getLabel($grp['type']))."</td>";

        print '<td>'.$purifier->purify($grp['access']).'</td>';

        // members
        print '<td>'.$purifier->purify($daoUsers->returnUsersNumberByGroupId($grp['group_id'])).'</td>';

        print "</tr>\n";
    }

    echo '<tbody></TABLE>'.PHP_EOL;

    echo '<div style="text-align:center">';

    if ($offset > 0) {
        echo  '<a href="?offset='.$purifier->purify(($offset-$limit).$group_name_param.$status_param).'">[ '. $purifier->purify($Language->getText('project_admin_utils', 'previous')).'  ]</a>';
        echo '&nbsp;';
    }
    
    echo ($offset+count($res['projects'])).'/'.$res['numrows'];
    
    if (($offset + $limit) < $res['numrows']) {
        echo '&nbsp;';
        echo '<a href="?offset='.$purifier->purify(($offset+$limit).$group_name_param.$status_param).'">[ '. $purifier->purify($Language->getText('project_admin_utils', 'next')).' ]</a>';
    }
    echo '</div>';
} else {
    echo '<tbody></TABLE>'.PHP_EOL;
}

site_admin_footer(array());

?>
