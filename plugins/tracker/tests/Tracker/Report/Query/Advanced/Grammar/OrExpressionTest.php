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

namespace Tuleap\Tracker\Report\Query\Advanced\Grammar;

use TuleapTestCase;

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class OrExpressionIntegrationTest extends TuleapTestCase
{
    public function itValidatesTheWholeExpression()
    {
        $user      = mock('PFUser');
        $tracker   = mock('Tracker');
        $validator = stub('Tuleap\Tracker\Report\Query\Advanced\Grammar\Validator')->validate()->returns(true);

        $expr = new OrExpression(
            new AndExpression(
                new Comparison("field", "=", "value"),
                null
            ),
            null
        );

        $this->assertTrue($expr->validate($user, $tracker, $validator));
    }
}
