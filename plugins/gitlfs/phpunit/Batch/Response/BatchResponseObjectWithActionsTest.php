<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GitLFS\Batch\Response\Action\BatchResponseActions;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;

class BatchResponseObjectWithActionsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBatchReponseObjectsWithActionsCanBeSerialized()
    {
        $action_content  = \Mockery::mock(BatchResponseActions::class);
        $action_content->shouldReceive('jsonSerialize')->andReturns(new \stdClass());

        $oid  = 'oid';
        $size = 123456;

        $lfs_object = \Mockery::mock(LFSObject::class);
        $lfs_object->shouldReceive('getSize')->andReturns($size);
        $lfs_object_id = \Mockery::mock(LFSObjectID::class);
        $lfs_object_id->shouldReceive('getValue')->andReturns($oid);
        $lfs_object->shouldReceive('getOID')->andReturns($lfs_object_id);

        $response_object            = new BatchResponseObjectWithActions($lfs_object, $action_content);
        $serialized_response_object = json_decode(json_encode($response_object));

        $this->assertSame($oid, $serialized_response_object->oid);
        $this->assertSame($size, $serialized_response_object->size);
        $this->assertEquals(new \stdClass(), $serialized_response_object->actions);
    }
}
