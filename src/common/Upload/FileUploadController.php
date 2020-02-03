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
use Tuleap\Request\ForbiddenException;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\Tus\TusCORSMiddleware;
use Tuleap\Tus\TusDataStore;
use Tuleap\Tus\TusRequestMethodOverride;
use Tuleap\Tus\TusServer;
use UserManager;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;

final class FileUploadController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var \Tuleap\Tus\TusServer
     */
    private $tus_server;
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    private function __construct(
        TusServer $tus_server,
        UserManager $user_manager,
        StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->tus_server     = $tus_server;
        $this->user_manager   = $user_manager;
        $this->stream_factory = $stream_factory;
    }

    public static function build(TusDataStore $data_store): self
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new self(
            new TusServer($response_factory, $data_store),
            UserManager::instance(),
            HTTPFactoryBuilder::streamFactory(),
            new SapiEmitter(),
            new SessionWriteCloseMiddleware(),
            new RESTCurrentUserMiddleware(\Tuleap\REST\UserManager::build(), new BasicAuthentication()),
            new TuleapRESTCORSMiddleware(),
            new TusCORSMiddleware(),
            new TusRequestMethodOverride($response_factory)
        );
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->user_manager->getCurrentUser()->isAnonymous()) {
            throw new ForbiddenException();
        }

        return $this->tus_server->handle(
            $request->withBody($this->stream_factory->createStreamFromFile('php://input'))
        );
    }
}
