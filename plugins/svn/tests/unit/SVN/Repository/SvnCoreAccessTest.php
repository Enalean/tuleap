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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\SVN\Dao;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class SvnCoreAccessTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Project
     */
    private $project;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Dao
     */
    private $dao;
    /**
     * @var SvnCoreAccess
     */
    private $plugin_svn_access;

    public function setUp(): void
    {
        $this->project = ProjectTestBuilder::aProject()->build();

        $this->dao = \Mockery::mock(Dao::class);
        $this->dao->shouldReceive('getCoreRepositoryId')->with($this->project)->andReturn(29);

        $this->plugin_svn_access = new SvnCoreAccess($this->dao);
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
