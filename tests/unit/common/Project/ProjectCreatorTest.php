<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use ProjectCreationData;
use ProjectCreator;
use ProjectManager;
use Psr\Log\NullLogger;
use ReferenceManager;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Project\Admin\Categories\ProjectCategoriesUpdater;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Project\Email\EmailCopier;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\Registration\ProjectDescriptionMandatoryException;
use Tuleap\Project\Registration\ProjectInvalidFullNameException;
use Tuleap\Project\Registration\ProjectInvalidShortNameException;
use Tuleap\Project\Registration\ProjectRegistrationChecker;
use Tuleap\Project\Registration\ProjectRegistrationErrorsCollection;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class ProjectCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public ProjectManager&MockObject $project_manager;
    public UserManager&MockObject $user_manager;
    public ProjectCreator&MockObject $creator;
    private ProjectServiceActivator&MockObject $service_activator;
    private FieldUpdator&MockObject $field_updator;
    private ProjectDashboardDuplicator&MockObject $dashboard_duplicator;
    private LabelDao&MockObject $label_dao;
    private UgroupDuplicator&MockObject $ugroup_duplicator;
    private SynchronizedProjectMembershipDuplicator&MockObject $synchronized_project_membership_duplicator;
    private ReferenceManager&MockObject $reference_manager;
    private \EventManager&MockObject $event_manager;
    private ProjectRegistrationChecker&MockObject $registration_checker;
    private ProjectCategoriesUpdater&MockObject $project_categories_updater;
    private EmailCopier&Stub $email_copier;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);
        $this->user_manager    = $this->createMock(UserManager::class);

        $this->event_manager                              = $this->createMock(\EventManager::class);
        $this->reference_manager                          = $this->createMock(ReferenceManager::class);
        $this->synchronized_project_membership_duplicator = $this->createMock(
            SynchronizedProjectMembershipDuplicator::class
        );
        $this->ugroup_duplicator                          = $this->createMock(UgroupDuplicator::class);
        $this->label_dao                                  = $this->createMock(LabelDao::class);
        $this->dashboard_duplicator                       = $this->createMock(ProjectDashboardDuplicator::class);
        $this->field_updator                              = $this->createMock(FieldUpdator::class);
        $this->service_activator                          = $this->createMock(ProjectServiceActivator::class);
        $this->registration_checker                       = $this->createMock(ProjectRegistrationChecker::class);
        $this->project_categories_updater                 = $this->createMock(ProjectCategoriesUpdater::class);
        $this->email_copier                               = $this->createStub(EmailCopier::class);
    }

    public function testMandatoryDescriptionNotSetRaiseException(): void
    {
        $this->buildProjectCreator(false);

        $this->user_manager->method('getCurrentUser')->willReturn(
            UserTestBuilder::aUser()->build()
        );

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $errors_collection = new ProjectRegistrationErrorsCollection();
        $errors_collection->addError(
            new ProjectDescriptionMandatoryException()
        );

        $this->registration_checker
            ->expects(self::once())
            ->method('collectAllErrorsForProjectRegistration')
            ->willReturn($errors_collection);

        self::expectException(ProjectDescriptionMandatoryException::class);

        $this->creator->processProjectCreation($project_creation_data);
    }

    public function testInvalidShortNameShouldRaiseException(): void
    {
        $this->buildProjectCreator(false);

        $this->user_manager->method('getCurrentUser')->willReturn(
            UserTestBuilder::aUser()->build()
        );

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $errors_collection = new ProjectRegistrationErrorsCollection();
        $errors_collection->addError(
            new ProjectInvalidShortNameException('')
        );

        $this->registration_checker
            ->expects(self::once())
            ->method('collectAllErrorsForProjectRegistration')
            ->willReturn($errors_collection);

        self::expectException(ProjectInvalidShortNameException::class);

        $this->creator->processProjectCreation($project_creation_data);
    }

    public function testInvalidFullNameShouldRaiseException(): void
    {
        $this->buildProjectCreator(false);

        $this->user_manager->method('getCurrentUser')->willReturn(
            UserTestBuilder::aUser()->build()
        );

        $project_creation_data = new ProjectCreationData(
            new DefaultProjectVisibilityRetriever(),
            new NullLogger()
        );

        $errors_collection = new ProjectRegistrationErrorsCollection();
        $errors_collection->addError(
            new ProjectInvalidFullNameException('')
        );

        $this->registration_checker
            ->expects(self::once())
            ->method('collectAllErrorsForProjectRegistration')
            ->willReturn($errors_collection);

        self::expectException(ProjectInvalidFullNameException::class);

        $this->creator->processProjectCreation($project_creation_data);
    }

    public function testItCreatesAProjectAndAutoActivateIt(): void
    {
        $this->buildProjectCreator(true);

        $project_creation_data = ProjectCreationData::buildFromFormArray(
            new DefaultProjectVisibilityRetriever(),
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );

        $errors_collection = new ProjectRegistrationErrorsCollection();
        $this->registration_checker
            ->expects(self::once())
            ->method('collectAllErrorsForProjectRegistration')
            ->willReturn($errors_collection);

        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $this->creator->expects(self::once())->method('createGroupEntry')->willReturn(101);
        $this->project_categories_updater->expects(self::once())->method('update');
        $this->creator->expects(self::once())->method('initFileModule');
        $this->creator->expects(self::once())->method('setProjectAdmin');
        $this->creator->expects(self::once())->method('fakeGroupIdIntoHTTPParams');
        $this->creator->expects(self::once())->method('setMessageToRequesterFromTemplate');
        $this->creator->expects(self::once())->method('initForumModuleFromTemplate');
        $this->creator->expects(self::once())->method('initSVNModuleFromTemplate');
        $this->creator->expects(self::once())->method('initFRSModuleFromTemplate');
        $this->creator->expects(self::once())->method('initTrackerV3ModuleFromTemplate');
        $this->creator->expects(self::once())->method('initWikiModuleFromTemplate');

        $this->email_copier->expects(self::once())->method('copyEmailOptionsFromTemplate');

        $this->dashboard_duplicator->expects(self::once())->method('duplicate');
        $this->field_updator->expects(self::once())->method('update');

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getProject')->willReturn($project);

        $this->event_manager->expects(self::exactly(2))->method('processEvent');

        $this->reference_manager->expects(self::once())->method('addSystemReferencesWithoutService');
        $this->synchronized_project_membership_duplicator->expects(self::once())->method('duplicate');
        $this->ugroup_duplicator->expects(self::once())->method('duplicateOnProjectCreation');

        $this->label_dao->expects(self::once())->method('duplicateLabelsIfNeededBetweenProjectsId');

        $this->service_activator->expects(self::once())->method('activateServicesFromTemplate');

        $this->creator->expects(self::once())->method('autoActivateProject');

        $this->creator->processProjectCreation($project_creation_data);
    }

    public function testItCreatesAProjectWithoutAutoValidation(): void
    {
        $this->buildProjectCreator(false);

        $project_creation_data = ProjectCreationData::buildFromFormArray(
            new DefaultProjectVisibilityRetriever(),
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );

        $errors_collection = new ProjectRegistrationErrorsCollection();
        $this->registration_checker
            ->expects(self::once())
            ->method('collectAllErrorsForProjectRegistration')
            ->willReturn($errors_collection);

        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $this->creator->expects(self::once())->method('createGroupEntry')->willReturn(101);
        $this->project_categories_updater->expects(self::once())->method('update');
        $this->creator->expects(self::once())->method('initFileModule');
        $this->creator->expects(self::once())->method('setProjectAdmin');
        $this->creator->expects(self::once())->method('fakeGroupIdIntoHTTPParams');
        $this->creator->expects(self::once())->method('setMessageToRequesterFromTemplate');
        $this->creator->expects(self::once())->method('initForumModuleFromTemplate');
        $this->creator->expects(self::once())->method('initSVNModuleFromTemplate');
        $this->creator->expects(self::once())->method('initFRSModuleFromTemplate');
        $this->creator->expects(self::once())->method('initTrackerV3ModuleFromTemplate');
        $this->creator->expects(self::once())->method('initWikiModuleFromTemplate');

        $this->email_copier->expects(self::once())->method('copyEmailOptionsFromTemplate');

        $this->dashboard_duplicator->expects(self::once())->method('duplicate');
        $this->field_updator->expects(self::once())->method('update');

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getProject')->willReturn($project);

        $this->event_manager->expects(self::exactly(2))->method('processEvent');

        $this->reference_manager->expects(self::once())->method('addSystemReferencesWithoutService');
        $this->synchronized_project_membership_duplicator->expects(self::once())->method('duplicate');
        $this->ugroup_duplicator->expects(self::once())->method('duplicateOnProjectCreation');

        $this->label_dao->expects(self::once())->method('duplicateLabelsIfNeededBetweenProjectsId');

        $this->service_activator->expects(self::once())->method('activateServicesFromTemplate');

        $this->creator->expects(self::never())->method('autoActivateProject');

        $this->creator->processProjectCreation($project_creation_data);
    }

    private function buildProjectCreator(bool $force_activation): void
    {
        $this->creator = $this->getMockBuilder(ProjectCreator::class)
            ->setConstructorArgs([
                $this->project_manager,
                $this->reference_manager,
                $this->user_manager,
                $this->ugroup_duplicator,
                false,
                $this->createMock(FRSPermissionCreator::class),
                $this->createMock(LicenseAgreementFactory::class),
                $this->dashboard_duplicator,
                $this->label_dao,
                $this->synchronized_project_membership_duplicator,
                $this->event_manager,
                $this->field_updator,
                $this->service_activator,
                $this->registration_checker,
                $this->project_categories_updater,
                $this->email_copier,
                $force_activation,
            ])
            ->onlyMethods([
                'createGroupEntry',
                'initFileModule',
                'setProjectAdmin',
                'fakeGroupIdIntoHTTPParams',
                'setMessageToRequesterFromTemplate',
                'initForumModuleFromTemplate',
                'initSVNModuleFromTemplate',
                'initFRSModuleFromTemplate',
                'initTrackerV3ModuleFromTemplate',
                'initWikiModuleFromTemplate',
                'autoActivateProject',
            ])
            ->getMock();
    }
}
