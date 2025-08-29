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
use Override;
use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\Baseline\Domain\BaselineArtifactRepository;
use Tuleap\Baseline\Domain\BaselineArtifact;
use Tuleap\Baseline\Domain\ProjectIdentifier;
use Tuleap\Baseline\Domain\UserIdentifier;

class BaselineArtifactRepositoryAdapter implements BaselineArtifactRepository
{
    public function __construct(
        private Tracker_ArtifactFactory $artifact_factory,
        private Tracker_Artifact_ChangesetFactory $changeset_factory,
        private SemanticValueAdapter $semantic_value_adapter,
        private ArtifactLinkRepository $artifact_link_adapter,
        private \UserManager $user_manager,
    ) {
    }

    #[Override]
    public function findById(UserIdentifier $user_identifier, int $id): ?BaselineArtifact
    {
        $current_user = $this->user_manager->getUserById($user_identifier->getId());
        if (! $current_user) {
            return null;
        }

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
            ProjectProxy::buildFromProject($artifact->getTracker()->getProject()),
            $last_changeset
        );
    }

    #[Override]
    public function findByIdAt(UserIdentifier $user_identifier, int $id, DateTimeInterface $date): ?BaselineArtifact
    {
        $current_user = $this->user_manager->getUserById($user_identifier->getId());
        if (! $current_user) {
            return null;
        }

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
            ProjectProxy::buildFromProject($tracker_artifact->getTracker()->getProject()),
            $changeset
        );
    }

    private function buildArtifact(
        PFUser $current_user,
        int $id,
        ProjectIdentifier $project,
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
