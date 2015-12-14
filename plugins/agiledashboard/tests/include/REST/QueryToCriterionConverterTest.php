<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

use TuleapTestCase;

require_once __DIR__ .'/../../bootstrap.php';

class QueryToCriterionConverterTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();

        $this->converter = new QueryToCriterionConverter();
    }

    public function itConvertsEmptyStringToStatusAll() {
        $this->assertIsA(
            $this->converter->convert(''),
            'Tuleap\AgileDashboard\Milestone\Criterion\StatusAll'
        );
    }

    public function itConvertsEmptyObjectToStatusAll() {
        $this->assertIsA(
            $this->converter->convert('{}'),
            'Tuleap\AgileDashboard\Milestone\Criterion\StatusAll'
        );
    }

    public function itConvertsOpenToStatusOpen() {
        $this->assertIsA(
            $this->converter->convert('{\"status\":\"open\"}'),
            'Tuleap\AgileDashboard\Milestone\Criterion\StatusOpen'
        );
    }

    public function itConvertsClosedToStatusClosed() {
        $this->assertIsA(
            $this->converter->convert('{\"status\":\"closed\"}'),
            'Tuleap\AgileDashboard\Milestone\Criterion\StatusClosed'
        );
    }

    public function itThrowsExceptionIfStatusKeyIsMalformed() {
        $this->expectException('Tuleap\AgileDashboard\REST\MalformedQueryParameterException');

        $this->converter->convert('{\"StaTuS\":\"closed\"}');
    }

    public function itThrowsExceptionIfStatusValueIsMalformed() {
        $this->expectException('Tuleap\AgileDashboard\REST\MalformedQueryParameterException');

        $this->converter->convert('{\"status\":\"ClOsEr\"}');
    }

    public function itThrowsExceptionIfNotAnObject() {
        $this->expectException('Tuleap\AgileDashboard\REST\MalformedQueryParameterException');

        $this->converter->convert('open');
    }
}
