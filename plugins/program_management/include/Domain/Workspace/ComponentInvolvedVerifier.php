<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Workspace;

use Tuleap\ProgramManagement\Domain\Program\VerifyIsProgram;
use Tuleap\ProgramManagement\Domain\ProjectReference;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;

final class ComponentInvolvedVerifier
{
    private VerifyIsTeam $team_verifier;
    private VerifyIsProgram $program_verifier;

    public function __construct(
        VerifyIsTeam $team_verifier,
        VerifyIsProgram $program_verifier,
    ) {
        $this->team_verifier    = $team_verifier;
        $this->program_verifier = $program_verifier;
    }

    public function isInvolvedInAProgramWorkspace(ProjectReference $project_data): bool
    {
        return $this->team_verifier->isATeam($project_data->getId())
            || $this->program_verifier->isAProgram($project_data->getId());
    }
}
