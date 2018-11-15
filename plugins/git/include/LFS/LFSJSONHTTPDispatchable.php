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

namespace Tuleap\Git\LFS;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSJSONHTTPDispatchable implements DispatchableWithRequestNoAuthz
{
    const GIT_LFS_MIME_TYPE = 'application/vnd.git-lfs+json';

    /**
     * @var DispatchableWithRequestNoAuthz
     */
    private $dispatchable_with_request;

    public function __construct(DispatchableWithRequestNoAuthz $dispatchable_with_request)
    {
        $this->dispatchable_with_request = $dispatchable_with_request;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $this->doesRequestAcceptGitLFSResponse($request)) {
            throw new \RuntimeException(self::GIT_LFS_MIME_TYPE . ' data must be an acceptable response', 406);
        }
        header('Content-Type: ' . self::GIT_LFS_MIME_TYPE);
        $this->dispatchable_with_request->process($request, $layout, $variables);
    }

    private function doesRequestAcceptGitLFSResponse(\HttpRequest $request)
    {
        return stripos(self::GIT_LFS_MIME_TYPE, trim($request->getFromServer('HTTP_ACCEPT'))) === 0;
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        return $this->dispatchable_with_request->userCanAccess($url_verification, $request, $variables);
    }
}
