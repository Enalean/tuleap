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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Password\Configuration\PasswordConfiguration;
use Tuleap\Password\Configuration\PasswordConfigurationRetriever;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;

final class PasswordPolicyDisplayControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TemporaryTestDirectory;

    /**
     * @var PasswordPolicyDisplayController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AdminPageRenderer
     */
    private $admin_renderer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|PasswordConfigurationRetriever
     */
    private $configuration_retriever;

    protected function setUp(): void
    {
        $this->admin_renderer          = M::mock(AdminPageRenderer::class);
        $this->configuration_retriever = M::mock(PasswordConfigurationRetriever::class);
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
        $site_admin = M::mock(\PFUser::class)->shouldReceive('isSuperUser')
            ->andReturnTrue()
            ->getMock();
        $this->admin_renderer->shouldReceive('header')->once();
        $this->admin_renderer->shouldReceive('footer')->once();
        $this->configuration_retriever->shouldReceive('getPasswordConfiguration')
            ->once()
            ->andReturn(new PasswordConfiguration(true));
        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($site_admin)->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        $this->assertStringContainsString('Password policy', $output);
        $this->assertStringContainsString('name="block-breached-password"', $output);
    }
}
