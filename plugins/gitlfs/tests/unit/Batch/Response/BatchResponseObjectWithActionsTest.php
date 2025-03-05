<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Batch\Response;

use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActions;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BatchResponseObjectWithActionsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBatchResponseObjectsWithActionsCanBeSerialized(): void
    {
        $action_content = new class implements BatchResponseActions {
            public function jsonSerialize(): \stdClass
            {
                return new \stdClass();
            }
        };

        $oid  = str_repeat('a', 64);
        $size = 123456;

        $lfs_object_id = new LFSObjectID($oid);
        $lfs_object    = new LFSObject($lfs_object_id, $size);

        $response_object            = new BatchResponseObjectWithActions($lfs_object, $action_content);
        $serialized_response_object = json_decode(json_encode($response_object));

        self::assertSame($oid, $serialized_response_object->oid);
        self::assertSame($size, $serialized_response_object->size);
        $this->assertEquals(new \stdClass(), $serialized_response_object->actions);
    }
}
