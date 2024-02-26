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

use Psr\Log\NullLogger;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Project\Admin\Categories\CategoryCollectionConsistencyChecker;
use Tuleap\Project\Admin\Categories\ProjectCategoriesException;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollection;
use Tuleap\Project\Admin\DescriptionFields\ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker;
use Tuleap\Project\DefaultProjectVisibilityRetriever;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\ProjectCreationData;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class ProjectRegistrationRESTCheckerTest extends TestCase
{
    use ForgeConfigSandbox;

    private ProjectRegistrationRESTChecker $checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CategoryCollectionConsistencyChecker
     */
    private $category_collection_consistency_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker
     */
    private $submitted_fields_collection_consistency_checker;

    protected function setUp(): void
    {
        $this->category_collection_consistency_checker         = $this->createMock(CategoryCollectionConsistencyChecker::class);
        $this->submitted_fields_collection_consistency_checker = $this->createMock(ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker::class);

        $this->checker = new ProjectRegistrationRESTChecker(
            new DefaultProjectVisibilityRetriever(),
            $this->category_collection_consistency_checker,
            $this->submitted_fields_collection_consistency_checker
        );
    }

    public function testValidatesWithoutErrorsWhenEverythingIsFine(): void
    {
        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        \ForgeConfig::set(\ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);

        $this->category_collection_consistency_checker
            ->expects(self::once())
            ->method('checkCollectionConsistency');

        $this->submitted_fields_collection_consistency_checker
            ->expects(self::once())
            ->method('checkFieldConsistency');

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

        $this->category_collection_consistency_checker
            ->expects(self::once())
            ->method('checkCollectionConsistency');

        $this->submitted_fields_collection_consistency_checker
            ->expects(self::once())
            ->method('checkFieldConsistency');

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

        $this->category_collection_consistency_checker
            ->expects(self::once())
            ->method('checkCollectionConsistency');

        $this->submitted_fields_collection_consistency_checker
            ->expects(self::once())
            ->method('checkFieldConsistency');

        $errors = $this->checker->collectAllErrorsForProjectRegistration(UserTestBuilder::aUser()->build(), $data);
        self::assertEmpty($errors->getErrors());
    }

    public function testItCollectsCategoryError(): void
    {
        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        \ForgeConfig::set(\ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);

        $this->category_collection_consistency_checker
            ->expects(self::once())
            ->method('checkCollectionConsistency')
            ->willThrowException(
                new class extends ProjectCategoriesException
                {
                    public function getI18NMessage(): string
                    {
                        return '';
                    }
                }
            );

        $this->submitted_fields_collection_consistency_checker
            ->expects(self::once())
            ->method('checkFieldConsistency');

        $errors = $this->checker->collectAllErrorsForProjectRegistration(UserTestBuilder::aUser()->build(), $data);

        self::assertNotEmpty($errors->getErrors());
        self::assertCount(1, $errors->getErrors());
    }

    public function testItCollectsFieldsError(): void
    {
        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        \ForgeConfig::set(\ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 1);

        $this->category_collection_consistency_checker
            ->expects(self::once())
            ->method('checkCollectionConsistency');

        $submitted_fields_collection_consistency_checker = new class ($this->createMock(DescriptionFieldsFactory::class)) extends ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker
        {
            public function checkFieldConsistency(
                ProjectRegistrationSubmittedFieldsCollection $field_collection,
                ProjectRegistrationErrorsCollection $errors_collection,
            ): void {
                $errors_collection->addError(
                    new class extends \Exception implements RegistrationErrorException
                    {
                        public function getI18NMessage(): string
                        {
                            return '';
                        }
                    }
                );
            }
        };

        $checker = new ProjectRegistrationRESTChecker(
            new DefaultProjectVisibilityRetriever(),
            $this->category_collection_consistency_checker,
            $submitted_fields_collection_consistency_checker
        );

        $errors = $checker->collectAllErrorsForProjectRegistration(UserTestBuilder::aUser()->build(), $data);

        self::assertCount(1, $errors->getErrors());
    }

    public function testItCollectsAllErrors(): void
    {
        $data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $data->setAccessFromProjectData(['is_public' => true]);

        \ForgeConfig::set(\ProjectManager::SYS_USER_CAN_CHOOSE_PROJECT_PRIVACY, 0);
        \ForgeConfig::set(DefaultProjectVisibilityRetriever::CONFIG_SETTING_NAME, \Project::ACCESS_PRIVATE);

        $this->category_collection_consistency_checker
            ->expects(self::once())
            ->method('checkCollectionConsistency')
            ->willThrowException(
                new class extends ProjectCategoriesException
                {
                    public function getI18NMessage(): string
                    {
                        return '';
                    }
                }
            );

        $submitted_fields_collection_consistency_checker = new class ($this->createMock(DescriptionFieldsFactory::class)) extends ProjectRegistrationSubmittedFieldsCollectionConsistencyChecker
        {
            public function checkFieldConsistency(
                ProjectRegistrationSubmittedFieldsCollection $field_collection,
                ProjectRegistrationErrorsCollection $errors_collection,
            ): void {
                $errors_collection->addError(
                    new class extends \Exception implements RegistrationErrorException
                    {
                        public function getI18NMessage(): string
                        {
                            return '';
                        }
                    }
                );
            }
        };

        $checker = new ProjectRegistrationRESTChecker(
            new DefaultProjectVisibilityRetriever(),
            $this->category_collection_consistency_checker,
            $submitted_fields_collection_consistency_checker
        );

        $errors = $checker->collectAllErrorsForProjectRegistration(UserTestBuilder::aUser()->build(), $data);

        self::assertCount(3, $errors->getErrors());
    }
}
