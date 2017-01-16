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

namespace Tuleap\Tracker\Report\Query\Advanced;

use CodendiDataAccess;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\LesserThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\NotEqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrOperand;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\GreaterThanComparison;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\EqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\LesserThanComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\NotEqualComparisonVisitor;
use Tuleap\Tracker\Report\Query\Advanced\QueryBuilder\GreaterThanComparisonVisitor;
use TuleapTestCase;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class QueryBuilderTest extends TuleapTestCase
{
    private $tracker;
    private $field_text;
    private $int_field;
    private $float_field;

    /** @var  QueryBuilderVisitor */
    private $query_builder;
    private $parameters;

    public function setUp()
    {
        parent::setUp();
        CodendiDataAccess::setInstance(mock('CodendiDataAccess'));

        $this->tracker     = aTracker()->withId(101)->build();
        $this->parameters  = new QueryBuilderParameters($this->tracker);
        $this->field_text  = aTextField()->withName('field')->withId(101)->build();
        $this->int_field   = anIntegerField()->withName('int')->withId(102)->build();
        $this->float_field = aFloatField()->withName('float')->withId(103)->build();

        $formelement_factory = stub('Tracker_FormElementFactory')->getUsedFieldByName(101, 'field')->returns($this->field_text);
        stub($formelement_factory)->getUsedFieldByName(101, 'int')->returns($this->int_field);
        stub($formelement_factory)->getUsedFieldByName(101, 'float')->returns($this->float_field);

        $this->query_builder = new QueryBuilderVisitor(
            $formelement_factory,
            new EqualComparisonVisitor(),
            new NotEqualComparisonVisitor(),
            new LesserThanComparisonVisitor(),
            new GreaterThanComparisonVisitor()
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

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
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

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
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

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
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

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "(le_where OR le_where_tail)");
    }

    public function itRetrievesForTextInEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new EqualComparison('field', 'value');

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_text/', $result->getFrom());
    }

    public function itRetrievesForIntegerFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new EqualComparison('int', 1);

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function itRetrievesForFloatFieldInEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new EqualComparison('float', 1.23);

        $result = $this->query_builder->visitEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function itRetrievesForTextInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new NotEqualComparison('field', 'value');

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_text/', $result->getFrom());
    }

    public function itRetrievesForIntegerFieldInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new NotEqualComparison('int', 1);

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function itRetrievesForFloatFieldInNotEqualComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new NotEqualComparison('float', 1.23);

        $result = $this->query_builder->visitNotEqualComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function itRetrievesForIntegerFieldInLesserThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new LesserThanComparison('int', 1);

        $result = $this->query_builder->visitLesserThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function itRetrievesForFloatFieldInLesserThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new LesserThanComparison('float', 1.23);

        $result = $this->query_builder->visitLesserThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFrom());
    }

    public function itRetrievesForIntegerFieldInGreaterThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new GreaterThanComparison('int', 1);

        $result = $this->query_builder->visitGreaterThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_int/', $result->getFrom());
    }

    public function itRetrievesForFloatFieldInGreaterThanComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new GreaterThanComparison('float', 1.23);

        $result = $this->query_builder->visitGreaterThanComparison($comparison, $this->parameters);

        $this->assertPattern('/tracker_changeset_value_float/', $result->getFrom());
    }
/**/
}
