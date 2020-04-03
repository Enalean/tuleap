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
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class LFSJSONHTTPDispatchable implements DispatchableWithRequestNoAuthz
{
    private const GIT_LFS_MIME_TYPE = 'application/vnd.git-lfs+json';

    /**
     * @var DispatchableWithRequestNoAuthz
     */
    private $dispatchable_with_request;

    public function __construct(DispatchableWithRequestNoAuthz $dispatchable_with_request)
    {
        $this->dispatchable_with_request = $dispatchable_with_request;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables): void
    {
        if (! $this->doesRequestAcceptGitLFSResponse($request)) {
            throw new \RuntimeException(self::GIT_LFS_MIME_TYPE . ' data must be an acceptable response', 406);
        }
        header('Content-Type: ' . self::GIT_LFS_MIME_TYPE);
        try {
            $this->dispatchable_with_request->process($request, $layout, $variables);
        } catch (NotFoundException | ForbiddenException | GitLFSException $exception) {
            $status_code = $exception->getCode();
            if ($status_code === 0) {
                $status_code = 500;
            }
            http_response_code($status_code);
            echo json_encode(['message' => $exception->getMessage()]);
        }
    }

    private function doesRequestAcceptGitLFSResponse(HTTPRequest $request): bool
    {
        return stripos(trim($request->getFromServer('HTTP_ACCEPT')), self::GIT_LFS_MIME_TYPE) === 0;
    }
}
