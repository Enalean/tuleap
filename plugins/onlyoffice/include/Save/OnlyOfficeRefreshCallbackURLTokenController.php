<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Save;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\NeverThrow\Fault;

final class OnlyOfficeRefreshCallbackURLTokenController extends \Tuleap\Request\DispatchablePSR15Compatible
{
    public function __construct(
        private OnlyOfficeSaveDocumentTokenRefresher $token_refresher,
        private CallbackURLSaveTokenIdentifierExtractor $callback_url_save_token_identifier_extractor,
        private ResponseFactoryInterface $response_factory,
        private LoggerInterface $logger,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->callback_url_save_token_identifier_extractor
            ->extractSaveTokenIdentifierFromTheCallbackURL(new ConcealedString($request->getBody()->getContents()))
            ->match(
                function (ConcealedString $save_token): ResponseInterface {
                    return $this->token_refresher->refreshToken($save_token, new \DateTimeImmutable())
                        ->match(
                            fn (): ResponseInterface => $this->response_factory->createResponse(204),
                            function (Fault $fault): ResponseInterface {
                                Fault::writeToLogger($fault, $this->logger, LogLevel::INFO);
                                return $this->response_factory->createResponse(400);
                            }
                        );
                },
                function (Fault $fault): ResponseInterface {
                    Fault::writeToLogger($fault, $this->logger, LogLevel::DEBUG);
                    return $this->response_factory->createResponse(204);
                }
            );
    }
}
