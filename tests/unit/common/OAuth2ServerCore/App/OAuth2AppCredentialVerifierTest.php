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

namespace Tuleap\OAuth2ServerCore\App;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class OAuth2AppCredentialVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppFactory
     */
    private $app_factory;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AppDao
     */
    private $app_dao;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitTokenVerificationStringHasher
     */
    private $hasher;
    /**
     * @var OAuth2AppCredentialVerifier
     */
    private $verifier;

    protected function setUp(): void
    {
        $this->app_factory = $this->createMock(AppFactory::class);
        $this->app_dao     = $this->createMock(AppDao::class);
        $this->hasher      = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->verifier    = new OAuth2AppCredentialVerifier(
            $this->app_factory,
            $this->app_dao,
            $this->hasher
        );
    }

    public function testAppCanBeVerifiedWhenTheProperClientIdentifierAndSecretAreGiven(): void
    {
        $expected_app = $this->buildApp();

        $this->app_dao->method('searchClientSecretByClientID')->willReturn('valid_secret_hash');
        $this->hasher->method('verifyHash')->willReturn(true);

        $app = $this->verifier->getApp(
            ClientIdentifier::fromOAuth2App($expected_app),
            new SplitToken($expected_app->getId(), SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );

        self::assertSame($expected_app, $app);
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

        $this->app_dao->method('searchClientSecretByClientID')->willReturn('other_secret_hash');
        $this->hasher->method('verifyHash')->willReturn(false);

        $this->expectException(InvalidOAuth2AppSecretException::class);
        $this->verifier->getApp(
            ClientIdentifier::fromOAuth2App($expected_app),
            new SplitToken($expected_app->getId(), SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );
    }

    public function testAppIsNotVerifiedWhenNoVerifierStringIsAssociatedWithIt(): void
    {
        $expected_app = $this->buildApp();

        $this->app_dao->method('searchClientSecretByClientID')->willReturn(null);

        $this->expectException(OAuth2MissingVerifierStringException::class);
        $this->verifier->getApp(
            ClientIdentifier::fromOAuth2App($expected_app),
            new SplitToken($expected_app->getId(), SplitTokenVerificationString::generateNewSplitTokenVerificationString())
        );
    }

    private function buildApp(): OAuth2App
    {
        $app = new OAuth2App(1, 'Name', 'https://example.com', true, ProjectTestBuilder::aProject()->build());
        $this->app_factory->method('getAppMatchingClientId')
            ->with(self::callback(
                static function (ClientIdentifier $identifier) use ($app): bool {
                    return $identifier->getInternalId() === ClientIdentifier::fromOAuth2App($app)->getInternalId();
                }
            ))
            ->willReturn($app);

        return $app;
    }
}
