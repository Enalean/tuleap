<?php
/**
 * Copyright (c) Enalean, 2016-present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    public function testItThrowsASyntaxErrorIfQueryIsEmpty(): void
    {
        $this->expectException(\Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError::class);

        $parser = new Parser();

        $parser->parse("");
    }

    public function testItParsesASimpleQuery(): void
    {
        $parser = new Parser();

        $result = $parser->parse('field = "value"');

        $expected = new OrExpression(
            new AndExpression(
                new EqualComparison(new Field('field'), new SimpleValueWrapper('value')),
                null
            ),
            null
        );

        $this->assertEquals($expected, $result);
    }

    public function testItDoesNotFailIfFieldNameContainsDigits(): void
    {
        $parser = new Parser();

        $parser->parse('field_1 = "value"');

        $this->addToAssertionCount(1);
    }
}
