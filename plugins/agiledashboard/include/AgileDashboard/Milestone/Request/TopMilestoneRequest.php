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

final class TopMilestoneRequest
{
    /**
     * @var \PFUser
     * @psalm-readonly
     */
    private $user;
    /**
     * @var \Project
     * @psalm-readonly
     */
    private $project;
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
     * @var string
     * @psalm-readonly
     */
    private $order;
    /**
     * @var FilteringQuery
     * @psalm-readonly
     */
    private $filtering_query;

    public function __construct(
        \PFUser $user,
        \Project $project,
        int $limit,
        int $offset,
        string $order,
        FilteringQuery $filtering_query
    ) {
        $this->user            = $user;
        $this->project         = $project;
        $this->limit           = $limit;
        $this->offset          = $offset;
        $this->order           = $order;
        $this->filtering_query = $filtering_query;
    }

    /**
     * @psalm-mutation-free
     */
    public function getStatusFilter(): ISearchOnStatus
    {
        return $this->filtering_query->getStatusFilter();
    }

    /**
     * @psalm-mutation-free
     */
    public function shouldFilterFutureMilestones(): bool
    {
        return $this->filtering_query->isFuturePeriod();
    }

    /**
     * @psalm-mutation-free
     */
    public function shouldFilterCurrentMilestones(): bool
    {
        return $this->filtering_query->isCurrentPeriod();
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
    public function getProject(): \Project
    {
        return $this->project;
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
    public function getOrder(): string
    {
        return $this->order;
    }
}
