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
use Tuleap\AgileDashboard\FormElement\BurnupEffort;

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
        $backlog_items = $this->burnup_dao->searchLinkedArtifactsAtGivenTimestamp($artifact_id, $timestamp);

        $already_seen_artifacts = [];

        $total_subelements  = 0;
        $closed_subelements = 0;
        foreach ($backlog_items as $item) {
            $artifact = $this->artifact_factory->getArtifactById($item['id']);
            if ($artifact === null) {
                continue;
            }

            if ($this->isArtifactAlreadyParsed($artifact, $already_seen_artifacts)) {
                continue;
            }

            $already_seen_artifacts[] = (int) $artifact->getId();

            $total_subelements += 1;

            $changeset = $this->changeset_factory->getChangesetAtTimestamp($artifact, $timestamp);
            if ($changeset === null) {
                continue;
            }

            if (! $artifact->isOpenAtGivenChangeset($changeset)) {
                $closed_subelements += 1;
            }

            $this->countChildren(
                $artifact,
                $changeset,
                $timestamp,
                $total_subelements,
                $closed_subelements,
                $already_seen_artifacts
            );
        }

        return new CountElementsInfo($closed_subelements, $total_subelements);
    }

    private function countChildren(
        Tracker_Artifact $artifact,
        Tracker_Artifact_Changeset $changeset,
        int $timestamp,
        int &$total_subelements,
        int &$closed_subelements,
        array &$already_seen_artifacts
    ) {
        $used_artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($artifact->getTracker());
        if (count($used_artifact_link_fields) === 0) {
            return;
        }

        $artifact_link_field = $used_artifact_link_fields[0];
        $artifact_link_value = $changeset->getValue($artifact_link_field);

        if ($artifact_link_value === null) {
            return;
        }

        assert($artifact_link_value instanceof Tracker_Artifact_ChangesetValue_ArtifactLink);

        foreach ($artifact_link_value->getArtifactIds() as $artifact_link_id) {
            $linked_artifact = $this->artifact_factory->getArtifactById($artifact_link_id);
            if ($linked_artifact === null) {
                continue;
            }

            if (! $this->isLinkedArtifactChildOfGivenArtifact($linked_artifact, $artifact)) {
                continue;
            }

            if ($this->isArtifactAlreadyParsed($linked_artifact, $already_seen_artifacts)) {
                continue;
            }
            $already_seen_artifacts[] = (int) $linked_artifact->getId();

            $total_subelements += 1;

            $child_changeset = $this->changeset_factory->getChangesetAtTimestamp($linked_artifact, $timestamp);
            if ($child_changeset === null) {
                continue;
            }

            if (! $linked_artifact->isOpenAtGivenChangeset($child_changeset)) {
                $closed_subelements += 1;
            }

            $this->countChildren(
                $linked_artifact,
                $child_changeset,
                $timestamp,
                $total_subelements,
                $closed_subelements,
                $already_seen_artifacts
            );
        }
    }

    private function isLinkedArtifactChildOfGivenArtifact(Tracker_Artifact $linked_artifact, Tracker_Artifact $artifact): bool
    {
        $parent_linked_artifact = $linked_artifact->getParentWithoutPermissionChecking();

        return $parent_linked_artifact !== null &&
            (int) $parent_linked_artifact->getId() === (int) $artifact->getId();
    }

    private function isArtifactAlreadyParsed(Tracker_Artifact $artifact, array $already_seen_artifacts): bool
    {
        return in_array((int) $artifact->getId(), $already_seen_artifacts, true);
    }
}
