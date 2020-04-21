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

class LockVerifyRequestTest extends TestCase
{

    public function testParsingRequest()
    {
        $json = <<<JSON
{
  "ref": {
    "name": "refs/heads/master"
  }
}
JSON;
        $verify_request = LockVerifyRequest::buildFromJSONString($json);

        $this->assertSame('refs/heads/master', $verify_request->getReference()->getName());
        $this->assertTrue($verify_request->isWrite());
        $this->assertFalse($verify_request->isRead());
    }

    public function testRequestCanBeParsedWhenNoRefIsGiven()
    {
        $json_without_ref = <<<JSON
{}
JSON;
        $verify_request_without_ref = LockVerifyRequest::buildFromJSONString($json_without_ref);

        $this->assertNull($verify_request_without_ref->getReference());
    }
}
