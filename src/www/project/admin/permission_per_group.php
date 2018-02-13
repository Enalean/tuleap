<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Tuleap\FRS\FRSPermissionDao;
use Tuleap\FRS\FRSPermissionFactory;
use Tuleap\FRS\PerGroup\PaneCollector;
use Tuleap\FRS\PerGroup\PermissionPerGroupFRSPackagesPresenterBuilder;
use Tuleap\FRS\PerGroup\PermissionPerGroupFRSServicePresenterBuilder;
use Tuleap\FRS\PerGroup\PermissionPerTypeExtractor;
use Tuleap\News\Admin\AdminNewsDao;
use Tuleap\News\Admin\PerGroup\NewsPermissionPerGroupPaneBuilder;
use Tuleap\News\Admin\PerGroup\NewsPermissionsManager;
use Tuleap\PHPWiki\PerGroup\PHPWikiPermissionPerGroupPaneBuilder;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\Permission\PanesPermissionPerGroupBuilder;
use Tuleap\Project\Admin\Permission\PermissionPerGroupBuilder;
use Tuleap\Project\Admin\Permission\PermissionPerGroupPresenter;
use Tuleap\Project\Admin\Permission\PermissionPerGroupUGroupRetriever;

require_once('pre.php');

$request  = HTTPRequest::instance();
$group_id = $request->getValidated('group_id', 'GroupId', 0);

session_require(array('group' => $group_id, 'admin_flags' => 'A'));

$project_manager = ProjectManager::instance();
$project         = $project_manager->getProject($group_id);

$title = _('Permissions per group');

$navigation_displayer = new HeaderNavigationDisplayer();
$navigation_displayer->displayBurningParrotNavigation($title, $project, 'permissions');

$ugroup_manager    = new UGroupManager();
$formatter         = new PermissionPerGroupUGroupFormatter($ugroup_manager);
$presenter_builder = new PermissionPerGroupBuilder($ugroup_manager);
$groups            = $presenter_builder->buildUGroup($project, $request);
$formatter         = new PermissionPerGroupUGroupFormatter($ugroup_manager);

$additional_panes_builder = new PanesPermissionPerGroupBuilder(
    EventManager::instance(),
    new PaneCollector(
        new PermissionPerGroupFRSServicePresenterBuilder(
            new PermissionPerTypeExtractor(
                new FRSPermissionFactory(new FRSPermissionDao()),
                $formatter
            ),
            $ugroup_manager
        ),
        new PermissionPerGroupFRSPackagesPresenterBuilder(
            $ugroup_manager,
            new PermissionPerGroupUGroupRetriever(PermissionsManager::instance()),
            new FRSPackageFactory(),
            $formatter
        )
    ),
    new PHPWikiPermissionPerGroupPaneBuilder(
        new Wiki_PermissionsManager(
            PermissionsManager::instance(),
            $project_manager,
            new UGroupLiteralizer()
        ),
        $formatter,
        $ugroup_manager,
        TemplateRendererFactory::build()
    ),
    new NewsPermissionPerGroupPaneBuilder(
        new NewsPermissionsManager(
            PermissionsManager::instance(),
            new AdminNewsDAO()
        ),
        $formatter,
        $ugroup_manager
    )
);

$additional_panes = $additional_panes_builder->getPanes($project, $request->get('group'));
$presenter        = new PermissionPerGroupPresenter($project, $groups, $additional_panes);

$templates_dir = ForgeConfig::get('tuleap_dir') . '/src/templates/project/admin/';
TemplateRendererFactory::build()
    ->getRenderer($templates_dir)
    ->renderToPage('permission-per-group', $presenter);

project_admin_footer(array());
