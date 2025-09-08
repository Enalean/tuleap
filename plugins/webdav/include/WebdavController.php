<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\WebDAV;

use HTTPRequest;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use WebDAVAuthentication;

final class WebdavController implements DispatchableWithRequestNoAuthz
{
    public const ROUTE_BASE = '/plugins/webdav';

    public const VERBS = [
        'GET',
        'HEAD',
        'POST',
        'PUT',
        'DELETE',
        'TRACE',
        'OPTIONS',
        'CONNECT',
        'PATCH',
        'COPY',
        'LOCK',
        'MKCOL',
        'MOVE',
        'PROPFIND',
        'PROPPATCH',
        'UNLOCK',
    ];

    /**
     * @var WebDAVAuthentication
     */
    private $authentication;
    /**
     * @var ServerBuilder
     */
    private $server_builder;

    public function __construct(WebDAVAuthentication $authentication, ServerBuilder $server_builder)
    {
        $this->authentication = $authentication;
        $this->server_builder = $server_builder;
    }

    #[\Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $this->server_builder->getServerOnSubPath($this->authentication->authenticate())->start();
    }

    public static function getFastRoutePattern(): string
    {
        return self::ROUTE_BASE . '[/{path:.*}]';
    }
}
