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

namespace unit\AgileDashboard\Milestone\Request;

use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusClosed;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Request\MalformedQueryParameterException;
use Tuleap\AgileDashboard\Milestone\Request\RawTopMilestoneRequest;
use Tuleap\AgileDashboard\Milestone\Request\TopMilestoneRequestRefiner;
use Tuleap\Test\Builders\UserTestBuilder;

final class TopMilestoneRequestRefinerTest extends TestCase
{
    /**
     * @var TopMilestoneRequestRefiner
     */
    private $refiner;

    protected function setUp(): void
    {
        $this->refiner = new TopMilestoneRequestRefiner();
    }

    public function testItReturnsARequestWithAllStatusWhenQueryIsEmpty(): void
    {
        $raw_request = $this->buildRawRequest();
        $request     = $this->refiner->refineRawRequest($raw_request, '');

        $this->assertInstanceOf(StatusAll::class, $request->getStatusFilter());
    }

    public function testItReturnsARequestWithAllStatusWhenQueryIsEmptyObject(): void
    {
        $raw_request = $this->buildRawRequest();
        $request     = $this->refiner->refineRawRequest($raw_request, '{}');

        $this->assertInstanceOf(StatusAll::class, $request->getStatusFilter());
    }

    public function testItReturnsARequestWithFutureFilter(): void
    {
        $raw_request = $this->buildRawRequest();
        $request     = $this->refiner->refineRawRequest($raw_request, '{\"period\":\"future\"}');

        $this->assertTrue($request->shouldFilterFutureMilestones());
    }

    public function testItReturnsARequestWithCurrentFilter(): void
    {
        $raw_request = $this->buildRawRequest();
        $request     = $this->refiner->refineRawRequest($raw_request, '{\"period\":\"current\"}');

        $this->assertTrue($request->shouldFilterCurrentMilestones());
    }

    public function testItReturnsARequestWithOpenStatusFilter(): void
    {
        $raw_request = $this->buildRawRequest();
        $request     = $this->refiner->refineRawRequest($raw_request, '{\"status\":\"open\"}');

        $this->assertInstanceOf(StatusOpen::class, $request->getStatusFilter());
    }

    public function testItReturnsARequestWithClosedStatusFilter(): void
    {
        $raw_request = $this->buildRawRequest();
        $request     = $this->refiner->refineRawRequest($raw_request, '{\"status\":\"closed\"}');

        $this->assertInstanceOf(StatusClosed::class, $request->getStatusFilter());
    }

    /**
     * @dataProvider invalidQueryDataProvider
     */
    public function testItThrowsIfQueryIsInvalid(string $query): void
    {
        $raw_request = $this->buildRawRequest();

        $this->expectException(MalformedQueryParameterException::class);
        $this->refiner->refineRawRequest($raw_request, $query);
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

    private function buildRawRequest(): RawTopMilestoneRequest
    {
        return new RawTopMilestoneRequest(
            UserTestBuilder::aUser()->build(),
            \Project::buildForTest(),
            50,
            0,
            'asc'
        );
    }
}
