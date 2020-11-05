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
use PHPUnit\Framework\TestCase;
use Tuleap\SVN\Dao;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class SvnCoreAccessTest extends TestCase
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
        $layout_inspector = new LayoutInspector();
        $event = new \Tuleap\SVN\SvnCoreAccess(
            $this->project,
            '/svn/?func=info&group_id=101',
            LayoutBuilder::buildWithInspector($layout_inspector)
        );
        $this->plugin_svn_access->process($event);
        $event->redirect();

        self::assertEquals('/plugins/svn/?roottype=svn&root=TestProject', $layout_inspector->getRedirectUrl());
    }

    public function testItRedirectsAccessToViewVcRoot(): void
    {
        $layout_inspector = new LayoutInspector();
        $event = new \Tuleap\SVN\SvnCoreAccess(
            $this->project,
            '/svn/viewvc.php/?root=TestProject',
            LayoutBuilder::buildWithInspector($layout_inspector)
        );
        $this->plugin_svn_access->process($event);
        $event->redirect();

        self::assertEquals('/plugins/svn/?root=TestProject', $layout_inspector->getRedirectUrl());
    }

    public function testItRedirectsAccessToViewVcDirectory(): void
    {
        $layout_inspector = new LayoutInspector();
        $event = new \Tuleap\SVN\SvnCoreAccess(
            $this->project,
            '/svn/viewvc.php/trunk/?root=mozilla',
            LayoutBuilder::buildWithInspector($layout_inspector)
        );
        $this->plugin_svn_access->process($event);
        $event->redirect();

        self::assertEquals('/plugins/svn/trunk/?root=mozilla', $layout_inspector->getRedirectUrl());
    }

    public function testItRedirectsAccessToViewVcFileLog(): void
    {
        $layout_inspector = new LayoutInspector();
        $event = new \Tuleap\SVN\SvnCoreAccess(
            $this->project,
            '/svn/viewvc.php/trunk/README?root=mozilla&view=log',
            LayoutBuilder::buildWithInspector($layout_inspector)
        );
        $this->plugin_svn_access->process($event);
        $event->redirect();

        self::assertEquals('/plugins/svn/trunk/README?root=mozilla&view=log', $layout_inspector->getRedirectUrl());
    }

    public function testItForbidsSOAPAccess(): void
    {
        $event = new \Tuleap\SVN\SvnCoreAccess(
            $this->project,
            \SVN_SOAPServer::FAKE_URL,
            null
        );
        $this->plugin_svn_access->process($event);

        self::assertTrue($event->hasRedirectUri());
    }
}
