<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\TuleapSynchro\ListEndpoints;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class ListEndpointsController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_rendered;

    /**
     * @var ListEndpointsRetriever
     */
    private $list_endpoint_retriever;
    /**
     * @var ListEndpointsPresenterBuilder
     */
    private $list_endpoints_presenter_builder;

    public function __construct(AdminPageRenderer $admin_page_rendered, ListEndpointsRetriever $list_endpoint_retriever, ListEndpointsPresenterBuilder $list_endpoints_presenter_builder)
    {
        $this->admin_page_rendered     = $admin_page_rendered;
        $this->list_endpoint_retriever = $list_endpoint_retriever;
        $this->list_endpoints_presenter_builder = $list_endpoints_presenter_builder;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param array $variables
     * @return void
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $include_assets = new IncludeAssets(__DIR__ . '/../../../../../src/www/assets/tuleap_synchro', '/assets/tuleap_synchro');
        $layout->includeFooterJavascriptFile(
            $include_assets->getFileURL('tuleap_synchro.js')
        );

        $layout->addCssAsset(
            new CssAsset(
                $include_assets,
                'tuleap-synchro'
            )
        );

        $list_endpoints_presenter = $this->list_endpoints_presenter_builder->build($this->list_endpoint_retriever->getAllEndpoints());

        $this->admin_page_rendered->renderAPresenter(
            'tuleap_synchro',
            __DIR__ . '/../../../templates',
            'list_endpoints',
            $list_endpoints_presenter
        );
    }
}
