<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use GitRepository;
use Mockery;
use PHPUnit\Framework\TestCase;
use Git;

require_once __DIR__ . '/../../bootstrap.php';

class HistoryValueFormatterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var GitRepository|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $repository;

    /**
     * @var GitRepository|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $migrated_repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ugroup_01 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(101)->getMock();
        $this->ugroup_02 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(102)->getMock();
        $this->ugroup_03 = \Mockery::spy(\ProjectUGroup::class)->shouldReceive('getId')->andReturns(103)->getMock();

        $this->ugroup_01->shouldReceive('getName')->andReturns('Contributors');
        $this->ugroup_02->shouldReceive('getName')->andReturns('Developers');
        $this->ugroup_03->shouldReceive('getName')->andReturns('Admins');

        $this->project    = \Mockery::spy(\Project::class)->shouldReceive('getID')->andReturns(101)->getMock();
        $this->repository = Mockery::mock(GitRepository::class);
        $this->repository->shouldReceive('getId')->andReturn(1);
        $this->repository->shouldReceive('getProject')->andReturn($this->project);
        $this->repository->shouldReceive('isMigratedToGerrit')->andReturnFalse();

        $this->migrated_repository = Mockery::mock(GitRepository::class);
        $this->migrated_repository->shouldReceive('getId')->andReturn(1);
        $this->migrated_repository->shouldReceive('getProject')->andReturn($this->project);
        $this->migrated_repository->shouldReceive('getRemoteServerId')->andReturn(1);
        $this->migrated_repository->shouldReceive('isMigratedToGerrit')->andReturnTrue();

        $this->permissions_manager = \Mockery::spy(\PermissionsManager::class);
        $this->ugroup_manager      = \Mockery::spy(\UGroupManager::class);
        $this->retriever           = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedRetriever::class);
        $this->default_factory     = \Mockery::spy(\Tuleap\Git\Permissions\DefaultFineGrainedPermissionFactory::class);
        $this->factory             = \Mockery::spy(\Tuleap\Git\Permissions\FineGrainedPermissionFactory::class);

        $this->formatter           = new HistoryValueFormatter(
            $this->permissions_manager,
            $this->ugroup_manager,
            $this->retriever,
            $this->default_factory,
            $this->factory
        );

        $this->ugroup_manager->shouldReceive('getUgroupsById')->with($this->project)->andReturns(array(
            101 => $this->ugroup_01,
            102 => $this->ugroup_02,
            103 => $this->ugroup_03,
        ));
    }

    public function testItExportsValueWithoutFineGrainedPermissionsForRepository(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 1, Git::PERM_READ)->andReturns(array(101));

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 1, Git::PERM_WRITE)->andReturns(array(102));

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 1, Git::PERM_WPLUS)->andReturns(array(103));

        $expected_result = <<<EOS
Read: Contributors
Write: Developers
Rewind: Admins
EOS;

        $result = $this->formatter->formatValueForRepository($this->repository);

        $this->assertEquals($expected_result, $result);
    }

    public function testItDoesNotExportWriteAndRewindIfRepositoryIsMigratedOnGerrit(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 1, Git::PERM_READ)->andReturns(array(101));

        $expected_result = <<<EOS
Read: Contributors
EOS;

        $result = $this->formatter->formatValueForRepository($this->migrated_repository);

        $this->assertEquals($expected_result, $result);
    }

    public function testItExportsValueWithoutFineGrainedPermissionsForProject(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 101, Git::DEFAULT_PERM_READ)->andReturns(array(101));

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 101, Git::DEFAULT_PERM_WRITE)->andReturns(array(102));

        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 101, Git::DEFAULT_PERM_WPLUS)->andReturns(array(103));

        $expected_result = <<<EOS
Read: Contributors
Write: Developers
Rewind: Admins
EOS;

        $result = $this->formatter->formatValueForProject($this->project);

        $this->assertEquals($expected_result, $result);
    }

    public function testItExportsValueWithFineGrainedPermissionsForRepository(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 1, Git::PERM_READ)->andReturns(array(101));

        $expected_result = <<<EOS
Read: Contributors
refs/heads/master Write: Developers
refs/heads/master Rewind: Admins
refs/tags/* Write: Contributors
EOS;

        $branch_fine_grained_permission = new FineGrainedPermission(
            1,
            1,
            'refs/heads/master',
            array($this->ugroup_02),
            array($this->ugroup_03)
        );

        $tag_fine_grained_permission = new FineGrainedPermission(
            2,
            1,
            'refs/tags/*',
            array($this->ugroup_01),
            array()
        );

        $this->retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->with($this->migrated_repository)->andReturns(true);

        $this->factory->shouldReceive('getBranchesFineGrainedPermissionsForRepository')->with($this->migrated_repository)->andReturns(array(1 => $branch_fine_grained_permission));

        $this->factory->shouldReceive('getTagsFineGrainedPermissionsForRepository')->with($this->migrated_repository)->andReturns(array(2 => $tag_fine_grained_permission));

        $result = $this->formatter->formatValueForRepository($this->migrated_repository);

        $this->assertEquals($expected_result, $result);
    }

    public function testItExportsValueWithFineGrainedPermissionsForProject(): void
    {
        $this->permissions_manager->shouldReceive('getAuthorizedUGroupIdsForProject')->with($this->project, 101, Git::DEFAULT_PERM_READ)->andReturns(array(101));

        $expected_result = <<<EOS
Read: Contributors
refs/heads/master Write: Developers
refs/heads/master Rewind: Admins
refs/tags/* Write: Contributors
EOS;

        $branch_fine_grained_permission = new DefaultFineGrainedPermission(
            1,
            101,
            'refs/heads/master',
            array($this->ugroup_02),
            array($this->ugroup_03)
        );

        $tag_fine_grained_permission = new DefaultFineGrainedPermission(
            2,
            101,
            'refs/tags/*',
            array($this->ugroup_01),
            array()
        );

        $this->retriever->shouldReceive('doesProjectUseFineGrainedPermissions')->with($this->project)->andReturns(true);

        $this->default_factory->shouldReceive('getBranchesFineGrainedPermissionsForProject')->with($this->project)->andReturns(array(1 => $branch_fine_grained_permission));

        $this->default_factory->shouldReceive('getTagsFineGrainedPermissionsForProject')->with($this->project)->andReturns(array(2 => $tag_fine_grained_permission));

        $result = $this->formatter->formatValueForProject($this->project);

        $this->assertEquals($expected_result, $result);
    }
}
