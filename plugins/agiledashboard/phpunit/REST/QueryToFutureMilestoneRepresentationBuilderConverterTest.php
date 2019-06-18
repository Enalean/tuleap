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
use Tuleap\AgileDashboard\Milestone\FutureMilestoneRepresentationBuilder;

class QueryToFutureMilestoneRepresentationBuilderConverterTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
    /**
     * @var QueryToFutureMilestoneRepresentationBuilderConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new QueryToFutureMilestoneRepresentationBuilderConverter(Mockery::mock(FutureMilestoneRepresentationBuilder::class));
    }

    public function testConvertsFutureToPeriodFuture(): void
    {
        $this->assertInstanceOf(FutureMilestoneRepresentationBuilder::class, $this->converter->convert('{\"period\":\"future\"}'));
    }

    public function testConvertsEmptyStringToPeriodAll(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"}.');

        $this->converter->convert('');
    }

    public function testConvertsEmptyObjectToPeriodAll(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"}.');

        $this->converter->convert('{}');
    }

    public function testThrowsExceptionIfPeriodKeyIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"}.');

        $this->converter->convert('{\"perIod\":\"closed\"}');
    }

    public function testThrowsExceptionIfPeriodValueIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"}.');

        $this->converter->convert('{\"period\":\"FutUre\"}');
    }

    public function testThrowsExceptionIfNotAnObject(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"period":"future"}.');

        $this->converter->convert('future');
    }
}
