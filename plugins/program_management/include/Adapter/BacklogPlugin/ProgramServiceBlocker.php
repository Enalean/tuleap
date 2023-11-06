<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace Tuleap\ProgramManagement\Adapter\BacklogPlugin;

use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Adapter\Workspace\RetrieveFullProject;
use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\BacklogBlocksProgramServiceIfNeeded;
use function Psl\Type\string;

final class ProgramServiceBlocker implements BacklogBlocksProgramServiceIfNeeded
{
    public function __construct(
        private readonly RetrieveFullProject $project_retriever,
    ) {
    }

    public function shouldProgramServiceBeBlocked(
        UserIdentifier $user_identifier,
        ProjectIdentifier $project_identifier,
    ): Option {
        $project = $this->project_retriever->getProject($project_identifier->getId());

        if ($project->usesService(\AgileDashboardPlugin::PLUGIN_SHORTNAME)) {
            return Option::fromValue(
                dgettext(
                    'tuleap-program_management',
                    'Program service cannot be enabled when the project also uses the Backlog service.'
                )
            );
        }
        return Option::nothing(string());
    }
}
