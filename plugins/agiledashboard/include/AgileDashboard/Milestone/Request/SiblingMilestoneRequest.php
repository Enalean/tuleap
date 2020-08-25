<?php
/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Milestone\Request;

use Tuleap\AgileDashboard\Milestone\Criterion\Status\ISearchOnStatus;

final class SiblingMilestoneRequest
{
    /**
     * @var \PFUser
     * @psalm-readonly
     */
    private $user;

    /**
     * @var \Planning_Milestone
     * @psalm-readonly
     */
    private $reference_milestone;

    /**
     * @var int
     * @psalm-readonly
     */
    private $limit;

    /**
     * @var int
     * @psalm-readonly
     */
    private $offset;

    /**
     * @var ISearchOnStatus
     * @psalm-readonly
     */
    private $status_query;

    public function __construct(
        \PFUser $user,
        \Planning_Milestone $reference_milestone,
        int $limit,
        int $offset,
        ISearchOnStatus $status_query
    ) {
        $this->user                = $user;
        $this->reference_milestone = $reference_milestone;
        $this->limit               = $limit;
        $this->offset              = $offset;
        $this->status_query        = $status_query;
    }

    /**
     * @psalm-mutation-free
     */
    public function getUser(): \PFUser
    {
        return $this->user;
    }

    /**
     * @psalm-mutation-free
     */
    public function getReferenceMilestone(): \Planning_Milestone
    {
        return $this->reference_milestone;
    }

    /**
     * @psalm-mutation-free
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @psalm-mutation-free
     */
    public function getOffset(): int
    {
        return $this->offset;
    }

    /**
     * @psalm-mutation-free
     */
    public function getStatusQuery(): ISearchOnStatus
    {
        return $this->status_query;
    }
}
