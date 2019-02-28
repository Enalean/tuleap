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

namespace Tuleap\Baseline\Support;

use DI\ContainerBuilder;
use ParagonIE\EasyDB\EasyDB;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Adapter\BaselineRepositoryAdapter;
use Tuleap\Baseline\Adapter\ChangesetRepositoryAdapter;
use Tuleap\Baseline\Adapter\ClockAdapter;
use Tuleap\Baseline\Adapter\CurrentUserProviderAdapter;
use Tuleap\Baseline\Adapter\FieldRepositoryAdapter;
use Tuleap\Baseline\Adapter\MilestoneRepositoryAdapter;
use Tuleap\Baseline\Adapter\ProjectPermissionsAdapter;
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
use Tuleap\Baseline\ProjectPermissions;
use Tuleap\Baseline\REST\BaselineController;
use Tuleap\Baseline\RoleAssignmentRepository;
use Tuleap\DB\DBFactory;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\UserManager;
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
                BaselineService::class                   => create(BaselineService::class)
                    ->constructor(
                        get(FieldRepository::class),
                        get(Permissions::class),
                        get(ChangesetRepository::class),
                        get(BaselineRepository::class),
                        get(CurrentUserProvider::class),
                        get(Clock::class)
                    ),
                Permissions::class                       => function (
                    CurrentUserProvider $current_user_provider,
                    ProjectPermissions $project_permissions,
                    RoleAssignmentRepository $role_assignment_repository
                ) {
                    return new PermissionsImpl(
                        $current_user_provider,
                        $project_permissions,
                        $role_assignment_repository
                    );
                },
                Clock::class                             => function () {
                    return new ClockAdapter();
                },
                UserManager::class                       => function () {
                    return UserManager::build();
                },
                CurrentUserProvider::class               => function (UserManager $user_manager) {
                    return new CurrentUserProviderAdapter($user_manager);
                },
                BaselineRepository::class                => function (EasyDB $db) {
                    return new BaselineRepositoryAdapter($db);
                },
                ChangesetRepository::class               => function (
                    Tracker_Artifact_ChangesetFactory $changesetFactory
                ) {
                    return new ChangesetRepositoryAdapter($changesetFactory);
                },
                FieldRepository::class                   => function () {
                    return new FieldRepositoryAdapter();
                },
                MilestoneRepository::class               => function (Tracker_ArtifactFactory $artifact_factory) {
                    return new MilestoneRepositoryAdapter($artifact_factory);
                },
                RoleAssignmentRepository::class          => function (EasyDB $db) {
                    return new RoleAssignmentRepositoryAdapter($db);
                },
                ProjectPermissions::class                => function (
                    ProjectStatusVerificator $project_status_verificator
                ) {
                    return new ProjectPermissionsAdapter($project_status_verificator);
                },
                ProjectStatusVerificator::class          => function () {
                    return ProjectStatusVerificator::build();
                },
                Tracker_ArtifactFactory::class           => function () {
                    return Tracker_ArtifactFactory::instance();
                },
                Tracker_Artifact_ChangesetFactory::class => function () {
                    return Tracker_Artifact_ChangesetFactoryBuilder::build();
                },
                EasyDB::class                            => function () {
                    return DBFactory::getMainTuleapDBConnection()->getDB();
                }
            ]
        );
    }
}
