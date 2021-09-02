<?php
/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\Team;

use Tuleap\AgileDashboard\Planning\RetrieveRootPlanning;
use Tuleap\ProgramManagement\Adapter\Workspace\UserProxy;
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureIdentifier;
use Tuleap\ProgramManagement\Domain\Team\PossibleParentSelectorEvent;
use Tuleap\ProgramManagement\Domain\Workspace\UserIdentifier;
use Tuleap\Tracker\Artifact\PossibleParentSelector;
use Tuleap\Tracker\Artifact\RetrieveArtifact;

final class PossibleParentSelectorProxy implements PossibleParentSelectorEvent
{
    private function __construct(private PossibleParentSelector $inner_event, private RetrieveRootPlanning $retrieve_root_planning, private RetrieveArtifact $retrieve_artifact)
    {
    }

    /**
     * @psalm-mutation-free
     */
    public static function fromEvent(PossibleParentSelector $possible_parent_selector, RetrieveRootPlanning $retrieve_root_planning, RetrieveArtifact $retrieve_artifact): self
    {
        return new self($possible_parent_selector, $retrieve_root_planning, $retrieve_artifact);
    }

    public function getUser(): UserIdentifier
    {
        return UserProxy::buildFromPFUser($this->inner_event->user);
    }

    public function trackerIsInRootPlanning(): bool
    {
        $planning = $this->retrieve_root_planning->getRootPlanning($this->inner_event->user, $this->getProjectId());
        return $planning && in_array($this->inner_event->tracker->getId(), $planning->getBacklogTrackersIds(), true);
    }

    /**
     * @psalm-mutation-free
     */
    public function getProjectId(): int
    {
        return (int) $this->inner_event->tracker->getGroupId();
    }

    public function disableCreate(): void
    {
        $this->inner_event->disableCreate();
    }

    public function setPossibleParents(FeatureIdentifier ...$features): void
    {
        $labels        = [];
        $artifacts     = [];
        $parent_labels = [];
        foreach ($features as $feature) {
            $artifact = $this->retrieve_artifact->getArtifactById($feature->id);
            if (! $artifact) {
                throw new \RuntimeException('Features must always have an artifact counter part');
            }
            $artifacts[]                                           = $artifact;
            $labels[$artifact->getTracker()->getName()]            = 1;
            $parent_labels[$artifact->getTracker()->getItemName()] = 1;
        }
        $this->inner_event->setPossibleParents(new \Tracker_Artifact_PaginatedArtifacts($artifacts, count($artifacts)));
        $this->inner_event->setLabel(
            sprintf(
                dngettext('tuleap-program_management', 'Open %1s', 'Open %1s', count(array_keys($labels))),
                implode(', ', array_keys($labels))
            )
        );
        $this->inner_event->setParentLabel(implode(', ', array_keys($parent_labels)));
    }
}
