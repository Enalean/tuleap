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
use GitRepository;
use PermissionsManager;
use PermissionsNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use TestHelper;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Git\XmlUgroupRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UGroupManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FineGrainedPermissionFactoryTest extends TestCase
{
    private ProjectManager&MockObject $project_manager;
    private FineGrainedPermissionFactory $factory;
    private GitRepository $repository;

    protected function setUp(): void
    {
        $dao            = $this->createMock(FineGrainedDao::class);
        $ugroup_manager = $this->createMock(UGroupManager::class);
        $normalizer     = $this->createMock(PermissionsNormalizer::class);

        $this->factory = new FineGrainedPermissionFactory(
            $dao,
            $ugroup_manager,
            $normalizer,
            $this->createMock(PermissionsManager::class),
            new PatternValidator(
                new FineGrainedPatternValidator(),
                new FineGrainedRegexpValidator(),
                $this->createMock(RegexpFineGrainedRetriever::class)
            ),
            new FineGrainedPermissionSorter(),
            $this->createMock(XmlUgroupRetriever::class)
        );

        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(43)->build();

        $project               = ProjectTestBuilder::aProject()->build();
        $this->project_manager = $this->createMock(ProjectManager::class);

        $ugroup_01 = ProjectUGroupTestBuilder::aCustomUserGroup(101)->build();
        $ugroup_02 = ProjectUGroupTestBuilder::aCustomUserGroup(102)->build();
        $ugroup_03 = ProjectUGroupTestBuilder::aCustomUserGroup(103)->build();

        $ugroup_manager->method('getById')->willReturnCallback(static fn($id) => match ($id) {
            101 => $ugroup_01,
            102 => $ugroup_02,
            103 => $ugroup_03,
        });
        $this->project_manager->method('getProjectById')->with(101)->willReturn($project);
        $normalizer->method('getNormalizedUGroupIds')->willReturn([]);

        $dao->method('searchBranchesFineGrainedPermissionsForRepository')->willReturn(TestHelper::arrayToDar([
            'id'            => 1,
            'repository_id' => 43,
            'pattern'       => 'refs/heads/master',
        ]));

        $dao->method('searchTagsFineGrainedPermissionsForRepository')->willReturn(TestHelper::arrayToDar([
            'id'            => 2,
            'repository_id' => 43,
            'pattern'       => 'refs/tags/v1',
        ]));

        $dao->method('searchWriterUgroupIdsForFineGrainedPermissions')
            ->willReturnCallback(static fn($id) => match ($id) {
                1 => TestHelper::arrayToDar(['ugroup_id' => 101], ['ugroup_id' => 102]),
                2 => TestHelper::arrayToDar(['ugroup_id' => 101]),
            });

        $dao->method('searchRewinderUgroupIdsForFineGrainePermissions')
            ->willReturnCallback(static fn($id) => match ($id) {
                1 => TestHelper::arrayToDar(['ugroup_id' => 103]),
                2 => TestHelper::arrayToDar(['ugroup_id' => 102]),
            });
    }

    private function buildRequest(array $params): Codendi_Request
    {
        return new Codendi_Request($params, $this->project_manager);
    }

    public function testItRetrievesUpdatedPermissions(): void
    {
        $params = [
            'edit-branch-write'  => [1 => [101, 102]],
            'edit-branch-rewind' => [1 => [102]],
            'edit-tag-write'     => [2 => [101]],
            'edit-tag-rewind'    => [2 => [102]],
            'group_id'           => 101,
        ];

        $request = $this->buildRequest($params);

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->repository);

        self::assertNotEmpty($updated);
        self::assertCount(1, $updated);
        self::assertEquals([1], array_keys($updated));
    }

    public function testItDealsWithRemovedUgroups(): void
    {
        $params = [
            'edit-branch-write'  => [1 => [101, 102]],
            'edit-branch-rewind' => [1 => [103]],
            'edit-tag-rewind'    => [2 => [102]],
            'group_id'           => 101,
        ];

        $request = $this->buildRequest($params);

        $updated = $this->factory->getUpdatedPermissionsFromRequest($request, $this->repository);

        self::assertNotEmpty($updated);
        self::assertCount(1, $updated);
        self::assertEquals([2], array_keys($updated));
    }
}
