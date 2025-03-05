<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use GitRepository;
use PermissionsManager;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ConfigPermissionsSerializerGerritTest extends TestCase
{
    private Git_Gitolite_ConfigPermissionsSerializer $serializer;
    private GitRepository $repository;
    private Git_Driver_Gerrit_ProjectCreatorStatus&MockObject $gerrit_status;

    public function setUp(): void
    {
        $project          = ProjectTestBuilder::aProject()->withId(102)->withUnixName('gpig')->build();
        $this->repository = GitRepositoryTestBuilder::aProjectRepository()->withId(1001)->inProject($project)->migratedToGerrit(2)->build();

        $permissions_manager = $this->createMock(PermissionsManager::class);
        $permissions_manager->method('getAuthorizedUGroupIdsForProject')
            ->willReturnCallback(static fn($project, $id, $perm) => match ($perm) {
                Git::PERM_READ  => [ProjectUGroup::REGISTERED],
                Git::PERM_WRITE => [ProjectUGroup::PROJECT_MEMBERS],
                Git::PERM_WPLUS => [],
            });
        PermissionsManager::setInstance($permissions_manager);

        $this->gerrit_status = $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class);

        $event_manager          = $this->createMock(EventManager::class);
        $fine_grained_retriever = $this->createMock(FineGrainedRetriever::class);
        $this->serializer       = new Git_Gitolite_ConfigPermissionsSerializer(
            $this->gerrit_status,
            'whatever',
            $fine_grained_retriever,
            $this->createMock(FineGrainedPermissionFactory::class),
            $this->createMock(RegexpFineGrainedRetriever::class),
            $event_manager
        );
        $fine_grained_retriever->method('doesRepositoryUseFineGrainedPermissions');
        $event_manager->method('processEvent');
    }

    public function tearDown(): void
    {
        PermissionsManager::clearInstance();
    }

    public function testItGeneratesTheDefaultConfiguration(): void
    {
        $this->gerrit_status->method('getStatus')->willReturn(null);
        self::assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function testItGrantsEverythingToGerritUserAfterMigrationIsDoneWithSuccess(): void
    {
        $this->gerrit_status->method('getStatus')->with($this->repository)->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::DONE);

        self::assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW+ = forge__gerrit_2\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function testItDoesntGrantAllPermissionsToGerritIfMigrationIsWaitingForExecution(): void
    {
        $this->gerrit_status->method('getStatus')->with($this->repository)->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::QUEUE);

        self::assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }

    public function testItDoesntGrantAllPermissionsToGerritIfMigrationIsError(): void
    {
        $this->gerrit_status->method('getStatus')->with($this->repository)->willReturn(Git_Driver_Gerrit_ProjectCreatorStatus::ERROR);

        self::assertSame(
            " R   = @site_active @gpig_project_members\n" .
            " RW  = @gpig_project_members\n",
            $this->serializer->getForRepository($this->repository)
        );
    }
}
