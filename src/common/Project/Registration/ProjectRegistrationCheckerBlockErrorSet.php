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

final class ProjectRegistrationCheckerBlockErrorSet implements ProjectRegistrationChecker
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
        foreach ($this->checkers as $checker) {
            $errors = $checker->collectAllErrorsForProjectRegistration($user, $project_creation_data);
            if (count($errors->getErrors()) > 0) {
                return $errors;
            }
        }

        return new ProjectRegistrationErrorsCollection();
    }
}
