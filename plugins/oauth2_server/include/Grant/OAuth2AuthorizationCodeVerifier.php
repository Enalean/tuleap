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

namespace Tuleap\OAuth2Server\Grant;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use UserManager;

class OAuth2AuthorizationCodeVerifier
{
    private const TEST_AUTH_CODE_ID = 1;
    // Not hashed: aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa
    private const TEST_AUTH_CODE_VERIFIER_HASHED_VALUE = '3ba3f5f43b92602683c19aee62a20342b084dd5971ddd33808d81a328879a547';

    /**
     * @var SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var UserManager
     */
    private $user_manager;

    public function __construct(SplitTokenVerificationStringHasher $hasher, UserManager $user_manager)
    {
        $this->hasher       = $hasher;
        $this->user_manager = $user_manager;
    }

    /**
     * @throws OAuth2AuthCodeNotFoundException
     * @throws InvalidOAuth2AuthCodeException
     */
    public function getAuthorizationCode(SplitToken $auth_code): OAuth2AuthorizationCode
    {
        if ($auth_code->getID() !== self::TEST_AUTH_CODE_ID) {
            throw new OAuth2AuthCodeNotFoundException($auth_code->getID());
        }

        $is_valid_auth_code = $this->hasher->verifyHash($auth_code->getVerificationString(), self::TEST_AUTH_CODE_VERIFIER_HASHED_VALUE);
        if (! $is_valid_auth_code) {
            throw new InvalidOAuth2AuthCodeException();
        }

        return OAuth2AuthorizationCode::approveForDemoScope($this->user_manager->getUserByUserName('admin'));
    }
}
