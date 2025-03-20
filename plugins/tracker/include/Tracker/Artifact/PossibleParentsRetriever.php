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

declare(strict_types=1);

namespace Tuleap\Tracker\Artifact;

use PFUser;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tracker_ArtifactFactory;
use Tuleap\Option\Option;
use Tuleap\Tracker\Hierarchy\ParentInHierarchyRetriever;
use Tuleap\Tracker\Permission\RetrieveUserPermissionOnTrackers;
use Tuleap\Tracker\Permission\TrackerPermissionType;

final readonly class PossibleParentsRetriever
{
    public function __construct(
        private Tracker_ArtifactFactory $artifact_factory,
        private EventDispatcherInterface $event_dispatcher,
        private ParentInHierarchyRetriever $parent_tracker_retriever,
        private RetrieveUserPermissionOnTrackers $tracker_permissions_retriever,
    ) {
    }

    public function getPossibleArtifactParents(
        Tracker $tracker,
        PFUser $user,
        int $limit,
        int $offset,
        bool $can_create,
    ): PossibleParentSelector {
        $possible_parents = $this->event_dispatcher->dispatch(
            new PossibleParentSelector($user, $tracker, $offset, $limit)
        );

        if (! $possible_parents->isSelectorDisplayed()) {
            return $possible_parents;
        }

        $parent_tracker = $this->getParentTrackerUserCanRead($tracker, $user);

        if ($parent_tracker->isNothing() && ! $possible_parents->getPossibleParents()) {
            $possible_parents->disableSelector();
            return $possible_parents;
        }

        $parent_tracker->apply(function (\Tracker $parent_tracker) use ($possible_parents, $user, $limit, $offset) {
            $possible_parents->setParentLabel($parent_tracker->getItemName());
            $possible_parents->addPossibleParents(
                $this->artifact_factory->getPaginatedPossibleParentArtifactsUserCanView(
                    $user,
                    $parent_tracker->getId(),
                    $limit,
                    $offset
                )
            );
        });

        if (! $can_create) {
            $possible_parents->disableCreate();
        }

        return $possible_parents;
    }

    /**
     * @return Option<Tracker>
     */
    private function getParentTrackerUserCanRead(Tracker $child_tracker, PFUser $user): Option
    {
        return $this->parent_tracker_retriever->getParentTracker($child_tracker)
            ->andThen(function (Tracker $parent_tracker) use ($user) {
                $permissions               = $this->tracker_permissions_retriever->retrieveUserPermissionOnTrackers(
                    $user,
                    [$parent_tracker],
                    TrackerPermissionType::PERMISSION_VIEW
                );
                $parent_tracker_is_allowed = array_search($parent_tracker, $permissions->allowed, true);
                if ($parent_tracker_is_allowed !== false) {
                    return Option::fromValue($parent_tracker);
                }
                return Option::nothing(Tracker::class);
            });
    }
}
