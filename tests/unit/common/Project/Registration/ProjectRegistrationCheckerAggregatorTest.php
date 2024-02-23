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
use Psr\Log\NullLogger;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectRegistrationCheckerAggregatorTest extends TestCase
{
    public function testItCollectsAllErrorsReturnedByCollectorsThatHaveErrors(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $checker_01 = new class implements ProjectRegistrationChecker {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                return new ProjectRegistrationErrorsCollection();
            }
        };

        $checker_02 = new class implements ProjectRegistrationChecker {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                $errors = new ProjectRegistrationErrorsCollection();
                $errors->addError(
                    new class extends \Exception implements RegistrationErrorException {
                        public function getI18NMessage(): string
                        {
                            return '';
                        }
                    }
                );

                return $errors;
            }
        };

        $checker_03 = new class implements ProjectRegistrationChecker {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                $errors = new ProjectRegistrationErrorsCollection();
                $errors->addError(
                    new class extends \Exception implements RegistrationErrorException {
                        public function getI18NMessage(): string
                        {
                            return '';
                        }
                    }
                );

                return $errors;
            }
        };

        $checker_block_error_set = new ProjectRegistrationCheckerAggregator(
            $checker_01,
            $checker_02,
            $checker_03,
        );

        $collected_errors = $checker_block_error_set->collectAllErrorsForProjectRegistration(
            $user,
            new ProjectCreationData(
                new DefaultProjectVisibilityRetriever(),
                new NullLogger()
            )
        );

        self::assertNotEmpty($collected_errors->getErrors());
        self::assertCount(2, $collected_errors->getErrors());
    }

    public function testItReturnsEmptyCollectionWhenThereIsNoErrorsInCollectors(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $checker_01 = new class implements ProjectRegistrationChecker {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                return new ProjectRegistrationErrorsCollection();
            }
        };

        $checker_02 = new class implements ProjectRegistrationChecker {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                return new ProjectRegistrationErrorsCollection();
            }
        };

        $checker_03 = new class implements ProjectRegistrationChecker {
            public function collectAllErrorsForProjectRegistration(PFUser $user, ProjectCreationData $project_creation_data): ProjectRegistrationErrorsCollection
            {
                return new ProjectRegistrationErrorsCollection();
            }
        };

        $checker_block_error_set = new ProjectRegistrationCheckerAggregator(
            $checker_01,
            $checker_02,
            $checker_03,
        );

        $collected_errors = $checker_block_error_set->collectAllErrorsForProjectRegistration(
            $user,
            new ProjectCreationData(
                new DefaultProjectVisibilityRetriever(),
                new NullLogger()
            )
        );

        self::assertEmpty($collected_errors->getErrors());
    }
}
