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
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;

final class DisplayKeysTokensControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DisplayKeysTokensController
     */
    private $controller;
    private $request;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|BaseLayout
     */
    private $layout;
    private $access_keys_presenter_builder;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\TemplateRenderer
     */
    private $renderer;

    public function setUp(): void
    {
        $this->renderer = M::mock(\TemplateRenderer::class);
        $renderer_factory = M::mock(
            TemplateRendererFactory::class,
            ['getRenderer' => $this->renderer]
        );
        $csrf_token = M::mock(CSRFSynchronizerToken::class);
        $this->access_keys_presenter_builder = M::mock(AccessKeyPresenterBuilder::class);

        $this->request = new \HTTPRequest();
        $this->layout = M::mock(BaseLayout::class);

        $this->controller = new DisplayKeysTokensController(
            $renderer_factory,
            $csrf_token,
            $this->access_keys_presenter_builder,
        );

        $_SESSION = array();
    }

    protected function tearDown(): void
    {
        unset($_SESSION);
    }

    public function testItThrowExceptionForAnonymous()
    {
        $this->expectException(ForbiddenException::class);
        $this->request->setCurrentUser(new \PFUser(['user_id' => 0, 'language_id' => 'en_US']));
        $this->controller->process($this->request, $this->layout, []);
    }

    public function testItRendersThePage()
    {
        $this->layout->shouldReceive('includeFooterJavascriptFile');
        $this->layout->shouldReceive('header');
        $this->layout->shouldReceive('footer');
        $this->layout->shouldReceive('addCssAsset');
        $this->access_keys_presenter_builder->shouldReceive('getForUser')->andReturn(new AccessKeyPresenter([], [], null, ''));
        $this->renderer->shouldReceive('renderToPage');

        $this->request->setCurrentUser(new \PFUser(['user_id' => 110, 'language_id' => 'en_US']));
        $this->controller->process($this->request, $this->layout, []);
    }
}
