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
use Tuleap\GitLFS\Transfer\Transfer;

class BatchSuccessfulResponseTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testResponseCanBeJsonSerialized()
    {
        $transfer        = new Transfer('test');
        $response_object = \Mockery::mock(BatchResponseObject::class);
        $response_object->shouldReceive('jsonSerialize')->andReturns(new \stdClass());
        $response = new BatchSuccessfulResponse($transfer, $response_object);

        $expected_value           = new \stdClass();
        $expected_value->transfer = 'test';
        $expected_actions         = new \stdClass();
        $expected_actions->upload = new \stdClass();
        $expected_value->objects  = [new \stdClass()];

        $this->assertEquals($expected_value, json_decode(json_encode($response)));
    }
}
