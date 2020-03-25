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
 */

declare(strict_types=1);

namespace Tuleap\TestManagement;

use Feedback;
use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAsset;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequest;

final class LegacyRoutingController implements DispatchableWithRequest, DispatchableWithBurningParrot
{
    /**
     * @var Router
     */
    private $legacy_router;
    /**
     * @var IncludeAssets
     */
    private $testmanagement_assets;
    /**
     * @var IncludeAssets
     */
    private $include_core_js_assets;

    public function __construct(
        Router $legacy_router,
        IncludeAssets $testmanagement_assets,
        IncludeAssets $include_core_js_assets
    ) {
        $this->legacy_router          = $legacy_router;
        $this->include_core_js_assets = $include_core_js_assets;
        $this->testmanagement_assets  = $testmanagement_assets;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $layout->includeFooterJavascriptFile($this->include_core_js_assets->getFileURL('ckeditor.js'));
        $layout->includeFooterJavascriptFile($this->testmanagement_assets->getFileURL('testmanagement.js'));
        $layout->addCssAsset(new CssAsset($this->testmanagement_assets, 'burningparrot'));

        try {
            $this->legacy_router->route($request);
        } catch (UserIsNotAdministratorException $e) {
            $layout->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-testmanagement', 'Permission denied')
            );
            $this->legacy_router->renderIndex($request);
        }
    }
}
