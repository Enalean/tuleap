<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace;

use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramFlags;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramFlag;
use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\Project\Flags\ProjectFlagPresenter;

final class ProgramFlagsBuilder implements BuildProgramFlags
{
    public function __construct(
        private \Tuleap\Project\Flags\ProjectFlagsBuilder $project_flags_builder,
        private RetrieveFullProject $retrieve_full_project,
    ) {
    }

    /**
     * @return ProgramFlag[]
     */
    #[\Override]
    public function build(ProgramIdentifier $program_identifier): array
    {
        $project = $this->retrieve_full_project->getProject($program_identifier->getId());

        return array_map(
            static fn(ProjectFlagPresenter $flag_presenter) => ProgramFlag::fromLabelAndDescription($flag_presenter->label, $flag_presenter->description),
            $this->project_flags_builder->buildProjectFlags($project)
        );
    }
}
