<?php
/*
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2016. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../file_utils.php';

use Tuleap\FRS\ToolbarPresenter;
use Tuleap\FRS\FRSPermissionManager;

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
    exit();
}

$permission_manager = FRSPermissionManager::build();

$user            = UserManager::instance()->getCurrentUser();
$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

if (!user_isloggedin()  || ! $permission_manager->isAdmin($project, $user)) {
    exit_permission_denied();
}

$vMode = new Valid_WhiteList('mode', array('delete'));
if ($request->valid($vMode) && $request->existAndNonEmpty('mode')) {
    // delete a processor from db
    if ($request->valid(new Valid_UInt('proc_id'))) {
        $proc_id = $request->get('proc_id');
        file_utils_delete_proc($proc_id);
    }
}

$renderer  = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/frs');
$presenter = new ToolbarPresenter($project);
$presenter->setProcessorsIsActive();
$presenter->displaySectionNavigation();

$project->getService(Service::FILE)->displayFRSHeader($project, _('Files Administration'));
$renderer->renderToPage('toolbar-presenter', $presenter);

$vAdd      = new Valid_String('add');
$vProcName = new Valid_String('procname');
$vProcName->required();
$vProcRank = new Valid_UInt('procrank');
$vProcRank->required();

if ($request->isPost() && $request->existAndNonEmpty('add')) {
    // add a new processor to the database
    if (
        $request->valid($vProcName) &&
        $request->valid($vProcRank) &&
        $request->valid($vAdd)
    ) {
        $procname = $request->get('procname');
        $procrank = $request->get('procrank');
        if ($procrank == "") {
            $feedback .= " " . $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_rank'));
        } elseif ($procname == "") {
            $feedback .= " " . $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_name'));
        } else {
            file_utils_add_proc($procname, $procrank);
        }
    } else {
        $feedback .= $Language->getText('file_file_utils', 'add_proc_fail');
    }
}

$vProcId = new Valid_UInt('proc_id');
$vProcId->required();
$vUpdate = new Valid_String('update');
$vProcessName = new Valid_String('processname');
$vProcessName->required();
$vProcessRank = new Valid_UInt('processrank');
$vProcessRank->required();

if ($request->isPost() && $request->existAndNonEmpty('update')) {
    // update a processor
    if (
        $request->valid($vProcessName) &&
        $request->valid($vProcessRank) &&
        $request->valid($vProcId)      &&
        $request->valid($vUpdate)
    ) {
        $proc_id     = $request->get('proc_id');
        $processname = $request->get('processname');
        $processrank = $request->get('processrank');
        if ($processrank == "") {
            $feedback .= " " . $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_rank'));
        } elseif ($processname == "") {
            $feedback .= " " . $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_name'));
        } else {
            file_utils_update_proc($proc_id, $processname, $processrank);
        }
    } else {
        $feedback .= $Language->getText('file_file_utils', 'update_proc_fail');
    }
}

$sql = "SELECT * FROM frs_processor WHERE group_id=" . db_ei($group_id) . " OR group_id=100 ORDER BY rank";
$result = db_query($sql);

?>

<P>
<H2><?php echo $Language->getText('file_admin_manageprocessors', 'manage_proclist'); ?></H2>
<?php echo $Language->getText('file_admin_manageprocessors', 'edit_proc'); ?>
<P>
<?php

file_utils_show_processors($result);

?>

<HR>
<H3><?php echo $Language->getText('file_admin_manageprocessors', 'add_proc'); ?></H3>

<?php

$return = '<TABLE><FORM ACTION="/file/admin/manageprocessors.php?group_id=' . $group_id . '" METHOD="POST">
    <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . $group_id . '">
    <TR><TD>' . $Language->getText('file_file_utils', 'proc_name') . ': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="procname" VALUE="" SIZE=30></TD></TR>
    <TR><TD>' . $Language->getText('file_file_utils', 'proc_rank') . ': <font color=red>*</font> </TD>
    <TD><INPUT TYPE="TEXT" NAME="procrank" VALUE="" SIZE=10></TD></TR></TABLE>
    <p><INPUT TYPE="SUBMIT" NAME="add" VALUE="' . $Language->getText('file_file_utils', 'add_proc') . '"></p></FORM>
    <p><font color="red">*</font>: ' . $Language->getText('file_file_utils', 'required_fields') . '</p>';

echo $return;

file_utils_footer(array());

?>
