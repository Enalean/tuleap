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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\LicenseAgreement\Admin;

use Mockery;
use PHPUnit\Framework\TestCase;
use TemplateRendererFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Templating\Mustache\MustacheEngine;

class LicenseAgreementControllersHelperTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ServiceFile
     */
    private $service_file;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRendererFactory
     */
    private $renderer_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|FRSPermissionManager
     */
    private $permissions_manager;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var LicenseAgreementControllersHelper
     */
    private $helper;

    protected function setUp(): void
    {
        $this->current_user = new \PFUser(['language_id' => 'en_US']);

        $this->service_file = Mockery::mock(\ServiceFile::class, ['displayFRSHeader' => 'foo']);
        $this->project = Mockery::mock(\Project::class, ['isError' => false, 'getID' => '101']);
        $this->project->shouldReceive('getFileService')->andReturn($this->service_file)->byDefault();

        $this->renderer_factory = Mockery::mock(TemplateRendererFactory::class);

        $this->permissions_manager = Mockery::mock(FRSPermissionManager::class);
        $this->permissions_manager->shouldReceive('isAdmin')->with($this->project, $this->current_user)->andReturnTrue()->byDefault();

        $this->helper = new LicenseAgreementControllersHelper($this->permissions_manager, $this->renderer_factory);
    }

    public function testItThrowsAndExceptionWhenServiceIsNotAvailable(): void
    {
        $this->project->shouldReceive('getFileService')->andReturnNull();

        $this->expectException(NotFoundException::class);

        $this->helper->assertCanAccess($this->project, $this->current_user);
    }

    public function testItThrowsAnExceptionWhenUserIsNotFileAdministrator(): void
    {
        $this->permissions_manager->shouldReceive('isAdmin')->with($this->project, $this->current_user)->andReturnFalse();

        $this->expectException(ForbiddenException::class);

        $this->helper->assertCanAccess($this->project, $this->current_user);
    }

    public function testItRendersFrsAdminHeader(): void
    {
        $header_renderer = Mockery::mock(MustacheEngine::class);
        $header_renderer->shouldReceive('renderToPage')->with('toolbar-presenter', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/templates/frs');
        }))->andReturn($header_renderer);

        $this->helper->renderHeader($this->project);
    }
}
