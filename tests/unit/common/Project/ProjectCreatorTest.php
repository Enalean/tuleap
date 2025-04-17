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

use EventManager;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
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
use Tuleap\Project\Banner\Banner;
use Tuleap\Project\Banner\BannerCreator;
use Tuleap\Project\Banner\BannerRetriever;
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
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\Project\Registration\StoreProjectInformationStub;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class ProjectCreatorTest extends TestCase
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
    private EventManager&MockObject $event_manager;
    private ProjectRegistrationChecker&MockObject $registration_checker;
    private ProjectCategoriesUpdater&MockObject $project_categories_updater;
    private EmailCopier&MockObject $email_copier;
    private StoreProjectInformationStub $store_project_information;
    private BannerRetriever&MockObject $banner_retriever;
    private BannerCreator&MockObject $banner_creator;

    protected function setUp(): void
    {
        $this->project_manager = $this->createMock(ProjectManager::class);
        $this->user_manager    = $this->createMock(UserManager::class);

        $this->event_manager                              = $this->createMock(EventManager::class);
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
        $this->email_copier                               = $this->createMock(EmailCopier::class);
        $this->store_project_information                  = StoreProjectInformationStub::build();
        $this->banner_retriever                           = $this->createMock(BannerRetriever::class);
        $this->banner_creator                             = $this->createMock(BannerCreator::class);
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
            ->expects($this->once())
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
            ->expects($this->once())
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
            ->expects($this->once())
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
            ->expects($this->once())
            ->method('collectAllErrorsForProjectRegistration')
            ->willReturn($errors_collection);

        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $this->project_categories_updater->expects($this->once())->method('update');
        $this->creator->expects($this->once())->method('initFileModule');
        $this->creator->expects($this->once())->method('setProjectAdmin');
        $this->creator->expects($this->once())->method('fakeGroupIdIntoHTTPParams');
        $this->creator->expects($this->once())->method('setMessageToRequesterFromTemplate');
        $this->creator->expects($this->once())->method('initForumModuleFromTemplate');
        $this->creator->expects($this->once())->method('initSVNModuleFromTemplate');
        $this->creator->expects($this->once())->method('initFRSModuleFromTemplate');
        $this->creator->expects($this->once())->method('initTrackerV3ModuleFromTemplate');
        $this->creator->expects($this->once())->method('initWikiModuleFromTemplate');

        $this->email_copier->expects($this->once())->method('copyEmailOptionsFromTemplate');

        $this->dashboard_duplicator->expects($this->once())->method('duplicate');
        $this->field_updator->expects($this->once())->method('update');

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getProject')->willReturn($project);

        $this->event_manager->expects($this->exactly(2))->method('processEvent');

        $this->reference_manager->expects($this->once())->method('addSystemReferencesWithoutService');
        $this->synchronized_project_membership_duplicator->expects($this->once())->method('duplicate');
        $this->ugroup_duplicator->expects($this->once())->method('duplicateOnProjectCreation');

        $this->label_dao->expects($this->once())->method('duplicateLabelsIfNeededBetweenProjectsId');

        $this->service_activator->expects($this->once())->method('activateServicesFromTemplate');

        $this->creator->expects($this->once())->method('autoActivateProject');

        $this->banner_retriever->method('getBannerForProject')->willReturn(new Banner('Some message'));
        $this->banner_creator->expects($this->once())->method('addBanner')->with(self::anything(), 'Some message');

        $this->creator->processProjectCreation($project_creation_data);

        self::assertTrue($this->store_project_information->isCalled());
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
            ->expects($this->once())
            ->method('collectAllErrorsForProjectRegistration')
            ->willReturn($errors_collection);

        $user = UserTestBuilder::buildWithDefaults();
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $this->project_categories_updater->expects($this->once())->method('update');
        $this->creator->expects($this->once())->method('initFileModule');
        $this->creator->expects($this->once())->method('setProjectAdmin');
        $this->creator->expects($this->once())->method('fakeGroupIdIntoHTTPParams');
        $this->creator->expects($this->once())->method('setMessageToRequesterFromTemplate');
        $this->creator->expects($this->once())->method('initForumModuleFromTemplate');
        $this->creator->expects($this->once())->method('initSVNModuleFromTemplate');
        $this->creator->expects($this->once())->method('initFRSModuleFromTemplate');
        $this->creator->expects($this->once())->method('initTrackerV3ModuleFromTemplate');
        $this->creator->expects($this->once())->method('initWikiModuleFromTemplate');

        $this->email_copier->expects($this->once())->method('copyEmailOptionsFromTemplate');

        $this->dashboard_duplicator->expects($this->once())->method('duplicate');
        $this->field_updator->expects($this->once())->method('update');

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_manager->method('getProject')->willReturn($project);

        $this->event_manager->expects($this->exactly(2))->method('processEvent');

        $this->reference_manager->expects($this->once())->method('addSystemReferencesWithoutService');
        $this->synchronized_project_membership_duplicator->expects($this->once())->method('duplicate');
        $this->ugroup_duplicator->expects($this->once())->method('duplicateOnProjectCreation');

        $this->label_dao->expects($this->once())->method('duplicateLabelsIfNeededBetweenProjectsId');

        $this->service_activator->expects($this->once())->method('activateServicesFromTemplate');

        $this->creator->expects($this->never())->method('autoActivateProject');

        $this->banner_retriever->method('getBannerForProject')->willReturn(null);

        $this->creator->processProjectCreation($project_creation_data);

        self::assertTrue($this->store_project_information->isCalled());
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
                $this->store_project_information,
                $this->banner_retriever,
                $this->banner_creator,
                $force_activation,
            ])
            ->onlyMethods([
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
