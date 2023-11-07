<?php
/*
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

namespace Tuleap\WebAuthn\Authentication;

use Psl\Json\Exception\DecodeException;
use Tuleap\Config\ConfigKeyHidden;
use Tuleap\Config\ConfigKeyInt;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Fault;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\WebAuthn\Challenge\RetrieveWebAuthnChallenge;
use Tuleap\WebAuthn\Source\GetAllCredentialSourceByUserId;
use Tuleap\WebAuthn\Source\WebAuthnCredentialSource;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\Exception\InvalidDataException;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use function Psl\Json\decode as psl_json_decode;

final class WebAuthnAuthentication
{
    #[FeatureFlagConfigKey('Feature flag to enable/disable passwordless login')]
    #[ConfigKeyInt(0)]
    #[ConfigKeyHidden]
    public const FEATURE_FLAG_LOGIN = 'enable_passwordless_login';

    public function __construct(
        private readonly GetAllCredentialSourceByUserId $source_dao,
        private readonly RetrieveWebAuthnChallenge $challenge_dao,
        private readonly PublicKeyCredentialRpEntity $relying_party_entity,
        private readonly PublicKeyCredentialLoader $credential_loader,
        private readonly AuthenticatorAssertionResponseValidator $assertion_response_validator,
    ) {
    }

    /**
     * @return Ok<null>|Err<Fault>
     */
    public function checkKeyResult(
        \PFUser $current_user,
        string $key_result,
    ): Ok|Err {
        $authenticators = array_map(
            static fn(WebAuthnCredentialSource $source) => $source->getSource()->getPublicKeyCredentialDescriptor(),
            $this->source_dao->getAllByUserId((int) $current_user->getId())
        );
        if (empty($authenticators)) {
            // User have no registered passkey
            return Result::ok(null);
        }

        try {
            $response = psl_json_decode($key_result);
        } catch (DecodeException) {
            return Result::err(Fault::fromMessage(_('The result of passkey is not well formed')));
        }

        if (! is_array($response)) {
            return Result::err(Fault::fromMessage(_('The result of passkey is not well formed')));
        }

        try {
            $public_key_credential = $this->credential_loader->loadArray($response);
        } catch (InvalidDataException) {
            return Result::err(Fault::fromMessage(_('The result of passkey is not well formed')));
        }

        $authentication_assertion_response = $public_key_credential->getResponse();
        if (! $authentication_assertion_response instanceof AuthenticatorAssertionResponse) {
            return Result::err(Fault::fromMessage(_('The result of passkey is not for authentication')));
        }

        return $this->challenge_dao
            ->searchChallenge((int) $current_user->getId())
            ->mapOr(
                function (string $challenge) use ($current_user, $public_key_credential, $authentication_assertion_response, $authenticators) {
                    $options = PublicKeyCredentialRequestOptions::create($challenge)
                        ->allowCredentials(...$authenticators);

                    try {
                        $this->assertion_response_validator->check(
                            $public_key_credential->getRawId(),
                            $authentication_assertion_response,
                            $options,
                            $this->relying_party_entity->getId() ?? '',
                            (string) $current_user->getId()
                        );
                    } catch (\Throwable) {
                        return Result::err(Fault::fromMessage(_('The result of passkey is invalid')));
                    }

                    return Result::ok(null);
                },
                Result::err(Fault::fromMessage(_('The authentication cannot be checked')))
            );
    }
}
