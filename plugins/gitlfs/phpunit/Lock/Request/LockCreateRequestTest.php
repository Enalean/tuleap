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

class LockCreateRequestTest extends TestCase
{

    public function testParsingRequest()
    {
        $json = <<<JSON
{
  "path": "test/testFile.png",
  "ref": {
    "name": "refs/heads/master"
  }
}
JSON;
        $lock_request = LockCreateRequest::buildFromJSONString($json);

        $this->assertSame('test/testFile.png', $lock_request->getPath());
        $this->assertSame('refs/heads/master', $lock_request->getReference()->getName());
        $this->assertTrue($lock_request->isWrite());
        $this->assertFalse($lock_request->isRead());
    }

    public function testRequestCanBeParsedWhenNoRefIsGiven()
    {
        $json_without_ref = <<<JSON
{
  "path": "test/testFile.png"
}
JSON;
        $lock_request = LockCreateRequest::buildFromJSONString($json_without_ref);

        $this->assertSame('test/testFile.png', $lock_request->getPath());
        $this->assertNull($lock_request->getReference());
    }
}
