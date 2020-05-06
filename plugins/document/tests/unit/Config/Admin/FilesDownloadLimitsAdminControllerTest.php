<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
use PHPUnit\Framework\TestCase;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabsPresenterCollection;
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabsPresenterCollectionBuilder;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;

class FilesDownloadLimitsAdminControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var FilesDownloadLimitsAdminController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|DocmanSettingsTabsPresenterCollectionBuilder
     */
    private $tabs_collection_builder;

    protected function setUp(): void
    {
        $this->admin_page_renderer     = Mockery::mock(AdminPageRenderer::class);
        $this->tabs_collection_builder = Mockery::mock(DocmanSettingsTabsPresenterCollectionBuilder::class);

        $this->controller = new FilesDownloadLimitsAdminController(
            $this->admin_page_renderer,
            new FileDownloadLimitsBuilder(),
            Mockery::mock(\CSRFSynchronizerToken::class),
            $this->tabs_collection_builder
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

        $tabs_collection = Mockery::mock(DocmanSettingsTabsPresenterCollection::class);
        $tabs_collection
            ->shouldReceive('getTabs')
            ->andReturn([]);

        $this->tabs_collection_builder
            ->shouldReceive('build')
            ->once()
            ->andReturn($tabs_collection);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
