<?php
/**
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

namespace Tuleap\PullRequest\DefaultSettings;

use GitPlugin;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectHistoryDao;
use Tuleap\Git\Tests\Stub\VerifyUserIsGitAdministratorStub;
use Tuleap\GlobalResponseMock;
use Tuleap\PullRequest\MergeSetting\MergeSettingDAO;
use Tuleap\PullRequest\Tests\Stub\ProvideCSRFTokenSynchronizerStub;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DefaultSettingsControllerTest extends TestCase
{
    use GlobalResponseMock;

    private VerifyUserIsGitAdministratorStub $admin_verifier;
    private MockObject&MergeSettingDAO $merge_setting_dao;
    private ProjectHistoryDao&MockObject $project_history_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->admin_verifier = VerifyUserIsGitAdministratorStub::withAlwaysGitAdministrator();

        $this->merge_setting_dao   = $this->createMock(MergeSettingDAO::class);
        $this->project_history_dao = $this->createMock(ProjectHistoryDao::class);
    }

    private function process(HTTPRequest $request): void
    {
        (new DefaultSettingsController(
            $this->merge_setting_dao,
            $this->project_history_dao,
            $this->admin_verifier,
            ProvideCSRFTokenSynchronizerStub::build()
        ))->process($request, LayoutBuilder::buildWithInspector(new LayoutInspector()), []);
    }

    public function testItThrowsNotFoundExceptionIfThePluginIsNotUsed(): void
    {
        $request = HTTPRequestBuilder::get()->withProject(
            ProjectTestBuilder::aProject()->withoutServices()->build()
        )->build();
        $this->expectException(NotFoundException::class);
        $this->process($request);
    }

    public function testItThrowsForbiddenExceptionIfTheUserIsNotProjectAdministratorOrGitAdministrator(): void
    {
        $project = ProjectTestBuilder::aProject()->withUsedService(GitPlugin::SERVICE_SHORTNAME)->withId(
            115
        )->build();

        $current_user = UserTestBuilder::aUser()->withUsername('notAdmin')->withMemberOf(
            $project
        )->withoutSiteAdministrator()->build();

        $this->admin_verifier = VerifyUserIsGitAdministratorStub::withNeverGitAdministrator();
        $request              = HTTPRequestBuilder::get()->withProject($project)->withUser($current_user)->build();
        $this->expectException(ForbiddenException::class);
        $this->process($request);
    }

    public function testItSaveTheNewMergeDefaultConfiguration(): void
    {
        $project              = ProjectTestBuilder::aProject()->withUsedService(GitPlugin::SERVICE_SHORTNAME)->withId(
            115
        )->build();
        $this->admin_verifier = VerifyUserIsGitAdministratorStub::withAlwaysGitAdministrator();

        $current_user = UserTestBuilder::aUser()->withUsername('notAdmin')->withMemberOf(
            $project
        )->withoutSiteAdministrator()->build();

        $request = HTTPRequestBuilder::get()->withProject($project)->withUser($current_user)->withParam(
            'is_merge_commit_allowed',
            '1'
        )->build();

        $this->merge_setting_dao->expects($this->once())->method('saveDefaultSettings');
        $this->project_history_dao->expects($this->once())->method('addHistory');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->expectExceptionMessage('/plugins/git/?action=admin-default-settings&group_id=115&pane=pullrequest');

        $this->process($request);
    }
}
