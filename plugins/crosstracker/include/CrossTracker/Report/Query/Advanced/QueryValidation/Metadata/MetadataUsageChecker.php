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
use Tracker_FormElementFactory;
use Tracker_Semantic_ContributorDao;
use Tracker_Semantic_DescriptionDao;
use Tracker_Semantic_StatusDao;
use Tracker_Semantic_TitleDao;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidComparisonCollectorParameters;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class MetadataUsageChecker
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var bool[]
     */
    private $cache_already_checked;
    /**
     * @var Tracker_Semantic_TitleDao
     */
    private $title_dao;
    /**
     * @var Tracker_Semantic_DescriptionDao
     */
    private $description_dao;
    /**
     * @var Tracker_Semantic_StatusDao
     */
    private $status_dao;
    /**
     * @var Tracker_Semantic_ContributorDao
     */
    private $assigned_to_dao;

    public function __construct(
        Tracker_FormElementFactory $form_element_factory,
        Tracker_Semantic_TitleDao $title_dao,
        Tracker_Semantic_DescriptionDao $description_dao,
        Tracker_Semantic_StatusDao $status_dao,
        Tracker_Semantic_ContributorDao $assigned_to_dao,
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->title_dao            = $title_dao;
        $this->description_dao      = $description_dao;
        $this->status_dao           = $status_dao;
        $this->assigned_to_dao      = $assigned_to_dao;

        $this->cache_already_checked = [];
    }

    /**
     * @throws DescriptionIsMissingInAtLeastOneTrackerException
     * @throws StatusIsMissingInAtLeastOneTrackerException
     * @throws SubmittedOnIsMissingInAtLeastOneTrackerException
     * @throws TitleIsMissingInAtLeastOneTrackerException
     * @throws LastUpdateDateIsMissingInAtLeastOneTrackerException
     * @throws SubmittedByIsMissingInAtLeastOneTrackerException
     * @throws LastUpdateByIsMissingInAtLeastOneTrackerException
     * @throws AssignedToIsMissingInAtLeastOneTrackerException
     */
    public function checkMetadataIsUsedByAllTrackers(
        Metadata $metadata,
        InvalidComparisonCollectorParameters $collector_parameters,
    ) {
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
    private function checkTitleIsUsedByAllTrackers(array $trackers_id)
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
    private function checkDescriptionIsUsedByAllTrackers(array $trackers_id)
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
    private function checkStatusIsUsedByAllTrackers(array $trackers_id)
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
    private function checkSubmittedOnIsUsedByAllTrackers(array $trackers, PFUser $user)
    {
        $count = $this->getNumberOfReadableFieldByType($trackers, $user, 'subon');
        if ($count > 0) {
            throw new SubmittedOnIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param Tracker[] $trackers
     * @throws LastUpdateDateIsMissingInAtLeastOneTrackerException
     */
    private function checkLastUpdateDateIsUsedByAllTrackers(array $trackers, PFUser $user)
    {
        $count = $this->getNumberOfReadableFieldByType($trackers, $user, 'lud');
        if ($count > 0) {
            throw new LastUpdateDateIsMissingInAtLeastOneTrackerException($count);
        }
    }

    private function checkSubmittedByIsUsedByAllTracker(array $trackers, PFUser $user)
    {
        $count = $this->getNumberOfReadableFieldByType($trackers, $user, 'subby');
        if ($count > 0) {
            throw new SubmittedByIsMissingInAtLeastOneTrackerException($count);
        }
    }

    private function checkLastUpdateByIsUsedByAllTracker(array $trackers, PFUser $user)
    {
        $count = $this->getNumberOfReadableFieldByType($trackers, $user, 'luby');
        if ($count > 0) {
            throw new LastUpdateByIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param array $trackers
     * @throws AssignedToIsMissingInAtLeastOneTrackerException
     */
    private function checkAssignedToIsUsedByAllTracker(array $trackers)
    {
        $count = $this->assigned_to_dao->getNbOfTrackerWithoutSemanticContributorDefined($trackers);
        if ($count > 0) {
            throw new AssignedToIsMissingInAtLeastOneTrackerException($count);
        }
    }

    /**
     * @param \Tracker_FormElement[] $fields
     * @return bool
     */
    private function isThereAtLeastOneReadableField(array $fields, PFUser $user)
    {
        foreach ($fields as $field) {
            if ($field->userCanRead($user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $trackers
     * @param $type
     * @return int
     */
    private function getNumberOfReadableFieldByType($trackers, PFUser $user, $type)
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
