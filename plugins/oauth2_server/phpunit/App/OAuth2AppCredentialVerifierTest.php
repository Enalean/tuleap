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

namespace Tuleap\OAuth2Server\App;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

final class OAuth2AppCredentialVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AppFactory
     */
    private $app_factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AppDao
     */
    private $app_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2AppCredentialVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->app_factory = \Mockery::mock(AppFactory::class);
        $this->app_dao     = \Mockery::mock(AppDao::class);
        $this->hasher      = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->verifier    = new OAuth2AppCredentialVerifier(
            $this->app_factory,
            $this->app_dao,
            $this->hasher
        );
    }

    public function testAppCanBeVerifiedWhenTheProperClientIdentifierAndSecretAreGiven(): void
    {
        $expected_app = $this->buildApp();

        $this->app_dao->shouldReceive('searchClientSecretByClientID')->andReturn('valid_secret_hash');
        $this->hasher->shouldReceive('verifyHash')->andReturn(true);

        $app = $this->verifier->getApp(
            ClientIdentifier::fromOAuth2App($expected_app),
            new SplitToken($expected_app->getId(), SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );

        $this->assertSame($expected_app, $app);
    }

    public function testAppIsNotVerifiedWhenTheClientSecretIsNotCoherentWithTheClientIdentifier(): void
    {
        $app = $this->buildApp();

        $this->expectException(OAuth2ClientIdentifierAndSecretMismatchException::class);
        $this->verifier->getApp(
            ClientIdentifier::fromOAuth2App($app),
            new SplitToken(444, SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );
    }

    public function testAppIsNotVerifiedWhenTheClientSecretDoesNotMatchTheExpectedOne(): void
    {
        $expected_app = $this->buildApp();

        $this->app_dao->shouldReceive('searchClientSecretByClientID')->andReturn('other_secret_hash');
        $this->hasher->shouldReceive('verifyHash')->andReturn(false);

        $this->expectException(InvalidOAuth2AppSecretException::class);
        $this->verifier->getApp(
            ClientIdentifier::fromOAuth2App($expected_app),
            new SplitToken($expected_app->getId(), SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );
    }

    public function testAppIsNotVerifiedWhenNoVerifierStringIsAssociatedWithIt(): void
    {
        $expected_app = $this->buildApp();

        $this->app_dao->shouldReceive('searchClientSecretByClientID')->andReturn(null);

        $this->expectException(OAuth2MissingVerifierStringException::class);
        $this->verifier->getApp(
            ClientIdentifier::fromOAuth2App($expected_app),
            new SplitToken($expected_app->getId(), SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );
    }

    private function buildApp(): OAuth2App
    {
        $app = new OAuth2App(1, 'Name', 'https://example.com', true, \Mockery::mock(\Project::class));
        $this->app_factory->shouldReceive('getAppMatchingClientId')
            ->with(
                \Mockery::on(
                    static function (ClientIdentifier $identifier) use ($app): bool {
                        return $identifier->getInternalId() === ClientIdentifier::fromOAuth2App($app)->getInternalId();
                    }
                )
            )
            ->andReturn($app);

        return $app;
    }
}
