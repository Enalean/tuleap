<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

/**
 * Base class to manage action that can be done on a workflow
 */
abstract class Tracker_Workflow_Action {

    const PANE_RULES                  = 'rules';
    const PANE_TRANSITIONS            = 'transitions';
    const PANE_CROSS_TRACKER_TRIGGERS = 'triggers';
    const PANE_WEBHOOKS               = 'webhooks';

    /** @var Tracker */
    protected $tracker;

    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    protected function displayHeader(Tracker_IDisplayTrackerLayout $engine)
    {
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');

        echo '<div class="tabbable">';
        echo '<ul class="nav nav-tabs">';
        foreach ($this->getPanes() as $identifier => $pane) {
            $active = '';
            if ($this->getPaneIdentifier() == $identifier) {
                $active = 'active';
            }
            $link = TRACKER_BASE_URL.'/?'. http_build_query(
                array(
                    'tracker' =>  (int)$this->tracker->id,
                    'func'    =>  $pane['func'],
                )
            );
            echo '<li class="'. $active .'"><a href="'. $link .'">'. $pane['title'] .'</a></li>';
        }
        echo '</ul>';
        echo '<div class="tab-content">';
    }

    private function getPanes() {
        return array(
            self::PANE_RULES => array(
                'func'  => Workflow::FUNC_ADMIN_RULES,
                'title' => $GLOBALS['Language']->getText('workflow_admin', 'tab_global_rules'),
            ),
            self::PANE_TRANSITIONS => array(
                'func'  => Workflow::FUNC_ADMIN_TRANSITIONS,
                'title' => $GLOBALS['Language']->getText('workflow_admin', 'tab_transitions'),
            ),
            self::PANE_CROSS_TRACKER_TRIGGERS => array(
                'func'  => Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS,
                'title' => $GLOBALS['Language']->getText('workflow_admin', 'tab_triggers'),
            ),
            self::PANE_WEBHOOKS => [
                'func'  => AdminWebhooks::FUNC_ADMIN_WEBHOOKS,
                'title' => dgettext('tuleap-tracker', "Webhooks"),
            ]
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
