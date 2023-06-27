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

namespace Tuleap\Password\Administration;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Password\Configuration\PasswordConfiguration;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class PasswordPolicyDisplayControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use TemporaryTestDirectory;

    private PasswordPolicyDisplayController $controller;
    private MockObject&AdminPageRenderer $admin_renderer;
    private MockObject&PasswordConfigurationRetriever $configuration_retriever;

    protected function setUp(): void
    {
        $this->admin_renderer          = $this->createMock(AdminPageRenderer::class);
        $this->configuration_retriever = $this->createMock(PasswordConfigurationRetriever::class);
        $this->controller              = new PasswordPolicyDisplayController(
            $this->admin_renderer,
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $this->configuration_retriever
        );
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testProcessRendersThePage(): void
    {
        $site_admin = UserTestBuilder::aUser()->withSiteAdministrator()->build();
        $this->admin_renderer->expects(self::once())->method('header');
        $this->admin_renderer->expects(self::once())->method('footer');
        $this->configuration_retriever
            ->expects(self::once())
            ->method('getPasswordConfiguration')
            ->willReturn(new PasswordConfiguration(true));
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($site_admin)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();

        self::assertStringContainsString('Password policy', $output);
        self::assertStringContainsString('name="block-breached-password"', $output);
    }
}
