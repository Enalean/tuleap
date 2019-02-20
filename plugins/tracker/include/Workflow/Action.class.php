<?php
/**
 * Copyright (c) Enalean, 2012 - 2019. All Rights Reserved.
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

use Tuleap\Tracker\Webhook\Actions\AdminWebhooks;
use Tuleap\Tracker\Workflow\FeatureFlag;

/**
 * Base class to manage action that can be done on a workflow
 */

//phpcs:ignoreFile

abstract class Tracker_Workflow_Action
{
    const PANE_RULES                  = 'rules';
    const PANE_TRANSITIONS            = 'transitions';
    const PANE_CROSS_TRACKER_TRIGGERS = 'triggers';
    const PANE_WEBHOOKS               = 'webhooks';

    /** @var Tracker */
    protected $tracker;

    public function __construct(Tracker $tracker)
    {
        $this->tracker = $tracker;
    }

    protected function displayHeader(Tracker_IDisplayTrackerLayout $engine)
    {
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');

        echo '<div class="tabbable">';
        echo '<ul class="nav nav-tabs">';
        foreach ($this->buildPanesLinks() as $link) {
            echo $link;
        }
        echo '</ul>';
        echo '<div class="tab-content">';
    }

    private function buildPanesLinks(): array
    {
        return [
            $this->buildGlobalRulesLink(),
            $this->buildTransitionsLink(),
            $this->buildTriggersLink(),
            $this->buildWebhooksLink()
        ];
    }

    private function buildHTMLLink(string $identifier, string $link, string $title): string
    {
        $active_classname = '';
        if ($this->getPaneIdentifier() === $identifier) {
            $active_classname = 'active';
        }
        return '<li class="' . $active_classname . '"><a href="' . $link . '">' . $title . '</a></li>';
    }

    private function buildLinkWithFuncQuery(string $identifier, string $func, string $title): string
    {
        $link = TRACKER_BASE_URL . '/?' . http_build_query(['tracker' => (int) $this->tracker->id, 'func' => $func]);
        return $this->buildHTMLLink($identifier, $link, $title);
    }

    private function buildGlobalRulesLink(): string
    {
        return $this->buildLinkWithFuncQuery(
            self::PANE_RULES,
            Workflow::FUNC_ADMIN_RULES,
            $GLOBALS['Language']->getText('workflow_admin', 'tab_global_rules')
        );
    }

    private function buildLegacyTransitionsLink(): string
    {
        return $this->buildLinkWithFuncQuery(
            self::PANE_TRANSITIONS,
            Workflow::FUNC_ADMIN_TRANSITIONS,
            $GLOBALS['Language']->getText('workflow_admin', 'tab_transitions')
        );
    }

    private function buildNewTransitionsLink(): string
    {
        $link = TRACKER_BASE_URL . '/workflow/' . urlencode($this->tracker->id) . '/transitions';
        return $this->buildHTMLLink(
            self::PANE_TRANSITIONS,
            $link,
            $GLOBALS['Language']->getText('workflow_admin', 'tab_transitions')
        );
    }

    private function buildTransitionsLink(): string
    {
        if ($this->isNewWorkflowDisabled($this->tracker)) {
            return $this->buildLegacyTransitionsLink();
        }

        return $this->buildNewTransitionsLink();
    }

    private function isNewWorkflowDisabled(\Tracker $tracker): bool
    {
        $whitelist = ForgeConfig::get('sys_tracker_whitelist_that_should_use_legacy_workflow_transitions_interface');
        if ($whitelist === false || empty($whitelist)) {
            return false;
        }

        foreach (explode(',', $whitelist) as $whitelisted_tracker_id) {
            if ($tracker->id === trim($whitelisted_tracker_id)) {
                return true;
            }
        }

        return false;
    }

    private function buildTriggersLink()
    {
        return $this->buildLinkWithFuncQuery(
            self::PANE_CROSS_TRACKER_TRIGGERS,
            Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS,
            $GLOBALS['Language']->getText('workflow_admin', 'tab_triggers')
        );
    }

    private function buildWebhooksLink()
    {
        return $this->buildLinkWithFuncQuery(
            self::PANE_WEBHOOKS,
            AdminWebhooks::FUNC_ADMIN_WEBHOOKS,
            dgettext('tuleap-tracker', "Webhooks")
        );
    }

    protected function displayFooter(Tracker_IDisplayTrackerLayout $engine)
    {
        echo '</div>';
        echo '</div>';
        $this->tracker->displayFooter($engine);
    }

    /**
     * @return string eg: rules, transitions
     */
    protected abstract function getPaneIdentifier();

    /**
     * Process the request
     */
    public abstract function process(Tracker_IDisplayTrackerLayout $layout, Codendi_Request $request, PFUser $current_user);
}
