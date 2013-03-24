<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Base class to manage action that can be done on a workflow
 */
abstract class Tracker_Workflow_Action {

    const PANE_RULES       = 'rules';
    const PANE_TRANSITIONS = 'transitions';

    /** @var Tracker */
    protected $tracker;

    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }

    protected function displayHeader($engine) {
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');

        echo '<div class="tabbable tabs-left">';
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
        );
    }

    protected function displayFooter($engine) {
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
?>
