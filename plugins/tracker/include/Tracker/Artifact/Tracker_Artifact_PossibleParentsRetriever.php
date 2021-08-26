<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\Tracker\Artifact\PossibleParentSelector;

class Tracker_Artifact_PossibleParentsRetriever
{
    public function __construct(private Tracker_ArtifactFactory $artifact_factory, private EventDispatcherInterface $event_dispatcher)
    {
    }

    public function getPossibleArtifactParents(Tracker $parent_tracker, PFUser $user, $limit, $offset): PossibleParentSelector
    {
        $possible_parents = $this->event_dispatcher->dispatch(new PossibleParentSelector($user, $parent_tracker));

        if ($possible_parents->getPossibleParents()) {
            return $possible_parents;
        }

        $possible_parents->setLabel(sprintf(dgettext('tuleap-tracker', 'Open %1$s'), $parent_tracker->getName()));
        $possible_parents->setPossibleParents(
            $this->artifact_factory->getPaginatedPossibleParentArtifactsUserCanView(
                $user,
                $parent_tracker->getId(),
                $limit,
                $offset
            )
        );

        return $possible_parents;
    }
}
