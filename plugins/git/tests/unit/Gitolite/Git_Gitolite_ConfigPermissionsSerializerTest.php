<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\Gitolite;

use EventManager;
use Git_Driver_Gerrit_ProjectCreatorStatus;
use Git_Gitolite_ConfigPermissionsSerializer;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\GetProtectedGitReferences;
use Tuleap\Git\Permissions\ProtectedReferencePermission;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;
use Tuleap\Git\Tests\Builders\GitRepositoryTestBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Git_Gitolite_ConfigPermissionsSerializerTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    public function testProtectedReferencesArePresentInTheSerialization(): void
    {
        $event_manager                 = $this->createMock(EventManager::class);
        $fine_grained_retriever        = $this->createMock(FineGrainedRetriever::class);
        $regexp_fine_grained_retriever = $this->createMock(RegexpFineGrainedRetriever::class);
        $serializer                    = $this->getMockBuilder(Git_Gitolite_ConfigPermissionsSerializer::class)
            ->setConstructorArgs([
                $this->createMock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
                '',
                $fine_grained_retriever,
                $this->createMock(FineGrainedPermissionFactory::class),
                $regexp_fine_grained_retriever,
                $event_manager,
            ])
            ->onlyMethods(['fetchConfigPermissions'])
            ->getMock();
        $serializer->method('fetchConfigPermissions');

        $event_manager->method('processEvent')->with(
            self::callback(function ($event) {
                if (! $event instanceof GetProtectedGitReferences) {
                    return false;
                }
                $event->addProtectedReference(new ProtectedReferencePermission('refs/tests/*'));
                return true;
            })
        );
        $fine_grained_retriever->method('doesRepositoryUseFineGrainedPermissions')->willReturn(false);
        $regexp_fine_grained_retriever->method('areRegexpActivatedForRepository')->willReturn(true);

        $repository = GitRepositoryTestBuilder::aProjectRepository()
            ->inProject(ProjectTestBuilder::aProject()->build())
            ->withId(1)
            ->build();

        $gitolite_permissions = $serializer->getForRepository($repository);

        self::assertEquals(' - refs/tests/.* = @all' . PHP_EOL, $gitolite_permissions);
    }
}
