<?php
/**
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

use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusClosed;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;

final class FilteringQueryParserTest extends TestCase
{
    /**
     * @var FilteringQueryParser
     */
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new FilteringQueryParser();
    }

    public function testItReturnsARequestWithAllStatusWhenQueryIsEmpty(): void
    {
        $filter = $this->parser->parse('');
        $this->assertInstanceOf(StatusAll::class, $filter->getStatusFilter());
    }

    public function testItReturnsARequestWithAllStatusWhenQueryIsEmptyObject(): void
    {
        $filter = $this->parser->parse('{}');
        $this->assertInstanceOf(StatusAll::class, $filter->getStatusFilter());
    }

    public function testItReturnsARequestWithFutureFilter(): void
    {
        $filter = $this->parser->parse('{\"period\":\"future\"}');
        $this->assertTrue($filter->isFuturePeriod());
    }

    public function testItReturnsARequestWithCurrentFilter(): void
    {
        $filter = $this->parser->parse('{\"period\":\"current\"}');
        $this->assertTrue($filter->isCurrentPeriod());
    }

    public function testItReturnsARequestWithOpenStatusFilter(): void
    {
        $filter = $this->parser->parse('{\"status\":\"open\"}');
        $this->assertInstanceOf(StatusOpen::class, $filter->getStatusFilter());
    }

    public function testItReturnsARequestWithClosedStatusFilter(): void
    {
        $filter = $this->parser->parse('{\"status\":\"closed\"}');
        $this->assertInstanceOf(StatusClosed::class, $filter->getStatusFilter());
    }

    /**
     * @dataProvider invalidQueryDataProvider
     */
    public function testItThrowsIfQueryIsInvalid(string $query): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->parser->parse($query);
    }

    public function invalidQueryDataProvider(): array
    {
        return [
            'Null query'                          => ['null'],
            'Query is not a JSON object'          => ['future'],
            'Malformed period key'                => ['{\"perIod\":\"closed\"}'],
            'Malformed status key'                => ['{\"stAtus\":\"cloed\"}'],
            'Unknown period value'                => ['{\"period\":\"FutUre\"}'],
            'Unknown status value'                => ['{\"status\":\"cloSed\"}'],
            'Both status and period are provided' => ['{\"status\":\"closed\", \"period\":\"future\"}']
        ];
    }
}
