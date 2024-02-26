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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\Registration;

use PFUser;
use Rule_ProjectFullName;
use Rule_ProjectName;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\ProjectDescriptionUsageRetriever;

final class ProjectRegistrationBaseChecker implements ProjectRegistrationChecker
{
    private Rule_ProjectName $rule_project_name;
    private Rule_ProjectFullName $rule_project_full_name;

    public function __construct(
        Rule_ProjectName $rule_project_name,
        Rule_ProjectFullName $rule_project_full_name,
    ) {
        $this->rule_project_name      = $rule_project_name;
        $this->rule_project_full_name = $rule_project_full_name;
    }

    public function collectAllErrorsForProjectRegistration(
        PFUser $user,
        ProjectCreationData $project_creation_data,
    ): ProjectRegistrationErrorsCollection {
        $errors_collection = new ProjectRegistrationErrorsCollection();

        if (! $this->rule_project_name->isValid($project_creation_data->getUnixName())) {
            $errors_collection->addError(
                new ProjectInvalidShortNameException($this->rule_project_name->getErrorMessage())
            );
        }

        if (! $this->rule_project_full_name->isValid($project_creation_data->getFullName())) {
            $errors_collection->addError(
                new ProjectInvalidFullNameException($this->rule_project_full_name->getErrorMessage())
            );
        }

        $description = $project_creation_data->getShortDescription();
        if (($description === null || $description === '') && ProjectDescriptionUsageRetriever::isDescriptionMandatory()) {
            $errors_collection->addError(
                new ProjectDescriptionMandatoryException()
            );
        }

        $this->verifyCompatibilityProjectVisibilityWithCurrentInstanceMode($project_creation_data, $errors_collection);

        return $errors_collection;
    }

    private function verifyCompatibilityProjectVisibilityWithCurrentInstanceMode(
        ProjectCreationData $project_creation_data,
        ProjectRegistrationErrorsCollection $errors_collection,
    ): void {
        $are_restricted_enabled      = \ForgeConfig::areRestrictedUsersAllowed();
        $selected_project_visibility = $project_creation_data->getAccess();

        if (
            ! $are_restricted_enabled &&
            ($selected_project_visibility === \Project::ACCESS_PRIVATE_WO_RESTRICTED || $selected_project_visibility === \Project::ACCESS_PUBLIC_UNRESTRICTED)
        ) {
            $errors_collection->addError(new ProjectVisibilityNeedsRestrictedUsersException($selected_project_visibility));
        }
    }
}
