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
use ProjectCreationData;
use Rule_ProjectFullName;
use Rule_ProjectName;
use Tuleap\Project\ProjectDescriptionUsageRetriever;

class ProjectRegistrationChecker
{
    private ProjectRegistrationUserPermissionChecker $permission_checker;
    private Rule_ProjectName $rule_project_name;
    private Rule_ProjectFullName $rule_project_full_name;

    public function __construct(
        ProjectRegistrationUserPermissionChecker $permission_checker,
        Rule_ProjectName $rule_project_name,
        Rule_ProjectFullName $rule_project_full_name
    ) {
        $this->permission_checker     = $permission_checker;
        $this->rule_project_name      = $rule_project_name;
        $this->rule_project_full_name = $rule_project_full_name;
    }

    public function collectAllErrorsForProjectRegistration(
        PFUser $user,
        ProjectCreationData $project_creation_data
    ): ProjectRegistrationErrorsCollection {
        $errors_collection = $this->collectPermissionErrorsForProjectRegistration($user);
        if (count($errors_collection->getErrors()) > 0) {
            return $errors_collection;
        }

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

        return $errors_collection;
    }

    public function collectPermissionErrorsForProjectRegistration(PFUser $user): ProjectRegistrationErrorsCollection
    {
        $errors_collection = new ProjectRegistrationErrorsCollection();

        try {
            $this->permission_checker->checkUserCreateAProject($user);
        } catch (RegistrationForbiddenException $exception) {
            $errors_collection->addError($exception);
        }

        return $errors_collection;
    }
}
