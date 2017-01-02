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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class QueryBuilderTest extends TuleapTestCase
{
    private $user;
    private $tracker;
    private $field;

    /** @var  QueryBuilder */
    private $query_builder;

    public function setUp()
    {
        parent::setUp();

        $this->user    = aUser()->build();
        $this->tracker = aTracker()->withId(101)->build();
        $this->field   = mock('Tracker_FormElement_Field_Text');

        $formelement_factory = stub('Tracker_FormElementFactory')->getUsedFieldByName()->returns($this->field);
        $this->query_builder = new QueryBuilder($formelement_factory);
    }

    public function itPassesTheCurrentObjectInternalIdAsASuffixInOrderToBeAbleToHaveTheFieldSeveralTimesInTheQuery()
    {
        $comparison = new Comparison('field', 'value');

        expect($this->field)->getExpertFrom("*", spl_object_hash($comparison))->once();
        expect($this->field)->getExpertWhere(spl_object_hash($comparison))->once();

        $this->query_builder->visitComparison($comparison, $this->user, $this->tracker);
    }

    public function itRetrievesInComparisonTheExpertFromAndWhereClausesOfTheField()
    {
        $comparison = new Comparison('field', 'value');

        stub($this->field)->getExpertFrom("value", "*")->returns("le_from");
        stub($this->field)->getExpertWhere("*")->returns("le_where");

        $result = $this->query_builder->visitComparison($comparison, $this->user, $this->tracker);

        $this->assertEqual($result->getFrom(), "le_from");
        $this->assertEqual($result->getWhere(), "le_where");
    }

    public function itRetrievesInAndExpressionTheExpertFromAndWhereClausesOfTheSubexpression()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\Comparison")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where);

        $and_expression = new AndExpression($comparison);

        $result = $this->query_builder->visitAndExpression($and_expression, $this->user, $this->tracker);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInAndExpressionTheExpertFromAndWhereClausesOfTheSubexpressionConcatenatedToTheTailOnes()
    {
        $from_where_expression = new FromWhere("le_from", "le_where");
        $from_where_tail       = new FromWhere("le_from_tail", "le_where_tail");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\Comparison")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_expression);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndOperand")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_tail);

        $and_expression = new AndExpression($comparison, $tail);

        $result = $this->query_builder->visitAndExpression($and_expression, $this->user, $this->tracker);

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "le_where AND le_where_tail");
    }

    public function itRetrievesInAndOperandTheExpertFromAndWhereClausesOfTheSubexpression()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\Comparison")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where);

        $and_operand = new AndOperand($comparison);

        $result = $this->query_builder->visitAndOperand($and_operand, $this->user, $this->tracker);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInAndOperandTheExpertFromAndWhereClausesOfTheSubexpressionConcatenatedToTheTailOnes()
    {
        $from_where_operand = new FromWhere("le_from", "le_where");
        $from_where_tail    = new FromWhere("le_from_tail", "le_where_tail");
        $comparison = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\Comparison")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_operand);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndOperand")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_tail);

        $and_operand = new AndOperand($comparison, $tail);

        $result = $this->query_builder->visitAndOperand($and_operand, $this->user, $this->tracker);

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "le_where AND le_where_tail");
    }

    public function itRetrievesInOrOperandTheExpertFromAndWhereClausesOfTheOperand()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where);

        $and_operand = new OrOperand($expression);

        $result = $this->query_builder->visitOrOperand($and_operand, $this->user, $this->tracker);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInOrOperandTheExpertFromAndWhereClausesOfTheOperandConcatenatedToTheTailOnes()
    {
        $from_where_operand = new FromWhere("le_from", "le_where");
        $from_where_tail    = new FromWhere("le_from_tail", "le_where_tail");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_operand);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\OrOperand")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_tail);

        $or_operand = new OrOperand($expression, $tail);

        $result = $this->query_builder->visitOrOperand($or_operand, $this->user, $this->tracker);

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "(le_where OR le_where_tail)");
    }

    public function itRetrievesInOrExpressionTheExpertFromAndWhereClausesOfTheOperand()
    {
        $from_where = new FromWhere("le_from", "le_where");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where);

        $or_expression = new OrExpression($expression);

        $result = $this->query_builder->visitOrExpression($or_expression, $this->user, $this->tracker);

        $this->assertEqual($result, $from_where);
    }

    public function itRetrievesInOrExpressionTheExpertFromAndWhereClausesOfTheOperandConcatenatedToTheTailOnes()
    {
        $from_where_operand = new FromWhere("le_from", "le_where");
        $from_where_tail    = new FromWhere("le_from_tail", "le_where_tail");
        $expression = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\AndExpression")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_operand);
        $tail = stub("Tuleap\\Tracker\\Report\\Query\\Advanced\\Grammar\\OrOperand")
            ->accept($this->query_builder, $this->user, $this->tracker)
            ->returns($from_where_tail);

        $or_expression = new OrExpression($expression, $tail);

        $result = $this->query_builder->visitOrExpression($or_expression, $this->user, $this->tracker);

        $this->assertEqual($result->getFrom(), "le_from le_from_tail");
        $this->assertEqual($result->getWhere(), "(le_where OR le_where_tail)");
    }
}
