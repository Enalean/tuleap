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
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Layout\JavascriptViteAsset;
use Widget;

class UserWidget extends Widget
{
    public const string NAME = 'timetracking';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    #[\Override]
    public function getTitle()
    {
        return dgettext('tuleap-timetracking', 'Personal time tracking');
    }

    #[\Override]
    public function getDescription()
    {
        return dgettext('tuleap-timetracking', 'Displays your time tracking of the whole platform.');
    }

    #[\Override]
    public function isUnique()
    {
        return true;
    }

    #[\Override]
    public function hasPreferences($widget_id)
    {
        return false;
    }

    #[\Override]
    public function getCategory()
    {
        return dgettext('tuleap-timetracking', 'Time tracking');
    }

    #[\Override]
    public function getContent(): string
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR);

        return $renderer->renderToString('widget', ['dashboard_id' => $this->dashboard_id]);
    }

    #[\Override]
    public function getIcon()
    {
        return 'fa-clock-o';
    }

    /**
     * @return JavascriptAssetGeneric[]
     */
    #[\Override]
    public function getJavascriptAssets(): array
    {
        return [
            new JavascriptViteAsset(
                new IncludeViteAssets(
                    __DIR__ . '/../../../scripts/personal-timetracking-widget/frontend-assets',
                    '/assets/timetracking/personal-timetracking-widget'
                ),
                'src/index.ts'
            ),
        ];
    }
}
