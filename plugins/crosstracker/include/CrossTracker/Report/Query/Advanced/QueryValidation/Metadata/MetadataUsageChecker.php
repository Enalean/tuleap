<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryValidation\Metadata;

use PFUser;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class MetadataUsageChecker implements CheckMetadataUsage
{
    /**
     * @var bool[]
     */
    private array $cache_already_checked;

    public function __construct(
        private readonly \Tracker_FormElementFactory $form_element_factory,
        private readonly \Tracker_Semantic_TitleDao $title_dao,
        private readonly \Tracker_Semantic_DescriptionDao $description_dao,
        private readonly \Tracker_Semantic_StatusDao $status_dao,
        private readonly \Tracker_Semantic_ContributorDao $assigned_to_dao,
    ) {
        $this->cache_already_checked = [];
    }

    public function checkMetadataIsUsedByAllTrackers(
        Metadata $metadata,
        InvalidComparisonCollectorParameters $collector_parameters,
    ): void {
        if (isset($this->cache_already_checked[$metadata->getName()])) {
            return;
        }
        $this->cache_already_checked[$metadata->getName()] = true;

        switch ($metadata->getName()) {
            case AllowedMetadata::TITLE:
                $this->checkTitleIsUsedByAllTrackers($collector_parameters->getTrackerIds());
                break;
            case AllowedMetadata::DESCRIPTION:
                $this->checkDescriptionIsUsedByAllTrackers($collector_parameters->getTrackerIds());
                break;
            case AllowedMetadata::STATUS:
                $this->checkStatusIsUsedByAllTrackers($collector_parameters->getTrackerIds());
                break;
            case AllowedMetadata::SUBMITTED_ON:
                $this->checkSubmittedOnIsUsedByAllTrackers(
                    $collector_parameters->getTrackers(),
                    $collector_parameters->getUser()
                );
                break;
            case AllowedMetadata::LAST_UPDATE_DATE:
                $this->checkLastUpdateDateIsUsedByAllTrackers(
                    $collector_parameters->getTrackers(),
                    $collector_parameters->getUser()
                );
                break;
            case AllowedMetadata::SUBMITTED_BY:
                $this->checkSubmittedByIsUsedByAllTracker(
                    $collector_parameters->getTrackers(),
                    $collector_parameters->getUser()
                );
                break;
            case AllowedMetadata::LAST_UPDATE_BY:
                $this->checkLastUpdateByIsUsedByAllTracker(
                    $collector_parameters->getTrackers(),
                    $collector_parameters->getUser()
                );
                break;
            case AllowedMetadata::ASSIGNED_TO:
                $this->checkAssignedToIsUsedByAllTracker(
                    $collector_parameters->getTrackerIds()
                );
                break;
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws TitleIsMissingInAtLeastOneTrackerException
     */
    private function checkTitleIsUsedByAllTrackers(array $trackers_id): void
    {
        $count = $this->title_dao->getNbOfTrackerWithoutSemanticTitleDefined($trackers_id);
        if ($count > 0) {
            throw new TitleIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws DescriptionIsMissingInAtLeastOneTrackerException
     */
    private function checkDescriptionIsUsedByAllTrackers(array $trackers_id): void
    {
        $count = $this->description_dao->getNbOfTrackerWithoutSemanticDescriptionDefined($trackers_id);
        if ($count > 0) {
            throw new DescriptionIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws StatusIsMissingInAtLeastOneTrackerException
     */
    private function checkStatusIsUsedByAllTrackers(array $trackers_id): void
    {
        $count = $this->status_dao->getNbOfTrackerWithoutSemanticStatusDefined($trackers_id);
        if ($count > 0) {
            throw new StatusIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param Tracker[] $trackers
     * @throws SubmittedOnIsMissingInAtLeastOneTrackerException
     */
    private function checkSubmittedOnIsUsedByAllTrackers(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'subon');
        if ($count > 0) {
            throw new SubmittedOnIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param Tracker[] $trackers
     * @throws LastUpdateDateIsMissingInAtLeastOneTrackerException
     */
    private function checkLastUpdateDateIsUsedByAllTrackers(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'lud');
        if ($count > 0) {
            throw new LastUpdateDateIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @throws SubmittedByIsMissingInAtLeastOneTrackerException
     */
    private function checkSubmittedByIsUsedByAllTracker(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'subby');
        if ($count > 0) {
            throw new SubmittedByIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @throws LastUpdateByIsMissingInAtLeastOneTrackerException
     */
    private function checkLastUpdateByIsUsedByAllTracker(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'luby');
        if ($count > 0) {
            throw new LastUpdateByIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param array $trackers
     * @throws AssignedToIsMissingInAtLeastOneTrackerException
     */
    private function checkAssignedToIsUsedByAllTracker(array $trackers): void
    {
        $count = $this->assigned_to_dao->getNbOfTrackerWithoutSemanticContributorDefined($trackers);
        if ($count > 0) {
            throw new AssignedToIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param \Tracker_FormElement[] $fields
     */
    private function isThereAtLeastOneReadableField(array $fields, PFUser $user): bool
    {
        foreach ($fields as $field) {
            if ($field->userCanRead($user)) {
                return true;
            }
        }

        return false;
    }

    private function getNumberOfFieldsUserCannotReadByType(array $trackers, PFUser $user, string $type): int
    {
        $count = 0;
        foreach ($trackers as $tracker) {
            $fields = $this->form_element_factory->getFormElementsByType($tracker, $type, true);
            if (empty($fields) || ! $this->isThereAtLeastOneReadableField($fields, $user)) {
                $count++;
            }
        }

        return $count;
    }
}
