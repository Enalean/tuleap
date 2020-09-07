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

namespace Tuleap\User\Account;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use UserManager;

final class UserWellKnownChangePasswordController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /**
     * @var EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var ResponseFactoryInterface
     */
    private $response_factory;
    /**
     * @var StreamFactoryInterface
     */
    private $stream_factory;

    public function __construct(
        UserManager $user_manager,
        EventDispatcherInterface $event_dispatcher,
        ResponseFactoryInterface $response_factory,
        StreamFactoryInterface $stream_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack
    ) {
        parent::__construct($emitter, ...$middleware_stack);
        $this->user_manager     = $user_manager;
        $this->event_dispatcher = $event_dispatcher;
        $this->response_factory = $response_factory;
        $this->stream_factory   = $stream_factory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $this->user_manager->getCurrentUser();
        if ($current_user->isAnonymous()) {
            return $this->response_factory->createResponse(404)
                ->withHeader('Content-Type', 'text/plain')
                ->withBody(
                    $this->stream_factory->createStream('Change password page is not accessible to anonymous user')
                );
        }

        $password_pre_update_event = $this->event_dispatcher->dispatch(new PasswordPreUpdateEvent($current_user));
        if (! $password_pre_update_event->areUsersAllowedToChangePassword()) {
            return $this->response_factory->createResponse(404)
                ->withHeader('Content-Type', 'text/plain')
                ->withBody(
                    $this->stream_factory->createStream('Change password page is not available')
                );
        }

        return $this->response_factory->createResponse(302)
            ->withHeader(
                'Location',
                sprintf('https://%s%s', \ForgeConfig::get('sys_https_host'), DisplaySecurityController::URL)
            );
    }
}
