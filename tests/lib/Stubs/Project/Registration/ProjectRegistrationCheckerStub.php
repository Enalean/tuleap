<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Stubs\Project\Registration;

use PFUser;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Project\Registration\ProjectRegistrationChecker;
use Tuleap\Project\Registration\ProjectRegistrationErrorsCollection;
use Tuleap\Project\Registration\RegistrationErrorException;

final class ProjectRegistrationCheckerStub implements ProjectRegistrationChecker
{
    private function __construct(private readonly ?RegistrationErrorException $exception)
    {
    }

    public static function withoutException(): self
    {
        return new self(null);
    }

    public static function withException(RegistrationErrorException $exception): self
    {
        return new self($exception);
    }

    public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data,): ProjectRegistrationErrorsCollection
    {
        $collection = new ProjectRegistrationErrorsCollection();
        if ($this->exception !== null) {
            $collection->addError($this->exception);
        }
        return $collection;
    }
}
