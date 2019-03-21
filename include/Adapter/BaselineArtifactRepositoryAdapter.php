<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

use AgileDashBoard_Semantic_InitialEffort;
use DateTimeInterface;
use PFUser;
use Project;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElement_Field;
use Tracker_Semantic_Description;
use Tracker_Semantic_Status;
use Tracker_Semantic_Title;
use Tuleap\Baseline\BaselineArtifact;
use Tuleap\Baseline\BaselineArtifactRepository;

class BaselineArtifactRepositoryAdapter implements BaselineArtifactRepository
{
    /** @var Tracker_ArtifactFactory */
    private $artifact_factory;

    /** @var AdapterPermissions */
    private $adapter_permissions;

    /** @var Tracker_Artifact_ChangesetFactory */
    private $changeset_factory;

    /** @var Tracker_ArtifactFactory */
    private $tracker_factory;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        AdapterPermissions $adapter_permissions,
        Tracker_Artifact_ChangesetFactory $changeset_factory,
        Tracker_ArtifactFactory $tracker_factory
    ) {
        $this->artifact_factory    = $artifact_factory;
        $this->adapter_permissions = $adapter_permissions;
        $this->changeset_factory   = $changeset_factory;
        $this->tracker_factory     = $tracker_factory;
    }

    public function findById(PFUser $current_user, int $id): ?BaselineArtifact
    {
        $artifact = $this->artifact_factory->getArtifactById($id);
        if ($artifact === null) {
            return null;
        }
        if (! $this->adapter_permissions->canUserReadArtifact($current_user, $artifact)) {
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
        $tracker_artifact = $this->tracker_factory->getArtifactById($id);
        if ($tracker_artifact === null) {
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
        Tracker_Artifact_Changeset $changeset
    ): BaselineArtifact {
        $title          = $this->getTrackerTitle($changeset);
        $description    = $this->getTrackerDescription($changeset);
        $initial_effort = $this->getTrackerInitialEffort($changeset);
        $status         = $this->getTrackerStatus($changeset);

        $tracker_artifact = $changeset->getArtifact();
        $tracker_name     = $tracker_artifact->getTracker()->getName();

        $linked_artifact_ids = $this->getLinkedArtifactIds($current_user, $changeset);

        return new BaselineArtifact(
            $id,
            $title,
            $description,
            $initial_effort,
            $status,
            $project,
            $tracker_name,
            $linked_artifact_ids
        );
    }

    private function getTrackerTitle(Tracker_Artifact_Changeset $changeset): ?string
    {
        $tracker     = $changeset->getTracker();
        $title_field = $this->getNullIfNotAllowed(Tracker_Semantic_Title::load($tracker)->getField());
        if ($title_field === null) {
            return null;
        }

        $changed_value = $changeset->getValue($title_field);
        if ($changed_value === null) {
            return null;
        }
        return $changed_value->getValue();
    }

    private function getTrackerDescription(Tracker_Artifact_Changeset $changeset): ?string
    {
        $tracker           = $changeset->getTracker();
        $description_field = $this->getNullIfNotAllowed(
            Tracker_Semantic_Description::load($tracker)->getField()
        );
        if ($description_field === null) {
            return null;
        }

        $changed_value = $changeset->getValue($description_field);
        if ($changed_value === null) {
            return null;
        }
        return $changed_value->getValue();
    }

    private function getTrackerInitialEffort(Tracker_Artifact_Changeset $changeset): ?int
    {
        $tracker           = $changeset->getTracker();
        $description_field = $this->getNullIfNotAllowed(
            AgileDashBoard_Semantic_InitialEffort::load($tracker)->getField()
        );
        if ($description_field === null) {
            return null;
        }

        $changed_value = $changeset->getValue($description_field);
        if ($changed_value === null) {
            return null;
        }
        return (int) $changed_value->getValue();
    }

    private function getTrackerStatus(Tracker_Artifact_Changeset $changeset): ?string
    {
        $tracker      = $changeset->getTracker();
        $status_field = $this->getNullIfNotAllowed(Tracker_Semantic_Status::load($tracker)->getField());
        if ($status_field === null) {
            return null;
        }

        return $status_field->getFirstValueFor($changeset);
    }

    private function getNullIfNotAllowed(?Tracker_FormElement_Field $field): ?Tracker_FormElement_Field
    {
        if ($field === null || ! $field->userCanRead()) {
            return null;
        }

        return $field;
    }

    /**
     * @return int[]
     */
    private function getLinkedArtifactIds(
        PFUser $current_user,
        Tracker_Artifact_Changeset $changeset
    ): array {
        $artifact_link_field = $changeset->getArtifact()->getAnArtifactLinkField($current_user);
        if ($artifact_link_field === null) {
            return [];
        }

        $tracker_artifacts   = $artifact_link_field->getLinkedArtifacts($changeset, $current_user);
        $linked_artifact_ids = array_map(
            function (Tracker_Artifact $tracker_artifact) {
                return $tracker_artifact->getId();
            },
            $tracker_artifacts
        );
        return $linked_artifact_ids;
    }
}
