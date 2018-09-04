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

use InoOicClient\Flow\Basic;
use InoOicClient\Client\ClientInfo;
use InoOicClient\Flow\Exception\AuthorizationException;
use InoOicClient\Flow\Exception\TokenRequestException;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\Token\TokenRequestSender;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestCreator;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoRequestSender;
use Tuleap\OpenIDConnectClient\Authentication\UserInfo\UserInfoResponseException;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use ForgeConfig;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Exception;

class Flow extends Basic {
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
        AuthorizationDispatcher $authorization_dispatcher,
        ProviderManager $provider_manager,
        TokenRequestCreator $token_request_creator,
        TokenRequestSender $token_request_sender,
        IDTokenVerifier $id_token_verifier,
        UserInfoRequestCreator $user_info_request_creator,
        UserInfoRequestSender $user_info_request_sender
    ) {
        $this->setStateManager($state_manager);
        $this->setAuthorizationDispatcher($authorization_dispatcher);
        $this->provider_manager          = $provider_manager;
        $this->token_request_creator     = $token_request_creator;
        $this->token_request_sender      = $token_request_sender;
        $this->id_token_verifier         = $id_token_verifier;
        $this->user_info_request_creator = $user_info_request_creator;
        $this->user_info_request_sender  = $user_info_request_sender;
    }

    public function setOptions(Provider $provider) {
        $configuration = $this->generateConfiguration($provider);
        parent::setOptions($configuration);
    }

    /**
     * @return array
     */
    private function generateConfiguration(Provider $provider) {
        return array(
            'client_info' => array(
                'client_id'    => $provider->getClientId(),
                'redirect_uri' => $this->getRedirectUri(),

                'authorization_endpoint' => $provider->getAuthorizationEndpoint(),
                'token_endpoint'         => $provider->getTokenEndpoint(),

                'authentication_info' => array(
                    'method' => 'client_secret_post',
                    'params' => array(
                        'client_secret' => $provider->getClientSecret()
                    )
                )
            )
        );
    }

    /**
     * @return string
     */
    private function getRedirectUri() {
        return 'https://'. ForgeConfig::get('sys_https_host') . '/plugins/openidconnectclient/';
    }

    /**
     * @param Provider $provider
     * @param $return_to
     * @return string
     */
    public function getAuthorizationRequestUri(Provider $provider, $return_to) {
        $this->setOptions($provider);
        $scope = 'openid';
        if ($provider->isUniqueAuthenticationEndpoint()) {
            $scope = 'openid profile email';
        }
        $authorization_request = $this->createAuthorizationRequest($scope);
        return $this->getAuthorizationDispatcher()->createAuthorizationRequestUri(
            $authorization_request,
            $provider,
            $return_to
        );
    }

    /**
     * @return FlowResponse
     * @throws AuthorizationException
     * @throws TokenRequestException
     * @throws UserInfoResponseException
     * @throws \Http\Client\Exception
     */
    public function process() {
        try {
            $authorization_response = $this->getAuthorizationDispatcher()->getAuthorizationResponse();
            $signed_state           = $authorization_response->getState();
            $state                  = $this->getStateManager()->validateState($signed_state);
            $provider               = $this->provider_manager->getById($state->getProviderId());
        } catch(Exception $ex) {
            throw new AuthorizationException(
                sprintf("Exception during authorization: [%s] %s", get_class($ex), $ex->getMessage()),
                null,
                $ex
            );
        }

        try {
            $token_request  = $this->token_request_creator->createTokenRequest(
                $provider,
                $authorization_response,
                $this->getRedirectUri()
            );
            $token_response = $this->token_request_sender->sendTokenRequest($token_request);
            $id_token       = $this->id_token_verifier->validate($provider, $state->getNonce(),
                $token_response->getIDToken());
        } catch (Exception $ex) {
            throw new TokenRequestException(
                sprintf("Exception during token request: [%s] %s", get_class($ex), $ex->getMessage()),
                null,
                $ex
            );
        }

        $user_info_request  = $this->user_info_request_creator->createUserInfoRequest($provider, $token_response);
        $user_info_response = $this->user_info_request_sender->sendUserInfoRequest($user_info_request);

        $this->getStateManager()->clearState();

        return new FlowResponse($provider, $state->getReturnTo(), $id_token['sub'], $user_info_response->getClaims());
    }

    /**
     * @return ClientInfo
     */
    public function getClientInfo() {
        if (! $this->clientInfo instanceof ClientInfo) {
            $this->clientInfo = new ClientInfo();
        }
        $this->clientInfo->fromArray($this->options->get(self::OPT_CLIENT_INFO, array()));
        return $this->clientInfo;
    }
}