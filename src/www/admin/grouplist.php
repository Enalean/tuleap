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

site_admin_header(array('title'=>$Language->getText('admin_grouplist','title'), 'main_classes' => array('framed')));

print "<p>".$Language->getText('admin_grouplist','for_categ').": ";
if ($group_name_search !="") {
    print "<b>".$Language->getText('admin_grouplist', 'begins_with', array($purifier->purify($group_name_search)))."</b>\n";
} else {
    print "<b>".$Language->getText('admin_grouplist','all_categ')."</b>\n";
}

/*
 * Add search field and Export CSV button
 */
$grp_status = implode(',', $status);
if ($grp_status == "") {
    $grp_status = "ANY";
}
$group_name_search_purify   = $purifier->purify($group_name_search);
$search_purify              = $purifier->purify($Language->getText('admin_main', 'search'));
$export                     = $purifier->purify($Language->getText('admin_main', 'export_csv'));
echo '<form name="groupsrch" action="grouplist.php" method="get" class="form-horizontal">
       <table>
        <tr>
         <td valign=top>
           <label> <strong>'.$Language->getText("admin_userlist","status").'</strong> </label>
             <select name="status[]" size=7 multiple="multiple">
               <option value="ANY" '.$anySelect.'>Any</option>
               <option value="'.Project::STATUS_ACTIVE.'" '.getSelectedFromStatus(Project::STATUS_ACTIVE, $status).'>'.$Language->getText('admin_groupedit','status_A').'</option>
               <option value="'.Project::STATUS_SYSTEM.'" '.getSelectedFromStatus(Project::STATUS_SYSTEM, $status).'>'.$Language->getText('admin_groupedit','status_s').'</option>
               <option value="'.Project::STATUS_INCOMPLETE.'" '.getSelectedFromStatus(Project::STATUS_INCOMPLETE, $status).'>'.$Language->getText('admin_groupedit','status_I').'</option>
               <option value="'.Project::STATUS_PENDING.'" '.getSelectedFromStatus(Project::STATUS_PENDING, $status).'>'.$Language->getText('admin_groupedit','status_P').'</option>
               <option value="'.Project::STATUS_HOLDING.'" '.getSelectedFromStatus(Project::STATUS_HOLDING, $status).'>'.$Language->getText('admin_groupedit','status_H').'</option>
               <option value="'.Project::STATUS_DELETED.'" '.getSelectedFromStatus(Project::STATUS_DELETED, $status).'>'.$Language->getText('admin_groupedit','status_D').'</option>
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
         <button type="submit" class="tlp-button-primary">'.$search_purify.'
           <i class="icon-search"></i>
         </button>
       </div>
      </form>';
echo '<form action="grouplist.php?group_name_search='.$group_name_search_purify.'&export&status='.$grp_status.'" method="post">';
    echo '<input type="submit" class="tlp-button-secondary" name="exp-csv" value="'.$export.'">';
echo '</form>';
?>

<TABLE class="tlp-table">
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

if (! empty($group_name_search)) {
    $group_name_param = "&group_name_search=" . urlencode($group_name_search);
} else {
    $group_name_param = "";
}

if (! empty ($status)) {
    $status       = implode(',', $status);
    $status_param = "&status=$status";
} else  {
    $status_param = "";
}

if ($res['numrows'] > 0) {
    $daoUsers = new UserGroupDao(CodendiDataAccess::instance());

    $status_classes = array(
        Project::STATUS_ACTIVE     => 'tlp-badge-success tlp-badge-outline',
        Project::STATUS_DELETED    => 'tlp-badge-danger tlp-badge-outline',
        Project::STATUS_HOLDING    => 'tlp-badge-secondary',
        Project::STATUS_INCOMPLETE => 'tlp-badge-warning',
        Project::STATUS_PENDING    => 'tlp-badge-info',
        Project::STATUS_SYSTEM     => 'tlp-badge-secondary tlp-badge-outline',
    );

    foreach ($res['projects'] as $grp) {
        $status_class = 'tlp-badge-secondary';
        if (isset($status_classes[$grp['status']])) {
            $status_class = $status_classes[$grp['status']];
        }

        print "<tr>";
        print '<td><a href="groupedit.php?group_id='.$grp['group_id'].'">'.$grp['group_name'].'</a></td>';
        print '<td>'.$grp['unix_group_name'].'</td>';
        print '<td><span class="'. $status_class .'">'.$Language->getText('admin_groupedit', 'status_'.$grp['status']).'</span></td>';
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
