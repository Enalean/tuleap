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

declare(strict_types=1);

namespace Tuleap\OAuth2Server\AuthorizationServer;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssViteAsset;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;

class AuthorizationFormRenderer
{
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;
    /**
     * @var \TemplateRenderer
     */
    private $renderer;
    /**
     * @var AuthorizationFormPresenterBuilder
     */
    private $presenter_builder;

    public function __construct(
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        TemplateRendererFactory $renderer_factory,
        AuthorizationFormPresenterBuilder $presenter_builder,
    ) {
        $this->response_factory  = $response_factory;
        $this->stream_factory    = $stream_factory;
        $this->renderer          = $renderer_factory->getRenderer(__DIR__ . '/../../templates');
        $this->presenter_builder = $presenter_builder;
    }

    public function renderForm(
        AuthorizationFormData $data,
        BaseLayout $layout,
    ): ResponseInterface {
        $presenter = $this->presenter_builder->build($data);
        $layout->addCssAsset(
            CssViteAsset::fromFileName(
                new IncludeViteAssets(__DIR__ . '/../../frontend-assets', '/assets/oauth2_server'),
                'themes/authorization-form.scss'
            )
        );

        ob_start();
        $layout->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-oauth2_server', 'Authorize application'))
                ->withMainClass(['tlp-framed'])
                ->build()
        );
        $this->renderer->renderToPage('authorization-form', $presenter);
        $layout->footer([]);

        return $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream((string) ob_get_clean())
        );
    }
}
