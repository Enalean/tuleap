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

use AgileDashboard_Milestone_MilestoneRepresentationBuilder;
use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\AgileDashboard\Milestone\FutureMilestoneRepresentationBuilder;
use Tuleap\AgileDashboard\Milestone\StatusMilestoneRepresentationBuilder;

class QueryToMilestoneRepresentationBuilderConverterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var QueryToMilestoneRepresentationBuilderConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new QueryToMilestoneRepresentationBuilderConverter(
            Mockery::mock(AgileDashboard_Milestone_MilestoneRepresentationBuilder::class),
            new QueryToFutureMilestoneRepresentationBuilderConverter(Mockery::mock(FutureMilestoneRepresentationBuilder::class)),
            new QueryToCriterionStatusConverter()
        );
    }

    public function testThrowsExceptionIfNull(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"} or {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('null');
    }


    public function testConvertsEmptyStringToStatusMilestoneRepresentationBuilder(): void
    {
        $this->converter->convert('');
        $this->assertInstanceOf(StatusMilestoneRepresentationBuilder::class, $this->converter->convert(''));
    }

    public function testConvertsEmptyObjectToStatusMilestoneRepresentationBuilder(): void
    {
        $this->assertInstanceOf(StatusMilestoneRepresentationBuilder::class, $this->converter->convert('{}'));
    }

    public function testConvertsEmptyObjectTFutureMilestoneRepresentationBuilder(): void
    {
        $this->assertInstanceOf(FutureMilestoneRepresentationBuilder::class, $this->converter->convert('{\"period\":\"future\"}'));
    }

    public function testThrowsExceptionIfPeriodKeyIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"} or {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('{\"perIod\":\"closed\"}');
    }

    public function testThrowsExceptionIfStatusKeyIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"} or {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('{\"stAtus\":\"cloed\"}');
    }

    public function testThrowsExceptionIfPeriodValueIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"}.');

        $this->converter->convert('{\"period\":\"FutUre\"}');
    }

    public function testThrowsExceptionIfStatusValueIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('{\"status\":\"cloSed\"}');
    }

    public function testThrowsExceptionIfNotAnObject(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"} or {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('future');
    }

    public function testThrowsExceptionIfQueryOnPeriodAndStatusAtTheSameTime(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"} or {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('{\"status\":\"closed\", \"period\":\"future\"}');
    }
}
