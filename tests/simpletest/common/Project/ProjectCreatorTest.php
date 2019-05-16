<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\Project\DefaultProjectVisibilityRetriever;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ProjectCreatorTest extends TuleapTestCase
{
    private const TEMPLATE_ID = 10;

    private $project_manager;

    public function setUp()
    {
        parent::setUp();

        $GLOBALS['ftp_frs_dir_prefix'] = dirname(__FILE__) . '/_fixtures';
        $GLOBALS['svn_prefix']         = 'whatever';

        $this->event_manager = stub('SystemEventManager')->isUserNameAvailable()->returns(true);
        stub($this->event_manager)->isProjectNameAvailable()->returns(true);
        SystemEventManager::setInstance($this->event_manager);

        $template_project = Mockery::mock(Project::class);
        $template_project->shouldReceive('isError')->andReturn(false);
        $this->project_manager = Mockery::spy(ProjectManager::class);
        $this->project_manager->shouldReceive('getProject')->with(self::TEMPLATE_ID)->andReturn($template_project);
        ProjectManager::setInstance($this->project_manager);

        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getUserByUserName()->returns(null);
        $user = mock('PFUser');
        stub($user)->isSuperUser()->returns(false);
        stub($this->user_manager)->getCurrentUser()->returns($user);
        UserManager::setInstance($this->user_manager);

        $backend_cvs = stub('BackendCVS')->isNameAvailable()->returns(true);
        BackendCVS::setInstance('CVS', $backend_cvs);

        ForgeConfig::store();
        ForgeConfig::set('sys_use_project_registration', 1);

        $this->creator = partial_mock(
            'ProjectCreator',
            array('createProject'),
            array(
                $this->project_manager,
                mock('ReferenceManager'),
                $this->user_manager,
                mock('Tuleap\Project\UgroupDuplicator'),
                false,
                mock('Tuleap\FRS\FRSPermissionCreator'),
                mock('Tuleap\Dashboard\Project\ProjectDashboardDuplicator'),
                mock('Tuleap\Service\ServiceCreator'),
                mock(\Tuleap\Project\Label\LabelDao::class),
                new DefaultProjectVisibilityRetriever()
            )
        );
    }

    public function tearDown()
    {
        BackendCVS::clearInstances();
        ProjectManager::clearInstance();
        SystemEventManager::clearInstance();
        ForgeConfig::restore();
        unset($GLOBALS['Language']);
        unset($GLOBALS['svn_prefix']);

        parent::tearDown();
    }

    public function testInvalidShortNameShouldRaiseException()
    {
        $this->expectException('Project_InvalidShortName_Exception');
        $this->creator->create('contains.point', 'sdf', ['project' => ['built_from_template' => self::TEMPLATE_ID]]);
    }

    public function testInvalidFullNameShouldRaiseException()
    {
        $this->expectException('Project_InvalidFullName_Exception');
        $this->creator->create('shortname', 'a', ['project' => ['built_from_template' => self::TEMPLATE_ID]]);
    }

    public function testCreationFailureShouldRaiseException()
    {
        $this->expectException('Project_Creation_Exception');
        $this->creator->create('shortname', 'Valid Full Name', ['project' => ['built_from_template' => self::TEMPLATE_ID]]);
    }

    public function itDoesNotCreateProjectWhenRegistrationIsDisabledAndTheUserIsNotSiteAdmin()
    {
        ForgeConfig::set('sys_use_project_registration', 0);

        $this->expectException('Tuleap\\Project\\ProjectRegistrationDisabledException');

        $this->creator->create('registrationdisablednotsiteadmin', 'Registration disabled without being siteadmin', array());
    }

    public function itDoesCreateProjectWhenRegistrationIsDisabledAndTheUserIsSiteAdmin()
    {
        $user_manager = mock('UserManager');
        stub($user_manager)->getUserByUserName()->returns(null);
        $user = mock('PFUser');
        stub($user)->isSuperUser()->returns(true);
        stub($user_manager)->getCurrentUser()->returns($user);
        UserManager::setInstance($user_manager);

        $project_creator = $this->creator = partial_mock(
            'ProjectCreator',
            array('createProject'),
            array(
                $this->project_manager,
                mock('ReferenceManager'),
                $user_manager,
                mock('Tuleap\Project\UgroupDuplicator'),
                false,
                mock('Tuleap\FRS\FRSPermissionCreator'),
                mock('Tuleap\Dashboard\Project\ProjectDashboardDuplicator'),
                mock('Tuleap\Service\ServiceCreator'),
                Mock(\Tuleap\Project\Label\LabelDao::class),
                new DefaultProjectVisibilityRetriever()
            )
        );
        $project_id      = 100;
        stub($project_creator)->createProject()->returns($project_id);

        ForgeConfig::set('sys_use_project_registration', 0);

        $this->project_manager->shouldReceive('getProject')->with($project_id)->andReturn(Mockery::mock(Project::class));

        $project_creator->create(
            'registrationdisabledsiteadmin',
            'Registration disabled but siteadmin',
            ['project' => ['built_from_template' => self::TEMPLATE_ID]]
        );
    }
}
