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
 * This class is responsible of processing the TRACKER_EVENT_ARTIFACT_PARENTS_SELECTOR event
 * in the agiledashboard.
 */
class Planning_ArtifactParentsSelectorEventListener
{

    /**
     * @var Tracker_ArtifactFactory
     */
    protected $artifact_factory;

    /**
     * @var Planning_ArtifactParentsSelector
     */
    protected $artifact_parents_selector;

    /**
     * @var Codendi_Request
     */
    protected $request;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Planning_ArtifactParentsSelector $artifact_parents_selector,
        Codendi_Request $request
    ) {
        $this->artifact_factory          = $artifact_factory;
        $this->artifact_parents_selector = $artifact_parents_selector;
        $this->request                   = $request;
    }

    /**
     * @param array $params the parameters of the event see TRACKER_EVENT_ARTIFACT_PARENTS_SELECTOR
     */
    public function process($params)
    {
        $source_artifact = $this->getSourceArtifact();
        if ($source_artifact) {
            $params['label']             = sprintf(dgettext('tuleap-agiledashboard', 'Available %1$s'), $params['parent_tracker']->getName());
            $params['possible_parents']  = $this->artifact_parents_selector->getPossibleParents($params['parent_tracker'], $source_artifact, $params['user']);
            $we_are_linking_the_artifact_to_a_parent = ($params['possible_parents'] == [$source_artifact]);
            if ($we_are_linking_the_artifact_to_a_parent) {
                $params['display_selector'] = false;
            }
        }
    }

    private function getSourceArtifact()
    {
        $source_artifact = null;
        if ($this->request->get('func') == 'new-artifact-link') {
            $source_artifact = $this->artifact_factory->getArtifactById($this->request->get('id'));
        } elseif ($this->request->get('child_milestone')) {
            $source_artifact = $this->artifact_factory->getArtifactById($this->request->get('child_milestone'));
        }
        return $source_artifact;
    }
}
