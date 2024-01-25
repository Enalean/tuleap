<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\SVN\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class SvnCoreAccessTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \Project $project;
    private MockObject&RepositoryManager $repository_manager;
    private SvnCoreAccess $plugin_svn_access;

    public function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->build();

        $this->repository_manager = $this->createMock(RepositoryManager::class);
        $this->repository_manager
            ->method('getCoreRepository')
            ->with($this->project)
            ->willReturn(
                CoreRepository::buildActiveRepository(
                    [
                        'id' => '29',
                        'name' => 'foo',
                        'project_id' => '101',
                        'is_core' => '1',
                        'has_default_permissions' => '1',
                        'accessfile_id' => '1001',
                        'repository_deletion_date' => null,
                        'backup_path' => null,
                    ],
                    $this->project
                )
            );

        $this->plugin_svn_access = new SvnCoreAccess($this->repository_manager);
    }

    public function testItRedirectsAccessToCoreSubversionIntro(): void
    {
        $event = new \Tuleap\SVNCore\SvnCoreAccess(
            $this->project,
            '/svn/?func=info&group_id=101',
            LayoutBuilder::build()
        );
        $this->plugin_svn_access->process($event);

        $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/svn/?roottype=svn&root=TestProject'));
        $event->redirect();
    }

    public function testItRedirectsAccessToViewVcRoot(): void
    {
        $event = new \Tuleap\SVNCore\SvnCoreAccess(
            $this->project,
            '/svn/viewvc.php/?root=TestProject',
            LayoutBuilder::build()
        );
        $this->plugin_svn_access->process($event);

        $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/svn/?root=TestProject'));
        $event->redirect();
    }

    public function testItRedirectsAccessToViewVcDirectory(): void
    {
        $event = new \Tuleap\SVNCore\SvnCoreAccess(
            $this->project,
            '/svn/viewvc.php/trunk/?root=mozilla',
            LayoutBuilder::build()
        );
        $this->plugin_svn_access->process($event);

        $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/svn/trunk/?root=mozilla'));
        $event->redirect();
    }

    public function testItRedirectsAccessToViewVcFileLog(): void
    {
        $event = new \Tuleap\SVNCore\SvnCoreAccess(
            $this->project,
            '/svn/viewvc.php/trunk/README?root=mozilla&view=log',
            LayoutBuilder::build()
        );
        $this->plugin_svn_access->process($event);

        $this->expectExceptionObject(new LayoutInspectorRedirection('/plugins/svn/trunk/README?root=mozilla&view=log'));
        $event->redirect();
    }
}
