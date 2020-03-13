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

namespace Tuleap\Git\Permissions;

use Codendi_Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class FineGrainedPermissionFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ProjectManager
     */
    private $project_manager;

    /**
     * @var FineGrainedPermissionFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dao            = Mockery::mock(FineGrainedDao::class);
        $this->ugroup_manager = \Mockery::spy(\UGroupManager::class);
        $this->normalizer     = \Mockery::spy(\PermissionsNormalizer::class);

        $this->factory = new FineGrainedPermissionFactory(
            $this->dao,
            $this->ugroup_manager,
            $this->normalizer,
            \Mockery::spy(\PermissionsManager::class),
            new PatternValidator(
                new FineGrainedPatternValidator(),
                new FineGrainedRegexpValidator(),
                \Mockery::spy(\Tuleap\Git\Permissions\RegexpFineGrainedRetriever::class)
            ),
            new FineGrainedPermissionSorter(),
            \Mockery::spy(\Tuleap\Git\XmlUgroupRetriever::class)
        );

        $this->repository = Mockery::mock(\GitRepository::class)->shouldReceive('getId')->andReturn(43)->getMock();

        $project               = \Mockery::spy(\Project::class);
        $this->project_manager = \Mockery::spy(\ProjectManager::class);

        $ugroup_01 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $ugroup_02 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $ugroup_03 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(103)->getMock();

        $this->ugroup_manager->shouldReceive('getById')->with(101)->andReturns($ugroup_01);
        $this->ugroup_manager->shouldReceive('getById')->with(102)->andReturns($ugroup_02);
        $this->ugroup_manager->shouldReceive('getById')->with(103)->andReturns($ugroup_03);
        $this->project_manager->shouldReceive('getProject')->with(101)->andReturns($project);
        $this->normalizer->shouldReceive('getNormalizedUGroupIds')->andReturns(array());

        $this->dao->shouldReceive('searchBranchesFineGrainedPermissionsForRepository')->andReturns(\TestHelper::arrayToDar(array(
            'id'            => 1,
            'repository_id' => 43,
            'pattern'       => 'refs/heads/master',
        )));

        $this->dao->shouldReceive('searchTagsFineGrainedPermissionsForRepository')->andReturns(\TestHelper::arrayToDar(array(
            'id'            => 2,
            'repository_id' => 43,
            'pattern'       => 'refs/tags/v1',
        )));

        $this->dao->shouldReceive('searchWriterUgroupIdsForFineGrainedPermissions')->with(1)->andReturns(\TestHelper::arrayToDar(array('ugroup_id' => 101), array('ugroup_id' => 102)));

        $this->dao->shouldReceive('searchRewinderUgroupIdsForFineGrainePermissions')->with(1)->andReturns(\TestHelper::arrayToDar(array(
            'ugroup_id' => 103,
        )));

        $this->dao->shouldReceive('searchWriterUgroupIdsForFineGrainedPermissions')->with(2)->andReturns(\TestHelper::arrayToDar(array(
            'ugroup_id' => 101,
        )));

        $this->dao->shouldReceive('searchRewinderUgroupIdsForFineGrainePermissions')->with(2)->andReturns(\TestHelper::arrayToDar(array(
            'ugroup_id' => 102,
        )));
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
            'edit-branch-write' => array(1 => array(101, 102)),
            'edit-branch-rewind' => array(1 => array(102)),
            'edit-tag-write' => array(2 => array(101)),
            'edit-tag-rewind' => array(2 => array(102)),
            'group_id' => 101
        ];

        $request = $this->buildRequest($params);

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->repository);

        $this->assertNotEmpty($updated);
        $this->assertCount(1, $updated);
        $this->assertEquals(array(1), array_keys($updated));
    }

    public function testItDealsWithRemovedUgroups(): void
    {
        $params = [
            'edit-branch-write' => array(1 => array(101, 102)),
            'edit-branch-rewind' => array(1 => array(103)),
            'edit-tag-rewind' => array(2 => array(102)),
            'group_id' => 101
        ];

        $request = $this->buildRequest($params);

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->repository);

        $this->assertNotEmpty($updated);
        $this->assertCount(1, $updated);
        $this->assertEquals(array(2), array_keys($updated));
    }
}
