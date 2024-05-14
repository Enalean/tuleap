<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Widget;

class TimetrackingManagementWidget extends Widget
{
    #[FeatureFlagConfigKey('Allow Timetracking Management widget. 0 to disallow, 1 to allow. By default they are disallowed. Guarded by allow_timetracking_management_widget feature flag.')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const FEATURE_FLAG = 'allow_timetracking_management_widget';

    public const NAME = 'timetracking-management-widget';

    public function __construct()
    {
        parent::__construct(self::NAME);
    }

    public function getTitle(): string
    {
        return dgettext('tuleap-timetracking', 'Timetracking management');
    }

    public function getDescription(): string
    {
        return dgettext('tuleap-timetracking', 'Displays aggregated time per user over a given period, with a view of time spent on each project.');
    }

    public function isUnique(): true
    {
        return true;
    }

    /**
     * @param string $widget_id
     */
    public function hasPreferences($widget_id): false
    {
        return false;
    }

    public function getCategory(): string
    {
        return dgettext('tuleap-timetracking', 'Time tracking');
    }

    public function getContent(): string
    {
        $renderer = TemplateRendererFactory::build()->getRenderer(TIMETRACKING_TEMPLATE_DIR);
        return $renderer->renderToString('timetracking-management', []);
    }

    public function getIcon(): string
    {
        return 'fa-clock-o';
    }
}
