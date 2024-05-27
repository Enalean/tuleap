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

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use ForgeConfig;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\PHPUnit\TestCase;

final class ParserTest extends TestCase
{
    use ForgeConfigSandbox;

    public function testItThrowsASyntaxErrorIfQueryIsEmpty(): void
    {
        self::expectException(SyntaxError::class);
        $parser = new Parser();
        $parser->parse('');
    }

    public function testItParsesASimpleQuery(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('field = "value"');
        $expected = new Query([], new OrExpression(
            new AndExpression(
                new EqualComparison(new Field('field'), new SimpleValueWrapper('value')),
                null
            ),
            null
        ));

        self::assertEquals($expected, $result);
    }

    public function testItDoesNotFailIfFieldNameContainsDigits(): void
    {
        self::expectNotToPerformAssertions();
        $parser = new Parser();
        $parser->parse('field_1 = "value"');
    }

    public function testItDoesNotFailIfFieldNameContainsHyphen(): void
    {
        self::expectNotToPerformAssertions();
        $parser = new Parser();
        $parser->parse('field-name = "value"');
    }

    public function testItFailIfSelectIsNotEnabled(): void
    {
        ForgeConfig::setFeatureFlag(Query::ENABLE_SELECT, 0);

        $parser = new Parser();
        self::expectExceptionMessage('SELECT syntax cannot be used');
        $parser->parse('SELECT field WHERE field = "value"');
    }

    public function testItDoesNotFailIfSelectIsEnabled(): void
    {
        ForgeConfig::setFeatureFlag(Query::ENABLE_SELECT, 1);

        $parser   = new Parser();
        $result   = $parser->parse('SELECT field WHERE field = "value"');
        $expected = new Query(
            [new Field('field')],
            new OrExpression(
                new AndExpression(
                    new EqualComparison(new Field('field'), new SimpleValueWrapper('value')),
                    null
                ),
                null
            )
        );
        self::assertEquals($expected, $result);
    }

    public function testSelectAcceptMultipleField(): void
    {
        ForgeConfig::setFeatureFlag(Query::ENABLE_SELECT, 1);

        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id, @title, category WHERE @status = OPEN()');
        $expected = new Query(
            [new Metadata('id'), new Metadata('title'), new Field('category')],
            new OrExpression(
                new AndExpression(
                    new EqualComparison(new Metadata('status'), new StatusOpenValueWrapper()),
                    null
                ),
                null
            )
        );
        self::assertEquals($expected, $result);
    }

    public function testItFailIfSelectEndWithComma(): void
    {
        ForgeConfig::setFeatureFlag(Query::ENABLE_SELECT, 1);

        $parser = new Parser();
        self::expectException(SyntaxError::class);
        $parser->parse('SELECT field, WHERE field = "value"');
    }
}
