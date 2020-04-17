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
 */

declare(strict_types=1);

namespace Tuleap\OAuth2Server\E2E\RelyingPartyOIDC;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;

final class OAuth2InitFlowController
{
    /**
     * @var OAuth2TestFlowSecretGenerator
     */
    private $secret_generator;
    /**
     * @var OAuth2TestFlowClientCredentialStorage
     */
    private $client_credential_storage;

    public function __construct(OAuth2TestFlowSecretGenerator $secret_generator, OAuth2TestFlowClientCredentialStorage $client_credential_storage)
    {
        $this->secret_generator          = $secret_generator;
        $this->client_credential_storage = $client_credential_storage;
    }

    public function __invoke(Request $request): Response
    {
        parse_str($request->getUri()->getQuery(), $query_params);

        // ⚠️ If you are reading this as an example (please don't) on how to
        // ⚠️ write an OAuth2.0 client or an OIDC relying party please not that the
        // ⚠️ client secret MUST NOT be exposed to the End-User
        // ⚠️ Here it is done this way for the sack of the test, i.e. to have the capability
        // ⚠️ to use a client dynamically register by project admin bot user.
        // ⚠️ Doing this in the real life breaks some security assumptions.
        $client_id     = $query_params['client_id'] ?? null;
        $client_secret = $query_params['client_secret'] ?? null;

        if ($client_id === null || $client_secret === null) {
            return new Response(
                Status::BAD_REQUEST,
                ['Content-Type' => 'text/plain'],
                'Missing client_id or client_secret'
            );
        }
        $this->client_credential_storage->setCredentials($client_id, $client_secret);

        $redirect_parameters = [
            'response_type'         => 'code',
            'client_id'             => $client_id,
            'client_secret'         => $client_secret,
            'scope'                 => 'openid offline_access profile',
            'redirect_uri'          => OAuth2TestFlowConstants::REDIRECT_URI,
            'state'                 => $this->secret_generator->getState(),
            'code_challenge'        => sodium_bin2base64(hash('sha256', $this->secret_generator->getPKCEChallenge(), true), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
            'code_challenge_method' => 'S256'
        ];
        return new Response(
            Status::FOUND,
            ['Location' => OAuth2TestFlowConstants::BASE_CLIENT_URI . '/oauth2/authorize?' . http_build_query($redirect_parameters)]
        );
    }
}
