<?php
/**
 * Copyright (c) Enalean, 2016 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\CommentWithoutPrivateCheckFromWhereBuilder;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\IntegerFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveUsedFieldsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class QueryBuilderVisitorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private QueryBuilderVisitor $query_builder;
    private QueryBuilderParameters $parameters;

    protected function setUp(): void
    {
        $tracker          = TrackerTestBuilder::aTracker()->withId(101)->build();
        $this->parameters = new QueryBuilderParameters($tracker, UserTestBuilder::buildWithDefaults());

        $formelement_factory = RetrieveUsedFieldsStub::withFields(
            TextFieldBuilder::aTextField(1)
                ->inTracker($tracker)
                ->withName('field')
                ->build(),
            IntegerFieldBuilder::anIntField(2)
                ->inTracker($tracker)
                ->withName('int')
                ->build(),
            FloatFieldBuilder::aFloatField(3)
                ->inTracker($tracker)
                ->withName('float')
                ->build(),
            DateFieldBuilder::aDateField(4)
                ->inTracker($tracker)
                ->withName('date')
                ->build(),
            ListFieldBuilder::aListField(6)
                ->inTracker($tracker)
                ->withName('sb')
                ->build(),
        );

        $db = $this->createMock(EasyDB::class);
        $db->method('escapeLikeValue')->willReturnArgument(0);

        $this->query_builder = new QueryBuilderVisitor(
            new QueryBuilder\EqualFieldComparisonVisitor($db),
            new QueryBuilder\NotEqualFieldComparisonVisitor($db),
            new QueryBuilder\LesserThanFieldComparisonVisitor(),
            new QueryBuilder\GreaterThanFieldComparisonVisitor(),
            new QueryBuilder\LesserThanOrEqualFieldComparisonVisitor(),
            new QueryBuilder\GreaterThanOrEqualFieldComparisonVisitor(),
            new QueryBuilder\BetweenFieldComparisonVisitor(),
            new QueryBuilder\InFieldComparisonVisitor(),
            new QueryBuilder\NotInFieldComparisonVisitor(),
            new QueryBuilder\FromWhereSearchableVisitor($formelement_factory),
            new QueryBuilder\MetadataEqualComparisonFromWhereBuilder(new CommentWithoutPrivateCheckFromWhereBuilder()),
            new QueryBuilder\MetadataNotEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataLesserThanComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataGreaterThanComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataLesserThanOrEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataGreaterThanOrEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataBetweenComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataInComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataNotInComparisonFromWhereBuilder(),
            new QueryBuilder\ArtifactLink\ReverseLinkFromWhereBuilder(\Tracker_ArtifactFactory::instance()),
            new QueryBuilder\ArtifactLink\ForwardLinkFromWhereBuilder(\Tracker_ArtifactFactory::instance()),
        );
    }

    public function testItRetrievesInAndExpressionTheExpertFromAndWhereClausesOfTheSubexpression(): void
    {
        $from_where = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $comparison = $this->createMock(EqualComparison::class);
        $comparison->method('acceptTermVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where);

        $and_expression = new AndExpression($comparison);

        $result = $this->query_builder->visitAndExpression($and_expression, $this->parameters);

        $this->assertEquals($from_where, $result);
    }

    public function testItRetrievesInAndExpressionTheExpertFromAndWhereClausesOfTheSubexpressionConcatenatedToTheTailOnes(): void
    {
        $from_where_expression = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $from_where_tail       = new ParametrizedFromWhere('le_from_tail', 'le_where_tail', [], []);
        $comparison            = $this->createMock(EqualComparison::class);
        $comparison->method('acceptTermVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_expression);
        $tail = $this->createMock(AndOperand::class);
        $tail->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_tail);

        $and_expression = new AndExpression($comparison, $tail);

        $result = $this->query_builder->visitAndExpression($and_expression, $this->parameters);

        $this->assertEquals('le_from le_from_tail', $result->getFrom());
        $this->assertEquals('(le_where) AND (le_where_tail)', $result->getWhere());
    }

    public function testItRetrievesInAndOperandTheExpertFromAndWhereClausesOfTheSubexpression(): void
    {
        $from_where = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $comparison = $this->createMock(EqualComparison::class);
        $comparison->method('acceptTermVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where);

        $and_operand = new AndOperand($comparison);

        $result = $this->query_builder->visitAndOperand($and_operand, $this->parameters);

        $this->assertEquals($from_where, $result);
    }

    public function testItRetrievesInAndOperandTheExpertFromAndWhereClausesOfTheSubexpressionConcatenatedToTheTailOnes(): void
    {
        $from_where_operand = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $from_where_tail    = new ParametrizedFromWhere('le_from_tail', 'le_where_tail', [], []);
        $comparison         = $this->createMock(EqualComparison::class);
        $comparison->method('acceptTermVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_operand);
        $tail = $this->createMock(AndOperand::class);
        $tail->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_tail);

        $and_operand = new AndOperand($comparison, $tail);

        $result = $this->query_builder->visitAndOperand($and_operand, $this->parameters);

        $this->assertEquals('le_from le_from_tail', $result->getFrom());
        $this->assertEquals('(le_where) AND (le_where_tail)', $result->getWhere());
    }

    public function testItRetrievesInOrOperandTheExpertFromAndWhereClausesOfTheOperand(): void
    {
        $from_where = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $expression = $this->createMock(AndExpression::class);
        $expression->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where);

        $and_operand = new OrOperand($expression);

        $result = $this->query_builder->visitOrOperand($and_operand, $this->parameters);

        $this->assertEquals($from_where, $result);
    }

    public function testItRetrievesInOrOperandTheExpertFromAndWhereClausesOfTheOperandConcatenatedToTheTailOnes(): void
    {
        $from_where_operand = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $from_where_tail    = new ParametrizedFromWhere('le_from_tail', 'le_where_tail', [], []);
        $expression         = $this->createMock(AndExpression::class);
        $expression->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_operand);
        $tail = $this->createMock(OrOperand::class);
        $tail->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_tail);

        $or_operand = new OrOperand($expression, $tail);

        $result = $this->query_builder->visitOrOperand($or_operand, $this->parameters);

        $this->assertEquals('le_from le_from_tail', $result->getFrom());
        $this->assertEquals('((le_where) OR (le_where_tail))', $result->getWhere());
    }

    public function testItRetrievesInOrExpressionTheExpertFromAndWhereClausesOfTheOperand(): void
    {
        $from_where = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $expression = $this->createMock(AndExpression::class);
        $expression->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where);

        $or_expression = new OrExpression($expression);

        $result = $this->query_builder->visitOrExpression($or_expression, $this->parameters);

        $this->assertEquals($from_where, $result);
    }

    public function testItRetrievesInOrExpressionTheExpertFromAndWhereClausesOfTheOperandConcatenatedToTheTailOnes(): void
    {
        $from_where_operand = new ParametrizedFromWhere('le_from', 'le_where', [], []);
        $from_where_tail    = new ParametrizedFromWhere('le_from_tail', 'le_where_tail', [], []);
        $expression         = $this->createMock(AndExpression::class);
        $expression->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_operand);
        $tail = $this->createMock(OrOperand::class);
        $tail->method('acceptLogicalVisitor')->with($this->query_builder, $this->parameters)
            ->willReturn($from_where_tail);

        $or_expression = new OrExpression($expression, $tail);

        $result = $this->query_builder->visitOrExpression($or_expression, $this->parameters);

        $this->assertEquals('le_from le_from_tail', $result->getFrom());
        $this->assertEquals('((le_where) OR (le_where_tail))', $result->getWhere());
    }

    public function testItRetrievesForTextInEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_text/', $result->getFrom());
    }

    public function testItRetrievesForIntegerFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new EqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function testItRetrievesForFloatFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new EqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function testItRetrievesForDateFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new EqualComparison(new Field('date'), new SimpleValueWrapper('2017-01-17'));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_date/', $result->getFrom());
    }

    public function testItRetrievesForTextInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new NotEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_text/', $result->getFrom());
    }

    public function testItRetrievesForIntegerFieldInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new NotEqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function testItRetrievesForFloatFieldInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new NotEqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function testItRetrievesForIntegerFieldInLesserThanComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new LesserThanComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitLesserThanComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function testItRetrievesForFloatFieldInLesserThanComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new LesserThanComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitLesserThanComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function testItRetrievesForIntegerFieldInGreaterThanComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new GreaterThanComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitGreaterThanComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function testItRetrievesForFloatFieldInGreaterThanComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new GreaterThanComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitGreaterThanComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function testItRetrievesForIntegerFieldInLesserThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new LesserThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitLesserThanOrEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function testItRetrievesForFloatFieldInLesserThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new LesserThanOrEqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitLesserThanOrEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function testItRetrievesForIntegerFieldInGreaterThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new GreaterThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitGreaterThanOrEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function testItRetrievesForFloatFieldInGreaterThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new GreaterThanOrEqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitGreaterThanOrEqualComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function testItRetrievesForIntegerFieldInBetweenComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new BetweenComparison(
            new Field('int'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(1),
                new SimpleValueWrapper(2)
            )
        );

        $result = $this->query_builder->visitBetweenComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function testItRetrievesForFloatFieldInBetweenComparisonTheExpertFromAndWhereClausesOfTheField(): void
    {
        $comparison = new BetweenComparison(
            new Field('float'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(1.23),
                new SimpleValueWrapper(2.56)
            )
        );

        $result = $this->query_builder->visitBetweenComparison($comparison, $this->parameters);

        $this->assertMatchesRegularExpression('/tracker_changeset_value_float/', $result->getFrom());
    }
}
