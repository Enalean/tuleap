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

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LockCreateRequestTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testParsingRequest(): void
    {
        $json         = <<<JSON
{
  "path": "test/testFile.png",
  "ref": {
    "name": "refs/heads/master"
  }
}
JSON;
        $lock_request = LockCreateRequest::buildFromJSONString($json);

        self::assertSame('test/testFile.png', $lock_request->getPath());
        $reference = $lock_request->getReference();
        self::assertNotNull($reference);
        self::assertSame('refs/heads/master', $reference->getName());
        self::assertTrue($lock_request->isWrite());
        self::assertFalse($lock_request->isRead());
    }

    public function testRequestCanBeParsedWhenNoRefIsGiven(): void
    {
        $json_without_ref = <<<JSON
{
  "path": "test/testFile.png"
}
JSON;
        $lock_request     = LockCreateRequest::buildFromJSONString($json_without_ref);

        self::assertSame('test/testFile.png', $lock_request->getPath());
        self::assertNull($lock_request->getReference());
    }
}
