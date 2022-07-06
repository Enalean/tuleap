<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use HTTPRequest;
use Psr\Log\LoggerInterface;
use Tuleap\Layout\BaseLayout;
use Tuleap\ProgramManagement\Domain\Team\VerifyIsTeam;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

final class SynchronizeTeamController implements DispatchableWithRequest, DispatchableWithProject
{
    public function __construct(
        private \ProjectManager $project_manager,
        private VerifyIsTeam $verify_is_team,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws NotFoundException
     */
    public function getProject(array $variables): \Project
    {
        $project = $this->project_manager->getProjectByUnixName($variables['project_name']);
        if (! $project) {
            throw new NotFoundException();
        }

        return $project;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        $project = $this->getProject($variables);
        if (! $project->usesService(ProgramService::SERVICE_SHORTNAME)) {
            throw new NotFoundException(
                dgettext("tuleap-program_management", "Program management service is disabled.")
            );
        }

        if (! $this->verify_is_team->isATeam($variables['team_id'])) {
            throw new ForbiddenException(
                dgettext(
                    "tuleap-program_management",
                    "Project is not defined as a Team project. It can not be synchronized."
                )
            );
        }

        \Tuleap\Project\ServiceInstrumentation::increment('program_management');

        $this->logger->debug("Should synchronize team " . $variables['team_id']);
    }
}
