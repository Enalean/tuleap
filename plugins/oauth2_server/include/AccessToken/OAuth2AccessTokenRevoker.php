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

namespace Tuleap\OAuth2Server\AccessToken;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;
use Tuleap\User\OAuth2\AccessToken\InvalidOAuth2AccessTokenException;
use Tuleap\User\OAuth2\AccessToken\OAuth2AccessTokenNotFoundException;

class OAuth2AccessTokenRevoker
{
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $access_token_identifier_unserializer;
    /**
     * @var OAuth2AuthorizationCodeRevoker
     */
    private $authorization_code_revoker;
    /**
     * @var OAuth2AccessTokenDAO
     */
    private $access_token_dao;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;

    public function __construct(
        SplitTokenIdentifierTranslator $access_token_identifier_unserializer,
        OAuth2AuthorizationCodeRevoker $authorization_code_revoker,
        OAuth2AccessTokenDAO $access_token_dao,
        SplitTokenVerificationStringHasher $hasher
    ) {
        $this->access_token_identifier_unserializer = $access_token_identifier_unserializer;
        $this->authorization_code_revoker           = $authorization_code_revoker;
        $this->access_token_dao                     = $access_token_dao;
        $this->hasher                               = $hasher;
    }

    /**
     * @throws SplitTokenException
     * @throws OAuth2AccessTokenNotFoundException
     * @throws InvalidOAuth2AccessTokenException
     */
    public function revokeGrantOfAccessToken(OAuth2App $app, ConcealedString $token_identifier): void
    {
        $access_token          = $this->access_token_identifier_unserializer->getSplitToken($token_identifier);
        $authorization_code_id = $this->getAuthorizationCodeIDFromAccessToken($access_token, $app);
        $this->authorization_code_revoker->revokeByAuthCodeId($authorization_code_id);
    }

    /**
     * @throws OAuth2AccessTokenNotFoundException
     * @throws InvalidOAuth2AccessTokenException
     */
    private function getAuthorizationCodeIDFromAccessToken(SplitToken $access_token, OAuth2App $app): int
    {
        $row = $this->access_token_dao->searchAccessTokenByApp($access_token->getID(), $app->getId());
        if ($row === null) {
            throw new OAuth2AccessTokenNotFoundException($access_token->getID());
        }

        $is_valid_access_token = $this->hasher->verifyHash($access_token->getVerificationString(), $row['verifier']);
        if (! $is_valid_access_token) {
            throw new InvalidOAuth2AccessTokenException();
        }

        return $row['authorization_code_id'];
    }
}
