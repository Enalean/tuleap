<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Git\CommitStatus;

final class CommitStatusWithKnownStatus implements CommitStatus
{
    public const int STATUS_SUCCESS         = 0;
    public const string STATUS_SUCCESS_NAME = 'success';
    public const int STATUS_FAILURE         = 1;
    public const string STATUS_FAILURE_NAME = 'failure';
    public const int STATUS_PENDING         = 2;
    public const string STATUS_PENDING_NAME = 'pending';

    /**
     * @var int
     */
    private $status;
    /**
     * @var \DateTime
     */
    private $date;

    public function __construct($status, \DateTimeImmutable $date)
    {
        switch ($status) {
            case self::STATUS_SUCCESS:
            case self::STATUS_FAILURE:
            case self::STATUS_PENDING:
                break;
            default:
                throw new \DomainException("Commit status $status is unknown");
        }
        $this->status = $status;
        $this->date   = $date;
    }

    /**
     * @return CommitStatusWithKnownStatus
     */
    public static function buildFromStatusName($status_name, \DateTimeImmutable $date)
    {
        switch ($status_name) {
            case self::STATUS_SUCCESS_NAME:
                $status = self::STATUS_SUCCESS;
                break;
            case self::STATUS_FAILURE_NAME:
                $status = self::STATUS_FAILURE;
                break;
            case self::STATUS_PENDING_NAME:
                $status = self::STATUS_PENDING;
                break;
            default:
                throw new \DomainException("Commit status $status_name is unknown");
        }
        return new self($status, $date);
    }

    /**
     * @return int
     */
    public function getStatusId()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    #[\Override]
    public function getStatusName()
    {
        switch ($this->status) {
            case self::STATUS_SUCCESS:
                return self::STATUS_SUCCESS_NAME;
            case self::STATUS_FAILURE:
                return self::STATUS_FAILURE_NAME;
            case self::STATUS_PENDING:
                return self::STATUS_PENDING_NAME;
            default:
                throw new \DomainException("Commit status $this->status is unknown");
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    #[\Override]
    public function getDate()
    {
        return $this->date;
    }
}
