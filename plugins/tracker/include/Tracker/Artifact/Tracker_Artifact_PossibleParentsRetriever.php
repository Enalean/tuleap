<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_Artifact_PossibleParentsRetriever
{

    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    public function __construct(Tracker_ArtifactFactory $artifact_factory)
    {
        $this->artifact_factory = $artifact_factory;
    }

    public function getPossibleArtifactParents(Tracker $parent_tracker, PFUser $user, $limit, $offset)
    {
        $label            = '';
        $possible_parents = [];
        $display_selector = true;
        EventManager::instance()->processEvent(
            TRACKER_EVENT_ARTIFACT_PARENTS_SELECTOR,
            [
                'user'             => $user,
                'parent_tracker'   => $parent_tracker,
                'possible_parents' => &$possible_parents,
                'label'            => &$label,
                'display_selector' => &$display_selector,
            ]
        );

        $paginated_possible_parents = null;
        if (! $possible_parents) {
            $label = sprintf(dgettext('tuleap-tracker', 'Open %1$s'), $parent_tracker->getName());

            $paginated_possible_parents = $this->artifact_factory->getPaginatedPossibleParentArtifactsUserCanView(
                $user,
                $parent_tracker->getId(),
                $limit,
                $offset
            );
        } else {
            $paginated_possible_parents = new Tracker_Artifact_PaginatedArtifacts($possible_parents, count($possible_parents));
        }

        return [$label, $paginated_possible_parents, $display_selector];
    }
}
