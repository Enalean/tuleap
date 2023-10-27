<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget;

use TemplateRendererFactory;
use Tuleap\Layout\CssAssetCollection;
use Tuleap\Layout\CssAssetWithoutVariantDeclinaisons;
use Tuleap\Layout\IncludeAssets;
use Widget;

class UserWidget extends Widget
{
    public const NAME = 'timetracking';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function getTitle()
    {
        return dgettext('tuleap-timetracking', 'Personal time tracking');
    }

    public function getDescription()
    {
        return dgettext('tuleap-timetracking', 'Displays your time tracking of the whole platform.');
    }

    public function isUnique()
    {
        return true;
    }

    public function hasPreferences($widget_id)
    {
        return false;
    }

    public function getCategory()
    {
        return dgettext('tuleap-timetracking', 'Time tracking');
    }

    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR);

        return $renderer->renderToString('widget', []);
    }

    public function getIcon()
    {
        return "fa-clock-o";
    }

    public function getJavascriptDependencies(): array
    {
        return [
            ['file' => $this->getAssets()->getFileURL('widget-timetracking.js')],
        ];
    }

    public function getStylesheetDependencies(): CssAssetCollection
    {
        return new CssAssetCollection([new CssAssetWithoutVariantDeclinaisons($this->getAssets(), 'style-bp-personal')]);
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../scripts/personal-timetracking-widget/frontend-assets',
            '/assets/timetracking/personal-timetracking-widget'
        );
    }
}
