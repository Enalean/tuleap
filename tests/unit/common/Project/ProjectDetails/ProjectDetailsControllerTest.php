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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalResponseMock;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsController;
use Tuleap\Project\Admin\ProjectDetails\ProjectDetailsDAO;
use Tuleap\Project\Admin\ProjectVisibilityPresenterBuilder;
use Tuleap\Project\Admin\ProjectVisibilityUserConfigurationPermissions;
use Tuleap\Project\Admin\Visibility\UpdateVisibilityChecker;
use Tuleap\Project\DescriptionFieldsFactory;
use Tuleap\Project\Icons\ProjectIconRetriever;
use Tuleap\Project\Registration\Template\TemplateFactory;
use Tuleap\Project\Registration\Template\Upload\RetrieveUploadedArchiveForProjectStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\TroveCat\TroveCatLinkDao;
use UGroupBinding;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectDetailsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalResponseMock;

    private ProjectVisibilityUserConfigurationPermissions&Stub $project_visibility_configuration;
    private EventManager&MockObject $event_manager;
    private CSRFSynchronizerToken&MockObject $csrf_token;
    private ProjectDetailsController $controller;
    private ProjectHistoryDao&MockObject $project_history_dao;
    private ProjectDetailsDAO&MockObject $project_details_dao;
    private Project&Stub $current_project;
    private DescriptionFieldsFactory&Stub $description_fields_factory;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->description_fields_factory       = $this->createStub(DescriptionFieldsFactory::class);
        $this->current_project                  = $this->createStub(Project::class);
        $this->project_details_dao              = $this->createMock(ProjectDetailsDAO::class);
        $project_manager                        = $this->createStub(ProjectManager::class);
        $this->event_manager                    = $this->createMock(EventManager::class);
        $this->project_history_dao              = $this->createMock(ProjectHistoryDao::class);
        $project_visibility_presenter_builder   = $this->createStub(ProjectVisibilityPresenterBuilder::class);
        $this->project_visibility_configuration = $this->createStub(ProjectVisibilityUserConfigurationPermissions::class);
        $ugroup_binding                         = $this->createStub(UGroupBinding::class);
        $trove_cat_link_dao                     = $this->createStub(TroveCatLinkDao::class);
        $this->csrf_token                       = $this->createMock(CSRFSynchronizerToken::class);

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
            $this->createStub(TemplateFactory::class),
            RetrieveUploadedArchiveForProjectStub::withoutArchive(),
            new ProjectIconRetriever(),
            new UpdateVisibilityChecker($this->event_manager),
        );

        $this->csrf_token->expects($this->once())->method('check');
    }

    public function testUpdateIsInvalidWhenProjectNameIsNotProvided(): void
    {
        $request = $this->createMock(\Tuleap\HTTPRequest::class);
        $matcher = self::exactly(2);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return false;
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return 'decription';
                }
            });

        $this->project_details_dao->expects($this->never())->method('updateGroupNameAndDescription');
        $this->project_history_dao->expects($this->never())->method('groupAddHistory');
        $this->event_manager->expects($this->never())->method('processEvent');

        $this->controller->update($request);

        self::assertCount(1, $this->global_response->inspector->getFeedback());
    }

    public function testUpdateIsInvalidWhenDescriptionIsNotProvidedAndFlagIsNotProvided(): void
    {
        $request = $this->createMock(\Tuleap\HTTPRequest::class);
        $matcher = self::exactly(2);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return false;
                }
            });

        $this->project_details_dao->expects($this->never())->method('updateGroupNameAndDescription');
        $this->project_history_dao->expects($this->never())->method('groupAddHistory');
        $this->event_manager->expects($this->never())->method('processEvent');

        $this->controller->update($request);

        self::assertCount(1, $this->global_response->inspector->getFeedback());
    }

    public function testUpdateIsInvalidWhenDescriptionIsNotProvidedAndDescriptionIsMandatoryForProject(): void
    {
        ForgeConfig::set('enable_not_mandatory_description', false);

        $request = $this->createMock(\Tuleap\HTTPRequest::class);
        $matcher = self::exactly(2);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return false;
                }
            });

        $this->project_details_dao->expects($this->never())->method('updateGroupNameAndDescription');
        $this->project_history_dao->expects($this->never())->method('groupAddHistory');
        $this->event_manager->expects($this->never())->method('processEvent');

        $this->controller->update($request);

        self::assertCount(1, $this->global_response->inspector->getFeedback());
    }

    public function testUpdateIsValidWhenDescriptionIsNotProvidedAndDescriptionIsNOTMandatoryForProject(): void
    {
        ForgeConfig::set('enable_not_mandatory_description', true);
        ForgeConfig::set('feature_flag_project_icon_display', '1');

        $request = $this->createMock(\Tuleap\HTTPRequest::class);
        $matcher = self::atLeast(8);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return false;
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 4) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 5) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 6) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return false;
                }
                if ($matcher->numberOfInvocations() === 7) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 8) {
                    self::assertSame('form-group-name-icon', $parameters[0]);
                    return '😬';
                }
            });
        $request->expects($this->atLeastOnce())->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $request->expects($this->atLeastOnce())->method('existAndNonEmpty')->willReturn(false);
        $request->expects($this->exactly(2))->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->description_fields_factory->method('getAllDescriptionFields')->willReturn([]);

        $this->current_project->method('getProjectsDescFieldsValue')->willReturn([]);

        $this->project_details_dao->expects($this->once())->method('updateGroupNameAndDescription');
        $this->project_history_dao->expects($this->once())->method('groupAddHistory');
        $this->event_manager->expects($this->once())->method('processEvent');
        $this->project_visibility_configuration->method('canUserConfigureProjectVisibility')->willReturn(false);
        $this->project_visibility_configuration->method('canUserConfigureTruncatedMail')->willReturn(false);

        $this->controller->update($request);

        self::assertEquals([['level' => Feedback::INFO, 'message' => 'Update successful']], $this->global_response->inspector->getFeedback());
    }

    public function testVisibilityUpdateIsNotValidWhenNotMatchingRestrictedUsersConstraints(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '1');
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        $request = $this->createMock(\Tuleap\HTTPRequest::class);
        $matcher = self::exactly(12);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return 'decription';
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 4) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 5) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 6) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return 'decription';
                }
                if ($matcher->numberOfInvocations() === 7) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 8) {
                    self::assertSame('form-group-name-icon', $parameters[0]);
                    return '';
                }
                if ($matcher->numberOfInvocations() === 9) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 10) {
                    self::assertSame('project_visibility', $parameters[0]);
                    return Project::ACCESS_PRIVATE_WO_RESTRICTED;
                }
                if ($matcher->numberOfInvocations() === 11) {
                    self::assertSame('term_of_service', $parameters[0]);
                    return true;
                }
                if ($matcher->numberOfInvocations() === 12) {
                    self::assertSame('project_visibility', $parameters[0]);
                    return Project::ACCESS_PRIVATE_WO_RESTRICTED;
                }
            });
        $current_user = $this->createStub(PFUser::class);
        $request->expects($this->atLeastOnce())->method('getCurrentUser')->willReturn($current_user);
        $request->expects($this->atLeastOnce())->method('existAndNonEmpty')->willReturn(false);

        $project = $this->createStub(Project::class);
        $project->method('getAdmins')->willReturn([
            UserTestBuilder::aRestrictedUser()->build(),
        ]);
        $project->method('getAccess')->willReturn(Project::ACCESS_PUBLIC);
        $project->method('getID')->willReturn(101);
        $current_user->method('isAdmin')->with(101)->willReturn(true);
        $request->expects($this->exactly(2))->method('getProject')->willReturn($project);

        $this->description_fields_factory->method('getAllDescriptionFields')->willReturn([]);

        $this->current_project->method('getProjectsDescFieldsValue')->willReturn([]);

        $this->project_details_dao->expects($this->once())->method('updateGroupNameAndDescription');
        $this->project_history_dao->expects($this->once())->method('groupAddHistory');
        $this->event_manager->expects($this->once())->method('processEvent');
        $this->project_visibility_configuration->method('canUserConfigureProjectVisibility')->willReturn(true);
        $this->project_visibility_configuration->method('canUserConfigureTruncatedMail')->willReturn(false);

        $this->controller->update($request);

        self::assertEquals(
            [
                ['level' => Feedback::INFO, 'message' => 'Update successful'],
                ['level' => Feedback::ERROR, 'message' => 'Cannot switch the project visibility because it will remove every restricted users from the project, and after that no administrator will be left.'],
            ],
            $this->global_response->inspector->getFeedback()
        );
    }

    public function testItUpdatesProject(): void
    {
        ForgeConfig::set('feature_flag_project_icon_display', '1');

        $request = $this->createMock(\Tuleap\HTTPRequest::class);
        $matcher = self::exactly(9);
        $request
            ->expects($matcher)
            ->method('get')->willReturnCallback(function (...$parameters) use ($matcher) {
                if ($matcher->numberOfInvocations() === 1) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 2) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return 'decription';
                }
                if ($matcher->numberOfInvocations() === 3) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 4) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 5) {
                    self::assertSame('form_group_name', $parameters[0]);
                    return 'project_name';
                }
                if ($matcher->numberOfInvocations() === 6) {
                    self::assertSame('form_shortdesc', $parameters[0]);
                    return 'decription';
                }
                if ($matcher->numberOfInvocations() === 7) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
                if ($matcher->numberOfInvocations() === 8) {
                    self::assertSame('form-group-name-icon', $parameters[0]);
                    return '';
                }
                if ($matcher->numberOfInvocations() === 9) {
                    self::assertSame('group_id', $parameters[0]);
                    return 102;
                }
            });
        $request->expects($this->atLeastOnce())->method('getCurrentUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $request->expects($this->atLeastOnce())->method('existAndNonEmpty')->willReturn(false);
        $request->expects($this->exactly(2))->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());

        $this->description_fields_factory->method('getAllDescriptionFields')->willReturn([]);

        $this->current_project->method('getProjectsDescFieldsValue')->willReturn([]);

        $this->project_details_dao->expects($this->once())->method('updateGroupNameAndDescription');
        $this->project_history_dao->expects($this->once())->method('groupAddHistory');
        $this->event_manager->expects($this->once())->method('processEvent');
        $this->project_visibility_configuration->method('canUserConfigureProjectVisibility')->willReturn(false);
        $this->project_visibility_configuration->method('canUserConfigureTruncatedMail')->willReturn(false);

        $this->controller->update($request);

        self::assertEquals([['level' => Feedback::INFO, 'message' => 'Update successful']], $this->global_response->inspector->getFeedback());
    }
}
