<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
use Tuleap\AgileDashboard\Milestone\Criterion\Status\StatusOpen;
use Tuleap\AgileDashboard\Milestone\Request\MalformedQueryParameterException;

class QueryToCriterionOnlyAllStatusConverterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var QueryToCriterionStatusConverter
     */
    private $converter;

    protected function setUp(): void
    {
        $this->converter = new QueryToCriterionOnlyAllStatusConverter();
    }

    public function testItConvertsEmptyStringToStatusOpen(): void
    {
        $this->assertInstanceOf(
            StatusOpen::class,
            $this->converter->convert('')
        );
    }

    public function testItConvertsEmptyObjectToStatusOpen(): void
    {
        $this->assertInstanceOf(
            StatusOpen::class,
            $this->converter->convert('{}')
        );
    }

    public function testItConvertsAllToStatusAll(): void
    {
        $this->assertInstanceOf(
            StatusAll::class,
            $this->converter->convert('{\"status\":\"all\"}')
        );
    }

    public function testItThrowsExceptionIfStatusKeyIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"status":"all"}.');

        $this->converter->convert('{\"StaTuS\":\"all\"}');
    }

    public function testItThrowsExceptionIfStatusValueIsMalformed(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"status":"all"}.');

        $this->converter->convert('{\"status\":\"AlL\"}');
    }

    public function testItThrowsExceptionIfNotAnObject(): void
    {
        $this->expectException(MalformedQueryParameterException::class);
        $this->expectExceptionMessage('Query is malformed. Expecting {"status":"all"}.');

        $this->converter->convert('all');
    }
}
