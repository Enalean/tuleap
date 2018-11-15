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
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\DispatchableWithRequestNoAuthz;

class LFSFeatureFlagDispatchable implements DispatchableWithRequestNoAuthz
{
    /**
     * @var DispatchableWithRequest
     */
    private $dispatchable_with_request;

    public function __construct(DispatchableWithRequestNoAuthz $dispatchable_with_request)
    {
        $this->dispatchable_with_request = $dispatchable_with_request;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (\ForgeConfig::get('git_lfs_dev_enable')) {
            $this->dispatchable_with_request->process($request, $layout, $variables);
            return;
        }
        http_response_code(501);
        echo json_encode(['message' => 'Git LFS support is an ongoing development']);
    }

    public function userCanAccess(\URLVerification $url_verification, \HTTPRequest $request, array $variables)
    {
        return $this->dispatchable_with_request->userCanAccess($url_verification, $request, $variables);
    }
}
