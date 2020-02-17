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

namespace Tuleap\User\OAuth2\AccessToken;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class OAuth2AccessTokenVerifier
{
    /**
     * @var OAuth2AccessTokenDAO
     */
    private $access_token_dao;
    /**
     * @var \UserManager
     */
    private $user_manager;
    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;

    public function __construct(
        OAuth2AccessTokenDAO $access_token_dao,
        \UserManager $user_manager,
        SplitTokenVerificationStringHasher $hasher
    ) {
        $this->access_token_dao = $access_token_dao;
        $this->user_manager     = $user_manager;
        $this->hasher           = $hasher;
    }

    /**
     * @throws OAuth2AccessTokenNotFoundException
     * @throws InvalidOAuth2AccessTokenException
     */
    public function getUser(SplitToken $access_token): \PFUser
    {
        $row = $this->access_token_dao->searchAccessToken($access_token->getID());
        if ($row === null) {
            throw new OAuth2AccessTokenNotFoundException($access_token->getID());
        }

        $is_valid_access_key = $this->hasher->verifyHash($access_token->getVerificationString(), $row['verifier']);
        if (! $is_valid_access_key) {
            throw new InvalidOAuth2AccessTokenException();
        }

        $user = $this->user_manager->getUserById($row['user_id']);
        if ($user === null) {
            throw new OAuth2AccessTokenMatchingUnknownUserException($row['user_id']);
        }
        return $user;
    }
}
