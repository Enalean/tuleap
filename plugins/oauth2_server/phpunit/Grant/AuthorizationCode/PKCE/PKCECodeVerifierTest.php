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

namespace Tuleap\OAuth2Server\Grant\AuthorizationCode;

use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\CodeVerifierDoesNotMatchChallengeException;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\InvalidFormatCodeVerifierException;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\MissingExpectedCodeVerifierException;
use Tuleap\OAuth2Server\Grant\AuthorizationCode\PKCE\PKCECodeVerifier;
use Tuleap\OAuth2Server\OAuth2TestScope;
use Tuleap\Test\Builders\UserTestBuilder;

final class PKCECodeVerifierTest extends TestCase
{
    /**
     * @var PKCECodeVerifier
     */
    private $pkce_code_verifier;

    protected function setUp(): void
    {
        $this->pkce_code_verifier = new PKCECodeVerifier();
    }

    public function testVerifyValidPKCECode(): void
    {
        $code_verifier  = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
        $code_challenge = hash('sha256', $code_verifier, true);
        $auth_code      = $this->buildAuthorizationCode($code_challenge);

        $this->expectNotToPerformAssertions();
        $this->pkce_code_verifier->verifyCode($auth_code, $code_verifier);
    }

    public function testPKCEFlowIsValidWhenNoCodeVerifierIsExpected(): void
    {
        $auth_code = $this->buildAuthorizationCode(null);

        $this->expectNotToPerformAssertions();
        $this->pkce_code_verifier->verifyCode($auth_code, null);
    }

    public function testThrowsAnErrorWhenACodeVerifierIsExpectedButNoneIsProvided(): void
    {
        $auth_code = $this->buildAuthorizationCode('code_challenge');

        $this->expectException(MissingExpectedCodeVerifierException::class);
        $this->pkce_code_verifier->verifyCode($auth_code, null);
    }

    /**
     * @dataProvider dataProviderMalformedCodeVerifier
     */
    public function testThrowsWhenCodeVerifierHasNotTheExpectedFormat(string $code_verifier): void
    {
        $auth_code = $this->buildAuthorizationCode('code_challenge');

        $this->expectException(InvalidFormatCodeVerifierException::class);
        $this->pkce_code_verifier->verifyCode($auth_code, $code_verifier);
    }

    public function dataProviderMalformedCodeVerifier(): array
    {
        return [
            'Too short'         => ['a'],
            'Too long'          => [str_repeat('a', 256)],
            'Not expected char' => [str_repeat('$', 43)]
        ];
    }

    public function testThrowsWhenTheCodeVerifierDoesNotMatchTheChallenge(): void
    {
        $auth_code           = $this->buildAuthorizationCode('code_challenge');
        $wrong_code_verifier = str_repeat('a', 43);

        $this->expectException(CodeVerifierDoesNotMatchChallengeException::class);
        $this->pkce_code_verifier->verifyCode($auth_code, $wrong_code_verifier);
    }

    private function buildAuthorizationCode(?string $code_challenge): OAuth2AuthorizationCode
    {
        return OAuth2AuthorizationCode::approveForSetOfScopes(
            new SplitToken(1, SplitTokenVerificationString::generateNewSplitTokenVerificationString()),
            UserTestBuilder::aUser()->build(),
            $code_challenge,
            [OAuth2TestScope::fromItself()]
        );
    }
}
