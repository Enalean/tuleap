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

namespace Tuleap\OAuth2ServerCore\Grant\AuthorizationCode;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\OAuth2ServerCore\OAuth2TestScope;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OAuth2AuthorizationCodeTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testBuildValidAuthorizationCode(): void
    {
        $user                = new \PFUser(['language_id' => 'en']);
        $scope               = OAuth2TestScope::fromItself();
        $pkce_code_challenge = 'code_chall';
        $nonce               = 'oidc_nonce';
        $auth_code           = OAuth2AuthorizationCode::approveForSetOfScopes(
            new SplitToken(12, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
            $user,
            $pkce_code_challenge,
            $nonce,
            [$scope]
        );

        self::assertSame(12, $auth_code->getID());
        self::assertSame($user, $auth_code->getUser());
        self::assertSame($pkce_code_challenge, $auth_code->getPKCECodeChallenge());
        self::assertSame($nonce, $auth_code->getOIDCNonce());
        $scopes = $auth_code->getScopes();
        self::assertCount(1, $scopes);
        self::assertEquals($scope, $scopes[0]);
    }
}
