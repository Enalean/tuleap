<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use CodendiDataAccess;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\BetweenValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanOrEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\CommentFromWhereBuilder;
use Tuleap\Tracker\Report\Query\FromWhere;
use TuleapTestCase;

require_once __DIR__.'/../../../../bootstrap.php';

class QueryBuilderVisitorTest extends TuleapTestCase
{
    private $tracker;
    private $field_text;
    private $int_field;
    private $float_field;
    private $date_field;
    private $selectbox_field;

    /** @var  QueryBuilderVisitor */
    private $query_builder;
    private $parameters;
    private $bind;

    public function setUp()
    {
        parent::setUp();
        CodendiDataAccess::setInstance(mock('CodendiDataAccess'));

        $this->tracker         = aTracker()->withId(101)->build();
        $this->parameters      = new QueryBuilderParameters($this->tracker);
        $this->field_text      = aTextField()->withName('field')->withId(101)->build();
        $this->int_field       = anIntegerField()->withName('int')->withId(102)->build();
        $this->float_field     = aFloatField()->withName('float')->withId(103)->build();
        $this->date_field      = aMockDateWithoutTimeField()->withName('date')->withId(104)->build();
        $this->bind            = mock('Tracker_FormElement_Field_List_Bind_Static');
        $this->selectbox_field = aSelectBoxField()->withName('sb')->withId(105)->withBind($this->bind)->build();

        $formelement_factory = stub('Tracker_FormElementFactory')->getUsedFieldByName(101, 'field')->returns($this->field_text);
        stub($formelement_factory)->getUsedFieldByName(101, 'int')->returns($this->int_field);
        stub($formelement_factory)->getUsedFieldByName(101, 'float')->returns($this->float_field);
        stub($formelement_factory)->getUsedFieldByName(101, 'date')->returns($this->date_field);
        stub($formelement_factory)->getUsedFieldByName(101, 'sb')->returns($this->selectbox_field);

        $this->query_builder = new QueryBuilderVisitor(
            new QueryBuilder\EqualFieldComparisonVisitor(),
            new QueryBuilder\NotEqualFieldComparisonVisitor(),
            new QueryBuilder\LesserThanFieldComparisonVisitor(),
            new QueryBuilder\GreaterThanFieldComparisonVisitor(),
            new QueryBuilder\LesserThanOrEqualFieldComparisonVisitor(),
            new QueryBuilder\GreaterThanOrEqualFieldComparisonVisitor(),
            new QueryBuilder\BetweenFieldComparisonVisitor(),
            new QueryBuilder\InFieldComparisonVisitor,
            new QueryBuilder\NotInFieldComparisonVisitor(),
            new QueryBuilder\SearchableVisitor($formelement_factory),
            new QueryBuilder\MetadataEqualComparisonFromWhereBuilder(new CommentFromWhereBuilder()),
            new QueryBuilder\MetadataNotEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataLesserThanComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataGreaterThanComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataLesserThanOrEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataGreaterThanOrEqualComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataBetweenComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataInComparisonFromWhereBuilder(),
            new QueryBuilder\MetadataNotInComparisonFromWhereBuilder()
        );
    }

    public function tearDown()
    {
        CodendiDataAccess::clearInstance();
        parent::tearDown();
    }

    public function itRetrievesInAndExpressionTheExpertFromAndWhereClausesOfTheSubexpression()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\EqualComparison")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where);

        $and_expression = new AndExpression($comparison);

