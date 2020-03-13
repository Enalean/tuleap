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
 * Handles HTTP actions related to milestone artifact creation.
 *
 * TODO:
 *   - Merge into MilestoneController ?
 *   - Use MilestoneController ?
 */
class Planning_ArtifactCreationController extends MVC2_PluginController
{

    /**
     * @var PlanningFactory
     */
    private $planning_factory;

    public function __construct(PlanningFactory $planning_factory, Codendi_Request $request)
    {
        parent::__construct('agiledashboard', $request);

        $this->planning_factory = $planning_factory;
    }

    public function createArtifact()
    {
        $planning_id = $this->request->get('planning_id');
        $planning    = $this->planning_factory->getPlanning($planning_id);
        $tracker_id  = $planning->getPlanningTrackerId();

        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . "/?tracker=$tracker_id&func=new-artifact&planning[$planning_id]=-1");
    }
}
