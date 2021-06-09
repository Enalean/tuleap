<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Timeframe;

use Psr\Log\LoggerInterface;
use TimePeriodWithoutWeekEnd;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\LinksRetriever;
use Tuleap\Tracker\Semantic\Timeframe\Exceptions\ArtifactHasNoLinkToArtifactOfTargetTracker;
use Tuleap\Tracker\Semantic\Timeframe\Exceptions\ArtifactHasTooManyLinksToArtifactsOfTargetTracker;

class TimeframeImpliedFromAnotherTracker implements IComputeTimeframes
{
    public const NAME = 'timeframe-implied-from-another-tracker';

    private \Tracker $tracker;

    private SemanticTimeframe $semantic_timeframe_implied_from_tracker;

    private LinksRetriever $links_retriever;

    public function __construct(
        \Tracker $tracker,
        SemanticTimeframe $semantic_timeframe_implied_from_tracker,
        LinksRetriever $links_retriever
    ) {
        $this->tracker                                 = $tracker;
        $this->semantic_timeframe_implied_from_tracker = $semantic_timeframe_implied_from_tracker;
        $this->links_retriever                         = $links_retriever;
    }

    public static function getName(): string
    {
        return self::NAME;
    }

    public function getConfigDescription(): string
    {
        return sprintf(
            dgettext('tuleap-tracker', 'Timeframes will be based on %s linking artifacts of this tracker.'),
            $this->semantic_timeframe_implied_from_tracker->getTracker()->getName()
        );
    }

    public function getStartDateField(): ?\Tracker_FormElement_Field_Date
    {
        return null;
    }

    public function getEndDateField(): ?\Tracker_FormElement_Field_Date
    {
        return null;
    }

    public function getDurationField(): ?\Tracker_FormElement_Field_Numeric
    {
        return null;
    }

    public function buildTimePeriodWithoutWeekendForArtifactForREST(Artifact $artifact, \PFUser $user, LoggerInterface $logger): TimePeriodWithoutWeekEnd
    {
        try {
            $artifact_from_target_tracker = $this->getReverselyLinkedArtifactFromTracker($artifact, $user);
            return $this->semantic_timeframe_implied_from_tracker->getTimeframeCalculator()
                ->buildTimePeriodWithoutWeekendForArtifactForREST(
                    $artifact_from_target_tracker,
                    $user,
                    $logger
                );
        } catch (
            ArtifactHasTooManyLinksToArtifactsOfTargetTracker |
            ArtifactHasNoLinkToArtifactOfTargetTracker $exception
        ) {
            return TimePeriodWithoutWeekEnd::buildFromNothingWithErrorMessage($exception->getMessage());
        }
    }

    public function buildTimePeriodWithoutWeekendForArtifact(Artifact $artifact, \PFUser $user, LoggerInterface $logger): TimePeriodWithoutWeekEnd
    {
        try {
            $artifact_from_target_tracker = $this->getReverselyLinkedArtifactFromTracker($artifact, $user);
            return $this->semantic_timeframe_implied_from_tracker->getTimeframeCalculator()
                ->buildTimePeriodWithoutWeekendForArtifact(
                    $artifact_from_target_tracker,
                    $user,
                    $logger
                );
        } catch (
            ArtifactHasTooManyLinksToArtifactsOfTargetTracker |
            ArtifactHasNoLinkToArtifactOfTargetTracker $exception
        ) {
            return TimePeriodWithoutWeekEnd::buildFromNothingWithErrorMessage($exception->getMessage());
        }
    }

    /**
     * @throws \Tracker_FormElement_Chart_Field_Exception
     */
    public function buildTimePeriodWithoutWeekendForArtifactChartRendering(Artifact $artifact, \PFUser $user, LoggerInterface $logger): TimePeriodWithoutWeekEnd
    {
        try {
            $artifact_from_target_tracker = $this->getReverselyLinkedArtifactFromTracker($artifact, $user);
            return $this->semantic_timeframe_implied_from_tracker->getTimeframeCalculator()
                ->buildTimePeriodWithoutWeekendForArtifactChartRendering(
                    $artifact_from_target_tracker,
                    $user,
                    $logger
                );
        } catch (
            ArtifactHasTooManyLinksToArtifactsOfTargetTracker |
            ArtifactHasNoLinkToArtifactOfTargetTracker $exception
        ) {
            throw new \Tracker_FormElement_Chart_Field_Exception(
                $exception->getMessage()
            );
        }
    }

    public function exportToXML(\SimpleXMLElement $root, array $xml_mapping): void
    {
    }

    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe
    {
        return null;
    }

    public function save(\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return false;
    }

    public function isFieldUsed(\Tracker_FormElement_Field $field): bool
    {
        if (! ($field instanceof \Tracker_FormElement_Field_ArtifactLink)) {
            return false;
        }

        $field_tracker_id = $field->getTrackerId();

        return $field_tracker_id === $this->tracker->getId()
            || $field_tracker_id === $this->semantic_timeframe_implied_from_tracker->getTracker()->getId();
    }

    public function isDefined(): bool
    {
        return true;
    }

    /**
     * @throws ArtifactHasTooManyLinksToArtifactsOfTargetTracker
     * @throws ArtifactHasNoLinkToArtifactOfTargetTracker
     */
    private function getReverselyLinkedArtifactFromTracker(Artifact $artifact, \PFUser $user): Artifact
    {
        $implied_from_tracker       = $this->semantic_timeframe_implied_from_tracker->getTracker();
        $reversely_linked_artifacts = $this->links_retriever->retrieveReverseLinksFromTracker($artifact, $user, $implied_from_tracker);
        $nb_links                   = count($reversely_linked_artifacts);

        if ($nb_links > 1) {
            throw new ArtifactHasTooManyLinksToArtifactsOfTargetTracker($artifact, $implied_from_tracker);
        }

        if ($nb_links === 0) {
            throw new ArtifactHasNoLinkToArtifactOfTargetTracker($artifact, $implied_from_tracker);
        }

        return $reversely_linked_artifacts[0];
    }
}
