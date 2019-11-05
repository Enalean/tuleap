<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Tests\Integration;

use EventManager;
use ForgeConfig;
use PHPUnit\Framework\TestCase;
use ProjectCreator;
use ProjectHistoryDao;
use ProjectManager;
use ReferenceManager;
use ServiceDao;
use Tuleap\Dashboard\Project\ProjectDashboardDao;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\Dashboard\Project\ProjectDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\Dashboard\Widget\DashboardWidgetRetriever;
use Tuleap\DB\DBFactory;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\FRSPermissionDao;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\UgroupDuplicator;
use Tuleap\Project\UGroups\Membership\DynamicUGroups\ProjectMemberAdderWithoutStatusCheckAndNotifications;
use Tuleap\Project\UGroups\Membership\MemberAdder;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator;
use Tuleap\Service\ServiceCreator;
use Tuleap\Widget\WidgetFactory;
use UGroupBinding;
use UGroupDao;
use UGroupManager;
use UGroupUserDao;
use User_ForgeUserGroupPermissionsDao;
use User_ForgeUserGroupPermissionsManager;
use UserManager;

class ProjectCreationTest extends TestCase
{
    use GlobalLanguageMock;

    public function setUp(): void
    {
        $GLOBALS['svn_prefix'] = '/tmp';
        $GLOBALS['cvs_prefix'] = '/tmp';
        $GLOBALS['sys_default_domain'] = '';
    }

    public function tearDown(): void
    {
        DBFactory::getMainTuleapDBConnection()->getDB()->run('DELETE FROM groups WHERE unix_group_name = "short-name"');
        unset($GLOBALS['svn_prefix']);
        unset($GLOBALS['cvs_prefix']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['feedback']);
        unset($GLOBALS['TROVE_BROWSELIMIT']);
        unset($GLOBALS['SVNACCESS']);
        unset($GLOBALS['SVNGROUPS']);
        $_GET = [];
        $_REQUEST = [];
    }

    public function testItCreatesAProject(): void
    {
        $send_notifications = true;
        $ugroup_user_dao    = new UGroupUserDao();
        $ugroup_manager     = new UGroupManager();
        $ugroup_binding     = new UGroupBinding($ugroup_user_dao, $ugroup_manager);
        $ugroup_duplicator  = new UgroupDuplicator(
            new UGroupDao(),
            $ugroup_manager,
            $ugroup_binding,
            MemberAdder::build(ProjectMemberAdderWithoutStatusCheckAndNotifications::build()),
            EventManager::instance()
        );

        $user_manager   = UserManager::instance();
        $widget_factory = new WidgetFactory(
            $user_manager,
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            EventManager::instance()
        );

        $widget_dao        = new DashboardWidgetDao($widget_factory);
        $project_dao       = new ProjectDashboardDao($widget_dao);
        $project_retriever = new ProjectDashboardRetriever($project_dao);
        $widget_retriever  = new DashboardWidgetRetriever($widget_dao);
        $duplicator        = new ProjectDashboardDuplicator(
            $project_dao,
            $project_retriever,
            $widget_dao,
            $widget_retriever,
            $widget_factory
        );

        $force_activation = false;

        ForgeConfig::set('sys_use_project_registration', 1);

        $projectCreator = new ProjectCreator(
            ProjectManager::instance(),
            ReferenceManager::instance(),
            $user_manager,
            $ugroup_duplicator,
            $send_notifications,
            new FRSPermissionCreator(
                new FRSPermissionDao(),
                new UGroupDao(),
                new ProjectHistoryDao()
            ),
            $duplicator,
            new ServiceCreator(new ServiceDao()),
            new LabelDao(),
            new DefaultProjectVisibilityRetriever(),
            new SynchronizedProjectMembershipDuplicator(new SynchronizedProjectMembershipDao()),
            new \Rule_ProjectName(),
            new \Rule_ProjectFullName(),
            $force_activation
        );

        $projectCreator->create('short-name', 'Long name', array(
            'project' => array(
                'form_short_description' => 'description',
                'is_test'                => false,
                'is_public'              => false,
                'services'               => array(),
                'built_from_template'    => 100,
            )
        ));

        ProjectManager::clearInstance();
        $project = ProjectManager::instance()->getProjectByUnixName('short-name');
        $this->assertEquals('Long name', $project->getPublicName());
    }
}