        $result = $this->query_builder->visitAndExpression($and_expression, $this->parameters);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInAndExpressionTheExpertFromAndWhereClausesOfTheSubexpressionConcatenatedToTheTailOnes()
    {
        $from_where_expression = new FromWhere("le_from", "le_where");
        $from_where_tail       = new FromWhere("le_from_tail", "le_where_tail");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\EqualComparison")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_expression);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndOperand")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_tail);

        $and_expression = new AndExpression($comparison, $tail);

        $result = $this->query_builder->visitAndExpression($and_expression, $this->parameters);

        $this->assertEqual($result->getFromAsString(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "le_where AND le_where_tail");
    }

    public function itRetrievesInAndOperandTheExpertFromAndWhereClausesOfTheSubexpression()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\EqualComparison")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where);

        $and_operand = new AndOperand($comparison);

        $result = $this->query_builder->visitAndOperand($and_operand, $this->parameters);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInAndOperandTheExpertFromAndWhereClausesOfTheSubexpressionConcatenatedToTheTailOnes()
    {
        $from_where_operand = new FromWhere("le_from", "le_where");
        $from_where_tail    = new FromWhere("le_from_tail", "le_where_tail");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\EqualComparison")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_operand);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndOperand")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_tail);

        $and_operand = new AndOperand($comparison, $tail);

        $result = $this->query_builder->visitAndOperand($and_operand, $this->parameters);

        $this->assertEqual($result->getFromAsString(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "le_where AND le_where_tail");
    }

    public function itRetrievesInOrOperandTheExpertFromAndWhereClausesOfTheOperand()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where);

        $and_operand = new OrOperand($expression);

        $result = $this->query_builder->visitOrOperand($and_operand, $this->parameters);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInOrOperandTheExpertFromAndWhereClausesOfTheOperandConcatenatedToTheTailOnes()
    {
        $from_where_operand = new FromWhere("le_from", "le_where");
        $from_where_tail    = new FromWhere("le_from_tail", "le_where_tail");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_operand);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\OrOperand")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_tail);

        $or_operand = new OrOperand($expression, $tail);

        $result = $this->query_builder->visitOrOperand($or_operand, $this->parameters);

        $this->assertEqual($result->getFromAsString(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "(le_where OR le_where_tail)");
    }

    public function itRetrievesInOrExpressionTheExpertFromAndWhereClausesOfTheOperand()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where);

        $or_expression = new OrExpression($expression);

        $result = $this->query_builder->visitOrExpression($or_expression, $this->parameters);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInOrExpressionTheExpertFromAndWhereClausesOfTheOperandConcatenatedToTheTailOnes()
    {
        $from_where_operand = new FromWhere("le_from", "le_where");
        $from_where_tail    = new FromWhere("le_from_tail", "le_where_tail");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_operand);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\OrOperand")
            ->accept($this->query_builder, $this->parameters)
            ->returns($from_where_tail);

        $or_expression = new OrExpression($expression, $tail);

        $result = $this->query_builder->visitOrExpression($or_expression, $this->parameters);

        $this->assertEqual($result->getFromAsString(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "(le_where OR le_where_tail)");
    }

    public function itRetrievesForTextInEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new EqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_text/', $result->getFromAsString());
    }

    public function itRetrievesForIntegerFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new EqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFromAsString());
    }

    public function itRetrievesForFloatFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new EqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFromAsString());
    }

    public function itRetrievesForDateFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new EqualComparison(new Field('date'), new SimpleValueWrapper('2017-01-17'));

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_date/', $result->getFromAsString());
    }

    public function itRetrievesForTextInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new NotEqualComparison(new Field('field'), new SimpleValueWrapper('value'));

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_text/', $result->getFromAsString());
    }

    public function itRetrievesForIntegerFieldInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new NotEqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFromAsString());
    }

    public function itRetrievesForFloatFieldInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new NotEqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFromAsString());
    }

    public function itRetrievesForIntegerFieldInLesserThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new LesserThanComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitLesserThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFromAsString());
    }

    public function itRetrievesForFloatFieldInLesserThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new LesserThanComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitLesserThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFromAsString());
    }

    public function itRetrievesForIntegerFieldInGreaterThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new GreaterThanComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitGreaterThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFromAsString());
    }

    public function itRetrievesForFloatFieldInGreaterThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new GreaterThanComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitGreaterThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFromAsString());
    }

    public function itRetrievesForIntegerFieldInLesserThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new LesserThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitLesserThanOrEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFromAsString());
    }

    public function itRetrievesForFloatFieldInLesserThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new LesserThanOrEqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitLesserThanOrEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFromAsString());
    }

    public function itRetrievesForIntegerFieldInGreaterThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new GreaterThanOrEqualComparison(new Field('int'), new SimpleValueWrapper(1));

        $result = $this->query_builder->visitGreaterThanOrEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFromAsString());
    }

    public function itRetrievesForFloatFieldInGreaterThanOrEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new GreaterThanOrEqualComparison(new Field('float'), new SimpleValueWrapper(1.23));

        $result = $this->query_builder->visitGreaterThanOrEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFromAsString());
    }

    public function itRetrievesForIntegerFieldInBetweenComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new BetweenComparison(
            new Field('int'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(1),
                new SimpleValueWrapper(2)
            )
        );

        $result = $this->query_builder->visitBetweenComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFromAsString());
    }

    public function itRetrievesForFloatFieldInBetweenComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new BetweenComparison(
            new Field('float'),
            new BetweenValueWrapper(
                new SimpleValueWrapper(1.23),
                new SimpleValueWrapper(2.56)
            )
        );

        $result = $this->query_builder->visitBetweenComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFromAsString());
    }
}
