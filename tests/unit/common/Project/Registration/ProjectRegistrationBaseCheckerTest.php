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

use ForgeConfig;
use Psr\Log\NullLogger;
use Rule_ProjectFullName;
use Rule_ProjectName;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectRegistrationBaseCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    private ProjectRegistrationChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Rule_ProjectName
     */
    private $rule_project_name;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&Rule_ProjectFullName
     */
    private $rule_project_full_name;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule_project_name      = $this->createMock(Rule_ProjectName::class);
        $this->rule_project_full_name = $this->createMock(Rule_ProjectFullName::class);

        $this->checker = new ProjectRegistrationBaseChecker(
            $this->rule_project_name,
            $this->rule_project_full_name
        );
    }

    /**
     * @testWith [true, true]
     *           [false, false]
     */
    public function testItCollectsAllDataErrors(bool $project_is_public, bool $project_allow_restricted): void
    {
        ForgeConfig::set("enable_not_mandatory_description", 0);
        ForgeConfig::set(\ForgeAccess::CONFIG, \ForgeAccess::REGULAR);

        $user = UserTestBuilder::aUser()->build();

        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $data->setUnixName("not:val_id");
        $data->setFullName("Not vaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaalid");
        $data->setAccessFromProjectData(['is_public' => $project_is_public, 'allow_restricted' => $project_allow_restricted]);

        $this->rule_project_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->rule_project_name
            ->expects(self::once())
            ->method('getErrorMessage')
            ->willReturn('error 01');

        $this->rule_project_full_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->rule_project_full_name
            ->expects(self::once())
            ->method('getErrorMessage')
            ->willReturn('error 02');

        $errors_collection = $this->checker->collectAllErrorsForProjectRegistration(
            $user,
            $data
        );

        self::assertNotEmpty($errors_collection->getErrors());
        self::assertCount(4, $errors_collection->getErrors());
        self::assertInstanceOf(ProjectInvalidShortNameException::class, $errors_collection->getErrors()[0]);
        self::assertInstanceOf(ProjectInvalidFullNameException::class, $errors_collection->getErrors()[1]);
        self::assertInstanceOf(ProjectDescriptionMandatoryException::class, $errors_collection->getErrors()[2]);
        self::assertInstanceOf(ProjectVisibilityNeedsRestrictedUsersException::class, $errors_collection->getErrors()[3]);
    }

    public function testItCollectionIsEmptyIfAllIsOK(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $data->setUnixName("shortnale");
        $data->setFullName("Long Name");
        $data->setShortDescription("Description");

        $this->rule_project_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->rule_project_full_name
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $errors_collection = $this->checker->collectAllErrorsForProjectRegistration(
            $user,
            $data
        );

        self::assertEmpty($errors_collection->getErrors());
    }
}
