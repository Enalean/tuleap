<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Team;

use Tuleap\ProgramManagement\Domain\Workspace\ProjectIdentifier;

final class TeamMustHaveExplicitBacklogEnabledException extends \RuntimeException implements TeamException
{
    private string $i18n_message;

    public function __construct(ProjectIdentifier $project_wanted_as_a_team)
    {
        parent::__construct(sprintf('Project #%d cannot be a Team because it is not in the explicit backlog mode', $project_wanted_as_a_team->getId()));
        $this->i18n_message = sprintf(
            dgettext(
                'tuleap-program_management',
                'Project #%d cannot be a Team because it is not in the explicit backlog mode'
            ),
            $project_wanted_as_a_team->getId()
        );
    }

    #[\Override]
    public function getI18NExceptionMessage(): string
    {
        return $this->i18n_message;
    }
}
