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

namespace Tuleap\Upload;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\Tus\TusCORSMiddleware;
use Tuleap\Tus\TusDataStore;
use Tuleap\Tus\TusRequestMethodOverride;
use Tuleap\Tus\TusServer;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

final class FileUploadController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    private function __construct(
        private readonly TusServer $tus_server,
        private readonly StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public static function build(TusDataStore $data_store, MiddlewareInterface $current_user_provider): self
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new self(
            new TusServer($response_factory, $data_store),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter(),
            new SessionWriteCloseMiddleware(),
            $current_user_provider,
            new TuleapRESTCORSMiddleware(),
            new TusCORSMiddleware(),
            new TusRequestMethodOverride($response_factory)
        );
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->tus_server->handle(
            $request->withBody($this->stream_factory->createStreamFromFile('php://input'))
        );
    }
}
