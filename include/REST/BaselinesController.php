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

namespace Tuleap\Baseline\REST;

use DateTime;
use DateTimeZone;
use DateUtils;
use Tracker;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tracker_ArtifactFactory;
use Tuleap\REST\I18NRestException;
use Tuleap\REST\UserManager;

class BaselinesController
{
    /**
     * @var UserManager
     */
    private $user_manager;

    /**
     * @var Tracker_Artifact_ChangesetFactory
     */
    private $changeset_factory;

    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;

    /**
     * @var FieldRepository
     */
    private $tracker_repository;

    /**
     * @var ArtifactPermissionsChecker
     */
    private $artifact_permissions_checker;

    public function __construct(
        UserManager $user_manager,
        Tracker_Artifact_ChangesetFactory $changeset_factory,
        Tracker_ArtifactFactory $artifact_factory,
        FieldRepository $tracker_repository,
        ArtifactPermissionsChecker $artifact_permissions_checker
    ) {
        $this->user_manager                 = $user_manager;
        $this->changeset_factory            = $changeset_factory;
        $this->artifact_factory             = $artifact_factory;
        $this->tracker_repository           = $tracker_repository;
        $this->artifact_permissions_checker = $artifact_permissions_checker;
    }

    /**
     * @throws I18NRestException 403
     * @throws I18NRestException 404
     * @throws \Luracast\Restler\RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function getByArtifactIdAndDate(int $artifact_id, string $last_modification_date_before_baseline_date
    ): SimplifiedBaselineRepresentation {

        $date_time = $this->parseDate($last_modification_date_before_baseline_date, "Y-m-d");
        $timestamp = $this->setMidnight($date_time)
            ->getTimestamp();

        $artifact = $this->artifact_factory->getArtifactById($artifact_id);
        if ($artifact === null) {
            throw new I18NRestException(
                404,
                sprintf(
                    dgettext('tuleap-baseline', 'No artifact found with id %u')
                    , $artifact_id
                )
            );
        }

        $this->artifact_permissions_checker->checkRead($artifact);

        $changeSet = $this->changeset_factory->getChangesetAtTimestamp(
            $artifact,
            $timestamp
        );
        if ($changeSet === null) {
            throw new I18NRestException(
                404,
                sprintf(
                    dgettext('tuleap-baseline', 'No changetset found at timestamp %u')
                    , $timestamp
                )
            );
        }

        $tracker                                     = $artifact->getTracker();
        $title                                       = $this->getTrackerTitle($tracker, $changeSet);
        $description                                 = $this->getTrackerDescription($tracker, $changeSet);
        $status                                      = $this->getTrackerStatus($tracker, $changeSet);
        $last_modification_date_before_baseline_date = $changeSet->getSubmittedOn();

        return new SimplifiedBaselineRepresentation(
            $title,
            $description,
            $status,
            $last_modification_date_before_baseline_date
        );
    }

    /**
     * @throws I18NRestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    private function parseDate(string $date, string $format): DateTime
    {
        $user_timezone_code   = $this->user_manager->getCurrentUser()->getTimezone();
        $user_timezone        = new DateTimeZone($user_timezone_code);
        $date_time_or_failure = DateTime::createFromFormat($format, $date, $user_timezone);
        if ($date_time_or_failure === false) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-baseline', 'Invalid date: %s. Expected format: %s')
                    , $date
                    , $format
                )
            );
        }
        return $date_time_or_failure;
    }

    private function setMidnight(DateTime $date_time): DateTime
    {
        return $date_time->setTime(0, 0, 0, 0);
    }

    private function getTrackerTitle(Tracker $tracker, Tracker_Artifact_Changeset $changeSet): ?string
    {
        $title_field = $this->tracker_repository->findTitleByTracker($tracker);
        if ($title_field === null) {
            return null;
        }

        return $changeSet->getValue($title_field)->getValue();
    }

    private function getTrackerDescription(Tracker $tracker, Tracker_Artifact_Changeset $changeSet): ?string
    {
        $description_field = $this->tracker_repository->findDescriptionByTracker($tracker);
        if ($description_field === null) {
            return null;
        }

        return $changeSet->getValue($description_field)->getValue();
    }

    private function getTrackerStatus(Tracker $tracker, Tracker_Artifact_Changeset $changeSet): ?string
    {
        $status_field = $this->tracker_repository->findStatusByTracker($tracker);
        if ($status_field === null) {
            return null;
        }

        return $status_field->getFirstValueFor($changeSet);
    }
}
