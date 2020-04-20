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

namespace Tuleap\APIExplorer;

use HTTPRequest;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class ExplorerController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    /**
     * @var TemplateRenderer
     */
    private $renderer;
    /**
     * @var IncludeAssets
     */
    private $assets;

    public function __construct(TemplateRenderer $renderer, IncludeAssets $assets)
    {
        $this->renderer = $renderer;
        $this->assets   = $assets;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment(\api_explorerPlugin::SERVICE_NAME_INSTRUMENTATION);

        $layout->includeFooterJavascriptFile($this->assets->getFileURL('api-explorer.js'));
        $layout->addCssAsset(
            new CssAssetWithoutVariantDeclinaisons($this->assets, 'style-api-explorer')
        );

        $layout->header(['title' => dgettext('tuleap-api_explorer', 'API Explorer'), 'main_classes' => ['tlp-framed']]);
        $this->renderer->renderToPage('explorer', []);
        $layout->footer([]);
    }
}
