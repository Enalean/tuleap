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

namespace Tuleap\CrossTracker\Query\Advanced\QueryValidation\Metadata;

use LogicException;
use PFUser;
use Tracker;
use Tracker_FormElement;
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use Tuleap\CrossTracker\Query\Advanced\AllowedMetadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class MetadataUsageChecker implements CheckMetadataUsage
{
    /**
     * @var bool[]
     */
    private array $cache_already_checked;

    public function __construct(
        private readonly Tracker_FormElementFactory $form_element_factory,
        private readonly Tracker_Semantic_TitleDao $title_dao,
        private readonly Tracker_Semantic_DescriptionDao $description_dao,
        private readonly Tracker_Semantic_StatusDao $status_dao,
        private readonly Tracker_Semantic_ContributorDao $assigned_to_dao,
    ) {
        $this->cache_already_checked = [];
    }

    public function checkMetadataIsUsedByAllTrackers(
        Metadata $metadata,
        array $trackers,
        PFUser $user,
    ): void {
        if (isset($this->cache_already_checked[$metadata->getName()])) {
            return;
        }
        $this->cache_already_checked[$metadata->getName()] = true;

        match ($metadata->getName()) {
            AllowedMetadata::TITLE            => $this->checkTitleIsUsedByAtLeastOneTracker(self::getTrackerIds($trackers)),
            AllowedMetadata::DESCRIPTION      => $this->checkDescriptionIsUsedByAtLeastOneTracker(self::getTrackerIds($trackers)),
            AllowedMetadata::STATUS           => $this->checkStatusIsUsedByAtLeastOneTracker(self::getTrackerIds($trackers)),
            AllowedMetadata::ASSIGNED_TO      => $this->checkAssignedToIsUsedByAtLeastOneTracker(self::getTrackerIds($trackers)),

            AllowedMetadata::SUBMITTED_ON     => $this->checkSubmittedOnIsUsedByAtLeastOneTracker($trackers, $user),
            AllowedMetadata::LAST_UPDATE_DATE => $this->checkLastUpdateDateIsUsedByAtLeastOneTracker($trackers, $user),
            AllowedMetadata::SUBMITTED_BY     => $this->checkSubmittedByIsUsedByAtLeastOneTracker($trackers, $user),
            AllowedMetadata::LAST_UPDATE_BY   => $this->checkLastUpdateByIsUsedByAtLeastOneTracker($trackers, $user),
            AllowedMetadata::ID               => $this->checkArtifactIdIsUsedByAtLeastOneTracker($trackers, $user),

            AllowedMetadata::PROJECT_NAME,
            AllowedMetadata::TRACKER_NAME     => null, // Nothing to check
            AllowedMetadata::PRETTY_TITLE     => $this->checkPrettyTitleIsUsedByAtLeastOneTracker($trackers, $user),
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }

    /**
     * @param Tracker[] $trackers
     * @return int[]
     */
    private static function getTrackerIds(array $trackers): array
    {
        return array_map(static fn(Tracker $tracker) => $tracker->getId(), $trackers);
    }

    /**
     * @param int[] $trackers_id
     * @throws TitleIsMissingInAllTrackersException
     */
    private function checkTitleIsUsedByAtLeastOneTracker(array $trackers_id): void
    {
        $count = $this->title_dao->getNbOfTrackerWithoutSemanticTitleDefined($trackers_id);
        if ($count === count($trackers_id)) {
            throw new TitleIsMissingInAllTrackersException();
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws DescriptionIsMissingInAllTrackersException
     */
    private function checkDescriptionIsUsedByAtLeastOneTracker(array $trackers_id): void
    {
        $count = $this->description_dao->getNbOfTrackerWithoutSemanticDescriptionDefined($trackers_id);
        if ($count === count($trackers_id)) {
            throw new DescriptionIsMissingInAllTrackersException();
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws StatusIsMissingInAllTrackersException
     */
    private function checkStatusIsUsedByAtLeastOneTracker(array $trackers_id): void
    {
        $count = $this->status_dao->getNbOfTrackerWithoutSemanticStatusDefined($trackers_id);
        if ($count === count($trackers_id)) {
            throw new StatusIsMissingInAllTrackersException();
        }
    }

    /**
     * @param int[] $trackers_id
     * @throws AssignedToIsMissingInAllTrackersException
     */
    private function checkAssignedToIsUsedByAtLeastOneTracker(array $trackers_id): void
    {
        $count = $this->assigned_to_dao->getNbOfTrackerWithoutSemanticContributorDefined($trackers_id);
        if ($count === count($trackers_id)) {
            throw new AssignedToIsMissingInAllTrackersException();
        }
    }

    /**
     * @param Tracker[] $trackers
     * @throws SubmittedOnIsMissingInAllTrackersException
     */
    private function checkSubmittedOnIsUsedByAtLeastOneTracker(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'subon');
        if ($count === count($trackers)) {
            throw new SubmittedOnIsMissingInAllTrackersException();
        }
    }

    /**
     * @param Tracker[] $trackers
     * @throws LastUpdateDateIsMissingInAllTrackersException
     */
    private function checkLastUpdateDateIsUsedByAtLeastOneTracker(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'lud');
        if ($count === count($trackers)) {
            throw new LastUpdateDateIsMissingInAllTrackersException();
        }
    }

    /**
     * @throws SubmittedByIsMissingInAllTrackersException
     */
    private function checkSubmittedByIsUsedByAtLeastOneTracker(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'subby');
        if ($count === count($trackers)) {
            throw new SubmittedByIsMissingInAllTrackersException();
        }
    }

    /**
     * @throws LastUpdateByIsMissingInAllTrackersException
     */
    private function checkLastUpdateByIsUsedByAtLeastOneTracker(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, 'luby');
        if ($count === count($trackers)) {
            throw new LastUpdateByIsMissingInAllTrackersException();
        }
    }

    /**
     * @throws ArtifactIdIsMissingInAllTrackersException
     */
    private function checkArtifactIdIsUsedByAtLeastOneTracker(array $trackers, PFUser $user): void
    {
        $count = $this->getNumberOfFieldsUserCannotReadByType($trackers, $user, Tracker_FormElementFactory::FIELD_ARTIFACT_ID_TYPE);
        if ($count === count($trackers)) {
            throw new ArtifactIdIsMissingInAllTrackersException();
        }
    }

    /**
     * @throws TitleIsMissingInAllTrackersException
     * @throws ArtifactIdIsMissingInAllTrackersException
     */
    private function checkPrettyTitleIsUsedByAtLeastOneTracker(array $trackers, PFUser $user): void
    {
        $this->checkTitleIsUsedByAtLeastOneTracker(self::getTrackerIds($trackers));
        $this->checkArtifactIdIsUsedByAtLeastOneTracker($trackers, $user);
    }

    /**
     * @param Tracker_FormElement[] $fields
     */
    private function isThereAtLeastOneReadableField(array $fields, PFUser $user): bool
    {
        if ($fields === []) {
            return true;
        }
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
            if (! $this->isThereAtLeastOneReadableField($fields, $user)) {
                $count++;
            }
        }

        return $count;
    }
}
