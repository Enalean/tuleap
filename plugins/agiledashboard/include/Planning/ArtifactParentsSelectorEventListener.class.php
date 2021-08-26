<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\PossibleParentSelector;

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

    public function process(PossibleParentSelector $possible_parent_selector): void
    {
        $source_artifact = $this->getSourceArtifact();
        if ($source_artifact) {
            $possible_parents = $this->artifact_parents_selector->getPossibleParents($possible_parent_selector->parent_tracker, $source_artifact, $possible_parent_selector->user);
            if ($possible_parents) {
                $possible_parent_selector->setLabel(sprintf(dgettext('tuleap-agiledashboard', 'Available %1$s'), $possible_parent_selector->parent_tracker->getName()));
                $possible_parent_selector->setPossibleParents(
                    new Tracker_Artifact_PaginatedArtifacts(
                        $possible_parents,
                        count($possible_parents),
                    )
                );
            }

            $we_are_linking_the_artifact_to_a_parent = ($possible_parents == [$source_artifact]);
            if ($we_are_linking_the_artifact_to_a_parent) {
                $possible_parent_selector->disableSelector();
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
