<?php
/**
 * Copyright Enalean (c) 2019 - Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Timetracking\Report;

use DateTime;
use Luracast\Restler\RestException;
use Tuleap\REST\JsonDecoder;
use Tuleap\Timetracking\REST\v1\TimetrackingDatesExtractor;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TimetrackingDatesExtractorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var TimetrackingDatesExtractor
     */
    private $dates_extractor;

    public function setUp(): void
    {
        parent::setUp();

        $this->dates_extractor = new TimetrackingDatesExtractor(new JsonDecoder());
    }

    public function testWhenQueryIsNotProvidedThenStartDateIsAMonthAgo(): void
    {
        $date       = new DateTime();
        $end_date   = $date->format('Y-m-d');
        $start_date = $date->modify('-1 month')->format('Y-m-d');

        $result = $this->dates_extractor->getDatesFromRoute(null);

        self::assertEquals($end_date, $result->getEndDate()->format('Y-m-d'));
        self::assertEquals($start_date, $result->getStartDate()->format('Y-m-d'));
    }

    public function testWhenStartDateIsNotProvidedThenStartDateIsAMonthAgo(): void
    {
        $date       = new DateTime();
        $end_date   = $date->format('Y-m-d');
        $start_date = $date->modify('-1 month')->format('Y-m-d');

        $query = json_encode(['trackers_id' => [1, 2, 3]]);

        $result = $this->dates_extractor->getDatesFromRoute($query);
        self::assertEquals($end_date, $result->getEndDate()->format('Y-m-d'));
        self::assertEquals($start_date, $result->getStartDate()->format('Y-m-d'));
    }

    public function testWhenStartDateIsProvidedByUserThenItReturnsTheUserStartDate(): void
    {
        $query = json_encode(['start_date' => '2010-03-01T00:00:00+01', 'end_date'   => '2019-03-21T00:00:00+01']);

        $result = $this->dates_extractor->getDatesFromRoute($query);
        self::assertEquals('2010-03-01T00:00:00+01:00', $result->getStartDate()->format(\DateTimeInterface::ATOM));
        self::assertEquals('2019-03-21T00:00:00+01:00', $result->getEndDate()->format(\DateTimeInterface::ATOM));
    }

    public function testItThrowsAnExceptionWhenDatesAreNotCorrectlyFormated(): void
    {
        $query = json_encode(['end_date' => '2019-03-21T00:00:00+01']);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage('Please provide a start date and an end date');

        $this->dates_extractor->getDatesFromRoute($query);
    }
}
