<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Mohamed CHAARI, 2006. STMicroelectronics.
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

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/../file_utils.php';

use Tuleap\FRS\ToolbarPresenter;
use Tuleap\FRS\FRSPermissionManager;

$request = HTTPRequest::instance();

$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$permission_manager = FRSPermissionManager::build();

$user            = UserManager::instance()->getCurrentUser();
$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

if (! user_isloggedin() || ! $permission_manager->isAdmin($project, $user)) {
    exit_permission_denied();
}

$service = $project->getService(Service::FILE);

if (! $service) {
    exit_error(
        $GLOBALS['Language']->getText(
            'project_service',
            'service_not_used',
            $GLOBALS['Language']->getText('project_admin_editservice', 'service_file_lbl_key')
        )
    );
}

$vMode = new Valid_WhiteList('mode', ['delete']);
if ($request->valid($vMode) && $request->existAndNonEmpty('mode')) {
    // delete a processor from db
    if ($request->valid(new Valid_UInt('proc_id'))) {
        $proc_id = $request->get('proc_id');
        file_utils_delete_proc($proc_id);
    }
}

$renderer  = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/frs');
$presenter = new ToolbarPresenter($project);
$presenter->setProcessorsIsActive();
$presenter->displaySectionNavigation();

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
        if ($procrank == '') {
            $feedback .= ' ' . $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_rank'));
        } elseif ($procname == '') {
            $feedback .= ' ' . $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_name'));
        } else {
            file_utils_add_proc($procname, $procrank);
        }
    } else {
        $feedback .= $Language->getText('file_file_utils', 'add_proc_fail');
    }
}

$vProcId = new Valid_UInt('proc_id');
$vProcId->required();
$vUpdate      = new Valid_String('update');
$vProcessName = new Valid_String('processname');
$vProcessName->required();
$vProcessRank = new Valid_UInt('processrank');
$vProcessRank->required();

if ($request->isPost() && $request->existAndNonEmpty('update')) {
    // update a processor
    if (
        $request->valid($vProcessName) &&
        $request->valid($vProcessRank) &&
        $request->valid($vProcId) &&
        $request->valid($vUpdate)
    ) {
        $proc_id     = $request->get('proc_id');
        $processname = $request->get('processname');
        $processrank = $request->get('processrank');
        if ($processrank == '') {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_rank')),
            );
        } elseif ($processname == '') {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $Language->getText('file_admin_manageprocessors', 'proc_fill', $Language->getText('file_file_utils', 'proc_name')),
            );
        } else {
            file_utils_update_proc($proc_id, $processname, $processrank);
        }
    } else {
        $GLOBALS['Response']->addFeedback(Feedback::ERROR, $Language->getText('file_file_utils', 'update_proc_fail'));
    }
}

$sql    = 'SELECT * FROM frs_processor WHERE group_id=' . db_ei($group_id) . ' OR group_id=100 ORDER BY `rank`';
$result = db_query($sql);

$service->displayFRSHeader($project, _('Files Administration'));
$renderer->renderToPage('toolbar-presenter', $presenter);

?>
<div class="tlp-framed">
    <h2><?php echo $Language->getText('file_admin_manageprocessors', 'manage_proclist'); ?></h2>

    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i class="tlp-pane-title-icon fa-solid fa-list" aria-hidden="true"></i>
                    <?php echo _('Processors list'); ?>
                </h1>
            </div>
            <div class="tlp-pane-section">
<?php

file_utils_show_processors($result);

?>

            </div>
        </div>
    </section>

    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i class="tlp-pane-title-icon fa-solid fa-plus" aria-hidden="true"></i>
                    <?php echo $Language->getText('file_admin_manageprocessors', 'add_proc'); ?>
                </h1>
            </div>
            <form action="" method="post" class="tlp-pane-section">
<?php

$purifier = Codendi_HTMLPurifier::instance();

$return = '
    <input type="hidden" name="group_id" value="' . $purifier->purify($group_id) . '">

    <div class="tlp-form-element">
        <label class="tlp-label" for="procname">
            ' . $Language->getText('file_file_utils', 'proc_name') . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>
        <input type="text" id="procname" name="procname" class="tlp-input" size="30" placeholder="x86_64">
    </div>
    <div class="tlp-form-element">
        <label class="tlp-label" for="procrank">
            ' . $Language->getText('file_file_utils', 'proc_rank') . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>
        <input type="text" id="procrank" name="procrank" class="tlp-input" size="10">
    </div>

    <div class="tlp-pane-section-submit">
        <button type="submit" name="add" value="1" class="tlp-button-primary">
            <i class="tlp-pane-title-icon fa-solid fa-save" aria-hidden="true"></i>
            ' . $Language->getText('file_file_utils', 'add_proc') . '
        </button>
    </div>';
echo $return;
?>
            </form>
        </div>
    </section>
</div>
<?php

file_utils_footer([]);
