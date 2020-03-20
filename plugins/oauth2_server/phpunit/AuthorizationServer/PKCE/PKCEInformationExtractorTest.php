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

namespace Tuleap\OAuth2Server\AuthorizationServer\PKCE;

use PHPUnit\Framework\TestCase;
use Tuleap\OAuth2Server\App\OAuth2App;

final class PKCEInformationExtractorTest extends TestCase
{
    /**
     * @var PKCEInformationExtractor
     */
    private $pkce_information_extractor;

    public function setUp(): void
    {
        $this->pkce_information_extractor = new PKCEInformationExtractor();
    }

    public function testCodeChallengeCanBeExtractedAuthorizationRequestQueryParameters(): void
    {
        $expected_code_challenge = hash('sha256', 'random_data', true);
        $code_challenge          = $this->pkce_information_extractor->extractCodeChallenge(
            $this->buildApp(true),
            [
                'code_challenge' => sodium_bin2base64($expected_code_challenge, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                'code_challenge_method' => 'S256'
            ]
        );

        $this->assertEquals($expected_code_challenge, $code_challenge);
    }

    public function testNoCodeChallengeIsExtractedWhenNoneIsProvidedAndTheUsageOfPKCEIsNotMandatory(): void
    {
        $this->assertNull($this->pkce_information_extractor->extractCodeChallenge($this->buildApp(false), []));
    }

    public function testExtractionFailsWhenNoCodeChallengeIsProvidedButPKCEUsageIsMandatory(): void
    {
        $this->expectException(MissingMandatoryCodeChallengeException::class);
        $this->pkce_information_extractor->extractCodeChallenge($this->buildApp(true), []);
    }

    public function testExtractionFailsWhenNoChallengeMethodIsProvided(): void
    {
        $this->expectException(NotSupportedChallengeMethodException::class);
        $this->pkce_information_extractor->extractCodeChallenge(
            $this->buildApp(true),
            ['code_challenge' => base64_encode(hash('sha256', 'random_data', true))]
        );
    }

    public function testExtractionFailsWhenChallengeMethodIsNotSupported(): void
    {
        $this->expectException(NotSupportedChallengeMethodException::class);
        $this->pkce_information_extractor->extractCodeChallenge(
            $this->buildApp(true),
            [
                'code_challenge'        => sodium_bin2base64(hash('sha256', 'random_data', true), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                'code_challenge_method' => 'plain'
            ]
        );
    }

    public function testExtractionFailsWhenTheCodeChallengeIsNotBase64URLEncoded(): void
    {
        $this->expectException(CodeChallengeNotBase64URLEncodedException::class);
        $this->pkce_information_extractor->extractCodeChallenge(
            $this->buildApp(true),
            ['code_challenge' => '{not_b64}', 'code_challenge_method' => 'S256']
        );
    }

    public function testExtractionFailsWhenTheCodeChallengeHasNotTheExpectedLength(): void
    {
        $this->expectException(IncorrectSizeCodeChallengeException::class);
        $this->pkce_information_extractor->extractCodeChallenge(
            $this->buildApp(true),
            [
                'code_challenge'        => sodium_bin2base64('not_sha256_length', SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING),
                'code_challenge_method' => 'S256'
            ]
        );
    }

    private function buildApp(bool $mandatory_pkce): OAuth2App
    {
        return new OAuth2App(
            1,
            'Name',
            'https://example.com',
            $mandatory_pkce,
            new \Project(['group_id' => 102])
        );
    }
}
