<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

namespace Tuleap\Document\Config\Admin;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Document\Config\HistoryEnforcementSettingsBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;

final class HistoryEnforcementAdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HistoryEnforcementAdminController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AdminPageRenderer
     */
    private $admin_page_renderer;

    protected function setUp(): void
    {
        $this->admin_page_renderer = Mockery::mock(AdminPageRenderer::class);

        $this->controller = new HistoryEnforcementAdminController(
            $this->admin_page_renderer,
            new HistoryEnforcementSettingsBuilder(),
            Mockery::mock(\CSRFSynchronizerToken::class),
        );
    }

    public function testItThrowExceptionForNonSiteAdminUser(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }


    public function testItRendersThePage(): void
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);

        $this->admin_page_renderer
            ->shouldReceive('renderANoFramedPresenter')
            ->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
