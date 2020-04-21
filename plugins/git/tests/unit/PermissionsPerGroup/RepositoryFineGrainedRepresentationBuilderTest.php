<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\PermissionsPerGroup;

use Git;
use GitPermissionsManager;
use GitRepository;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectUGroup;
use Tuleap\Git\Permissions\FineGrainedPermission;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;

require_once dirname(__FILE__) . '/../bootstrap.php';

class RepositoryFineGrainedRepresentationBuilderTest extends TestCase
{
    /**
     * @var int
     */
    private $project_admin_id;
    /**
     * @var int
     */
    private $project_member_id;
    /**
     * @var Project
     */
    private $project;
    /**
     * @var GitRepository
     */
    private $repository;
    /**
     * @var CollectionOfUGroupsRepresentationFormatter
     */
    private $formatter;
    /**
     * @var FineGrainedPermissionFactory
     */
    private $fine_grained_factory;
    /**
     * @var CollectionOfUGroupRepresentationBuilder
     */
    private $collection_of_ugroup_builder;

    /**
     * @var GitPermissionsManager
     */
    private $permissions_manager;

    /**
     * @var RepositoryFineGrainedRepresentationBuilder
     */
    private $representation_builder;

    public function setUp(): void
    {
        parent::setUp();

        $this->permissions_manager          = $this->createMock(GitPermissionsManager::class);
        $this->collection_of_ugroup_builder = $this->createMock(CollectionOfUGroupRepresentationBuilder::class);
        $this->fine_grained_factory         = $this->createMock(FineGrainedPermissionFactory::class);
        $this->formatter                    = $this->createMock(CollectionOfUGroupsRepresentationFormatter::class);

        $this->representation_builder = new RepositoryFineGrainedRepresentationBuilder(
            $this->permissions_manager,
            $this->collection_of_ugroup_builder,
            $this->formatter,
            $this->fine_grained_factory,
            new AdminUrlBuilder()
        );

        $this->repository = $this->createMock(GitRepository::class);
        $this->repository->method('getId')->willReturn(1);
        $this->repository->method('getFullName')->willReturn('repo name');
        $this->project = $this->createMock(Project::class);

        $this->project_member_id = ProjectUGroup::PROJECT_MEMBERS;
        $this->project_admin_id  = ProjectUGroup::PROJECT_ADMIN;
    }

    public function testItShouldReturnAllFinedGrainedPermissionsWhenNoFilterDefined()
    {
        $this->permissions_manager->method('getRepositoryGlobalPermissions')->willReturn(
            [Git::PERM_READ => [$this->project_admin_id]]
        );
        $this->collection_of_ugroup_builder->method('build')->willReturn(
            [$this->project_admin_id]
        );

        $branch_permission = new FineGrainedPermission(
            1,
            $this->repository->getId(),
            '/name',
            [$this->project_member_id],
            [$this->project_member_id]
        );

        $tag_permission = new FineGrainedPermission(
            2,
            $this->repository->getId(),
            '/name/tag',
            [$this->project_member_id],
            [$this->project_member_id]
        );
        $this->fine_grained_factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn(
            [$branch_permission]
        );
        $this->fine_grained_factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn(
            [$tag_permission]
        );

        $formatted_ugroup = [$this->project_member_id => $this->project_member_id];
        $this->formatter->method('formatCollectionOfUgroups')->willReturn($formatted_ugroup);

        $expected__branch_fine_grained_representation = new FineGrainedPermissionRepresentation(
            1,
            $formatted_ugroup,
            $formatted_ugroup,
            '/name',
            '',
            [$this->project_member_id, $this->project_member_id]
        );
        $expected__tag_fine_grained_representation    = new FineGrainedPermissionRepresentation(
            2,
            $formatted_ugroup,
            $formatted_ugroup,
            '',
            '/name/tag',
            [$this->project_member_id, $this->project_member_id]
        );

        $expected = new RepositoryFineGrainedRepresentation(
            [$this->project_admin_id],
            'repo name',
            '/plugins/git/?action=repo_management&repo_id=1&pane=perms',
            [$expected__branch_fine_grained_representation, $expected__tag_fine_grained_representation]
        );
        $result   = $this->representation_builder->build(
            $this->repository,
            $this->project,
            null
        );

        $this->assertEquals($expected, $result);
    }

