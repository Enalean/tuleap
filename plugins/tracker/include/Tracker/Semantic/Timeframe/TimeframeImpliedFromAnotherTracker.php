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
use Tuleap\Date\DatePeriodWithoutWeekEnd;
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
        LinksRetriever $links_retriever,
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
            dgettext('tuleap-tracker', 'Timeframes will be inherited from %s linking artifacts of this tracker.'),
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

    public function buildDatePeriodWithoutWeekendForChangesetForREST(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd
    {
        if ($changeset === null) {
            return DatePeriodWithoutWeekEnd::buildWithoutAnyDates();
        }

        try {
            $artifact_from_target_tracker = $this->getReverselyLinkedArtifactFromTracker($changeset->getArtifact(), $user);
            return $this->semantic_timeframe_implied_from_tracker->getTimeframeCalculator()
                ->buildDatePeriodWithoutWeekendForChangesetForREST(
                    $artifact_from_target_tracker->getLastChangeset(),
                    $user,
                    $logger
                );
        } catch (ArtifactHasTooManyLinksToArtifactsOfTargetTracker $exception) {
            return DatePeriodWithoutWeekEnd::buildFromNothingWithErrorMessage($exception->getMessage());
        } catch (ArtifactHasNoLinkToArtifactOfTargetTracker $exception) {
            return DatePeriodWithoutWeekEnd::buildWithoutAnyDates();
        }
    }

    public function buildDatePeriodWithoutWeekendForChangeset(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd
    {
        if ($changeset === null) {
            return DatePeriodWithoutWeekEnd::buildWithoutAnyDates();
        }

        try {
            $artifact_from_target_tracker = $this->getReverselyLinkedArtifactFromTracker($changeset->getArtifact(), $user);
            return $this->semantic_timeframe_implied_from_tracker->getTimeframeCalculator()
                ->buildDatePeriodWithoutWeekendForChangeset(
                    $artifact_from_target_tracker->getLastChangeset(),
                    $user,
                    $logger
                );
        } catch (ArtifactHasTooManyLinksToArtifactsOfTargetTracker $exception) {
            return DatePeriodWithoutWeekEnd::buildFromNothingWithErrorMessage($exception->getMessage());
        } catch (ArtifactHasNoLinkToArtifactOfTargetTracker $exception) {
            return DatePeriodWithoutWeekEnd::buildWithoutAnyDates();
        }
    }

    /**
     * @throws \Tracker_FormElement_Chart_Field_Exception
     */
    public function buildDatePeriodWithoutWeekendForChangesetChartRendering(?\Tracker_Artifact_Changeset $changeset, \PFUser $user, LoggerInterface $logger): DatePeriodWithoutWeekEnd
    {
        if ($changeset === null) {
            return DatePeriodWithoutWeekEnd::buildWithoutAnyDates();
        }

        try {
            $artifact_from_target_tracker = $this->getReverselyLinkedArtifactFromTracker($changeset->getArtifact(), $user);
            return $this->semantic_timeframe_implied_from_tracker->getTimeframeCalculator()
                ->buildDatePeriodWithoutWeekendForChangesetChartRendering(
                    $artifact_from_target_tracker->getLastChangeset(),
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
        $implied_from_tracker_id = $this->semantic_timeframe_implied_from_tracker->getTracker()->getId();

        $semantic = $root->addChild('semantic');
        $semantic->addAttribute('type', SemanticTimeframe::NAME);
        $semantic->addChild('inherited_from_tracker')->addAttribute('id', \Tracker::XML_ID_PREFIX . $implied_from_tracker_id);
    }

    public function exportToREST(\PFUser $user): ?IRepresentSemanticTimeframe
    {
        $implied_from_tracker = $this->semantic_timeframe_implied_from_tracker->getTracker();
        if (! $implied_from_tracker->userCanView($user)) {
            return null;
        }

        return new SemanticTimeframeImpliedFromAnotherTrackerRepresentation(
            $implied_from_tracker->getId()
        );
    }

    public function save(\Tracker $tracker, SemanticTimeframeDao $dao): bool
    {
        return $dao->save(
            $tracker->getId(),
            null,
            null,
            null,
            $this->semantic_timeframe_implied_from_tracker->getTracker()->getId()
        );
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
            throw new ArtifactHasNoLinkToArtifactOfTargetTracker();
        }

        return $reversely_linked_artifacts[0];
    }

    public function getTrackerFromWhichTimeframeIsImplied(): ?\Tracker
    {
        return $this->semantic_timeframe_implied_from_tracker->getTracker();
    }
}
