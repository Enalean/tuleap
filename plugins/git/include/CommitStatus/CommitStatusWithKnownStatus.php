<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
    const STATUS_SUCCESS      = 0;
    const STATUS_SUCCESS_NAME = 'success';
    const STATUS_FAILURE      = 1;
    const STATUS_FAILURE_NAME = 'failure';

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
    public function getStatusName()
    {
        switch ($this->status) {
            case self::STATUS_SUCCESS:
                return self::STATUS_SUCCESS_NAME;
            case self::STATUS_FAILURE:
                return self::STATUS_FAILURE_NAME;
            default:
                throw new \DomainException("Commit status $this->status is unknown");
        }
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getDate()
    {
        return $this->date;
    }
}
