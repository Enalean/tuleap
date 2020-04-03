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

namespace Tuleap\Project\Admin\Routing;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\Admin\Navigation\FooterDisplayer;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\ProjectRetriever;

final class AdministrationLayoutHelperTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AdministrationLayoutHelper */
    private $helper;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever */
    private $project_retriever;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectAdministratorChecker */
    private $administrator_checker;
    /** @var M\LegacyMockInterface|M\MockInterface|HeaderNavigationDisplayer */
    private $header_displayer;
    /** @var M\LegacyMockInterface|M\MockInterface|FooterDisplayer */
    private $footer_displayer;

    protected function setUp(): void
    {
        $this->project_retriever     = M::mock(ProjectRetriever::class);
        $this->administrator_checker = M::mock(ProjectAdministratorChecker::class);
        $this->header_displayer      = M::mock(HeaderNavigationDisplayer::class);
        $this->footer_displayer      = M::mock(FooterDisplayer::class);
        $this->helper                = new AdministrationLayoutHelper(
            $this->project_retriever,
            $this->administrator_checker,
            $this->header_displayer,
            $this->footer_displayer
        );
    }

    public function testItCallsCallbackWithProjectAndCurrentUser(): void
    {
        $request                = M::mock(\HTTPRequest::class);
        $project_id             = '101';
        $page_title             = 'Project Administration';
        $current_pane_shortname = 'details';

        $current_user = M::mock(\PFUser::class);
        $request->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user);

        $project = M::mock(\Project::class);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->once()
            ->with($project_id)
            ->andReturn($project);
        $this->administrator_checker->shouldReceive('checkUserIsProjectAdministrator')
            ->once()
            ->with($current_user, $project);
        $this->header_displayer->shouldReceive('displayBurningParrotNavigation')
            ->once()
            ->with($page_title, $project, $current_pane_shortname);
        $this->footer_displayer->shouldReceive('display');

        $callback = function (\Project $param_project, \PFUser $param_current_user) use ($project, $current_user): void {
            $this->assertSame($project, $param_project);
            $this->assertSame($current_user, $param_current_user);
        };

        $this->helper->renderInProjectAdministrationLayout(
            $request,
            $project_id,
            $page_title,
            $current_pane_shortname,
            $callback
        );
    }
}
