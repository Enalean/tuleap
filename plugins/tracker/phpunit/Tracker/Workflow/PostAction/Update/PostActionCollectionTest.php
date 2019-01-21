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
 */

namespace Tuleap\Tracker\Workflow\PostAction\Update;

use Mockery;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\CIBuild;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\PostActionIdCollection;
use Tuleap\Tracker\Workflow\PostAction\Update\Internal\SetDateValue;

class PostActionCollectionTest extends TestCase
{

    public function testCompareCIBuildActionsToIdentifiesNewActions()
    {
        $added_action = new CIBuild(null, 'http://example.test');
        $actions      = new PostActionCollection($added_action);
        $existing_ids = Mockery::mock(PostActionIdCollection::class);
        $existing_ids->shouldReceive('contains')->andReturnFalse();

        $diff = $actions->compareCIBuildActionsTo($existing_ids);

        $this->assertEquals([$added_action], $diff->getAddedActions());
    }

    public function testCompareCIBuildActionsToIdentifiesUpdatedActions()
    {
        $updated_action = new CIBuild(2, 'http://example.test');
        $actions        = new PostActionCollection($updated_action);
        $existing_ids = Mockery::mock(PostActionIdCollection::class);
        $existing_ids->shouldReceive('contains')
            ->withArgs([2])
            ->andReturnTrue();

        $diff = $actions->compareCIBuildActionsTo($existing_ids);

        $this->assertEquals([$updated_action], $diff->getUpdatedActions());
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\UnknownPostActionIdsException
     */
    public function testCompareCIBuildActionsToThrowsWhenUnknownPostActionId()
    {
        $unknown_action = new CIBuild(10, 'https://example.com');
        $actions        = new PostActionCollection($unknown_action);
        $existing_ids = Mockery::mock(PostActionIdCollection::class);
        $existing_ids->shouldReceive('contains')
            ->withArgs([10])
            ->andReturnFalse();

        $actions->compareCIBuildActionsTo($existing_ids);
    }

    public function testGetCiBuildActionsReturnsCIBuildActions()
    {
        $ci_build_action       = new CIBuild(1, 'http://example.test');
        $set_date_value_action = new SetDateValue(1, 22, 13);
        $actions               = new PostActionCollection($ci_build_action, $set_date_value_action);

        $ci_build_actions = $actions->getCIBuildActions();

        $this->assertEquals([$ci_build_action], $ci_build_actions);
    }
}
