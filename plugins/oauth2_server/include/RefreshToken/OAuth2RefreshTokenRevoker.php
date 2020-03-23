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

namespace Tuleap\OAuth2Server\RefreshToken;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenException;
use Tuleap\Authentication\SplitToken\SplitTokenIdentifierTranslator;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\OAuth2AuthorizationCodeRevoker;

class OAuth2RefreshTokenRevoker
{
    /**
     * @var SplitTokenIdentifierTranslator
     */
    private $refresh_token_identifier_unserializer;
    /**
     * @var OAuth2AuthorizationCodeRevoker
     */
    private $authorization_code_revoker;
    /**
     * @var OAuth2RefreshTokenDAO
     */
    private $refresh_token_DAO;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;

    public function __construct(
        SplitTokenIdentifierTranslator $refresh_token_identifier_unserializer,
        OAuth2AuthorizationCodeRevoker $authorization_code_revoker,
        OAuth2RefreshTokenDAO $refresh_token_DAO,
        SplitTokenVerificationStringHasher $hasher
    ) {
        $this->refresh_token_identifier_unserializer = $refresh_token_identifier_unserializer;
        $this->authorization_code_revoker            = $authorization_code_revoker;
        $this->refresh_token_DAO                     = $refresh_token_DAO;
        $this->hasher                                = $hasher;
    }

    /**
     * @throws SplitTokenException
     * @throws OAuth2RefreshTokenNotFoundException
     * @throws InvalidOAuth2RefreshTokenException
     */
    public function revokeGrantOfRefreshToken(OAuth2App $app, ConcealedString $token_identifier): void
    {
        $refresh_token         = $this->refresh_token_identifier_unserializer->getSplitToken($token_identifier);
        $authorization_code_id = $this->getAuthorizationCodeIDFromRefreshToken($refresh_token, $app);
        $this->authorization_code_revoker->revokeByAuthCodeId($authorization_code_id);
    }

    /**
     * @throws OAuth2RefreshTokenNotFoundException
     * @throws InvalidOAuth2RefreshTokenException
     */
    private function getAuthorizationCodeIDFromRefreshToken(SplitToken $refresh_token, OAuth2App $app): int
    {
        $row = $this->refresh_token_DAO->searchRefreshTokenByApp($refresh_token->getID(), $app->getId());
        if ($row === null) {
            throw new OAuth2RefreshTokenNotFoundException($refresh_token->getID());
        }

        $is_valid_refresh_token = $this->hasher->verifyHash($refresh_token->getVerificationString(), $row['verifier']);
        if (! $is_valid_refresh_token) {
            throw new InvalidOAuth2RefreshTokenException();
        }

        return $row['authorization_code_id'];
    }
}
