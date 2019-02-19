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

use ParagonIE\EasyDB\EasyDB;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetFactoryBuilder;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Adapter\BaselineRepositoryImpl;
use Tuleap\Baseline\Adapter\ChangesetRepositoryImpl;
use Tuleap\Baseline\Adapter\ClockImpl;
use Tuleap\Baseline\Adapter\CurrentUserProviderImpl;
use Tuleap\Baseline\Adapter\FieldRepositoryImpl;
use Tuleap\Baseline\Adapter\MilestoneRepositoryImpl;
use Tuleap\Baseline\Adapter\ProjectPermissionsImpl;
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
use Tuleap\DB\DBFactory;
use Tuleap\REST\ProjectStatusVerificator;
use Tuleap\REST\UserManager;

/**
 * This class is responsible for dependency injections in all Baseline plugin.
 * Some dependencies can be overridden for test purpose.
 * Each getter use memoization to speed up context building.
 */
class DependenciesContext
{
    // REST
    /** @var BaselineController */
    private $baseline_controller;

    // Domain
    /** @var BaselineService */
    private $baseline_service;

    /** @var MilestoneRepository */
    private $milestone_repository;

    /** @var ChangesetRepository */
    private $changeset_repository;

    /** @var BaselineRepository */
    private $baseline_repository;

    /** @var CurrentUserProvider */
    private $current_user_provider;

    /** @var Permissions */
    private $permissions;

    /** @var ProjectPermissions */
    private $project_permissions;

    // Adapters
    /** @var UserManager */
    private $user_manager;

    /** @var Tracker_Artifact_ChangesetFactory */
    private $changeset_factory;

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var FieldRepository */
    private $field_repository;

    /** @var ProjectStatusVerificator */
    private $project_status_verificator;

    /** @var Clock */
    private $clock;

    /** @var EasyDB */
    private $database;

    public function getBaselineController(): BaselineController
    {
        if ($this->baseline_controller === null) {
            $this->baseline_controller = new BaselineController(
                $this->getCurrentUserProvider(),
                $this->getMilestoneRepository(),
                $this->getBaselineService()
            );
        }
        return $this->baseline_controller;
    }

    public function setBaselineController(BaselineController $baseline_controller): void
    {
        $this->baseline_controller = $baseline_controller;
    }

    public function getBaselineService(): BaselineService
    {
        if ($this->baseline_service === null) {
            $this->baseline_service = new BaselineService(
                $this->getFieldRepository(),
                $this->getPermissions(),
                $this->getChangesetRepository(),
                $this->getBaselineRepository(),
                $this->getCurrentUserProvider(),
                $this->getClock()
            );
        }
        return $this->baseline_service;
    }

    public function setBaselineService(BaselineService $baseline_service): void
    {
        $this->baseline_service = $baseline_service;
    }

    public function getMilestoneRepository(): MilestoneRepository
    {
        if ($this->milestone_repository === null) {
            $this->milestone_repository = new MilestoneRepositoryImpl(
                $this->getArtifactFactory()
            );
        }
        return $this->milestone_repository;
    }

    public function setMilestoneRepository(MilestoneRepository $milestone_repository): void
    {
        $this->milestone_repository = $milestone_repository;
    }

    public function getChangesetRepository(): ChangesetRepository
    {
        if ($this->changeset_repository === null) {
            $this->changeset_repository = new ChangesetRepositoryImpl(
                $this->getChangesetFactory()
            );
        }
        return $this->changeset_repository;
    }

    public function setChangesetRepository(ChangesetRepository $changeset_repository): void
    {
        $this->changeset_repository = $changeset_repository;
    }

    public function getBaselineRepository(): BaselineRepository
    {
        if ($this->baseline_repository === null) {
            $this->baseline_repository = new BaselineRepositoryImpl(
                $this->getDatabase()
            );
        }
        return $this->baseline_repository;
    }

    public function setBaselineRepository(BaselineRepository $baseline_repository): void
    {
        $this->baseline_repository = $baseline_repository;
    }

    public function getCurrentUserProvider(): CurrentUserProvider
    {
        if ($this->current_user_provider === null) {
            $this->current_user_provider = new CurrentUserProviderImpl(
                $this->getUserManager()
            );
        }
        return $this->current_user_provider;
    }

    public function setCurrentUserProvider(CurrentUserProvider $current_user_provider): void
    {
        $this->current_user_provider = $current_user_provider;
    }

    public function getPermissions(): Permissions
    {
        if ($this->permissions === null) {
            $this->permissions = new PermissionsImpl(
                $this->getCurrentUserProvider(),
                $this->getProjectPermissions()
            );
        }
        return $this->permissions;
    }

    public function setPermissions(Permissions $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getProjectPermissions(): ProjectPermissions
    {
        if ($this->project_permissions === null) {
            $this->project_permissions = new ProjectPermissionsImpl(
                $this->getProjectStatusVerificator()
            );
        }
        return $this->project_permissions;
    }

    public function setProjectPermissions(ProjectPermissions $project_permissions): void
    {
        $this->project_permissions = $project_permissions;
    }

    public function getUserManager(): UserManager
    {
        if ($this->user_manager === null) {
            $this->user_manager = UserManager::build();
        }
        return $this->user_manager;
    }

    public function setUserManager(UserManager $user_manager): void
    {
        $this->user_manager = $user_manager;
    }

    public function getChangesetFactory(): Tracker_Artifact_ChangesetFactory
    {
        if ($this->changeset_factory === null) {
            $this->changeset_factory = Tracker_Artifact_ChangesetFactoryBuilder::build();
        }
        return $this->changeset_factory;
    }

    public function setChangesetFactory(Tracker_Artifact_ChangesetFactory $changeset_factory): void
    {
        $this->changeset_factory = $changeset_factory;
    }

    public function getArtifactFactory(): Tracker_ArtifactFactory
    {
        if ($this->artifact_factory === null) {
            $this->artifact_factory = Tracker_ArtifactFactory::instance();
        }
        return $this->artifact_factory;
    }

    public function setArtifactFactory(Tracker_ArtifactFactory $artifact_factory): void
    {
        $this->artifact_factory = $artifact_factory;
    }

    public function getFieldRepository(): FieldRepository
    {
        if ($this->field_repository === null) {
            $this->field_repository = new FieldRepositoryImpl();
        }
        return $this->field_repository;
    }

    public function setFieldRepository(FieldRepository $field_repository): void
    {
        $this->field_repository = $field_repository;
    }

    public function getProjectStatusVerificator(): ProjectStatusVerificator
    {
        if ($this->project_status_verificator === null) {
            $this->project_status_verificator = ProjectStatusVerificator::build();
        }
        return $this->project_status_verificator;
    }

    public function setProjectStatusVerificator(ProjectStatusVerificator $project_status_verificator): void
    {
        $this->project_status_verificator = $project_status_verificator;
    }

    public function getClock(): Clock
    {
        if ($this->clock === null) {
            $this->clock = new ClockImpl();
        }
        return $this->clock;
    }

    public function setClock(Clock $clock): void
    {
        $this->clock = $clock;
    }

    public function getDatabase(): EasyDB
    {
        if ($this->database === null) {
            $this->database = DBFactory::getMainTuleapDBConnection()->getDB();
        }
        return $this->database;
    }
}
