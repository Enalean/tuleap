<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Baseline\Adapter;

use DateTimeInterface;
use PFUser;
use Project;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\BaselineArtifact;

class BaselineArtifactRepositoryAdapter implements BaselineArtifactRepository
{
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var Tracker_Artifact_ChangesetFactory */
    private $changeset_factory;

    /** @var SemanticValueAdapter */
    private $semantic_value_adapter;

    /** @var ArtifactLinkRepository */
    private $artifact_link_adapter;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_Artifact_ChangesetFactory $changeset_factory,
        SemanticValueAdapter $semantic_value_adapter,
        ArtifactLinkRepository $artifact_link_adapter,
    ) {
        $this->artifact_factory       = $artifact_factory;
        $this->changeset_factory      = $changeset_factory;
        $this->semantic_value_adapter = $semantic_value_adapter;
        $this->artifact_link_adapter  = $artifact_link_adapter;
    }

    public function findById(PFUser $current_user, int $id): ?BaselineArtifact
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if ($artifact === null) {
            return null;
        }
        if (! $artifact->userCanView($current_user)) {
            return null;
        }

        $last_changeset = $this->changeset_factory->getLastChangeset($artifact);
        if ($last_changeset === null) {
            return null;
        }

        return $this->buildArtifact(
            $current_user,
            $id,
            $artifact->getTracker()->getProject(),
            $last_changeset
        );
    }

    public function findByIdAt(PFUser $current_user, int $id, DateTimeInterface $date): ?BaselineArtifact
    {
        $tracker_artifact = $this->artifact_factory->getArtifactById($id);
        if ($tracker_artifact === null || ! $tracker_artifact->userCanView($current_user)) {
            return null;
        }
        $changeset = $this->changeset_factory->getChangesetAtTimestamp($tracker_artifact, $date->getTimestamp());
        if ($changeset === null) {
            return null;
        }
        return $this->buildArtifact(
            $current_user,
            $id,
            $tracker_artifact->getTracker()->getProject(),
            $changeset
        );
    }

    private function buildArtifact(
        PFUser $current_user,
        int $id,
        Project $project,
        Tracker_Artifact_Changeset $changeset,
    ): BaselineArtifact {
        $title          = $this->semantic_value_adapter->findTitle($changeset, $current_user);
        $description    = $this->semantic_value_adapter->findDescription($changeset, $current_user);
        $initial_effort = $this->semantic_value_adapter->findInitialEffort($changeset, $current_user);
        $status         = $this->semantic_value_adapter->findStatus($changeset, $current_user);

        $tracker      = $changeset->getArtifact()->getTracker();
        $tracker_id   = (int) $tracker->getId();
        $tracker_name = $tracker->getItemName();

        $linked_artifact_ids = $this->artifact_link_adapter->findLinkedArtifactIds($current_user, $changeset);

        return new BaselineArtifact(
            $id,
            $title,
            $description,
            $initial_effort,
            $status,
            $project,
            $tracker_id,
            $tracker_name,
            $linked_artifact_ids
        );
    }
}
