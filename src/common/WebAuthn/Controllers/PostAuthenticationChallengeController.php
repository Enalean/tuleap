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
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\DispatchableWithRequestNoAuthz;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\User\RetrieveUserByUserName;
use Tuleap\WebAuthn\Challenge\SaveWebAuthnChallenge;
use Tuleap\WebAuthn\Source\GetAllCredentialSourceByUserId;
use Tuleap\WebAuthn\Source\WebAuthnCredentialSource;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialRequestOptions;
use function Psl\Json\decode as psl_json_decode;

final class PostAuthenticationChallengeController extends DispatchablePSR15Compatible implements DispatchableWithRequestNoAuthz
{
    public function __construct(
        private readonly ProvideCurrentUser&RetrieveUserByUserName $user_manager,
        private readonly GetAllCredentialSourceByUserId $source_dao,
        private readonly SaveWebAuthnChallenge $challenge_dao,
        private readonly JSONResponseBuilder $json_response_builder,
        private readonly RestlerErrorResponseBuilder $error_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $this->user_manager->getCurrentUser();
        $is_anonymous = $current_user->isAnonymous();
        if ($is_anonymous) {
            if (empty($body = $request->getBody()->getContents())) {
                return $this->error_response_builder->build(400, _('Request body is empty'));
            }

            try {
                $request_body = psl_json_decode($body);
            } catch (DecodeException) {
                return $this->error_response_builder->build(400, _('Request body is not well formed'));
            }
            if (! array_key_exists('username', $request_body)) {
                return $this->error_response_builder->build(400, _('"username" field is missing from the request body'));
            }
            $username = $request_body['username'];
            if (! is_string($username)) {
                return $this->error_response_builder->build(400, _('Request body is not well formed'));
            }

            $current_user = $this->user_manager->getUserByUserName($username);
            if ($current_user === null) {
                return $this->error_response_builder->build(404, sprintf(_('User %s not found'), $username));
            }
        }

        return $this->generateResponseForUser($current_user, $is_anonymous);
    }

    private function generateResponseForUser(\PFUser $user, bool $is_anonymous): ResponseInterface
    {
        $authenticators = array_map(
            static fn(WebAuthnCredentialSource $source) => $source->getSource()->getPublicKeyCredentialDescriptor(),
            $this->source_dao->getAllByUserId((int) $user->getId())
        );
        if (empty($authenticators)) {
            if ($is_anonymous) {
                $authenticators = [
                    PublicKeyCredentialDescriptor::create(PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY, 'false_id'),
                ];
            } else {
                return $this->error_response_builder->build(403, _('You have to register your passkey before authenticate with it'));
            }
        }

        $challenge = random_bytes(32);

        $options = PublicKeyCredentialRequestOptions::create($challenge)
            ->allowCredentials(...$authenticators);

        $this->challenge_dao->saveChallenge(
            (int) $user->getId(),
            $challenge
        );

        return $this->json_response_builder->fromData($options->jsonSerialize())->withStatus(200);
    }
}
