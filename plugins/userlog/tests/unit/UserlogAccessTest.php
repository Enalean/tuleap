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
use PFUser;
use Project;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserlogAccessTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsAUserlogAccessObjectFromRequest(): void
    {
        $project = $this->createMock(Project::class);
        $user    = $this->createMock(PFUser::class);

        $request = $this->createMock(HTTPRequest::class);

        $request->method('getProject')->willReturn($project);
        $request->method('getCurrentUser')->willReturn($user);
        $request->method('getFromServer')->with()->willReturnMap(
            [
                ['HTTP_USER_AGENT', 'user_agent'],
                ['REQUEST_METHOD', 'GET'],
                ['REQUEST_URI', '/projects/'],
                ['HTTP_REFERER', 'referer'],
            ],
        );
        $request->method('getIPAddress')->willReturn('127.0.0.1');

        $userlog_access = UserlogAccess::buildFromRequest($request);

        self::assertEquals($project, $userlog_access->getProject());
        self::assertEquals($user, $userlog_access->getUser());
        self::assertEquals('user_agent', $userlog_access->getUserAgent());
        self::assertEquals('GET', $userlog_access->getRequestMethod());
        self::assertEquals('/projects/', $userlog_access->getRequestUri());
        self::assertEquals('127.0.0.1', $userlog_access->getIpAddress());
        self::assertEquals('referer', $userlog_access->getHttpReferer());
    }
}
