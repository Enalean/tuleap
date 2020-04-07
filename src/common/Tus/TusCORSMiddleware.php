<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Tus;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TusCORSMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $response = $response->withHeader('Access-Control-Allow-Methods', ['OPTIONS', 'HEAD', 'PATCH', 'POST', 'DELETE']);
        $response = $response->withAddedHeader(
            'Access-Control-Allow-Headers',
            ['Tus-Resumable', 'Upload-Offset', 'Upload-Length', 'X-HTTP-Method-Override']
        );
        $response = $response->withAddedHeader(
            'Access-Control-Expose-Headers',
            ['Tus-Version', 'Tus-Resumable', 'Upload-Offset', 'Upload-Length', 'Tus-Extension', 'Tus-Max-Size']
        );

        return $response;
    }
}
