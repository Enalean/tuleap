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

use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ParserTest extends TestCase
{
    public function testItThrowsASyntaxErrorIfQueryIsEmpty(): void
    {
        $this->expectException(SyntaxError::class);
        $parser = new Parser();
        $parser->parse('');
    }

    public function testItParsesASimpleQuery(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('field = "value"');
        $expected = new Query([], null, new OrExpression(
            new AndExpression(
                new EqualComparison(new Field('field'), new SimpleValueWrapper('value')),
                null
            ),
            null
        ), null);

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

    public function testSelectField(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT field FROM @tracker.name = "something" WHERE field = "value"');
        $expected = new Query(
            [new Field('field')],
            new From(new FromTracker('@tracker.name', new FromTrackerEqual('something')), null),
            new OrExpression(
                new AndExpression(
                    new EqualComparison(new Field('field'), new SimpleValueWrapper('value')),
                    null
                ),
                null
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testSelectAcceptMultipleField(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id, @title , category FROM @tracker.name =   "something" WHERE @status = OPEN()');
        $expected = new Query(
            [new Metadata('id'), new Metadata('title'), new Field('category')],
            new From(new FromTracker('@tracker.name', new FromTrackerEqual('something')), null),
            new OrExpression(
                new AndExpression(
                    new EqualComparison(new Metadata('status'), new StatusOpenValueWrapper()),
                    null
                ),
                null
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromTrackerNameEqual(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @tracker.name = "user_story" WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromTracker('@tracker.name', new FromTrackerEqual('user_story')), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromTrackerNameIn(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @tracker.name In ("user_story", "bug") WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromTracker('@tracker.name', new FromTrackerIn(['user_story', 'bug'])), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromProjectSelf(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @project = "self" WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromProject('@project', new FromProjectEqual('self')), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromProjectAggregated(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @project = "aggregated" WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromProject('@project', new FromProjectEqual('aggregated')), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromProjectNameEqual(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @project.name = "fabulous_project" WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromProject('@project.name', new FromProjectEqual('fabulous_project')), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromProjectNameIn(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @project.name IN("MyAwesomeProject" ,  "fabulous_project") WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromProject('@project.name', new FromProjectIn(['MyAwesomeProject', 'fabulous_project'])), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromProjectCategoryEqual(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @project.category = "topic::power" WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromProject('@project.category', new FromProjectEqual('topic::power')), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromProjectCategoryIn(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @project.category IN("topic::sla" ,  "open_source", "active") WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(new FromProject('@project.category', new FromProjectIn(['topic::sla', 'open_source', 'active'])), null),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromTrackerAndProject(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @tracker.name = "user_story" AND @project.name = "fabulous_project" WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(
                new FromTracker('@tracker.name', new FromTrackerEqual('user_story')),
                new FromProject('@project.name', new FromProjectEqual('fabulous_project')),
            ),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testFromProjectAndTracker(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse('SELECT @id FROM @project.name = "fabulous_project" AND @tracker.name = "user_story" WHERE @id >= 1');
        $expected = new Query(
            [new Metadata('id')],
            new From(
                new FromProject('@project.name', new FromProjectEqual('fabulous_project')),
                new FromTracker('@tracker.name', new FromTrackerEqual('user_story')),
            ),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            null,
        );
        self::assertEquals($expected, $result);
    }

    public function testItFailIfSelectEndWithComma(): void
    {
        $parser = new Parser();
        $this->expectException(SyntaxError::class);
        $parser->parse('SELECT field, WHERE field = "value"');
    }

    public function testOrderByFieldAsc(): void
    {
        $parser   = new Parser();
        $result1  = $parser->parse('SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY field ASC');
        $result2  = $parser->parse('SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY field ASCENDING');
        $expected = new Query(
            [new Metadata('id')],
            new From(
                new FromProject('@project', new FromProjectEqual('self')),
                null,
            ),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            new OrderBy(new Field('field'), OrderByDirection::ASCENDING),
        );
        self::assertEquals($expected, $result1);
        self::assertEquals($expected, $result2);
    }

    public function testOrderByMetadataDesc(): void
    {
        $parser   = new Parser();
        $result1  = $parser->parse('SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY @last_update_date DESC');
        $result2  = $parser->parse('SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY @last_update_date DESCENDING');
        $expected = new Query(
            [new Metadata('id')],
            new From(
                new FromProject('@project', new FromProjectEqual('self')),
                null,
            ),
            new OrExpression(
                new AndExpression(
                    new GreaterThanOrEqualComparison(new Metadata('id'), new SimpleValueWrapper(1)),
                    null,
                ),
                null,
            ),
            new OrderBy(new Metadata('last_update_date'), OrderByDirection::DESCENDING),
        );
        self::assertEquals($expected, $result1);
        self::assertEquals($expected, $result2);
    }

    public function testItAcceptNonBreakingSpace(): void
    {
        $parser   = new Parser();
        $result   = $parser->parse("text_field = ''");
        $expected = new Query(
            [],
            null,
            new OrExpression(new AndExpression(new EqualComparison(new Field('text_field'), new SimpleValueWrapper('')), null), null),
            null,
        );
        self::assertEquals($expected, $result);
    }
}
