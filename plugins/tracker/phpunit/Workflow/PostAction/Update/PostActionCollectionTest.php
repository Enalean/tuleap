<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuild;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetFloatValue;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetIntValue;

class PostActionCollectionTest extends TestCase
{

    public function testGetCiBuildActionsReturnsCIBuildActions()
    {
        $ci_build_action       = new CIBuild(1, 'http://example.test');
        $set_date_value_action = new SetDateValue(1, 22, 13);
        $actions               = new PostActionCollection($ci_build_action, $set_date_value_action);

        $ci_build_actions = $actions->getCIBuildActions();

        $this->assertEquals([$ci_build_action], $ci_build_actions);
    }

    public function testGetSetDateValueActionsReturnsSetDateValueActions()
    {
        $ci_build_action       = new CIBuild(1, 'http://example.test');
        $set_date_value_action = new SetDateValue(1, 22, 1);
        $actions               = new PostActionCollection($ci_build_action, $set_date_value_action);

        $ci_build_actions = $actions->getSetDateValueActions();

        $this->assertEquals([$set_date_value_action], $ci_build_actions);
    }

    public function testGetSetIntValueActionsReturnsSetIntValueActions()
    {
        $ci_build_action      = new CIBuild(1, 'http://example.test');
        $set_int_value_action = new SetIntValue(1, 22, 13);
        $actions              = new PostActionCollection($ci_build_action, $set_int_value_action);

        $ci_build_actions = $actions->getSetIntValueActions();

        $this->assertEquals([$set_int_value_action], $ci_build_actions);
    }

    public function testGetSetFloatValueActionsReturnsSetFloatValueActions()
    {
        $ci_build_action        = new CIBuild(1, 'http://example.test');
        $set_float_value_action = new SetFloatValue(1, 22, 1.3);
        $actions                = new PostActionCollection($ci_build_action, $set_float_value_action);

        $ci_build_actions = $actions->getSetFloatValueActions();

        $this->assertEquals([$set_float_value_action], $ci_build_actions);
    }

    public function testCompareCIBuildActionsToIdentifiesNewActions()
    {
        $added_action = new CIBuild(null, 'http://example.test');
        $actions      = new PostActionCollection($added_action);

        $diff = $actions->compareCIBuildActionsTo([1, 2, 3]);

        $this->assertEquals([$added_action], $diff->getAddedActions());
    }

    public function testCompareCIBuildActionsToIdentifiesUpdatedActions()
    {
        $updated_action = new CIBuild(2, 'http://example.test');
        $actions        = new PostActionCollection($updated_action);

        $diff = $actions->compareCIBuildActionsTo([1, 2, 3]);

        $this->assertEquals([$updated_action], $diff->getUpdatedActions());
    }
}
