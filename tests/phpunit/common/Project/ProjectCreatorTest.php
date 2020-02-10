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

use ForgeConfig;
use Mockery;
use PHPUnit\Framework\TestCase;
use Project_InvalidFullName_Exception;
use Project_InvalidShortName_Exception;
use ProjectCreator;
use ProjectManager;
use ReferenceManager;
use Rule_ProjectFullName;
use Rule_ProjectName;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\GlobalSVNPollution;
use Tuleap\Project\Admin\DescriptionFields\FieldUpdator;
use Tuleap\Project\Admin\Service\ProjectServiceActivator;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator;
use UserManager;

/**
 * @see tests/simpletest/common/Project/ProjectCreatorTest.php
 */
final class ProjectCreatorTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, GlobalSVNPollution, ForgeConfigSandbox;

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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Rule_ProjectFullName
     */
    private $rule_project_full_name;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Rule_ProjectName
     */
    private $rule_short_name;

    protected function setUp(): void
    {
        $this->project_manager        = Mockery::mock(ProjectManager::class);
        $this->user_manager           = Mockery::mock(UserManager::class);
        $this->rule_short_name        = Mockery::mock(Rule_ProjectName::class);
        $this->rule_project_full_name = Mockery::mock(Rule_ProjectFullName::class);

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
    }

    public function testMandatoryDescriptionNotSetRaiseException(): void
    {
        $this->buildProjectCreator(false);
        $this->rule_short_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();

        $this->creator->shouldReceive('processProjectCreation')->never();
        $this->expectException(ProjectDescriptionMandatoryException::class);
        $this->creator->createFromRest(
            'shortname',
            'public name',
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
    }

    public function testNotMandatoryDescriptionIsValid(): void
    {
        $this->buildProjectCreator(false);
        ForgeConfig::set('enable_not_mandatory_description', true);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $this->rule_short_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnTrue();

        $this->creator->shouldReceive('processProjectCreation')->once();
        $this->creator->createFromRest(
            'shortname',
            'public name',
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
    }

    public function testInvalidShortNameShouldRaiseException(): void
    {
        $this->buildProjectCreator(false);
        $this->rule_short_name->shouldReceive('isValid')->once()->andReturnFalse();
        $this->rule_short_name->shouldReceive('getErrorMessage')->once();

        $this->creator->shouldReceive('processProjectCreation')->never();
        $this->expectException(Project_InvalidShortName_Exception::class);
        $this->creator->createFromRest(
            'shortname',
            'public name',
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
    }

    public function testInvalidFullNameShouldRaiseException(): void
    {
        $this->buildProjectCreator(false);
        $this->rule_short_name->shouldReceive('isValid')->once()->andReturnTrue();
        $this->rule_project_full_name->shouldReceive('isValid')->once()->andReturnFalse();
        $this->rule_project_full_name->shouldReceive('getErrorMessage')->once();

        $this->creator->shouldReceive('processProjectCreation')->never();
        $this->expectException(Project_InvalidFullName_Exception::class);
        $this->creator->createFromRest(
            'shortname',
            'public name',
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
    }

    public function testItCreatesAProjectAndAutoActivateIt(): void
    {
        $this->buildProjectCreator(true);
        ForgeConfig::set('enable_not_mandatory_description', true);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $user = Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $this->creator->shouldReceive('createGroupEntry')->andReturn(101)->once();
        $this->creator->shouldReceive('setCategories')->once();
        $this->creator->shouldReceive('initFileModule')->once();
        $this->creator->shouldReceive('setProjectAdmin')->once();
        $this->creator->shouldReceive('fakeGroupIdIntoHTTPParams')->once();
        $this->creator->shouldReceive('setMessageToRequesterFromTemplate')->once();
        $this->creator->shouldReceive('initForumModuleFromTemplate')->once();
        $this->creator->shouldReceive('initCVSModuleFromTemplate')->once();
        $this->creator->shouldReceive('initSVNModuleFromTemplate')->once();
        $this->creator->shouldReceive('initFRSModuleFromTemplate')->once();
        $this->creator->shouldReceive('initTrackerV3ModuleFromTemplate')->once();
        $this->creator->shouldReceive('initWikiModuleFromTemplate')->once();
        $this->creator->shouldReceive('copyEmailOptionsFromTemplate')->once();

        $this->dashboard_duplicator->shouldReceive('duplicate')->once();
        $this->field_updator->shouldReceive('update')->once();

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('isError')->andReturns(false);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);

        $this->rule_short_name->shouldReceive('isValid')->andReturn(true);
        $this->rule_project_full_name->shouldReceive('isValid')->andReturn(true);

        $this->event_manager->shouldReceive('processEvent')->twice();

        $this->reference_manager->shouldReceive('addSystemReferencesWithoutService')->once();
        $this->synchronized_project_membership_duplicator->shouldReceive('duplicate')->once();
        $this->ugroup_duplicator->shouldReceive('duplicateOnProjectCreation')->once();

        $this->label_dao->shouldReceive('duplicateLabelsIfNeededBetweenProjectsId')->once();

        $this->service_updator->shouldReceive('activateServicesFromTemplate')->once();

        $this->creator->shouldReceive('autoActivateProject')->once();

        $this->creator->create(
            "test",
            "shortname",
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
    }

    public function testItCreatesAProjectWithoutAutoValidation(): void
    {
        $this->buildProjectCreator(false);
        ForgeConfig::set('enable_not_mandatory_description', true);
        ForgeConfig::set('sys_default_domain', 'example.com');

        $user = Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $this->creator->shouldReceive('createGroupEntry')->andReturn(101)->once();
        $this->creator->shouldReceive('setCategories')->once();
        $this->creator->shouldReceive('initFileModule')->once();
        $this->creator->shouldReceive('setProjectAdmin')->once();
        $this->creator->shouldReceive('fakeGroupIdIntoHTTPParams')->once();
        $this->creator->shouldReceive('setMessageToRequesterFromTemplate')->once();
        $this->creator->shouldReceive('initForumModuleFromTemplate')->once();
        $this->creator->shouldReceive('initCVSModuleFromTemplate')->once();
        $this->creator->shouldReceive('initSVNModuleFromTemplate')->once();
        $this->creator->shouldReceive('initFRSModuleFromTemplate')->once();
        $this->creator->shouldReceive('initTrackerV3ModuleFromTemplate')->once();
        $this->creator->shouldReceive('initWikiModuleFromTemplate')->once();
        $this->creator->shouldReceive('copyEmailOptionsFromTemplate')->once();

        $this->dashboard_duplicator->shouldReceive('duplicate')->once();
        $this->field_updator->shouldReceive('update')->once();

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive('isError')->andReturns(false);
        $this->project_manager->shouldReceive('getProject')->andReturn($project);

        $this->rule_short_name->shouldReceive('isValid')->andReturn(true);
        $this->rule_project_full_name->shouldReceive('isValid')->andReturn(true);

        $this->event_manager->shouldReceive('processEvent')->twice();

        $this->reference_manager->shouldReceive('addSystemReferencesWithoutService')->once();
        $this->synchronized_project_membership_duplicator->shouldReceive('duplicate')->once();
        $this->ugroup_duplicator->shouldReceive('duplicateOnProjectCreation')->once();

        $this->label_dao->shouldReceive('duplicateLabelsIfNeededBetweenProjectsId')->once();

        $this->service_updator->shouldReceive('activateServicesFromTemplate')->once();

        $this->creator->shouldReceive('autoActivateProject')->never();

        $this->creator->createFromRest(
            'shortname',
            'public name',
            TemplateFromProjectForCreation::fromGlobalProjectAdminTemplate(),
            []
        );
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
                new DefaultProjectVisibilityRetriever(),
                $this->synchronized_project_membership_duplicator,
                $this->rule_short_name,
                $this->rule_project_full_name,
                $this->event_manager,
                $this->field_updator,
                $this->service_updator,
                $force_activation
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }
}