    public function testItShouldReturnWhenSelectedUGroupNotPresentInRepositoryAndFineGrained()
    {
        $this->permissions_manager->method('getRepositoryGlobalPermissions')->willReturn([Git::PERM_READ => []]);
        $this->collection_of_ugroup_builder->method('build')->willReturn([]);

        $branch_permission = new FineGrainedPermission(
            1,
            $this->repository->getId(),
            '/name',
            [$this->project_member_id],
            [$this->project_member_id]
        );

        $tag_permission = new FineGrainedPermission(
            1,
            $this->repository->getId(),
            '/name/tag',
            [$this->project_member_id],
            [$this->project_member_id]
        );
        $this->fine_grained_factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn(
            [$branch_permission]
        );
        $this->fine_grained_factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn(
            [$tag_permission]
        );

        $formatted_ugroup = [];
        $this->formatter->method('formatCollectionOfUgroups')->willReturn($formatted_ugroup);

        $expected = null;
        $result   = $this->representation_builder->build(
            $this->repository,
            $this->project,
            ProjectUGroup::WIKI_ADMIN
        );

        $this->assertEquals($expected, $result);
    }

    public function testItShouldReturnRepositoryReadersAndCorrespondingFineGrainedWhenPermissionFoundInFineGrained()
    {
        $this->permissions_manager->method('getRepositoryGlobalPermissions')->willReturn(
            [Git::PERM_READ => [$this->project_admin_id]]
        );
        $this->collection_of_ugroup_builder->method('build')->willReturn(
            [$this->project_admin_id]
        );

        $branch_permission = new FineGrainedPermission(
            1,
            $this->repository->getId(),
            '/name',
            [$this->project_member_id],
            []
        );

        $tag_permission = new FineGrainedPermission(
            2,
            $this->repository->getId(),
            '/name/tag',
            [],
            []
        );
        $this->fine_grained_factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn(
            [$branch_permission]
        );
        $this->fine_grained_factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn(
            [$tag_permission]
        );

        $formatted_ugroup = [$this->project_member_id => $this->project_member_id];
        $this->formatter->method('formatCollectionOfUgroups')->withConsecutive(
            [$this->equalTo([$this->project_member_id]), $this->equalTo($this->project)],
            [$this->equalTo([]), $this->equalTo($this->project)],
            [$this->equalTo([]), $this->equalTo($this->project)],
            [$this->equalTo([]), $this->equalTo($this->project)]
        )->willReturnOnConsecutiveCalls($formatted_ugroup, [], [], []);

        $expected__branch_fine_grained_representation = new FineGrainedPermissionRepresentation(
            1,
            $formatted_ugroup,
            [],
            '/name',
            '',
            [$this->project_member_id]
        );

        $expected = new RepositoryFineGrainedRepresentation(
            [$this->project_admin_id],
            'repo name',
            '/plugins/git/?action=repo_management&repo_id=1&pane=perms',
            [$expected__branch_fine_grained_representation]
        );
        $result   = $this->representation_builder->build(
            $this->repository,
            $this->project,
            $this->project_member_id
        );

        $this->assertEquals($expected, $result);
    }

    public function testItShouldReturnOnlyRepositoryFinedGrainedPermissionsFilterMatchesOnlyReaders()
    {
        $this->permissions_manager->method('getRepositoryGlobalPermissions')->willReturn(
            [Git::PERM_READ => [$this->project_admin_id]]
        );
        $this->collection_of_ugroup_builder->method('build')->willReturn(
            [$this->project_admin_id]
        );

        $branch_permission = new FineGrainedPermission(
            1,
            $this->repository->getId(),
            '/name',
            [$this->project_member_id],
            [$this->project_member_id]
        );

        $tag_permission = new FineGrainedPermission(
            1,
            $this->repository->getId(),
            '/name/tag',
            [$this->project_member_id],
            [$this->project_member_id]
        );
        $this->fine_grained_factory->method('getBranchesFineGrainedPermissionsForRepository')->willReturn(
            [$branch_permission]
        );
        $this->fine_grained_factory->method('getTagsFineGrainedPermissionsForRepository')->willReturn(
            [$tag_permission]
        );

        $formatted_ugroup = [$this->project_member_id => $this->project_member_id];
        $this->formatter->method('formatCollectionOfUgroups')->willReturn($formatted_ugroup);

        $expected = new RepositoryFineGrainedRepresentation(
            [$this->project_admin_id],
            'repo name',
            '/plugins/git/?action=repo_management&repo_id=1&pane=perms',
            []
        );
        $result   = $this->representation_builder->build(
            $this->repository,
            $this->project,
            $this->project_admin_id
        );

        $this->assertEquals($expected, $result);
    }
}
