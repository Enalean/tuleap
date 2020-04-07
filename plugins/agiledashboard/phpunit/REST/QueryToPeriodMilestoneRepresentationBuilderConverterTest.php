<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\CurrentMilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\Milestone\FutureMilestoneRepresentationBuilder;

class QueryToPeriodMilestoneRepresentationBuilderConverterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var QueryToPeriodMilestoneRepresentationBuilderConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new QueryToPeriodMilestoneRepresentationBuilderConverter(
            Mockery::mock(FutureMilestoneRepresentationBuilder::class),
            Mockery::mock(CurrentMilestoneRepresentationBuilder::class)
        );
    }

    public function testConvertsFutureToFutureBuilder(): void
    {
        $this->assertInstanceOf(FutureMilestoneRepresentationBuilder::class, $this->converter->convert('{\"period\":\"future\"}'));
    }

    public function testConvertsCurrentToCurrentBuilder(): void
    {
        $this->assertInstanceOf(CurrentMilestoneRepresentationBuilder::class, $this->converter->convert('{\"period\":\"current\"}'));
    }

    public function testThrowsExceptionIfEmptyString(): void
    {
        $this->expectExceptionObject(MalformedQueryParameterException::invalidQueryPeriodParameter());

        $this->converter->convert('');
    }

    public function testThrowsExceptionIfEmptyObject(): void
    {
        $this->expectExceptionObject(MalformedQueryParameterException::invalidQueryPeriodParameter());

        $this->converter->convert('{}');
    }

    public function testThrowsExceptionIfPeriodKeyIsMalformed(): void
    {
        $this->expectExceptionObject(MalformedQueryParameterException::invalidQueryPeriodParameter());

        $this->converter->convert('{\"perIod\":\"closed\"}');
    }

    public function testThrowsExceptionIfPeriodValueIsMalformed(): void
    {
        $this->expectExceptionObject(MalformedQueryParameterException::invalidQueryPeriodParameter());

        $this->converter->convert('{\"period\":\"FutUre\"}');
    }

    public function testThrowsExceptionIfNotAnObject(): void
    {
        $this->expectExceptionObject(MalformedQueryParameterException::invalidQueryPeriodParameter());

        $this->converter->convert('future');
    }
}
