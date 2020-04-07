<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\REST;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Rest_Exception_InvalidTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Request\ForbiddenException;
use Tuleap\User\AccessKey\AccessKeyException;
use Tuleap\User\OAuth2\OAuth2Exception;
use User_StatusInvalidException;

final class RESTCurrentUserMiddleware implements MiddlewareInterface
{
    /**
     * @var UserManager
     */
    private $rest_user_manager;
    /**
     * @var BasicAuthentication
     */
    private $basic_rest_authentication;

    public function __construct(UserManager $rest_user_manager, BasicAuthentication $basic_rest_authentication)
    {
        $this->rest_user_manager         = $rest_user_manager;
        $this->basic_rest_authentication = $basic_rest_authentication;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->basic_rest_authentication->__isAllowed();
        try {
            $current_user = $this->rest_user_manager->getCurrentUser(null);
        } catch (AccessKeyException | SplitTokenException | Rest_Exception_InvalidTokenException | User_StatusInvalidException | OAuth2Exception $exception) {
            throw new ForbiddenException($exception->getMessage());
        }

        return $handler->handle($request->withAttribute(self::class, $current_user));
    }
}
