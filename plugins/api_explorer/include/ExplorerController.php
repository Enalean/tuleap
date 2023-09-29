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
use Tuleap\Layout\HeaderConfigurationBuilder;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class ExplorerController implements DispatchableWithRequestNoAuthz, DispatchableWithBurningParrot
{
    /**
     * @var TemplateRenderer
     */
    private $renderer;

    public function __construct(TemplateRenderer $renderer, private IncludeViteAssets $assets)
    {
        $this->renderer = $renderer;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        \Tuleap\Project\ServiceInstrumentation::increment(\api_explorerPlugin::SERVICE_NAME_INSTRUMENTATION);

        $layout->addJavascriptAsset(new JavascriptViteAsset($this->assets, 'scripts/index.tsx'));

        $layout->header(
            HeaderConfigurationBuilder::get(dgettext('tuleap-api_explorer', 'API Explorer'))
                ->withMainClass(['tlp-framed'])
                ->build()
        );
        $this->renderer->renderToPage('explorer', []);
        $layout->footer([]);
    }
}
