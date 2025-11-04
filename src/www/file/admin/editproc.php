<?php
/**
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;

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


$vProcId = new Valid_UInt('proc_id');
$vProcId->required();
if ($request->valid($vProcId)) {
    $proc_id = $request->get('proc_id');
} else {
    $GLOBALS['Response']->redirect('manageprocessors.php?group_id=' . $group_id);
}


$renderer  = TemplateRendererFactory::build()->getRenderer(__DIR__ . '/../../../templates/frs');
$presenter = new ToolbarPresenter($project);
$presenter->setProcessorsIsActive();
$presenter->displaySectionNavigation();

$service->displayFRSHeader($project, _('Files Administration'), new BreadCrumbCollection());
$renderer->renderToPage('toolbar-presenter', $presenter);

$sql    = 'SELECT name, `rank` FROM frs_processor WHERE group_id=' . db_ei($group_id) . ' AND processor_id=' . db_ei($proc_id);
$result = db_query($sql);
$name   = db_result($result, 0, 'name');
$rank   = db_result($result, 0, 'rank');

if (db_numrows($result) < 1) {
    // invalid  processor  id
    $feedback .= ' ' . $Language->getText('file_admin_manageprocessors', 'invalid_procid');
    file_utils_footer([]);
    exit;
}

?>
<div class="tlp-framed">
    <section class="tlp-pane">
        <div class="tlp-pane-container">
            <div class="tlp-pane-header">
                <h1 class="tlp-pane-title">
                    <i class="tlp-pane-title-icon fa-solid fa-pencil" aria-hidden="true"></i>
                    <?php echo $Language->getText('file_admin_manageprocessors', 'update_proc'); ?>
                </h1>
            </div>
<?php

$purifier = Codendi_HTMLPurifier::instance();

$management_page_url = '/file/admin/manageprocessors.php?group_id=' . urlencode((string) $project->getID());
$csrf_token          = new CSRFSynchronizerToken($management_page_url);

$return = '
<form action="' . $purifier->purify($management_page_url) . '" method="post" class="tlp-pane-section">
    <input type="hidden" name="group_id" value="' . $purifier->purify($group_id) . '">
    <input type="hidden" name="proc_id" value="' . $purifier->purify($proc_id) . '">
    ' . $csrf_token->fetchHTMLInput() . '

    <div class="tlp-form-element">
        <label class="tlp-label" for="processname">
            ' . $Language->getText('file_file_utils', 'proc_name') . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>
        <input type="text" id="processname" name="processname" class="tlp-input" size="30" placeholder="x86_64" value="' . $purifier->purify($name) . '">
    </div>
    <div class="tlp-form-element">
        <label class="tlp-label" for="processrank">
            ' . $Language->getText('file_file_utils', 'proc_rank') . '
            <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
        </label>
        <input type="text" id="processrank" name="processrank" class="tlp-input" size="10" value="' . $purifier->purify($rank) . '" >
    </div>

    <div class="tlp-pane-section-submit">
        <button type="submit" name="update" value="1" class="tlp-button-primary">
            <i class="tlp-pane-title-icon fa-solid fa-save" aria-hidden="true"></i>
            ' . $Language->getText('file_file_utils', 'update_proc') . '
        </button>
    </div>
</form>';
echo $return;
?>
        </div>
    </section>
</div>
<?php

file_utils_footer([]);
