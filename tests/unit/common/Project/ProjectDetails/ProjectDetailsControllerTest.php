<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

namespace phpunit\common\Project\ProjectDetails;

use CSRFSynchronizerToken;
use EventManager;
use Feedback;
use ForgeAccess;
use ForgeConfig;
use HTTPRequest;
use Mockery;
use PFUser;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\BaseLayout;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsController;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\Project\Admin\ProjectVisibilityPresenterBuilder;
use Tuleap\Project\Admin\ProjectVisibilityUserConfigurationPermissions;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityChecker;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Icons\ProjectIconRetriever;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TroveCat\TroveCatLinkDao;
use UGroupBinding;

class ProjectDetailsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectVisibilityUserConfigurationPermissions
     */
    private $project_visibility_configuration;
    /**
     * @var EventManager|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $event_manager;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var ProjectDetailsController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectHistoryDao
     */
    private $project_history_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectDetailsDAO
     */
    private $project_details_dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $current_project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DescriptionFieldsFactory
     */
    private $description_fields_factory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->description_fields_factory       = Mockery::mock(DescriptionFieldsFactory::class);
        $this->current_project                  = Mockery::mock(Project::class);
        $this->project_details_dao              = Mockery::mock(ProjectDetailsDAO::class);
        $project_manager                        = Mockery::mock(ProjectManager::class);
        $this->event_manager                    = Mockery::mock(EventManager::class);
        $this->project_history_dao              = Mockery::mock(ProjectHistoryDao::class);
        $project_visibility_presenter_builder   = Mockery::mock(ProjectVisibilityPresenterBuilder::class);
        $this->project_visibility_configuration = Mockery::mock(
            ProjectVisibilityUserConfigurationPermissions::class
        );
        $ugroup_binding                         = Mockery::mock(UGroupBinding::class);
        $trove_cat_link_dao                     = Mockery::mock(TroveCatLinkDao::class);
        $this->csrf_token                       = Mockery::mock(CSRFSynchronizerToken::class);

        $this->controller = new ProjectDetailsController(
            $this->description_fields_factory,
            $this->current_project,
            $this->project_details_dao,
            $project_manager,
            $this->event_manager,
            $this->project_history_dao,
            $project_visibility_presenter_builder,
            $this->project_visibility_configuration,
            $ugroup_binding,
            $trove_cat_link_dao,
            $this->csrf_token,
            Mockery::mock(TemplateFactory::class),
            new ProjectIconRetriever(),
            new UpdateVisibilityChecker($this->event_manager),
        );

        $GLOBALS['Response'] = Mockery::mock(BaseLayout::class);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Response']);

        parent::tearDown();
    }

    public function testUpdateIsInvalidWhenProjectNameIsNotProvided(): void
    {
        $this->csrf_token->shouldReceive('check')->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->once()->withArgs(['form_group_name'])->andReturn(false);
        $request->shouldReceive('get')->once()->withArgs(['form_shortdesc'])->andReturn('decription');

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->controller->update($request);
    }

    public function testUpdateIsInvalidWhenDescriptionIsNotProvidedAndFlagIsNotProvided(): void
    {
        $this->csrf_token->shouldReceive('check')->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->once()->withArgs(['form_group_name'])->andReturn('project_name');
        $request->shouldReceive('get')->once()->withArgs(['form_shortdesc'])->andReturn(false);

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->controller->update($request);
    }

    public function testUpdateIsInvalidWhenDescriptionIsNotProvidedAndDescriptionIsMandatoryForProject(): void
    {
        ForgeConfig::set('enable_not_mandatory_description', false);

        $this->csrf_token->shouldReceive('check')->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->once()->withArgs(['form_group_name'])->andReturn('project_name');
        $request->shouldReceive('get')->once()->withArgs(['form_shortdesc'])->andReturn(false);

        $GLOBALS['Response']->shouldReceive('addFeedback')->once();

        $this->controller->update($request);
    }

    public function testUpdateIsValidWhenDescriptionIsNotProvidedAndDescriptionIsNOTMandatoryForProject(): void
    {
        ForgeConfig::set('enable_not_mandatory_description', true);
        ForgeConfig::set('feature_flag_project_icon_display', '1');

        $this->csrf_token->shouldReceive('check')->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->twice()->withArgs(['form_group_name'])->andReturn('project_name');
        $request->shouldReceive('get')->twice()->withArgs(['form_shortdesc'])->andReturn(false);
        $request->shouldReceive('get')->atLeast()->once()->withArgs(['form-group-name-icon'])->andReturn("😬");
        $request->shouldReceive('get')->atLeast()->once()->withArgs(['group_id'])->andReturn(102);
        $request->shouldReceive('getCurrentUser')->atLeast()->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('existAndNonEmpty')->atLeast()->once()->andReturnFalse();
        $request->shouldReceive('getProject')->twice()->andReturn(Mockery::mock(Project::class));

        $this->description_fields_factory->shouldReceive('getAllDescriptionFields')->twice()->andReturn([]);

        $this->current_project->shouldReceive('getProjectsDescFieldsValue')->once()->andReturn([]);

        $this->project_details_dao->shouldReceive('updateGroupNameAndDescription')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->once();
        $this->event_manager->shouldReceive('processEvent')->once();
        $this->project_visibility_configuration->shouldReceive('canUserConfigureProjectVisibility')->once(
        )->andReturnFalse();
        $this->project_visibility_configuration->shouldReceive('canUserConfigureTruncatedMail')->once()->andReturnFalse(
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->once()->withArgs([Feedback::INFO, _('Update successful')]);

        $this->controller->update($request);
    }

    public function testVisibilityUpdateIsNotValidWhenNotMatchingRestrictedUsersConstraints(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '1');
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $this->csrf_token->shouldReceive('check')->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->twice()->withArgs(['form_group_name'])->andReturn('project_name');
        $request->shouldReceive('get')->twice()->withArgs(['form_shortdesc'])->andReturn('decription');
        $request->shouldReceive('get')->atLeast()->once()->withArgs(['form-group-name-icon'])->andReturn("");
        $request->shouldReceive('get')->atLeast()->once()->withArgs(['group_id'])->andReturn(102);
        $request->shouldReceive('get')->withArgs(['project_visibility'])->andReturn(Project::ACCESS_PRIVATE_WO_RESTRICTED);
        $request->shouldReceive('get')->withArgs(['term_of_service'])->andReturn(true);
        $current_user = $this->createMock(PFUser::class);
        $request->shouldReceive('getCurrentUser')->atLeast()->once()->andReturn($current_user);
        $request->shouldReceive('existAndNonEmpty')->atLeast()->once()->andReturnFalse();

        $project = $this->createMock(Project::class);
        $project->method('getAdmins')->willReturn([
            UserTestBuilder::aRestrictedUser()->build(),
        ]);
        $project->method('getAccess')->willReturn(Project::ACCESS_PUBLIC);
        $project->method('getID')->willReturn(101);
        $current_user->method('isAdmin')->with(101)->willReturn(true);
        $request->shouldReceive('getProject')->twice()->andReturn($project);

        $this->description_fields_factory->shouldReceive('getAllDescriptionFields')->twice()->andReturn([]);

        $this->current_project->shouldReceive('getProjectsDescFieldsValue')->once()->andReturn([]);

        $this->project_details_dao->shouldReceive('updateGroupNameAndDescription')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->once();
        $this->event_manager->shouldReceive('processEvent')->once();
        $this->project_visibility_configuration->shouldReceive('canUserConfigureProjectVisibility')->once(
        )->andReturnTrue();
        $this->project_visibility_configuration->shouldReceive('canUserConfigureTruncatedMail')->once()->andReturnFalse(
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->once()->withArgs(
            [Feedback::ERROR, _('Cannot switch the project visibility because it will remove every restricted users from the project, and after that no administrator will be left.')]
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->once()->withArgs([Feedback::INFO, _('Update successful')]);

        $this->controller->update($request);
    }

    public function testItUpdatesProject(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '1');

        $this->csrf_token->shouldReceive('check')->once();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('get')->twice()->withArgs(['form_group_name'])->andReturn('project_name');
        $request->shouldReceive('get')->twice()->withArgs(['form_shortdesc'])->andReturn('decription');
        $request->shouldReceive('get')->atLeast()->once()->withArgs(['form-group-name-icon'])->andReturn("");
        $request->shouldReceive('get')->atLeast()->once()->withArgs(['group_id'])->andReturn(102);
        $request->shouldReceive('getCurrentUser')->atLeast()->once()->andReturn(Mockery::mock(PFUser::class));
        $request->shouldReceive('existAndNonEmpty')->atLeast()->once()->andReturnFalse();
        $request->shouldReceive('getProject')->twice()->andReturn(Mockery::mock(Project::class));

        $this->description_fields_factory->shouldReceive('getAllDescriptionFields')->twice()->andReturn([]);

        $this->current_project->shouldReceive('getProjectsDescFieldsValue')->once()->andReturn([]);

        $this->project_details_dao->shouldReceive('updateGroupNameAndDescription')->once();
        $this->project_history_dao->shouldReceive('groupAddHistory')->once();
        $this->event_manager->shouldReceive('processEvent')->once();
        $this->project_visibility_configuration->shouldReceive('canUserConfigureProjectVisibility')->once(
        )->andReturnFalse();
        $this->project_visibility_configuration->shouldReceive('canUserConfigureTruncatedMail')->once()->andReturnFalse(
        );

        $GLOBALS['Response']->shouldReceive('addFeedback')->once()->withArgs([Feedback::INFO, _('Update successful')]);

        $this->controller->update($request);
    }
}
