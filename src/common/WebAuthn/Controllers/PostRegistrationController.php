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
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Layout\Feedback\ISerializeFeedback;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\WebAuthn\Challenge\RetrieveWebAuthnChallenge;
use Tuleap\WebAuthn\Source\SaveCredentialSourceWithName;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use function Psl\Json\decode as psl_json_decode;

final class PostRegistrationController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly ProvideCurrentUser $user_manager,
        private readonly RetrieveWebAuthnChallenge $challenge_dao,
        private readonly SaveCredentialSourceWithName $source_dao,
        private readonly PublicKeyCredentialRpEntity $relying_party_entity,
        private readonly array $credential_parameters,
        private readonly PublicKeyCredentialLoader $credential_loader,
        private readonly AuthenticatorAttestationResponseValidator $attestation_response_validator,
        private readonly ResponseFactoryInterface $response_factory,
        private readonly RestlerErrorResponseBuilder $error_response_builder,
        private readonly ISerializeFeedback $serialize_feedback,
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
        if (! array_key_exists('response', $request_body)) {
            return $this->error_response_builder->build(400, _('"response" field is missing from the request body'));
        }
        if (! array_key_exists('name', $request_body)) {
            return $this->error_response_builder->build(400, _('"name" field is missing from the request body'));
        }
        $response = $request_body['response'];
        $name     = $request_body['name'];
        if (! is_array($response) || ! is_string($name) || empty($name)) {
            return $this->error_response_builder->build(400, _('Request body is not well formed'));
        }

        try {
            $public_key_credential = $this->credential_loader->loadArray($response);
        } catch (InvalidDataException $e) {
            return $this->error_response_builder->build(400, _('The result of passkey is not well formed'));
        }

        $authentication_attestation_response = $public_key_credential->getResponse();
        if (! $authentication_attestation_response instanceof AuthenticatorAttestationResponse) {
            return $this->error_response_builder->build(400, _('The result of passkey is not for registration'));
        }

        return $this->challenge_dao
            ->searchChallenge((int) $current_user->getId())
            ->mapOr(
                function (string $challenge) use ($current_user, $authentication_attestation_response, $name) {
                    $user_entity = new PublicKeyCredentialUserEntity(
                        $current_user->getUserName(),
                        (string) $current_user->getId(),
                        $current_user->getRealName()
                    );

                    $options = PublicKeyCredentialCreationOptions::create(
                        $this->relying_party_entity,
                        $user_entity,
                        $challenge,
                        $this->credential_parameters
                    )->setAttestation(PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE);

                    try {
                        $credential_source = $this->attestation_response_validator->check(
                            $authentication_attestation_response,
                            $options,
                            $this->relying_party_entity->getId() ?? ''
                        );
                    } catch (\Throwable $e) {
                        return $this->error_response_builder->build(400, _('The result of passkey is invalid'));
                    }

                    $this->source_dao->saveCredentialSourceWithName($credential_source, $name);

                    $this->serialize_feedback->serialize(
                        $current_user,
                        new NewFeedback(\Feedback::SUCCESS, sprintf(_("Key '%s' successfully added"), $name))
                    );

                    return $this->response_factory->createResponse(200);
                },
                $this->error_response_builder->build(400, _('The registration cannot be checked'))
            );
    }
}
