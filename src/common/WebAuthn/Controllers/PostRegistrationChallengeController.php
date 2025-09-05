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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RestlerErrorResponseBuilder;
use Tuleap\Http\Response\JSONResponseBuilder;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\User\ProvideCurrentUser;
use Tuleap\WebAuthn\Challenge\SaveWebAuthnChallenge;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

final class PostRegistrationChallengeController extends DispatchablePSR15Compatible
{
    /**
     * @param PublicKeyCredentialParameters[] $credential_parameters
     */
    public function __construct(
        private readonly ProvideCurrentUser $user_manager,
        private readonly SaveWebAuthnChallenge $challenge_dao,
        private readonly PublicKeyCredentialSourceRepository $source_dao,
        private readonly PublicKeyCredentialRpEntity $relying_party_entity,
        private readonly array $credential_parameters,
        private readonly JSONResponseBuilder $response_builder,
        private readonly RestlerErrorResponseBuilder $error_response_builder,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $current_user = $this->user_manager->getCurrentUser();
        if ($current_user->isAnonymous()) {
            return $this->error_response_builder->build(401);
        }

        $user_entity = new PublicKeyCredentialUserEntity(
            $current_user->getUserName(),
            (string) $current_user->getId(),
            $current_user->getRealName()
        );

        $challenge = random_bytes(32);

        $registered_sources = array_map(
            static fn(PublicKeyCredentialSource $source) => $source->getPublicKeyCredentialDescriptor(),
            $this->source_dao->findAllForUserEntity($user_entity)
        );

        $options = PublicKeyCredentialCreationOptions::create(
            $this->relying_party_entity,
            $user_entity,
            $challenge,
            $this->credential_parameters,
            null,
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $registered_sources
        );

        $this->challenge_dao->saveChallenge(
            (int) $current_user->getId(),
            $challenge
        );

        return $this->response_builder->fromData($options->jsonSerialize())->withStatus(200);
    }
}
