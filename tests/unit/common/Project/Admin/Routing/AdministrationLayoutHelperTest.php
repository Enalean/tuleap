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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\Admin\Navigation\FooterDisplayer;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class AdministrationLayoutHelperTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AdministrationLayoutHelper $helper;
    private ProjectRetriever&MockObject $project_retriever;
    private ProjectAdministratorChecker&MockObject $administrator_checker;
    private HeaderNavigationDisplayer&MockObject $header_displayer;
    private FooterDisplayer&MockObject $footer_displayer;

    protected function setUp(): void
    {
        $this->project_retriever     = $this->createMock(ProjectRetriever::class);
        $this->administrator_checker = $this->createMock(ProjectAdministratorChecker::class);
        $this->header_displayer      = $this->createMock(HeaderNavigationDisplayer::class);
        $this->footer_displayer      = $this->createMock(FooterDisplayer::class);
        $this->helper                = new AdministrationLayoutHelper(
            $this->project_retriever,
            $this->administrator_checker,
            $this->header_displayer,
            $this->footer_displayer
        );
    }

    public function testItCallsCallbackWithProjectAndCurrentUser(): void
    {
        $request                = $this->createMock(\HTTPRequest::class);
        $project_id             = '101';
        $page_title             = 'Project Administration';
        $current_pane_shortname = 'details';

        $current_user = UserTestBuilder::buildWithDefaults();
        $request
            ->expects(self::once())
            ->method('getCurrentUser')
            ->willReturn($current_user);

        $project = ProjectTestBuilder::aProject()->build();
        $this->project_retriever
            ->expects(self::once())
            ->method('getProjectFromId')
            ->with($project_id)
            ->willReturn($project);
        $this->administrator_checker
            ->expects(self::once())
            ->method('checkUserIsProjectAdministrator')
            ->with($current_user, $project);
        $this->header_displayer
            ->expects(self::once())
            ->method('displayBurningParrotNavigation')
            ->with($page_title, $project, $current_pane_shortname);
        $this->footer_displayer->method('display');

        $callback = function (\Project $param_project, \PFUser $param_current_user) use ($project, $current_user): void {
            self::assertSame($project, $param_project);
            self::assertSame($current_user, $param_current_user);
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
