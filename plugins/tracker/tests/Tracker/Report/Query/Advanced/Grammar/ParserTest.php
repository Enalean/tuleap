<?php
/**
 * Copyright (c) Enalean, 2016-2017. All Rights Reserved.
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

use TuleapTestCase;

require_once __DIR__.'/../../../../../bootstrap.php';

class ParserTest extends TuleapTestCase
{
    public function itThrowsASyntaxErrorIfQueryIsEmpty()
    {
        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\Grammar\SyntaxError');

        $parser = new Parser();

        $parser->parse("");
    }

    public function itParsesASimpleQuery()
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

        $this->assertEqual($expected, $result);
    }

    public function itDoesNotFailIfFieldNameContainsDigits()
    {
        $parser = new Parser();

        $parser->parse('field_1 = "value"');
    }
}
