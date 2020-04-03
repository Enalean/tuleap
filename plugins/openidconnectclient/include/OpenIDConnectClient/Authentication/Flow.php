<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Authentication;

use Tuleap\OpenIDConnectClient\Authentication\Authorization\AuthorizationResponse;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestSender;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestSender;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;

class Flow
{
    /**
     * @var StateManager
     */
    private $state_manager;
    /**
     * @var ProviderManager
     */
    private $provider_manager;
    /**
     * @var TokenRequestCreator
     */
    private $token_request_creator;
    /**
     * @var TokenRequestSender
     */
    private $token_request_sender;
    /**
     * @var IDTokenVerifier
     */
    private $id_token_verifier;
    /**
     * @var UserInfoRequestCreator
     */
    private $user_info_request_creator;
    /**
     * @var UserInfoRequestSender
     */
    private $user_info_request_sender;

    public function __construct(
        StateManager $state_manager,
        ProviderManager $provider_manager,
        TokenRequestCreator $token_request_creator,
        TokenRequestSender $token_request_sender,
        IDTokenVerifier $id_token_verifier,
        UserInfoRequestCreator $user_info_request_creator,
        UserInfoRequestSender $user_info_request_sender
    ) {
        $this->state_manager             = $state_manager;
        $this->provider_manager          = $provider_manager;
        $this->token_request_creator     = $token_request_creator;
        $this->token_request_sender      = $token_request_sender;
        $this->id_token_verifier         = $id_token_verifier;
        $this->user_info_request_creator = $user_info_request_creator;
        $this->user_info_request_sender  = $user_info_request_sender;
    }

    /**
     * @throws MalformedIDTokenException
     * @throws \Http\Client\Exception
     * @throws \Tuleap\OpenIDConnectClient\Provider\ProviderNotFoundException
     */
    public function process(\HTTPRequest $request): FlowResponse
    {
        $authorization_response = AuthorizationResponse::buildFromHTTPRequest($request);
        $signed_state           = $authorization_response->getState();
        $state                  = $this->state_manager->validateState($signed_state);
        $provider               = $this->provider_manager->getById($state->getProviderId());

        $token_request  = $this->token_request_creator->createTokenRequest(
            $provider,
            $authorization_response,
            $provider->getRedirectUri()
        );
        $token_response = $this->token_request_sender->sendTokenRequest($token_request);
        $id_token       = $this->id_token_verifier->validate(
            $provider,
            $state->getNonce(),
            $token_response->getIDToken()
        );

        $user_info_request  = $this->user_info_request_creator->createUserInfoRequest($provider, $token_response);
        $user_info_response = $this->user_info_request_sender->sendUserInfoRequest($user_info_request);

        $this->state_manager->clearState();

        return new FlowResponse($provider, $state->getReturnTo(), $id_token['sub'], $user_info_response->getClaims());
    }
}
