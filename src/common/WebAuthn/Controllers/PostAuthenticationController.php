<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\WebAuthn\Controllers;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Psl\Json\Exception\DecodeException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialLoader;
use function Psl\Json\decode as psl_json_decode;

final class PostAuthenticationController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly ProvideCurrentUser $user_manager,
        private readonly PublicKeyCredentialLoader $credential_loader,
        private readonly RestlerErrorResponseBuilder $error_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $this->user_manager->getCurrentUser();
        if ($current_user->isAnonymous()) {
            return $this->error_response_builder->build(401);
        }

        if (empty($body = $request->getBody()->getContents())) {
            return $this->error_response_builder->build(400, _('Request body is empty'));
        }

        try {
            $request_body = psl_json_decode($body);
        } catch (DecodeException) {
            return $this->error_response_builder->build(400, _('Request body is not well formed'));
        }

        if (! is_array($request_body)) {
            return $this->error_response_builder->build(400, _('Request body is not well formed'));
        }

        try {
            $public_key_credential = $this->credential_loader->loadArray($request_body);
        } catch (InvalidDataException) {
            return $this->error_response_builder->build(400, _('The result of passkey is not well formed'));
        }

        $authentication_assertion_response = $public_key_credential->getResponse();
        if (! $authentication_assertion_response instanceof AuthenticatorAssertionResponse) {
            return $this->error_response_builder->build(400, _('The result of passkey is not for authentication'));
        }

        return $this->error_response_builder->build(501);
    }
}
