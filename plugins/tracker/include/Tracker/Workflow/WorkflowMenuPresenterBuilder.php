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

final class WorkflowMenuPresenterBuilder
{
    public function build(Tracker $tracker): WorkflowMenuPresenter
    {
        $menu = [
            new WorkflowMenuItem(
                $this->buildFrontRouterUrl($tracker),
                dgettext('tuleap-tracker', 'Transitions rules'),
                'transitions',
            ),
            new WorkflowMenuItem(
                $this->buildLegacyUrl(Workflow::FUNC_ADMIN_RULES, $tracker),
                dgettext('tuleap-tracker', 'Global rules'),
                'global-rules',
            ),
            new WorkflowMenuItem(
                $this->buildLegacyUrl(Workflow::FUNC_ADMIN_DEPENDENCIES, $tracker),
                dgettext('tuleap-tracker', 'Field dependencies'),
                'field-dependencies',
            ),
            new WorkflowMenuItem(
                $this->buildLegacyUrl(Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS, $tracker),
                dgettext('tuleap-tracker', 'Triggers'),
                'triggers',
            ),
            new WorkflowMenuItem(
                $this->buildLegacyUrl(AdminWebhooks::FUNC_ADMIN_WEBHOOKS, $tracker),
                dgettext('tuleap-tracker', 'Webhooks'),
                'webhooks',
            ),
        ];

        return new WorkflowMenuPresenter($menu);
    }

    private function buildLegacyUrl(string $action, Tracker $tracker): string
    {
        return TRACKER_BASE_URL . '/?' . http_build_query([
            'tracker' => $tracker->getId(),
            'func'    => $action,
        ]);
    }

    private function buildFrontRouterUrl(Tracker $tracker): string
    {
        return TRACKER_BASE_URL . Workflow::BASE_PATH . '/' . urlencode((string) $tracker->getId()) . Workflow::TRANSITION_PATH;
    }
}
