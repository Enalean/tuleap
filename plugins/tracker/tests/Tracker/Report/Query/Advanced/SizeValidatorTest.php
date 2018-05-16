<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Tuleap\Tracker\Report\Query\Advanced\Grammar\AndExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrExpression;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SimpleValueWrapper;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use TuleapTestCase;

require_once __DIR__.'/../../../../bootstrap.php';

class SizeValidatorTest extends TuleapTestCase
{
    private $tracker;
    private $user;
    private $validator;

    public function setUp()
    {
        parent::setUp();

        $this->tracker = aTracker()->withId(101)->build();
        $this->user    = aUser()->build();

        $this->validator = new SizeValidatorVisitor(2);
    }

    public function itDoesNotThrowAnExceptionIfDeptDoesNotExceedLimit()
    {
        $subexpression = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\EqualComparison');
        $tail          = mock('Tuleap\Tracker\Report\Query\Advanced\Grammar\AndOperand');
        $expression    = new AndExpression($subexpression, $tail);

        $expression->accept($this->validator, new SizeValidatorParameters(0));
    }

    public function itThrowsAnExceptionIfDepthExceedLimit()
    {
        $comparison    = new EqualComparison(new Field("field"), new SimpleValueWrapper('value'));
        $subexpression = new AndExpression($comparison, null);
        $expression    = new OrExpression($subexpression, null);

        $this->expectException('Tuleap\Tracker\Report\Query\Advanced\LimitSizeIsExceededException');
        $expression->accept($this->validator, new SizeValidatorParameters(0));
    }
}
