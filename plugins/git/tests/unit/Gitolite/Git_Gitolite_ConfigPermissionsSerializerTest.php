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

require_once __DIR__ . '/../bootstrap.php';

use PHPUnit\Framework\TestCase;
use Tuleap\Git\Permissions\FineGrainedPermissionFactory;
use Tuleap\Git\Permissions\FineGrainedRetriever;
use Tuleap\Git\Permissions\GetProtectedGitReferences;
use Tuleap\Git\Permissions\ProtectedReferencePermission;
use Tuleap\Git\Permissions\RegexpFineGrainedRetriever;

class Git_Gitolite_ConfigPermissionsSerializerTest extends TestCase // @codingStandardsIgnoreLine
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testProtectedReferencesArePresentInTheSerialization()
    {
        $mirror_data_mapper            = Mockery::mock(Git_Mirror_MirrorDataMapper::class);
        $event_manager                 = Mockery::mock(EventManager::class);
        $fine_grained_retriever        = Mockery::mock(FineGrainedRetriever::class);
        $regexp_fine_grained_retriever = Mockery::mock(RegexpFineGrainedRetriever::class);
        $serializer                    = Mockery::mock(
            Git_Gitolite_ConfigPermissionsSerializer::class . '[fetchConfigPermissions]',
            [
                $mirror_data_mapper,
                Mockery::mock(Git_Driver_Gerrit_ProjectCreatorStatus::class),
                '',
                $fine_grained_retriever,
                Mockery::mock(FineGrainedPermissionFactory::class),
                $regexp_fine_grained_retriever,
                $event_manager
            ]
        );
        $serializer->shouldReceive('fetchConfigPermissions');

        $mirror_data_mapper->shouldReceive('fetchAllRepositoryMirrors')->andReturns([]);
        $event_manager->shouldReceive('processEvent')->with(
            Mockery::on(function ($event) {
                if (! $event instanceof GetProtectedGitReferences) {
                    return false;
                }
                $event->addProtectedReference(new ProtectedReferencePermission('refs/tests/*'));
                return true;
            })
        );
        $fine_grained_retriever->shouldReceive('doesRepositoryUseFineGrainedPermissions')->andReturns(false);
        $regexp_fine_grained_retriever->shouldReceive('areRegexpActivatedForRepository')->andReturns(true);

        $repository = Mockery::mock(GitRepository::class);
        $repository->shouldReceive('getProject')->andReturns(Mockery::mock(Project::class));
        $repository->shouldReceive('getId')->andReturns(1);
        $repository->shouldReceive('isMigratedToGerrit')->andReturns(false);

        $gitolite_permissions = $serializer->getForRepository($repository);

        $this->assertEquals(' - refs/tests/.* = @all' . PHP_EOL, $gitolite_permissions);
    }
}
