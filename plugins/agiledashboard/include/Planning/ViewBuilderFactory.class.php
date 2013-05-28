<?php
/**
 * Copyright Enalean (c) 2013. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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
 * This class builds Planning_ViewBuilder
 */
class Planning_ViewBuilderFactory {

    /** @var Codendi_Request */
    private $request;

    /** @var PlanningFactory */
    private $planning_factory;

    public function __construct(
        Codendi_Request $request,
        PlanningFactory $planning_factory
    ) {
        $this->request          = $request;
        $this->planning_factory = $planning_factory;
    }

    /**
     * Builds a new cross-tracker search view builder.
     *
     * @param Codendi_Request $request
     *
     * @return Tracker_CrossSearch_ViewBuilder
     */
    public function getViewBuilder() {
        $form_element_factory = Tracker_FormElementFactory::instance();
        $group_id             = $this->request->get('group_id');
        $user                 = $this->request->getCurrentUser();
        $tracker_manager      = new TrackerManager(); //God object
        $planning_trackers    = $this->planning_factory->getPlanningTrackers($group_id, $user);
        $art_link_field_ids   = $form_element_factory->getArtifactLinkFieldsOfTrackers($planning_trackers);

        return new Planning_ViewBuilder(
            $form_element_factory,
            $tracker_manager->getCrossSearch($art_link_field_ids),
            $tracker_manager->getCriteriaBuilder($user, $planning_trackers)
        );
    }
}

?>
