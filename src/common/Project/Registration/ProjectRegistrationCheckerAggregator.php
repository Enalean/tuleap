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
use Tuleap\Project\ProjectCreationData;

final class ProjectRegistrationCheckerAggregator implements ProjectRegistrationChecker
{
    /**
     * @var ProjectRegistrationChecker[]
     */
    private array $checkers;

    public function __construct(ProjectRegistrationChecker ...$checkers)
    {
        $this->checkers = $checkers;
    }

    public function collectAllErrorsForProjectRegistration(
        PFUser $user,
        ProjectCreationData $project_creation_data,
    ): ProjectRegistrationErrorsCollection {
        $errors_collection = new ProjectRegistrationErrorsCollection();
        foreach ($this->checkers as $checker) {
            foreach ($checker->collectAllErrorsForProjectRegistration($user, $project_creation_data)->getErrors() as $error) {
                $errors_collection->addError($error);
            }
        }

        return $errors_collection;
    }
}
