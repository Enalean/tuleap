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
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\FormElement\Burnup;

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\FormElement\BurnupDataDAO;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\Changeset\ArtifactLink\ArtifactLinkChangesetValue;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatus;

class CountElementsCalculator
{
    public function __construct(
        private readonly Tracker_Artifact_ChangesetFactory $changeset_factory,
        private readonly Tracker_ArtifactFactory $artifact_factory,
        private readonly Tracker_FormElementFactory $form_element_factory,
        private readonly BurnupDataDAO $burnup_dao,
        private readonly RetrieveSemanticStatus $semantic_status_retriever,
    ) {
    }

    public function getValue(int $artifact_id, int $timestamp, array $backlog_trackers_ids): CountElementsInfo
    {
        $items_dar     = $this->burnup_dao->searchLinkedArtifactsAtGivenTimestamp($artifact_id, $timestamp, $backlog_trackers_ids);
        $backlog_items = [];
        array_push($backlog_items, ...$items_dar);
        $elements_count = array_reduce(
            $backlog_items,
            function (ElementsCount $accumulator, array $item) use ($timestamp): ElementsCount {
                $artifact = $this->artifact_factory->getArtifactById($item['id']);
                if ($artifact === null) {
                    return $accumulator;
                }
                return $this->countElements($accumulator, $artifact, $timestamp);
            },
            new ElementsCount(0, 0, [])
        );
        return CountElementsInfo::buildFromElementsCount($elements_count);
    }

    private function countChildren(
        Artifact $artifact,
        Tracker_Artifact_Changeset $changeset,
        int $timestamp,
        ElementsCount $initial_accumulator,
    ): ElementsCount {
        $used_artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($artifact->getTracker());
        if (count($used_artifact_link_fields) === 0) {
            return $initial_accumulator;
        }

        $artifact_link_field = $used_artifact_link_fields[0];
        $artifact_link_value = $changeset->getValue($artifact_link_field);

        if ($artifact_link_value === null) {
            return $initial_accumulator;
        }

        assert($artifact_link_value instanceof ArtifactLinkChangesetValue);

        return array_reduce(
            $artifact_link_value->getArtifactIds(),
            function (ElementsCount $accumulator, int $artifact_link_id) use ($artifact, $timestamp): ElementsCount {
                $linked_artifact = $this->artifact_factory->getArtifactById($artifact_link_id);
                if ($linked_artifact === null) {
                    return $accumulator;
                }

                if (! $this->isLinkedArtifactChildOfGivenArtifact($linked_artifact, $artifact)) {
                    return $accumulator;
                }

                return $this->countElements($accumulator, $linked_artifact, $timestamp);
            },
            $initial_accumulator
        );
    }

    private function countElements(
        ElementsCount $accumulator,
        Artifact $artifact,
        int $timestamp,
    ): ElementsCount {
        if ($accumulator->isArtifactAlreadyParsed($artifact)) {
            return $accumulator;
        }

        $already_seen_artifacts = array_merge($accumulator->getAlreadySeenArtifacts(), [$artifact->getId()]);
        $total_subelements      = $accumulator->getTotalElements() + 1;
        $closed_subelements     = $accumulator->getClosedElements();

        $changeset = $this->changeset_factory->getChangesetAtTimestamp($artifact, $timestamp);
        if ($changeset === null) {
            return new ElementsCount($total_subelements, $closed_subelements, $already_seen_artifacts);
        }

        if (! $this->semantic_status_retriever->fromTracker($artifact->getTracker())->isOpenAtGivenChangeset($changeset)) {
            $closed_subelements += 1;
        }

        return $this->countChildren(
            $artifact,
            $changeset,
            $timestamp,
            new ElementsCount($total_subelements, $closed_subelements, $already_seen_artifacts)
        );
    }

    private function isLinkedArtifactChildOfGivenArtifact(Artifact $linked_artifact, Artifact $artifact): bool
    {
        $parent_linked_artifact = $linked_artifact->getParentWithoutPermissionChecking();

        return $parent_linked_artifact !== null &&
               $parent_linked_artifact->getId() === $artifact->getId();
    }
}
