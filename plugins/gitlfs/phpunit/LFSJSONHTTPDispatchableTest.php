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
    public function testRequestAcceptingGitLFSResponseAreProcessed($accept_header)
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

    public function providerAcceptHeader()
    {
        return [
            ['application/vnd.git-lfs+json'],
            ['application/vnd.git-lfs+json; charset=utf-8']
        ];
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testRequestNotAcceptingGitLFSResponseAreNotProcessed()
    {
        $dispatchable = \Mockery::mock(DispatchableWithRequestNoAuthz::class);
        $dispatchable->shouldReceive('process')->never();

        $request = \Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getFromServer')->with('HTTP_ACCEPT')->andReturns('text/plain');

        $lfs_json_dispatchable = new LFSJSONHTTPDispatchable($dispatchable);
        $lfs_json_dispatchable->process(
            $request,
            \Mockery::mock(BaseLayout::class),
            []
        );
    }
}
