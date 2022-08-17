<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\Request\NotFoundException;

final class OnlyOfficeEditorCrossOriginAssetsController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    public function __construct(
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        private string $path_to_assets_dir,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $asset_name = $request->getQueryParams()['name'] ?? '';

        $matches = [];
        if (preg_match('@.+/(?P<js_asset_name>onlyoffice-editor\.[[:xdigit:]]+\.js)$@', $asset_name, $matches) !== 1) {
            throw new NotFoundException();
        }

        $js_asset_name = $matches['js_asset_name'] ?? '';

        $js_asset_resource = @fopen($this->path_to_assets_dir . '/' . $js_asset_name, 'rb');
        if ($js_asset_resource === false) {
            throw new NotFoundException();
        }

        return $this->response_factory->createResponse()
            ->withHeader('Content-Type', 'application/javascript')
            ->withHeader('Access-Control-Allow-Origin', 'null')
            ->withBody($this->stream_factory->createStreamFromResource($js_asset_resource));
    }
}
