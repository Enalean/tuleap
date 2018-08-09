<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\TuleapSynchro;

use HTTPRequest;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class ListTlpEndPointsController implements DispatchableWithRequest
{
    /**
     * @var AdminPageRenderer
     */
    private $admin_page_rendered;

    public function __construct(AdminPageRenderer $admin_page_rendered)
    {
        $this->admin_page_rendered = $admin_page_rendered;
    }

    /**
     * Is able to process a request routed by FrontRouter
     *
     * @param HTTPRequest $request
     * @param BaseLayout $layout
     * @param array $variables
     * @return void
     * @throws ForbiddenException
     */
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->getCurrentUser()->isSuperUser()) {
            throw new ForbiddenException();
        }

        $include_assets = new IncludeAssets(__DIR__.'/../../../src/www/assets/tuleap_synchro/scripts', '/assets/tuleap_synchro/scripts');
        $layout->includeFooterJavascriptFile(
            $include_assets->getFileURL('tuleap_synchro.js')
        );

        $this->admin_page_rendered->renderAPresenter(
            'tuleap_synchro',
            __DIR__.'/../templates',
            'list_endpoints',
            []
        );
    }
}
