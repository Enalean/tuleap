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

namespace Tuleap\OpenIDConnectClient;

use ForgeConfig;
use HTTPRequest;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\OpenIDConnectClient\Login\ConnectorPresenterBuilder;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LoginController implements DispatchableWithRequestNoAuthz
{
    /**
     * @var ConnectorPresenterBuilder
     */
    private $connector_presenter_builder;

    public function __construct(ConnectorPresenterBuilder $connector_presenter_builder)
    {
        $this->connector_presenter_builder = $connector_presenter_builder;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array       $variables
     * @return void
     * @throws ForbiddenException
     * @throws NotFoundException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $return_to = $request->get('return_to');
        $presenter = $this->connector_presenter_builder->getLoginSpecificPageConnectorPresenter($return_to);

        $title = sprintf(_('%1$s login'), ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME));
        $layout->header(
            HeaderConfigurationBuilder::get($title)
                ->withBodyClass(['login-page'])
                ->build()
        );
        $renderer = TemplateRendererFactory::build()->getRenderer(OPENIDCONNECTCLIENT_TEMPLATE_DIR);
        $renderer->renderToPage('login-page', $presenter);
        $layout->footer(FooterConfiguration::withoutContent());
    }
}
