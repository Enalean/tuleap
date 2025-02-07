<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\GitLFS;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

final class LFSJSONHTTPDispatchableTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @runInSeparateProcess
     * @dataProvider providerAcceptHeader
     */
    public function testRequestAcceptingGitLFSResponseAreProcessed(string $accept_header): void
    {
        $dispatchable = $this->createMock(DispatchableWithRequestNoAuthz::class);
        $dispatchable->expects(self::once())->method('process');

        $request = $this->createMock(HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_ACCEPT')
            ->willReturn($accept_header);

        $lfs_json_dispatchable = new LFSJSONHTTPDispatchable($dispatchable);
        $lfs_json_dispatchable->process(
            $request,
            $this->createStub(BaseLayout::class),
            []
        );
    }

    public static function providerAcceptHeader(): array
    {
        return [
            ['application/vnd.git-lfs+json'],
            ['application/vnd.git-lfs+json; charset=utf-8'],
        ];
    }

    public function testRequestNotAcceptingGitLFSResponseAreNotProcessed(): void
    {
        $dispatchable = $this->createMock(DispatchableWithRequestNoAuthz::class);
        $dispatchable->expects(self::never())->method('process');

        $request = $this->createMock(HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_ACCEPT')->willReturn('text/plain');

        $lfs_json_dispatchable = new LFSJSONHTTPDispatchable($dispatchable);

        $this->expectException(\RuntimeException::class);

        $lfs_json_dispatchable->process(
            $request,
            $this->createStub(BaseLayout::class),
            []
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testGitLFSErrorsAreGivenAccordingToTheGitLFSSpecification(): void
    {
        $dispatchable = $this->createStub(DispatchableWithRequestNoAuthz::class);

        $error_message = 'Error message test';
        $error_code    = 444;
        $git_lfs_error = new class ($error_message, $error_code) extends GitLFSException {
            public function __construct(string $message, int $code)
            {
                parent::__construct($message, $code);
            }
        };

        $dispatchable->method('process')->willThrowException($git_lfs_error);

        $request = $this->createMock(HTTPRequest::class);
        $request->method('getFromServer')->with('HTTP_ACCEPT')
            ->willReturn('application/vnd.git-lfs+json');

        $lfs_json_dispatchable = new LFSJSONHTTPDispatchable($dispatchable);
        $lfs_json_dispatchable->process(
            $request,
            $this->createStub(BaseLayout::class),
            []
        );

        $this->expectOutputString(json_encode(['message' => $error_message]));
        self::assertSame($error_code, http_response_code());
    }
}
