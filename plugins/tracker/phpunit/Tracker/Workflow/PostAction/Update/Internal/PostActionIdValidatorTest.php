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

namespace Tuleap\Tracker\Workflow\PostAction\Update\Internal;

require_once __DIR__ . '/../../../../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Tracker\Workflow\Update\PostAction;

class PostActionIdValidatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostActionIdValidator
     */
    private $validator;

    protected function setUp()
    {
        $this->validator = new PostActionIdValidator();
    }

    public function testValidateDoesNotThrowWhenValid()
    {
        $first_post_action  = $this->createPostActionWithId(1);
        $second_post_action = $this->createPostActionWithId(2);

        $this->validator->validate($first_post_action, $second_post_action);
    }

    /**
     * @expectedException \Tuleap\Tracker\Workflow\PostAction\Update\Internal\DuplicatePostActionException
     */
    public function testValidateThrowsWhenDuplicateCIBuildIds()
    {
        $first_post_action  = $this->createPostActionWithId(2);
        $second_post_action = $this->createPostActionWithId(2);

        $this->validator->validate($first_post_action, $second_post_action);
    }

    private function createPostActionWithId(int $id)
    {
        $post_action = Mockery::mock(PostAction::class);
        return $post_action->shouldReceive('getId')
            ->andReturn($id)
            ->getMock();
    }
}
