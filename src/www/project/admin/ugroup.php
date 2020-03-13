<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\ProjectUGroup\CannotCreateUGroupException;
use Tuleap\Project\Admin\ProjectUGroup\UGroupListPresenterBuilder;
use Tuleap\Project\Admin\ProjectUGroup\UGroupRouter;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/permissions.php';

$em      = EventManager::instance();
$request = HTTPRequest::instance();

$group_id = $request->getValidated('group_id', 'GroupId', 0);

$ugroup_delete_token = new CSRFSynchronizerToken('/project/admin/ugroup.php');

session_require(array('group' => $group_id, 'admin_flags' => 'A'));

if ($request->existAndNonEmpty('func')) {
    $ugroup_id   = $request->getValidated('ugroup_id', 'UInt', 0);

    switch ($request->get('func')) {
        case 'delete':
            $ugroup_delete_token->check();
            if ($group_id > 100) {
                ugroup_delete($group_id, $ugroup_id);
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, _('You can not delete system user groups'));
            }
            break;
        case 'do_create':
            $name     = $request->getValidated('ugroup_name', 'String', '');
            $desc     = $request->getValidated('ugroup_description', 'String', '');
            $template = $request->getValidated('group_templates', 'String', '');

            try {
                $ugroup_id = ugroup_create($group_id, $name, $desc, $template);
                $GLOBALS['Response']->redirect(
                    '/project/admin/editugroup.php?group_id=' . urlencode($group_id) .
                    '&ugroup_id=' . urlencode($ugroup_id)
                );
            } catch (CannotCreateUGroupException $exception) {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $exception->getMessage());
            }
            break;
    }
    $GLOBALS['Response']->redirect('/project/admin/ugroup.php?group_id=' . urlencode($group_id));
}

$pm = ProjectManager::instance();
$project = $pm->getProject($group_id);

$title = $Language->getText('project_admin_ugroup', 'manage_ug');

$include_assets = new IncludeAssets(ForgeConfig::get('tuleap_dir') . '/src/www/assets', '/assets');
$GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('project-admin-ugroups.js'));

$navigation_displayer = new HeaderNavigationDisplayer();
$navigation_displayer->displayBurningParrotNavigation($title, $project, 'groups');

$presenter_builder = new UGroupListPresenterBuilder(
    new UGroupManager(),
    new SynchronizedProjectMembershipDetector(new SynchronizedProjectMembershipDao())
);

$synchronized_membership_token = UGroupRouter::getCSRFTokenSynchronizer();

$templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/project/admin/user_groups';
TemplateRendererFactory::build()
    ->getRenderer($templates_dir)
    ->renderToPage(
        'list-groups',
        $presenter_builder->build($project, $ugroup_delete_token, $synchronized_membership_token)
    );

project_admin_footer(array());
