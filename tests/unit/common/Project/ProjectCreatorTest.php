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

use Mockery;
use ProjectCreationData;
use ProjectCreator;
use ProjectManager;
use Psr\Log\NullLogger;
use ReferenceManager;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\GlobalSVNPollution;
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
use Tuleap\Test\Builders\UserTestBuilder;
use UserManager;

final class ProjectCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    public $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    public $user_manager;
    /**
     * @var ProjectCreator
     */
    public $creator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectServiceActivator
     */
    private $service_updator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FieldUpdator
     */
    private $field_updator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectDashboardDuplicator
     */
    private $dashboard_duplicator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LabelDao
     */
    private $label_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UgroupDuplicator
     */
    private $ugroup_duplicator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|SynchronizedProjectMembershipDuplicator
     */
    private $synchronized_project_membership_duplicator;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReferenceManager
     */
    private $reference_manager;
    /**
     * @var \EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectRegistrationChecker
     */
    private $registration_checker;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ProjectCategoriesUpdater
     */
    private $project_categories_updater;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub&EmailCopier
     */
    private $email_copier;

    protected function setUp(): void
    {
        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->user_manager    = Mockery::mock(UserManager::class);

        $this->event_manager                              = Mockery::mock(\EventManager::class);
        $this->reference_manager                          = Mockery::mock(ReferenceManager::class);
        $this->synchronized_project_membership_duplicator = Mockery::mock(
            SynchronizedProjectMembershipDuplicator::class
        );
        $this->ugroup_duplicator                          = Mockery::mock(UgroupDuplicator::class);
        $this->label_dao                                  = Mockery::mock(LabelDao::class);
        $this->dashboard_duplicator                       = Mockery::mock(ProjectDashboardDuplicator::class);
        $this->field_updator                              = Mockery::mock(FieldUpdator::class);
        $this->service_updator                            = Mockery::mock(ProjectServiceActivator::class);
        $this->registration_checker                       = $this->createMock(ProjectRegistrationChecker::class);
        $this->project_categories_updater                 = $this->createMock(ProjectCategoriesUpdater::class);
        $this->email_copier                               = $this->createStub(EmailCopier::class);
    }

    public function testMandatoryDescriptionNotSetRaiseException(): void
    {
        $this->buildProjectCreator(false);

        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
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

        $this->expectException(ProjectDescriptionMandatoryException::class);

        $this->creator->processProjectCreation($project_creation_data);
    }

    public function testInvalidShortNameShouldRaiseException(): void
    {
        $this->buildProjectCreator(false);

        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
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

        $this->expectException(ProjectInvalidShortNameException::class);

        $this->creator->processProjectCreation($project_creation_data);
    }

    public function testInvalidFullNameShouldRaiseException(): void
    {
        $this->buildProjectCreator(false);

        $this->user_manager->shouldReceive('getCurrentUser')->andReturn(
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

        $this->expectException(ProjectInvalidFullNameException::class);

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

        $user = Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $this->creator->shouldReceive('createGroupEntry')->andReturn(101)->once();
        $this->project_categories_updater->expects(self::once())->method('update');
        $this->creator->shouldReceive('initFileModule')->once();
        $this->creator->shouldReceive('setProjectAdmin')->once();
        $this->creator->shouldReceive('fakeGroupIdIntoHTTPParams')->once();
        $this->creator->shouldReceive('setMessageToRequesterFromTemplate')->once();
        $this->creator->shouldReceive('initForumModuleFromTemplate')->once();
        $this->creator->shouldReceive('initSVNModuleFromTemplate')->once();
        $this->creator->shouldReceive('initFRSModuleFromTemplate')->once();
        $this->creator->shouldReceive('initTrackerV3ModuleFromTemplate')->once();
        $this->creator->shouldReceive('initWikiModuleFromTemplate')->once();

        $this->email_copier->expects(self::once())->method('copyEmailOptionsFromTemplate');

        $this->dashboard_duplicator->shouldReceive('duplicate')->once();
        $this->field_updator->shouldReceive('update')->once();

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('isError')->andReturns(false);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);

        $this->event_manager->shouldReceive('processEvent')->twice();

        $this->reference_manager->shouldReceive('addSystemReferencesWithoutService')->once();
        $this->synchronized_project_membership_duplicator->shouldReceive('duplicate')->once();
        $this->ugroup_duplicator->shouldReceive('duplicateOnProjectCreation')->once();

        $this->label_dao->shouldReceive('duplicateLabelsIfNeededBetweenProjectsId')->once();

        $this->service_updator->shouldReceive('activateServicesFromTemplate')->once();

        $this->creator->shouldReceive('autoActivateProject')->once();

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

        $user = Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $this->creator->shouldReceive('createGroupEntry')->andReturn(101)->once();
        $this->project_categories_updater->expects(self::once())->method('update');
        $this->creator->shouldReceive('initFileModule')->once();
        $this->creator->shouldReceive('setProjectAdmin')->once();
        $this->creator->shouldReceive('fakeGroupIdIntoHTTPParams')->once();
        $this->creator->shouldReceive('setMessageToRequesterFromTemplate')->once();
        $this->creator->shouldReceive('initForumModuleFromTemplate')->once();
        $this->creator->shouldReceive('initSVNModuleFromTemplate')->once();
        $this->creator->shouldReceive('initFRSModuleFromTemplate')->once();
        $this->creator->shouldReceive('initTrackerV3ModuleFromTemplate')->once();
        $this->creator->shouldReceive('initWikiModuleFromTemplate')->once();

        $this->email_copier->expects(self::once())->method('copyEmailOptionsFromTemplate');

        $this->dashboard_duplicator->shouldReceive('duplicate')->once();
        $this->field_updator->shouldReceive('update')->once();

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('isError')->andReturns(false);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);

        $this->event_manager->shouldReceive('processEvent')->twice();

        $this->reference_manager->shouldReceive('addSystemReferencesWithoutService')->once();
        $this->synchronized_project_membership_duplicator->shouldReceive('duplicate')->once();
        $this->ugroup_duplicator->shouldReceive('duplicateOnProjectCreation')->once();

        $this->label_dao->shouldReceive('duplicateLabelsIfNeededBetweenProjectsId')->once();

        $this->service_updator->shouldReceive('activateServicesFromTemplate')->once();

        $this->creator->shouldReceive('autoActivateProject')->never();

        $this->creator->processProjectCreation($project_creation_data);
    }

    private function buildProjectCreator(bool $force_activation): void
    {
        $this->creator = Mockery::mock(
            ProjectCreator::class,
            [
                $this->project_manager,
                $this->reference_manager,
                $this->user_manager,
                $this->ugroup_duplicator,
                false,
                Mockery::mock(FRSPermissionCreator::class),
                Mockery::mock(LicenseAgreementFactory::class),
                $this->dashboard_duplicator,
                $this->label_dao,
                $this->synchronized_project_membership_duplicator,
                $this->event_manager,
                $this->field_updator,
                $this->service_updator,
                $this->registration_checker,
                $this->project_categories_updater,
                $this->email_copier,
                $force_activation,
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }
}
