<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\REST;

use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusAll;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusClosed;
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Request\MalformedQueryParameterException;

class QueryToCriterionStatusConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var QueryToCriterionStatusConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new QueryToCriterionStatusConverter();
    }

    public function testItConvertsEmptyStringToStatusAll(): void
    {
        $this->assertInstanceOf(
            StatusAll::class,
            $this->converter->convert('')
        );
    }

    public function testItConvertsEmptyObjectToStatusAll(): void
    {
        $this->assertInstanceOf(
            StatusAll::class,
            $this->converter->convert('{}')
        );
    }

    public function testItConvertsOpenToStatusOpen(): void
    {
        $this->assertInstanceOf(
            StatusOpen::class,
            $this->converter->convert('{\"status\":\"open\"}')
        );
    }

    public function testItConvertsClosedToStatusClosed(): void
    {
        $this->assertInstanceOf(
            StatusClosed::class,
            $this->converter->convert('{\"status\":\"closed\"}')
        );
    }

    public function testItThrowsExceptionIfStatusKeyIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('{\"StaTuS\":\"closed\"}');
    }

    public function testItThrowsExceptionIfStatusValueIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('{\"status\":\"ClOsEr\"}');
    }

    public function testItThrowsExceptionIfNotAnObject(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"status":"open"} or {"status":"closed"}.');

        $this->converter->convert('open');
    }
}
