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

namespace Tuleap\Project\Banner;

use HTTPRequest;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use TemplateRendererFactory;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\ProjectRetriever;

final class BannerAdministrationControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, ForgeConfigSandbox;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HeaderNavigationDisplayer
     */
    private $header_navigation_displayer;
    /**
     * @var BannerAdministrationController
     */
    private $controller;

    protected function setUp() : void
    {
        $this->project_retriever           = Mockery::mock(ProjectRetriever::class);
        $this->header_navigation_displayer = Mockery::mock(HeaderNavigationDisplayer::class);
        $this->controller                  = new BannerAdministrationController(
            TemplateRendererFactory::build(),
            $this->header_navigation_displayer,
            Mockery::mock(IncludeAssets::class),
            $this->project_retriever,
            Mockery::mock(BannerRetriever::class)
        );
    }

    public function testNonProjectAdministratorCanNotAccessThePage() : void
    {
        $project = Mockery::mock(Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->once()
            ->with('102')
            ->andReturn($project);

        $request      = Mockery::mock(HTTPRequest::class);
        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive('isAdmin')->andReturn(false);
        $request->shouldReceive('getCurrentUser')->andReturn($current_user);

        $this->expectException(ForbiddenException::class);
        $this->controller->process($request, Mockery::mock(BaseLayout::class), ['id' => '102']);
    }
}
