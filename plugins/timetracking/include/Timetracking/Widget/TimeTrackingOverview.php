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
 */

namespace Tuleap\Timetracking\Widget;

use TemplateRendererFactory;
use Tuleap\Layout\IncludeAssets;
use Widget;

class TimeTrackingOverview extends Widget
{
    const NAME = 'timetracking-overview';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function getTitle()
    {
        return dgettext('tuleap-timetracking', 'Timetracking overview');
    }

    public function getDescription()
    {
        return dgettext('tuleap-timetracking', 'Displays time spent on multiple trackers');
    }

    public function getCategory()
    {
        return 'plugin_timetracking';
    }

    public function isUnique()
    {
        return false;
    }

    public function getContent()
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR);

        return $renderer->renderToString('timetracking-overview', []);
    }

    public function getJavascriptDependencies()
    {
        $include_assets = new IncludeAssets(
            __DIR__ . '/../../../www/assets',
            TIMETRACKING_BASE_URL . '/assets'
        );
        return [
            ['file' => $include_assets->getFileURL('timetracking-overview.js')]
        ];
    }
}
