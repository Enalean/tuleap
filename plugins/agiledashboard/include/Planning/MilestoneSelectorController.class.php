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
require_once 'common/mvc2/Controller.class.php';
require_once 'MilestoneFactory.class.php';
require_once 'PlanningFactory.class.php';

class Planning_MilestoneSelectorController extends MVC2_Controller {
    private $milestone_factory;

    public function __construct(Codendi_Request $request, Planning_MilestoneFactory $milestone_factory) {
        parent::__construct('agiledashboard', $request);
        $this->milestone_factory = $milestone_factory;
    }

    public function show() {
        $milestone = $this->milestone_factory->getCurrentMilestone(
            $this->request->getCurrentUser(),
            $this->request->getValidated('planning_id', 'uint', 0)
        );
        if ($milestone->getArtifact()) {
            $redirect_parameters = array(
                'group_id'    => $milestone->getGroupId(),
                'planning_id' => $milestone->getPlanningId(),
                'action'      => 'show',
                'aid'         => $milestone->getArtifact()->getId(),
            );
            EventManager::instance()->processEvent(
                    AGILEDASHBOARD_EVENT_MILESTONE_SELECTOR_REDIRECT,
                    array(
                        'milestone' => $milestone,
                        'redirect_parameters' => &$redirect_parameters
                    )
            );
            $this->redirect($redirect_parameters);
        }
    }
}

?>
