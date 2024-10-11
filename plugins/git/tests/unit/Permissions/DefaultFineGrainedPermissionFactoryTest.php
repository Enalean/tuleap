<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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

namespace Tuleap\Git\Permissions;

use Codendi_Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

class DefaultFineGrainedPermissionFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DefaultFineGrainedPermissionFactory
     */
    private $factory;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $project;
    /**
     * @var \UGroupManager&Mockery\MockInterface
     */
    private $ugroup_manager;
    /**
     * @var \PermissionsNormalizer&Mockery\MockInterface
     */
    private $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $dao                  = Mockery::mock(FineGrainedDao::class);
        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $this->normalizer     = \Mockery::spy(\PermissionsNormalizer::class);

        $this->factory = new DefaultFineGrainedPermissionFactory(
            $dao,
            $this->ugroup_manager,
            $this->normalizer,
            \Mockery::spy(\PermissionsManager::class),
            new PatternValidator(
                new FineGrainedPatternValidator(),
                new FineGrainedRegexpValidator(),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class)
            ),
            new FineGrainedPermissionSorter(),
        );

        $this->project         = \Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns(101)->getMock();
        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $ugroup_01 = \Mockery::mock(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $ugroup_02 = \Mockery::mock(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $ugroup_03 = \Mockery::mock(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(103)->getMock();

        $this->ugroup_manager->shouldReceive('getById')->with(101)->andReturns($ugroup_01);
        $this->ugroup_manager->shouldReceive('getById')->with(102)->andReturns($ugroup_02);
        $this->ugroup_manager->shouldReceive('getById')->with(103)->andReturns($ugroup_03);
        $this->project_manager->shouldReceive('getProject')->with(101)->andReturns($this->project);
        $this->normalizer->shouldReceive('getNormalizedUGroupIds')->andReturns([]);

        $dao->shouldReceive('searchDefaultBranchesFineGrainedPermissions')->andReturns(\TestHelper::arrayToDar([
            'id'         => 1,
            'project_id' => 101,
            'pattern'    => 'refs/heads/master',
        ]));

        $dao->shouldReceive('searchDefaultTagsFineGrainedPermissions')->andReturns(\TestHelper::arrayToDar([
            'id'         => 2,
            'project_id' => 101,
            'pattern'    => 'refs/tags/v1',
        ]));

        $dao->shouldReceive('searchDefaultWriterUgroupIdsForFineGrainedPermissions')->with(1)->andReturns(\TestHelper::arrayToDar(['ugroup_id' => 101], ['ugroup_id' => 102]));

        $dao->shouldReceive('searchDefaultRewinderUgroupIdsForFineGrainePermissions')->with(1)->andReturns(\TestHelper::arrayToDar([
            'ugroup_id' => 103,
        ]));

        $dao->shouldReceive('searchDefaultWriterUgroupIdsForFineGrainedPermissions')->with(2)->andReturns(\TestHelper::arrayToDar([
            'ugroup_id' => 101,
        ]));

        $dao->shouldReceive('searchDefaultRewinderUgroupIdsForFineGrainePermissions')->with(2)->andReturns(\TestHelper::arrayToDar([
            'ugroup_id' => 102,
        ]));
    }

    private function buildRequest(array $params): Codendi_Request
    {
        return new Codendi_Request(
            $params,
            $this->project_manager
        );
    }

    public function testItRetrievesUpdatedPermissions(): void
    {
        $params = [
            'edit-branch-write' => [1 => [101, 102]],
            'edit-branch-rewind' => [1 => [102]],
            'edit-tag-write' => [2 => [101]],
            'edit-tag-rewind' => [2 => [102]],
            'group_id' => 101,
        ];

        $request = $this->buildRequest($params);

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->project);

        $this->assertNotEmpty($updated);
        $this->assertCount(1, $updated);
        $this->assertEquals([1], array_keys($updated));
    }

    public function testItDealsWithRemovedUgroups(): void
    {
        $params = [
            'edit-branch-write' => [1 => [101, 102]],
            'edit-branch-rewind' => [1 => [103]],
            'edit-tag-rewind' => [2 => [102]],
            'group_id' => 101,
        ];

        $request = $this->buildRequest($params);

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->project);

        $this->assertNotEmpty($updated);
        $this->assertCount(1, $updated);
        $this->assertEquals([2], array_keys($updated));
    }
}
