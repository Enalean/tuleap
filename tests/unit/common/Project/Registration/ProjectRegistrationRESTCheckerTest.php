<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\Project\Registration;

use ProjectCreationData;
use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectRegistrationRESTCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    private ProjectRegistrationRESTChecker $checker;

    protected function setUp(): void
    {
        $this->checker = new ProjectRegistrationRESTChecker(new DefaultProjectVisibilityRetriever());
    }

    public function testValidatesWithoutErrorsWhenEverythingIsFine(): void
    {
        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        \ForgeConfig::set(\ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);

        $errors = $this->checker->collectAllErrorsForProjectRegistration(UserTestBuilder::aUser()->build(), $data);
        self::assertEmpty($errors->getErrors());
    }

    public function testFindsAnErrorWhenUserCannotChooseTheProjectVisibilityAndNonDefaultProjectVisibilityIsSelected(): void
    {
        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );
        $data->setAccessFromProjectData(['is_public' => true]);

        \ForgeConfig::set(\ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 0);
        \ForgeConfig::set(DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME, \Project::ACCESS_PRIVATE);

        $errors = $this->checker->collectAllErrorsForProjectRegistration(UserTestBuilder::aUser()->build(), $data);
        self::assertInstanceOf(ProjectAccessLevelCannotBeChosenByUserException::class, $errors->getErrors()[0]);
    }

    public function testDoesNotDetectAnErrorWhenUserCannotChooseTheProjectVisibilityButTheDefaultProjectVisibilityIsSelected(): void
    {
        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );
        $data->setAccessFromProjectData(['is_public' => false]);

        \ForgeConfig::set(\ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 0);
        \ForgeConfig::set(DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME, \Project::ACCESS_PRIVATE);

        $errors = $this->checker->collectAllErrorsForProjectRegistration(UserTestBuilder::aUser()->build(), $data);
        self::assertEmpty($errors->getErrors());
    }
}
