<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Event;

use PFUser;
use Tracker;
use Tracker_Report;
use Tuleap\Event\Dispatchable;

class TrackerReportProcessAdditionalQuery implements Dispatchable
{
    public const NAME = 'trackerReportProcessAdditionalQuery';

    /**
     * @var Tracker_Report
     */
    private $tracker_report;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var array
     */
    private $additional_criteria;

    /**
     * @var array
     */
    private $result = [];

    /**
     * @var bool
     */
    private $search_performed = false;

    public function __construct(
        Tracker_Report $tracker_report,
        Tracker $tracker,
        PFUser $user,
        array $additional_criteria
    ) {
        $this->tracker_report      = $tracker_report;
        $this->tracker             = $tracker;
        $this->user                = $user;
        $this->additional_criteria = $additional_criteria;
    }

    public function getTracker(): Tracker
    {
        return $this->tracker;
    }

    public function getUser(): PFUser
    {
        return $this->user;
    }

    /**
     * @return array
     */
    public function getAdditionalCriteria(): array
    {
        return $this->additional_criteria;
    }

    /**
     * @return array
     */
    public function getResult(): array
    {
        return $this->result;
    }

    /**
     * @param array $result
     */
    public function addResult(array $result): void
    {
        $this->result[] = $result;
    }

    public function isSearchPerformed(): bool
    {
        return $this->search_performed;
    }

    public function setSearchIsPerformed(): void
    {
        $this->search_performed = true;
    }

    public function getTrackerReport(): Tracker_Report
    {
        return $this->tracker_report;
    }
}
