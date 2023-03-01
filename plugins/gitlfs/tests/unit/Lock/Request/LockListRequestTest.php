<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

final class LockListRequestTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildFromRequest(): void
    {
        $request = $this->createStub(\HTTPRequest::class);

        $request->method('get')->willReturnCallback(
            fn (string $variable): string => match ($variable) {
                'id' => '2',
                'path' => 'test/toto.bin',
                'refspec' => 'refs/heads/master',
            }
        );

        $list_request = LockListRequest::buildFromHTTPRequest($request);

        $this->assertSame('test/toto.bin', $list_request->getPath());
        $this->assertSame(2, $list_request->getId());
        $this->assertSame('refs/heads/master', $list_request->getReference()->getName());
    }

    public function testRequestCanBeParsedWhenNoRefIsGiven(): void
    {
        $request_without_ref = $this->createStub(\HTTPRequest::class);

        $request_without_ref->method('get')->willReturnCallback(
            fn (string $variable): ?string => match ($variable) {
                'id' => '2',
                'path' => 'test/toto.bin',
                'refspec' => null,
            }
        );

        $list_request = LockListRequest::buildFromHTTPRequest($request_without_ref);

        $this->assertSame('test/toto.bin', $list_request->getPath());
        $this->assertSame(2, $list_request->getId());
        $this->assertNull($list_request->getReference());
    }

    /**
     * Git LFS before v2.8.0 used an upload token to get the list of locks (i.e. write request)
     * Git LFS v2.8.0+ uses a download to get the list of locks (i.e. read request)
     *
     * Git LFS requests should be considered as both read and write requests to keep the compatibility
     * with Git LFS before v2.8.0.
     * https://github.com/git-lfs/git-lfs/pull/3715
     */
    public function testListRequestShouldBeConsideredAsBothReadAndWriteRequest(): void
    {
        $request = $this->createStub(\HTTPRequest::class);
        $request->method('get')->willReturnCallback(
            fn (string $variable): string => match ($variable) {
                'id' => '2',
                'path' => 'test/toto.bin',
                'refspec' => 'refs/heads/master',
            }
        );
        $list_request = LockListRequest::buildFromHTTPRequest($request);

        $this->assertTrue($list_request->isRead());
        $this->assertTrue($list_request->isWrite());
    }
}
