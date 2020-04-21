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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

declare(strict_types=1);

namespace Tuleap\Userlog;

use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;

final class UserlogAccessTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsAUserlogAccessObjectFromRequest()
    {
        $project = Mockery::mock(Project::class);
        $user    = Mockery::mock(PFUser::class);

        $request = Mockery::mock(HTTPRequest::class);

        $request->shouldReceive('getProject')->andReturn($project);
        $request->shouldReceive('getCurrentUser')->andReturn($user);
        $request->shouldReceive('getFromServer')->with('HTTP_USER_AGENT')->andReturn('user_agent');
        $request->shouldReceive('getFromServer')->with('REQUEST_METHOD')->andReturn('GET');
        $request->shouldReceive('getFromServer')->with('REQUEST_URI')->andReturn('/projects/');
        $request->shouldReceive('getIPAddress')->andReturn('127.0.0.1');
        $request->shouldReceive('getFromServer')->with('HTTP_REFERER')->andReturn('referer');

        $userlog_access = UserlogAccess::buildFromRequest($request);

        $this->assertEquals($project, $userlog_access->getProject());
        $this->assertEquals($user, $userlog_access->getUser());
        $this->assertEquals('user_agent', $userlog_access->getUserAgent());
        $this->assertEquals('GET', $userlog_access->getRequestMethod());
        $this->assertEquals('/projects/', $userlog_access->getRequestUri());
        $this->assertEquals('127.0.0.1', $userlog_access->getIpAddress());
        $this->assertEquals('referer', $userlog_access->getHttpReferer());
    }
}
