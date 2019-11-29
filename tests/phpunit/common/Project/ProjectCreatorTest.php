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

use Backend;
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
use SystemEventManager;
use Tuleap\Dashboard\Project\ProjectDashboardDuplicator;
use Tuleap\ForgeConfigSandbox;
use Tuleap\FRS\FRSPermissionCreator;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\GlobalSVNPollution;
use Tuleap\Project\Label\LabelDao;
use Tuleap\Project\Registration\Template\TemplateFromProjectForCreation;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDuplicator;
use Tuleap\Service\ServiceCreator;
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
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Rule_ProjectFullName
     */
    private $rule_project_full_name;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Rule_ProjectName
     */
    private $rule_short_name;

    protected function setUp(): void
    {
        parent::setUp();

        $this->project_manager = Mockery::mock(ProjectManager::class);
        ProjectManager::setInstance($this->project_manager);
        $this->user_manager = Mockery::mock(UserManager::class);
        UserManager::setInstance($this->user_manager);
        $this->rule_short_name        = Mockery::mock(Rule_ProjectName::class);
        $this->rule_project_full_name = Mockery::mock(Rule_ProjectFullName::class);

        $this->creator = Mockery::mock(
            ProjectCreator::class,
            [
                $this->project_manager,
                Mockery::mock(ReferenceManager::class),
                $this->user_manager,
                Mockery::mock(UgroupDuplicator::class),
                false,
                Mockery::mock(FRSPermissionCreator::class),
                Mockery::mock(LicenseAgreementFactory::class),
                Mockery::mock(ProjectDashboardDuplicator::class),
                Mockery::mock(ServiceCreator::class),
                Mockery::mock(LabelDao::class),
                new DefaultProjectVisibilityRetriever(),
                Mockery::mock(SynchronizedProjectMembershipDuplicator::class),
                $this->rule_short_name,
                $this->rule_project_full_name,
                false
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();
    }

    protected function tearDown(): void
    {
        ProjectManager::clearInstance();
        UserManager::clearInstance();
        SystemEventManager::clearInstance();
        Backend::clearInstances();

        parent::tearDown();
    }

    public function testMandatoryDescriptionNotSetRaiseException(): void
    {
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
}
