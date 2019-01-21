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

namespace Tuleap\GitLFS\Lock\Request;

use PHPUnit\Framework\TestCase;

class LockDeleteRequestTest extends TestCase
{

    public function testParsingRequest()
    {
        $json = <<<JSON
{
  "force": true,
  "ref": {
    "name": "refs/heads/master"
  }
}
JSON;
        $delete_request = LockDeleteRequest::buildFromJSONString($json);

        $this->assertSame('refs/heads/master', $delete_request->getReference()->getName());
        $this->assertTrue($delete_request->getForce());
        $this->assertTrue($delete_request->isWrite());
        $this->assertFalse($delete_request->isRead());
    }

    public function testRequestCanBeParsedWhenNoRefIsGiven()
    {
        $json_without_ref = <<<JSON
{
  "force": false
}
JSON;
        $lock_request_without_ref = LockDeleteRequest::buildFromJSONString($json_without_ref);

        $this->assertFalse($lock_request_without_ref->getForce());
        $this->assertNull($lock_request_without_ref->getReference());
    }
}
