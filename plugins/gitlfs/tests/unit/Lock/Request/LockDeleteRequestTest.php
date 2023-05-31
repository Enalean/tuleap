<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\GitLFS\Lock\Request;

final class LockDeleteRequestTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testParsingRequest(): void
    {
        $json           = <<<JSON
{
  "force": true,
  "ref": {
    "name": "refs/heads/master"
  }
}
JSON;
        $delete_request = LockDeleteRequest::buildFromJSONString($json);

        $reference = $delete_request->getReference();
        self::assertNotNull($reference);
        self::assertSame('refs/heads/master', $reference->getName());
        self::assertTrue($delete_request->getForce());
        self::assertTrue($delete_request->isWrite());
        self::assertFalse($delete_request->isRead());
    }

    public function testRequestCanBeParsedWhenNoRefIsGiven(): void
    {
        $json_without_ref         = <<<JSON
{
  "force": false
}
JSON;
        $lock_request_without_ref = LockDeleteRequest::buildFromJSONString($json_without_ref);

        self::assertFalse($lock_request_without_ref->getForce());
        self::assertNull($lock_request_without_ref->getReference());
    }
}
