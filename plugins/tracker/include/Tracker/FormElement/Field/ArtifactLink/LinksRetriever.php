<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Field\ArtifactLink;

use PFUser;
use Tracker_Artifact_Changeset;
use Tracker_ArtifactLinkInfo;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;

class LinksRetriever
{
    private ArtifactLinkFieldValueDao $artifact_link_dao;
    private \Tracker_ArtifactFactory $artifact_factory;

    public function __construct(
        ArtifactLinkFieldValueDao $artifact_link_dao,
        \Tracker_ArtifactFactory $artifact_factory,
    ) {
        $this->artifact_link_dao = $artifact_link_dao;
        $this->artifact_factory  = $artifact_factory;
    }

    /**
     * @return Artifact[]
     */
    public function retrieveReverseLinks(Artifact $artifact, \PFUser $user): array
    {
        $linked_and_reverse_artifacts = [];
        $artifact_link_field          = $artifact->getAnArtifactLinkField($user);
        $last_changeset               = $artifact->getLastChangeset();

        if ($artifact_link_field && $last_changeset) {
            $linked_and_reverse_artifacts = $this->getReverseArtifacts($last_changeset, $user, $artifact_link_field);
        }

        return $linked_and_reverse_artifacts;
    }

    /**
     * @return Artifact[]
     */
    public function retrieveReverseLinksFromTracker(Artifact $artifact, \PFUser $user, \Tracker $target_tracker): array
    {
        $artifact_link_field = $artifact->getAnArtifactLinkField($user);
        $last_changeset      = $artifact->getLastChangeset();

        if (! $artifact_link_field || ! $last_changeset) {
            return [];
        }

        $dar = $this->artifact_link_dao->searchReverseLinksByIdAndSourceTrackerId(
            $artifact->getId(),
            $target_tracker->getId()
        );

        if ($dar === false) {
            return [];
        }

        $artifacts_ids = [];
        foreach ($dar as $link_info) {
            $artifacts_ids[] = $link_info['artifact_id'];
        }

        return $this->getArtifactsFromIdsUserCanRead($artifacts_ids, $user);
    }

    /**
     * @return Artifact[]
     */
    public function retrieveLinkedAndReverseArtifacts(Artifact $artifact, PFUser $user): array
    {
        $linked_and_reverse_artifacts = [];
        $artifact_link_field          = $artifact->getAnArtifactLinkField($user);
        $last_changeset               = $artifact->getLastChangeset();

        if ($artifact_link_field && $last_changeset) {
            $linked_and_reverse_artifacts = $this->getLinkedAndReverseArtifacts($last_changeset, $user, $artifact_link_field);
        }

        return $linked_and_reverse_artifacts;
    }

    /**
     * Retrieve linked artifacts and reverse linked artifacts according to user's permissions
     *
     * @return Artifact[]
     */
    private function getLinkedAndReverseArtifacts(Tracker_Artifact_Changeset $changeset, PFUser $user, ArtifactLinkField $artifact_link): array
    {
        $changeset_value  = $changeset->getValue($artifact_link);
        $all_artifact_ids = $this->getReverseLinksIds($changeset->getArtifact());

        if ($changeset_value) {
            assert($changeset_value instanceof ArtifactLinkChangesetValue);
            $all_artifact_ids = array_unique(array_merge($all_artifact_ids, $changeset_value->getArtifactIds()));
        }

        return $this->getArtifactsFromIdsUserCanRead($all_artifact_ids, $user);
    }

    /**
     * @return Artifact[]
     */
    private function getReverseArtifacts(Tracker_Artifact_Changeset $changeset, PFUser $user, ArtifactLinkField $artifact_link): array
    {
        $all_artifact_ids = $this->getReverseLinksIds($changeset->getArtifact());

        return $this->getArtifactsFromIdsUserCanRead($all_artifact_ids, $user);
    }

    /**
     * @return Tracker_ArtifactLinkInfo[]
     */
    private function getReverseLinksIds(Artifact $artifact): array
    {
        $reverse_links_infos = $this->artifact_link_dao->searchReverseLinksById($artifact->getId());

        $reverse_links_ids = [];
        foreach ($reverse_links_infos as $reverse_link_info) {
            $reverse_links_ids[] = $reverse_link_info['artifact_id'];
        }

        return $reverse_links_ids;
    }

    /**
     * @return Artifact[]
     */
    private function getArtifactsFromIdsUserCanRead(array $artifacts_ids, PFUser $user): array
    {
        $artifacts = [];
        foreach ($artifacts_ids as $reverse_link_info) {
            $artifact_linking = $this->artifact_factory->getArtifactById($reverse_link_info);

            if ($artifact_linking === null || ! $artifact_linking->userCanView($user)) {
                continue;
            }

            $artifacts[] = $artifact_linking;
        }
        return $artifacts;
    }
}
