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
use Tuleap\ProgramManagement\Domain\Program\Backlog\Feature\FeatureReference;
use Tuleap\ProgramManagement\Domain\Team\PossibleParentSelectorEvent;
use Tuleap\ProgramManagement\Domain\Workspace\UserReference;
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

    #[\Override]
    public function getUser(): UserReference
    {
        return UserProxy::buildFromPFUser($this->inner_event->user);
    }

    #[\Override]
    public function trackerIsInRootPlanning(): bool
    {
        $planning = $this->retrieve_root_planning->getRootPlanning($this->inner_event->user, $this->getProjectId());
        return $planning && in_array($this->inner_event->tracker->getId(), $planning->getBacklogTrackersIds(), true);
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getProjectId(): int
    {
        return (int) $this->inner_event->tracker->getGroupId();
    }

    #[\Override]
    public function disableCreate(): void
    {
        $this->inner_event->disableCreate();
    }

    #[\Override]
    public function setPossibleParents(int $total_size, FeatureReference ...$features): void
    {
        $artifacts = [];
        foreach ($features as $feature) {
            $artifact = $this->retrieve_artifact->getArtifactById($feature->id);
            if (! $artifact) {
                throw new \RuntimeException('Features must always have an artifact counter part');
            }
            $artifact->setTitle($feature->title);
            $artifacts[] = $artifact;
        }
        $this->inner_event->addPossibleParents(
            new \Tracker_Artifact_PaginatedArtifacts($artifacts, $total_size)
        );
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getLimit(): int
    {
        return $this->inner_event->limit;
    }

    /**
     * @psalm-mutation-free
     */
    #[\Override]
    public function getOffset(): int
    {
        return $this->inner_event->offset;
    }

    #[\Override]
    public function getTrackerId(): int
    {
        return $this->inner_event->tracker->getId();
    }

    #[\Override]
    public function disableSelector(): void
    {
        $this->inner_event->disableSelector();
    }
}
