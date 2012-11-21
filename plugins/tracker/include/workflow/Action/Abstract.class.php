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

abstract class Tracker_Workflow_Action_Abstract {
    /** @var Tracker */
    protected $tracker;
    
    public function __construct(Tracker $tracker) {
        $this->tracker = $tracker;
    }
    
    protected function displayHeader($engine) {
        $this->tracker->displayAdminItemHeader($engine, 'editworkflow');

        $transitions_link = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' =>  (int)$this->tracker->id,
                'func'    =>  'admin-workflow'
            )
        );

        $workflow_link = TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker' =>  (int)$this->tracker->id,
                'func'    =>  'admin-workflow-rules'
            )
        );
        echo '<div class="tabbable tabs-left">';
        echo '<ul class="nav nav-tabs">';
        echo '<li class="active"><a href="'. $workflow_link .'">Workflow Rules</a></li>'; //TODO: i18n
        echo '<li><a href="'. $transitions_link .'">Transitions</a></li>'; //TODO: i18n
        echo '</ul>';
        echo '<div class="tab-content">';
    }

    protected function displayFooter($engine) {
        echo '</div>';
        echo '</div>';
        $this->tracker->displayFooter($engine);
    }

}

?>
