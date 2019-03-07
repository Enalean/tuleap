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

declare(strict_types=1);

namespace Tuleap\Baseline\REST;

use DateTime;
use DateTimeZone;
use Tuleap\Baseline\BaselineService;
use Tuleap\Baseline\ChangesetNotFoundException;
use Tuleap\Baseline\CurrentUserProvider;
use Tuleap\Baseline\MilestoneRepository;
use Tuleap\Baseline\NotAuthorizedException;
use Tuleap\Baseline\TransientBaseline;
use Tuleap\REST\I18NRestException;

class BaselineController
{
    /**
     * @var CurrentUserProvider
     */
    private $current_user_provider;

    /**
     * @var MilestoneRepository
     */
    private $milestone_repository;

    /**
     * @var BaselineService
     */
    private $baseline_service;

    public function __construct(
        CurrentUserProvider $current_user_provider,
        MilestoneRepository $milestone_repository,
        BaselineService $baseline_service
    ) {
        $this->current_user_provider = $current_user_provider;
        $this->milestone_repository  = $milestone_repository;
        $this->baseline_service      = $baseline_service;
    }

    /**
     * @throws I18NRestException 404
     * @throws I18NRestException 403
     * @throws \Luracast\Restler\RestException
     * @throws \Rest_Exception_InvalidTokenException
     * @throws \User_PasswordExpiredException
     * @throws \User_StatusDeletedException
     * @throws \User_StatusInvalidException
     * @throws \User_StatusPendingException
     * @throws \User_StatusSuspendedException
     */
    public function post(string $name, int $milestone_id): BaselineRepresentation
    {
        $milestone = $this->milestone_repository->findById($milestone_id);
        if ($milestone === null) {
            throw new I18NRestException(
                404,
                sprintf(
                    dgettext('tuleap-baseline', 'No milestone found with id %u'),
                    $milestone_id
                )
            );
        }

        $baseline = new TransientBaseline($name, $milestone);
        try {
            $created_baseline = $this->baseline_service->create($baseline);
            return BaselineRepresentation::fromBaseline($created_baseline);
        } catch (NotAuthorizedException $exception) {
            throw new I18NRestException(
                403,
                sprintf(
                    dgettext('tuleap-baseline', 'This operation is not allowed. %s'),
                    $exception->getMessage()
                )
            );
        }
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
    public function getByMilestoneIdAndDate(
        int $milestone_id,
        string $last_modification_date_before_baseline_date
    ): SimplifiedBaselineRepresentation {

        $date = $this->parseDate($last_modification_date_before_baseline_date, "Y-m-d");

        $milestone = $this->milestone_repository->findById($milestone_id);
        if ($milestone === null) {
            throw new I18NRestException(
                404,
                sprintf(
                    dgettext('tuleap-baseline', 'No milestone found with id %u'),
                    $milestone_id
                )
            );
        }

        try {
            $simplified_baseline = $this->baseline_service->findSimplified(
                $milestone,
                $this->setMidnight($date)
            );
            return new SimplifiedBaselineRepresentation(
                $simplified_baseline->getTitle(),
                $simplified_baseline->getDescription(),
                $simplified_baseline->getStatus(),
                $simplified_baseline->getLastModificationDateBeforeBaselineDate()->getTimestamp()
            );
        } catch (NotAuthorizedException $exception) {
            throw new I18NRestException(
                403,
                sprintf(
                    dgettext('tuleap-baseline', 'This operation is not allowed. %s'),
                    $exception->getMessage()
                )
            );
        } catch (ChangesetNotFoundException $exception) {
            throw new I18NRestException(
                404,
                sprintf(
                    dgettext('tuleap-baseline', 'No changetset found before %s'),
                    $exception->getDate()->format('Y-m-d H:i:s')
                )
            );
        }
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
    private function parseDate(
        string $date,
        string $format
    ): DateTime {
        $user_timezone_code   = $this->current_user_provider->getUser()->getTimezone();
        $user_timezone        = new DateTimeZone($user_timezone_code);
        $date_time_or_failure = DateTime::createFromFormat($format, $date, $user_timezone);
        if ($date_time_or_failure === false) {
            throw new I18NRestException(
                400,
                sprintf(
                    dgettext('tuleap-baseline', 'Invalid date: %s. Expected format: %s'),
                    $date,
                    $format
                )
            );
        }
        return $date_time_or_failure;
    }

    private function setMidnight(DateTime $date_time): DateTime
    {
        return $date_time->setTime(0, 0, 0, 0);
    }
}
