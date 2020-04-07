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
use ProjectUGroup;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use UGroupManager;

final class PermissionPerGroupBuilderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionPerGroupBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ugroup_manager = \Mockery::mock(\UGroupManager::class);
        $this->builder        = new PermissionPerGroupBuilder($this->ugroup_manager);
    }

    public function testItAddsAnonymousUgroupIfPlatformAllowsThem(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
        $request = \Mockery::spy(\HTTPRequest::class);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($project)->andReturns(array());

        $ugroups = $this->builder->buildUGroup($project, $request);

        $this->assertEquals(ProjectUGroup::ANONYMOUS, $ugroups['dynamic'][0]['id']);
    }

    public function testItAddsAuthenticatedUgroupIfPlatformAllowsThem(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
        $request = \Mockery::spy(\HTTPRequest::class);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($project)->andReturns(array());

        $ugroups = $this->builder->buildUGroup($project, $request);

        $this->assertEquals(ProjectUGroup::AUTHENTICATED, $ugroups['dynamic'][0]['id']);
    }

    public function testItAlwaysAddRegisteredUgroup(): void
    {
        $project = \Mockery::spy(\Project::class, ['getID' => false, 'getUnixName' => false, 'isPublic' => false]);
        $request = \Mockery::spy(\HTTPRequest::class);

        $this->ugroup_manager->shouldReceive('getStaticUGroups')->with($project)->andReturns(array());

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $ugroups = $this->builder->buildUGroup($project, $request);
        $this->assertEquals(ProjectUGroup::REGISTERED, $ugroups['dynamic'][1]['id']);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $ugroups = $this->builder->buildUGroup($project, $request);
        $this->assertEquals(ProjectUGroup::REGISTERED, $ugroups['dynamic'][1]['id']);
    }
}
