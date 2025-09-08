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

use Tuleap\ProgramManagement\Domain\Program\ProgramIdentifier;
use Tuleap\ProgramManagement\Domain\Workspace\BuildProgramPrivacy;
use Tuleap\ProgramManagement\Domain\Workspace\ProgramPrivacy;
use Tuleap\Project\ProjectPrivacyPresenter;

final class ProgramPrivacyBuilder implements BuildProgramPrivacy
{
    public function __construct(private RetrieveFullProject $retrieve_full_project)
    {
    }

    #[\Override]
    public function build(ProgramIdentifier $program_identifier): ProgramPrivacy
    {
        $project         = $this->retrieve_full_project->getProject($program_identifier->getId());
        $project_privacy = ProjectPrivacyPresenter::fromProject($project);

        return ProgramPrivacy::fromPrivacy(
            $project_privacy->are_restricted_users_allowed,
            $project_privacy->project_is_public_incl_restricted,
            $project_privacy->project_is_private,
            $project_privacy->project_is_public,
            $project_privacy->project_is_private_incl_restricted,
            $project_privacy->explanation_text,
            $project_privacy->privacy_title,
            $project_privacy->project_name
        );
    }
}
