<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Project\Admin\PermissionsPerGroup;

use ForgeAccess;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Service;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\ProjectTestBuilder;
use UGroupManager;

final class PermissionPerGroupBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private UGroupManager&MockObject $ugroup_manager;
    private PermissionPerGroupBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ugroup_manager = $this->createMock(\UGroupManager::class);
        $this->builder        = new PermissionPerGroupBuilder($this->ugroup_manager);
        $GLOBALS['Language']->method('getText')->willReturn('whatever');
    }

    public function testItAddsAnonymousUgroupIfPlatformAllowsThem(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withAccessPrivate()
            ->withUsedService(Service::WIKI)
            ->build();
        $request = $this->createMock(\HTTPRequest::class);
        $request->method('get');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->ugroup_manager->method('getStaticUGroups')->with($project)->willReturn([]);
        $this->ugroup_manager->method('getUGroup');

        $ugroups = $this->builder->buildUGroup($project, $request);

        self::assertEquals(ProjectUGroup::ANONYMOUS, $ugroups['dynamic'][0]['id']);
    }

    public function testItAddsAuthenticatedUgroupIfPlatformAllowsThem(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withAccessPrivate()
            ->withUsedService(Service::WIKI)
            ->build();
        $request = $this->createMock(\HTTPRequest::class);
        $request->method('get');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->ugroup_manager->method('getStaticUGroups')->with($project)->willReturn([]);
        $this->ugroup_manager->method('getUGroup');

        $ugroups = $this->builder->buildUGroup($project, $request);

        self::assertEquals(ProjectUGroup::AUTHENTICATED, $ugroups['dynamic'][0]['id']);
    }

    public function testItAlwaysAddRegisteredUgroup(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withAccessPrivate()
            ->withUsedService(Service::WIKI)
            ->build();
        $request = $this->createMock(\HTTPRequest::class);
        $request->method('get');

        $this->ugroup_manager->method('getStaticUGroups')->with($project)->willReturn([]);
        $this->ugroup_manager->method('getUGroup');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $ugroups = $this->builder->buildUGroup($project, $request);
        self::assertEquals(ProjectUGroup::REGISTERED, $ugroups['dynamic'][1]['id']);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $ugroups = $this->builder->buildUGroup($project, $request);
        self::assertEquals(ProjectUGroup::REGISTERED, $ugroups['dynamic'][1]['id']);
    }
}
