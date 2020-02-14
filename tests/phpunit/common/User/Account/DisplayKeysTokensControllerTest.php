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
 *
 */

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\ForbiddenException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class DisplayKeysTokensControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration, TemporaryTestDirectory;

    /**
     * @var DisplayKeysTokensController
     */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|AccessKeyPresenterBuilder
     */
    private $access_keys_presenter_builder;

    public function setUp(): void
    {
        $csrf_token = M::mock(CSRFSynchronizerToken::class);
        $this->access_keys_presenter_builder = M::mock(AccessKeyPresenterBuilder::class);

        $this->controller = new DisplayKeysTokensController(
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            $csrf_token,
            $this->access_keys_presenter_builder,
        );

        $_SESSION = array();
    }

    protected function tearDown(): void
    {
        unset($_SESSION);
    }

    public function testItThrowExceptionForAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePage(): void
    {
        $this->access_keys_presenter_builder->shouldReceive('getForUser')->andReturn(new AccessKeyPresenter([], [], null, ''));

        ob_start();
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::aUser()->withId(110)->build())->build(),
            LayoutBuilder::build(),
            []
        );
        $output = ob_get_clean();
        $this->assertStringContainsString('Personal access keys', $output);
    }
}
