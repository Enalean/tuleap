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
use PHPUnit\Framework\MockObject\Stub;
use Project;
use ServiceFile;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LicenseAgreementControllersHelperTest extends TestCase
{
    private ServiceFile&Stub $service_file;
    private Project&Stub $project;
    private TemplateRendererFactory&Stub $renderer_factory;
    private FRSPermissionManager&Stub $permissions_manager;
    private PFUser $current_user;
    private LicenseAgreementControllersHelper $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->service_file = $this->createStub(\ServiceFile::class);
        $this->service_file->method('displayFRSHeader');

        $this->project = $this->createConfiguredStub(Project::class, ['isError' => false, 'getID' => '101']);

        $this->renderer_factory = $this->createStub(TemplateRendererFactory::class);

        $this->permissions_manager = $this->createStub(FRSPermissionManager::class);

        $this->helper = new LicenseAgreementControllersHelper($this->permissions_manager, $this->renderer_factory);
    }

    public function testItThrowsAndExceptionWhenServiceIsNotAvailable(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->current_user)->willReturn(true);
        $this->project->method('getService')->with(\Service::FILE)->willReturn(null);

        $this->expectException(NotFoundException::class);

        $this->helper->assertCanAccess($this->project, $this->current_user);
    }

    public function testItThrowsAnExceptionWhenUserIsNotFileAdministrator(): void
    {
        $this->permissions_manager->method('isAdmin')->with($this->project, $this->current_user)->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->helper->assertCanAccess($this->project, $this->current_user);
    }

    public function testItRendersFrsAdminHeader(): void
    {
        $this->project->method('getService')->with(\Service::FILE)->willReturn($this->service_file);
        $header_renderer = $this->createMock(TemplateRenderer::class);
        $header_renderer->expects($this->once())->method('renderToPage')->with('toolbar-presenter', self::anything());
        $this->renderer_factory->method('getRenderer')->with(self::callback(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/templates/frs');
        }))->willReturn($header_renderer);

        $this->helper->renderHeader($this->project);
    }
}
