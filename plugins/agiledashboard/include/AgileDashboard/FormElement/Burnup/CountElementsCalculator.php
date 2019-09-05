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

use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_Artifact_ChangesetValue_ArtifactLink;
use Tracker_ArtifactFactory;
use Tracker_FormElementFactory;
use Tuleap\AgileDashboard\FormElement\BurnupDao;

class CountElementsCalculator
{
    /**
     * @var Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;

    /**
     * @var BurnupDao
     */
    private $burnup_dao;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;


    public function __construct(
        Tracker_Artifact_ChangesetFactory $changeset_factory,
        Tracker_ArtifactFactory $artifact_factory,
        Tracker_FormElementFactory $form_element_factory,
        BurnupDao $burnup_dao
    ) {
        $this->changeset_factory    = $changeset_factory;
        $this->burnup_dao           = $burnup_dao;
        $this->artifact_factory     = $artifact_factory;
        $this->form_element_factory = $form_element_factory;
    }

    public function getValue(int $artifact_id, int $timestamp): CountElementsInfo
    {
        $items_dar      = $this->burnup_dao->searchLinkedArtifactsAtGivenTimestamp($artifact_id, $timestamp);
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
        Tracker_Artifact $artifact,
        Tracker_Artifact_Changeset $changeset,
        int $timestamp,
        ElementsCount $initial_accumulator
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

        assert($artifact_link_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

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
        Tracker_Artifact $artifact,
        int $timestamp
    ): ElementsCount {
        if ($accumulator->isArtifactAlreadyParsed($artifact)) {
            return $accumulator;
        }

        $already_seen_artifacts = array_merge($accumulator->getAlreadySeenArtifacts(), [(int) $artifact->getId()]);
        $total_subelements      = $accumulator->getTotalElements() + 1;
        $closed_subelements     = $accumulator->getClosedElements();

        $changeset = $this->changeset_factory->getChangesetAtTimestamp($artifact, $timestamp);
        if ($changeset === null) {
            return new ElementsCount($total_subelements, $closed_subelements, $already_seen_artifacts);
        }

        if (! $artifact->isOpenAtGivenChangeset($changeset)) {
            $closed_subelements += 1;
        }

        return $this->countChildren(
            $artifact,
            $changeset,
            $timestamp,
            new ElementsCount($total_subelements, $closed_subelements, $already_seen_artifacts)
        );
    }

    private function isLinkedArtifactChildOfGivenArtifact(Tracker_Artifact $linked_artifact, Tracker_Artifact $artifact): bool
    {
        $parent_linked_artifact = $linked_artifact->getParentWithoutPermissionChecking();

        return $parent_linked_artifact !== null &&
            (int) $parent_linked_artifact->getId() === (int) $artifact->getId();
    }
}
