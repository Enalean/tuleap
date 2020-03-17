<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\ProjectAdmin;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\App\OAuth2AppRemover;
use Tuleap\Project\Admin\Routing\ProjectAdministratorChecker;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DeleteAppControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var DeleteAppController */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectAdministratorChecker
     */
    private $administrator_checker;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|OAuth2AppRemover
     */
    private $app_remover;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->project_retriever     = M::mock(ProjectRetriever::class);
        $this->administrator_checker = M::mock(ProjectAdministratorChecker::class);
        $this->app_remover           = M::mock(OAuth2AppRemover::class);
        $this->csrf_token            = M::mock(\CSRFSynchronizerToken::class);
        $this->controller            = new DeleteAppController(
            $this->project_retriever,
            $this->administrator_checker,
            $this->app_remover,
            $this->csrf_token
        );
    }

    public function testProcessRedirectsWithErrorWhenAppIdIsOmitted(): void
    {
        $current_user = UserTestBuilder::aUser()->build();
        $request      = HTTPRequestBuilder::get()->withUser($current_user)->withParam('app_id', '')->build();
        $layout       = M::mock(BaseLayout::class);
        $this->mockValidProjectAndUserIsProjecTAdmin($current_user);

        $layout->shouldReceive('addFeedback')
            ->once()
            ->with(\Feedback::ERROR, M::type('string'));
        $layout->shouldReceive('redirect')
            ->once()
            ->with('/plugins/oauth2_server/project/102/admin');
        $this->app_remover->shouldNotReceive('deleteAppByID');

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }

    public function testProcessDeletesAppAndRedirects(): void
    {
        $current_user = UserTestBuilder::aUser()->build();
        $request      = HTTPRequestBuilder::get()->withUser($current_user)->withParam('app_id', '12')->build();
        $layout       = M::mock(BaseLayout::class);
        $this->mockValidProjectAndUserIsProjecTAdmin($current_user);

        $layout->shouldReceive('addFeedback')
            ->once()
            ->with(\Feedback::INFO, M::type('string'));
        $layout->shouldReceive('redirect')
            ->once()
            ->with('/plugins/oauth2_server/project/102/admin');
        $this->app_remover->shouldReceive('deleteAppByID')
            ->once()
            ->with(12);

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }

    public function testGetUrl(): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->once()
            ->andReturn(102)
            ->getMock();

        $this->assertSame('/plugins/oauth2_server/project/102/admin/delete-app', DeleteAppController::getUrl($project));
    }

    private function mockValidProjectAndUserIsProjectAdmin(\PFUser $current_user): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->once()
            ->andReturn(102)
            ->getMock();
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->once()
            ->andReturn($project);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->with($current_user, $project)
            ->once();
        $this->csrf_token->shouldReceive('check')->once();
    }
}
