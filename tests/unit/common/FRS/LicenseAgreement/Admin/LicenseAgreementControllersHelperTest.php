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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ServiceFile;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\PHPUnit\TestCase;

class LicenseAgreementControllersHelperTest extends TestCase
{
    /**
     * @var MockObject&ServiceFile
     */
    private $service_file;
    /**
     * @var MockObject&Project
     */
    private $project;
    /**
     * @var MockObject&TemplateRendererFactory
     */
    private $renderer_factory;
    /**
     * @var MockObject&FRSPermissionManager
     */
    private $permissions_manager;
    private PFUser $current_user;
    private LicenseAgreementControllersHelper $helper;

    protected function setUp(): void
    {
        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->service_file = $this->createConfiguredMock(ServiceFile::class, ['displayFRSHeader' => 'foo']);
        $this->project      = $this->createConfiguredMock(Project::class, ['isError' => false, 'getID' => '101']);

        $this->renderer_factory = $this->createMock(TemplateRendererFactory::class);

        $this->permissions_manager = $this->createMock(FRSPermissionManager::class);

        $this->helper = new LicenseAgreementControllersHelper($this->permissions_manager, $this->renderer_factory);
    }

    public function testItThrowsAndExceptionWhenServiceIsNotAvailable(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->current_user)->willReturn(true);
        $this->project->method('getFileService')->willReturn(null);

        self::expectException(NotFoundException::class);

        $this->helper->assertCanAccess($this->project, $this->current_user);
    }

    public function testItThrowsAnExceptionWhenUserIsNotFileAdministrator(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->current_user)->willReturn(false);

        self::expectException(ForbiddenException::class);

        $this->helper->assertCanAccess($this->project, $this->current_user);
    }

    public function testItRendersFrsAdminHeader(): void
    {
        $this->project->method('getFileService')->willReturn($this->service_file);
        $header_renderer = $this->createMock(TemplateRenderer::class);
        $header_renderer->expects(self::once())->method('renderToPage')->with('toolbar-presenter', self::anything());
        $this->renderer_factory->method('getRenderer')->with(self::callback(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/templates/frs');
        }))->willReturn($header_renderer);

        $this->helper->renderHeader($this->project);
    }
}
