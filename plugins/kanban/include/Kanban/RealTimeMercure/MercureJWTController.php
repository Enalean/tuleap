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

namespace Tuleap\Kanban\RealTimeMercure;

use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_KanbanFactory;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Log\LoggerInterface;
use Tuleap\JWT\generators\MercureJWTGenerator;
use Tuleap\RealTimeMercure\MercureClient;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;

final class MercureJWTController extends DispatchablePSR15Compatible
{
    public function __construct(
        private AgileDashboard_KanbanFactory $kanban_factory,
        private LoggerInterface $logger,
        private ResponseFactoryInterface $response_factory,
        private StreamFactoryInterface $stream_factory,
        private ProvideCurrentUser $user_provider,
        private MercureJWTGenerator $mercure_jwt_generator,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (! \ForgeConfig::getFeatureFlag(MercureClient::FEATURE_FLAG_KANBAN_KEY)) {
            return $this->response_factory->createResponse(404);
        }
        $id = (int) $request->getAttribute("kanban_id");
        try {
            $this->kanban_factory->getKanban($this->user_provider->getCurrentUser(), $id);
        } catch (AgileDashboard_KanbanCannotAccessException | \AgileDashboard_KanbanNotFoundException $e) {
            $this->logger->info('Kanban error in generating the token in Kanban JWT Request', ['exception' => $e]);
            return $this->response_factory->createResponse(404);
        }
        $token = $this->mercure_jwt_generator->getTokenWithoutSubscription('Kanban', $id, $this->user_provider->getCurrentUser());
        if ($token ===  null) {
            $this->logger->info('Error while generating the token in Kanban JWT Request');
            return $this->response_factory->createResponse(404);
        }
        return $this->response_factory->createResponse()->withBody(
            $this->stream_factory->createStream(
                $token->getString()
            )
        );
    }
}
