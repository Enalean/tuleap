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
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialLoader;
use function Psl\Json\decode as psl_json_decode;

final class PostRegistrationController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly ProvideCurrentUser $user_manager,
        private readonly PublicKeyCredentialLoader $credential_loader,
        private readonly ResponseFactoryInterface $response_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $this->user_manager->getCurrentUser();
        if ($current_user->isAnonymous()) {
            return $this->response_factory->createResponse(401);
        }

        if (empty($body = $request->getBody()->getContents())) {
            return $this->response_factory->createResponse(400, _('Request body is empty'));
        }

        try {
            $request_body = psl_json_decode($body);
        } catch (DecodeException) {
            return $this->response_factory->createResponse(400, _('Request body is not well formed'));
        }
        if (! array_key_exists('response', $request_body)) {
            return $this->response_factory->createResponse(400, _('"response" field is missing from the request body'));
        }
        if (! array_key_exists('name', $request_body)) {
            return $this->response_factory->createResponse(400, _('"name" field is missing from the request body'));
        }
        $response = $request_body['response'];
        $name     = $request_body['name'];
        if (! is_array($response) || ! is_string($name)) {
            return $this->response_factory->createResponse(400, _('Request body is not well formed'));
        }

        try {
            $public_key_credential = $this->credential_loader->loadArray($response);
        } catch (InvalidDataException $e) {
            return $this->response_factory->createResponse(400, _('The result of passkey is not well formed'));
        }

        $authentication_attestation_response = $public_key_credential->getResponse();
        if (! $authentication_attestation_response instanceof AuthenticatorAttestationResponse) {
            return $this->response_factory->createResponse(400, _('The result of passkey is not for registration'));
        }

        // Get options
        // - challenge
        // - user entity
        // - relying party
        // - credential parameters

        // Check attestation response

        // Save source

        return $this->response_factory->createResponse(501);
    }
}
