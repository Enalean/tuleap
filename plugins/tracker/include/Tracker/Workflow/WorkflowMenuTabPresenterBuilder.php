<?php
/**
* Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
namespace Tuleap\Tracker\Workflow;

use Tracker;
use Tuleap\Tracker\Webhook\Actions\AdminWebhooks;
use Workflow;

class WorkflowMenuTabPresenterBuilder
{
    public const TAB_RULES                  = 'rules';
    public const TAB_TRANSITIONS            = 'transitions';
    public const TAB_CROSS_TRACKER_TRIGGERS = 'triggers';
    public const TAB_WEBHOOKS               = 'webhooks';

    public function build(Tracker $tracker, $active_tab_name, array $used_services, bool $is_split_feature_flag_enabled)
    {
        $tabs_menu                                = $this->buildTabsMenu($tracker);
        $tabs_menu[$active_tab_name]['is_active'] = true;
        return new WorkflowMenuTabPresenter(array_values($tabs_menu), $tracker->getId(), $used_services, $is_split_feature_flag_enabled);
    }

    private function buildTabsMenu(Tracker $tracker)
    {
        return [
            self::TAB_RULES => [
                'url'       => $this->buildLegacyUrl(Workflow::FUNC_ADMIN_RULES, $tracker),
                'title'     => dgettext('tuleap-tracker', 'Global Rules'),
                'is_active' => false,
            ],
            self::TAB_TRANSITIONS => [
                'url'       => $this->buildFrontRouterUrl(Workflow::TRANSITION_PATH, $tracker),
                'title'     => dgettext('tuleap-tracker', 'Transitions Rules'),
                'is_active' => false,
            ],
            self::TAB_CROSS_TRACKER_TRIGGERS => [
                'url'       => $this->buildLegacyUrl(Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS, $tracker),
                'title'     => dgettext('tuleap-tracker', 'Triggers'),
                'is_active' => false,
            ],
            self::TAB_WEBHOOKS => [
                'url'       => $this->buildLegacyUrl(AdminWebhooks::FUNC_ADMIN_WEBHOOKS, $tracker),
                'title'     => dgettext('tuleap-tracker', 'Webhooks'),
                'is_active' => false,
            ],
        ];
    }

    private function buildLegacyUrl($action, Tracker $tracker)
    {
        return TRACKER_BASE_URL . '/?' . http_build_query([
            'tracker' => (int) $tracker->getId(),
            'func'    => $action,
        ]);
    }

    private function buildFrontRouterUrl($tracker_path, Tracker $tracker)
    {
        return TRACKER_BASE_URL . Workflow::BASE_PATH . '/' . urlencode($tracker->getId()) . $tracker_path;
    }
}
