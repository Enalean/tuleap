<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Timetracking\Widget\People;

use DateTimeImmutable;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Timetracking\REST\v1\PeopleTimetracking\SearchQueryByWidgetId;
use Tuleap\Timetracking\REST\v1\PeopleTimetracking\WidgetNotFoundFault;

final class UserTimesTimeframeRetriever implements RetrieveUserTimesTimeframe
{
    public function __construct(
        private GetWidgetInformation $dao_widget,
        private SearchQueryByWidgetId $dao_query,
        private \DateTimeImmutable $now,
    ) {
    }

    #[\Override]
    public function getTimeframe(int $query_id, \PFUser $manager): Ok|Err
    {
        $widget_information = $this->dao_widget->getWidgetInformationFromQuery($query_id);
        if ($widget_information === null) {
            return Result::err(WidgetNotFoundFault::build());
        }
        if ((int) $widget_information['user_id'] !== (int) $manager->getId()) {
            return Result::err(WidgetNotFoundFault::build());
        }

        $query = $this->dao_query->searchQueryById($query_id);
        if ($query === null) {
            return Result::err(WidgetNotFoundFault::build());
        }

        if ($query['predefined_time_period'] !== null) {
            return $this->getTimeframeFromTimeperiod(PredefinedTimePeriod::from($query['predefined_time_period']));
        }

        return Result::ok(new UserTimesTimeframe(
            new DateTimeImmutable()->setTimestamp((int) $query['start_date']),
            new DateTimeImmutable()->setTimestamp((int) $query['end_date']),
        ));
    }

    private function getTimeframeFromTimeperiod(PredefinedTimePeriod $predefined_time_period): Ok|Err
    {
        return Result::ok(match ($predefined_time_period) {
            PredefinedTimePeriod::TODAY => new UserTimesTimeframe(
                $this->now,
                $this->now,
            ),
            PredefinedTimePeriod::YESTERDAY => new UserTimesTimeframe(
                $this->now->sub(new \DateInterval('P1D')),
                $this->now->sub(new \DateInterval('P1D')),
            ),
            PredefinedTimePeriod::LAST_7_DAYS => new UserTimesTimeframe(
                $this->now->sub(new \DateInterval('P7D')),
                $this->now,
            ),
            PredefinedTimePeriod::CURRENT_WEEK => new UserTimesTimeframe(
                $this->now->modify('monday this week'),
                $this->now->modify('sunday this week'),
            ),
            PredefinedTimePeriod::LAST_WEEK => new UserTimesTimeframe(
                $this->now->modify('monday last week'),
                $this->now->modify('sunday last week'),
            ),
            PredefinedTimePeriod::LAST_MONTH => new UserTimesTimeframe(
                $this->now->modify('first day of last month'),
                $this->now->modify('last day of last month'),
            ),
        });
    }
}
