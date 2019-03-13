<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Baseline\Support;

use DI\ContainerBuilder;
use ParagonIE\EasyDB\EasyDB;
use ProjectManager;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Adapter\AdapterPermissions;
use Tuleap\Baseline\Adapter\BaselineRepositoryAdapter;
use Tuleap\Baseline\Adapter\ChangesetRepositoryAdapter;
use Tuleap\Baseline\Adapter\ClockAdapter;
use Tuleap\Baseline\Adapter\CurrentUserProviderAdapter;
use Tuleap\Baseline\Adapter\FieldRepositoryAdapter;
use Tuleap\Baseline\Adapter\MilestoneRepositoryAdapter;
use Tuleap\Baseline\Adapter\ProjectRepositoryAdapter;
use Tuleap\Baseline\Adapter\RoleAssignmentRepositoryAdapter;
use Tuleap\Baseline\BaselineRepository;
use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\ChangesetRepository;
use Tuleap\Baseline\Clock;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\FieldRepository;
use Tuleap\Baseline\MilestoneRepository;
use Tuleap\Baseline\Permissions;
use Tuleap\Baseline\PermissionsImpl;
use Tuleap\Baseline\ProjectRepository;
use Tuleap\Baseline\REST\BaselineController;
use Tuleap\Baseline\REST\ProjectBaselineController;
use Tuleap\Baseline\RoleAssignmentRepository;
use Tuleap\DB\DBFactory;
use Tuleap\REST\UserManager;
use URLVerification;
use function DI\create;
use function DI\get;

/**
 * Configure all dependencies for injections.
 */
class ContainerBuilderFactory
{
    public static function create(): ContainerBuilder
    {
        $container_builder = new ContainerBuilder();
        return $container_builder->addDefinitions(
            [
                BaselineController::class                => create(BaselineController::class)
                    ->constructor(
                        get(CurrentUserProvider::class),
                        get(MilestoneRepository::class),
                        get(BaselineService::class)
                    ),
                ProjectBaselineController::class         => create(ProjectBaselineController::class)
                    ->constructor(
                        get(CurrentUserProvider::class),
                        get(BaselineService::class),
                        get(ProjectRepository::class)
                    ),
                BaselineService::class                   => create(BaselineService::class)
                    ->constructor(
                        get(FieldRepository::class),
                        get(Permissions::class),
                        get(ChangesetRepository::class),
                        get(BaselineRepository::class),
                        get(CurrentUserProvider::class),
                        get(Clock::class)
                    ),
                Permissions::class                       => create(PermissionsImpl::class)
                    ->constructor(get(RoleAssignmentRepository::class))
                ,
                Clock::class                             => function () {
                    return new ClockAdapter();
                },
                UserManager::class                       => function () {
                    return UserManager::build();
                },
                CurrentUserProvider::class               => function (UserManager $user_manager) {
                    return new CurrentUserProviderAdapter($user_manager);
                },
                BaselineRepository::class                => create(BaselineRepositoryAdapter::class)
                    ->constructor(
                        get(EasyDB::class),
                        get(\UserManager::class),
                        get(MilestoneRepository::class)
                    ),
                ChangesetRepository::class               => create(ChangesetRepositoryAdapter::class)
                    ->constructor(get(Tracker_Artifact_ChangesetFactory::class)),
                FieldRepository::class                   => function () {
                    return new FieldRepositoryAdapter();
                },
                MilestoneRepository::class               => create(MilestoneRepositoryAdapter::class)
                    ->constructor(get(Tracker_ArtifactFactory::class), get(AdapterPermissions::class))
                ,
                RoleAssignmentRepository::class          => function (EasyDB $db) {
                    return new RoleAssignmentRepositoryAdapter($db);
                },
                ProjectRepository::class                 => create(ProjectRepositoryAdapter::class)
                    ->constructor(get(ProjectManager::class), get(AdapterPermissions::class)),
                AdapterPermissions::class                => create(AdapterPermissions::class)
                    ->constructor(get(URLVerification::class)),
                ProjectManager::class                    => function () {
                    return ProjectManager::instance();
                },
                Tracker_ArtifactFactory::class           => function () {
                    return Tracker_ArtifactFactory::instance();
                },
                Tracker_Artifact_ChangesetFactory::class => function () {
                    return Tracker_Artifact_ChangesetFactoryBuilder::build();
                },
                EasyDB::class                            => function () {
                    return DBFactory::getMainTuleapDBConnection()->getDB();
                },
                URLVerification::class                   => create(URLVerification::class)->constructor()
            ]
        );
    }
}
