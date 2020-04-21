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
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSJSONHTTPDispatchableTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @runInSeparateProcess
     * @dataProvider providerAcceptHeader
     */
    public function testRequestAcceptingGitLFSResponseAreProcessed($accept_header): void
    {
        $dispatchable = \Mockery::mock(DispatchableWithRequestNoAuthz::class);
        $dispatchable->shouldReceive('process')->once();

        $request = \Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_ACCEPT')
            ->andReturns($accept_header);

        $lfs_json_dispatchable = new LFSJSONHTTPDispatchable($dispatchable);
        $lfs_json_dispatchable->process(
            $request,
            \Mockery::mock(BaseLayout::class),
            []
        );
    }

    public function providerAcceptHeader(): array
    {
        return [
            ['application/vnd.git-lfs+json'],
            ['application/vnd.git-lfs+json; charset=utf-8']
        ];
    }

    public function testRequestNotAcceptingGitLFSResponseAreNotProcessed(): void
    {
        $dispatchable = \Mockery::mock(DispatchableWithRequestNoAuthz::class);
        $dispatchable->shouldReceive('process')->never();

        $request = \Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_ACCEPT')->andReturns('text/plain');

        $lfs_json_dispatchable = new LFSJSONHTTPDispatchable($dispatchable);

        $this->expectException(\RuntimeException::class);

        $lfs_json_dispatchable->process(
            $request,
            \Mockery::mock(BaseLayout::class),
            []
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testGitLFSErrorsAreGivenAccordingToTheGitLFSSpecification(): void
    {
        $dispatchable = \Mockery::mock(DispatchableWithRequestNoAuthz::class);

        $error_message = 'Error message test';
        $error_code    = 444;
        $git_lfs_error = new class ($error_message, $error_code) extends GitLFSException {
            public function __construct(string $message, int $code)
            {
                parent::__construct($message, $code);
            }
        };

        $dispatchable->shouldReceive('process')->andThrows($git_lfs_error);

        $request = \Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_ACCEPT')
            ->andReturns('application/vnd.git-lfs+json');

        $lfs_json_dispatchable = new LFSJSONHTTPDispatchable($dispatchable);
        $lfs_json_dispatchable->process(
            $request,
            \Mockery::mock(BaseLayout::class),
            []
        );

        $this->expectOutputString(json_encode(['message' => $error_message]));
        $this->assertSame($error_code, http_response_code());
    }
}
