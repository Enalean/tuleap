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
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;

final class RefinedTopMilestoneRequest
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
     * @var ISearchOnStatus
     * @psalm-readonly
     */
    private $status_query;
    /**
     * @var PeriodQuery|null
     * @psalm-readonly
     */
    private $period_query;

    private function __construct(
        RawTopMilestoneRequest $raw_request,
        ISearchOnStatus $status_query,
        ?PeriodQuery $period_query
    ) {
        $this->user                = $raw_request->getUser();
        $this->project             = $raw_request->getProject();
        $this->limit               = $raw_request->getLimit();
        $this->offset              = $raw_request->getOffset();
        $this->order               = $raw_request->getOrder();
        $this->status_query        = $status_query;
        $this->period_query        = $period_query;
    }

    public static function withStatusQuery(
        RawTopMilestoneRequest $raw_request,
        ISearchOnStatus $status_query
    ): self {
        return new self($raw_request, $status_query, null);
    }

    public static function withPeriodQuery(
        RawTopMilestoneRequest $raw_request,
        PeriodQuery $period_query
    ): self {
        return new self($raw_request, new StatusOpen(), $period_query);
    }

    /**
     * @psalm-mutation-free
     */
    public function getStatusFilter(): ISearchOnStatus
    {
        return $this->status_query;
    }

    /**
     * @psalm-mutation-free
     */
    public function shouldFilterFutureMilestones(): bool
    {
        if (! $this->period_query) {
            return false;
        }
        return $this->period_query->isFuture();
    }

    /**
     * @psalm-mutation-free
     */
    public function shouldFilterCurrentMilestones(): bool
    {
        if (! $this->period_query) {
            return false;
        }
        return $this->period_query->isCurrent();
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
