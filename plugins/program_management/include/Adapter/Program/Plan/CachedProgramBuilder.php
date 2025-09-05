<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Program\Plan;

use Tuleap\ProgramManagement\Adapter\Program\ProgramDaoProject;
use Tuleap\ProgramManagement\Adapter\Workspace\ProjectManagerAdapter;
use Tuleap\ProgramManagement\Adapter\Workspace\UserManagerAdapter;
use Tuleap\ProgramManagement\Domain\Program\Plan\BuildProgram;
use Tuleap\ProgramManagement\Domain\Program\Plan\ProjectIsAProgramOrUsedInPlanChecker;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;

final class CachedProgramBuilder implements BuildProgram, ProjectIsAProgramOrUsedInPlanChecker
{
    private static ?self $instance = null;

    /**
     * @var array<int, bool>
     */
    private array $permission_cache = [];

    public static function instance(): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $user_manager_adapter = new UserManagerAdapter(\UserManager::instance());
        $program_dao          = new ProgramDaoProject();
        $program_adapter      = new ProgramAdapter(
            new ProjectManagerAdapter(\ProjectManager::instance(), $user_manager_adapter),
            new ProjectAccessChecker(
                new RestrictedUserCanAccessProjectVerifier(),
                \EventManager::instance()
            ),
            $program_dao,
            $user_manager_adapter,
            $program_dao
        );
        self::$instance       = new self($program_adapter, $program_adapter,);
        return self::$instance;
    }

    /**
     * @psalm-internal Tuleap\ProgramManagement\Adapter\Program\Plan
     */
    public function __construct(
        private readonly BuildProgram $program_builder,
        private readonly ProjectIsAProgramOrUsedInPlanChecker $program_admin_builder,
    ) {
    }

    #[\Override]
    public function ensureProgramIsAProject(int $project_id, UserIdentifier $user): void
    {
        if (isset($this->permission_cache[$project_id])) {
            return;
        }
        $this->program_builder->ensureProgramIsAProject($project_id, $user);
        $this->permission_cache[$project_id] = true;
    }

    #[\Override]
    public function ensureProjectIsAProgramOrIsPartOfPlan(int $project_id, UserIdentifier $user): void
    {
        if (isset($this->permission_cache[$project_id])) {
            return;
        }
        $this->program_admin_builder->ensureProjectIsAProgramOrIsPartOfPlan($project_id, $user);
        $this->permission_cache[$project_id] = true;
    }
}
