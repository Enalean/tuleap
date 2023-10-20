<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Authentication\Scope\AuthenticationScope;
use Tuleap\Authentication\Scope\AuthenticationScopeDefinition;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\ForgeConfigSandbox;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeIdentifier;
use Tuleap\User\AccessKey\Scope\AccessKeyScopeRetriever;

final class AccessKeyVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;

    private const LAST_ACCESS_RESOLUTION             = 3600;
    private const IP_ADDRESS_REQUESTING_VERIFICATION = '2001:db8::1777';

    private AccessKeyDAO&MockObject $dao;
    private SplitTokenVerificationStringHasher&MockObject $hasher;
    private \UserManager&MockObject $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AccessKeyScopeRetriever
     */
    private $access_key_scope_retriever;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&SplitToken
     */
    private $access_key;
    private AccessKeyVerifier $verifier;

    protected function setUp(): void
    {
        $this->dao                        = $this->createMock(AccessKeyDAO::class);
        $this->hasher                     = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->user_manager               = $this->createMock(\UserManager::class);
        $this->access_key_scope_retriever = $this->createMock(AccessKeyScopeRetriever::class);
        $this->access_key                 = $this->createMock(SplitToken::class);
        $this->verifier                   = new AccessKeyVerifier(
            $this->dao,
            $this->hasher,
            $this->user_manager,
            $this->access_key_scope_retriever
        );
    }

    /**
     * @dataProvider lastAccessValuesProvider
     */
    public function testAUserCanBeRetrievedFromItsAccessKey($expect_to_log_access, $last_usage, $last_ip): void
    {
        \ForgeConfig::set('last_access_resolution', self::LAST_ACCESS_RESOLUTION);
        $this->access_key->method('getID')->willReturn(1);
        $this->dao->method('searchAccessKeyVerificationAndTraceabilityDataByID')->willReturn(
            ['user_id' => 101, 'verifier' => 'valid', 'last_usage' => $last_usage, 'last_ip' => $last_ip, 'expiration_date' => null]
        );
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $this->access_key->method('getVerificationString')->willReturn($verification_string);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);
        $scope = $this->createMock(AuthenticationScope::class);
        $scope->method('covers')->willReturn(true);
        $this->access_key_scope_retriever->method('getScopesByAccessKeyID')->willReturn([$scope]);
        $expected_user = $this->createMock(\PFUser::class);
        $this->user_manager->method('getUserById')->with(101)->willReturn($expected_user);
        if ($expect_to_log_access) {
            $this->dao->expects(self::once())->method('updateAccessKeyUsageByID');
        } else {
            $this->dao->expects(self::never())->method('updateAccessKeyUsageByID');
        }

        $this->verifier->getUser($this->access_key, $this->createMock(AuthenticationScope::class), '2001:db8::1777');
    }

    public static function lastAccessValuesProvider(): array
    {
        return [
            [ // Different IP and last seen outside of the last access resolution
                true,
                (new DateTimeImmutable('- ' . 2 * self::LAST_ACCESS_RESOLUTION . ' seconds'))->getTimestamp(),
                '192.0.2.7',
            ],
            [ // Different IP and last seen inside of the last access resolution
                true,
                (new DateTimeImmutable(self::LAST_ACCESS_RESOLUTION / 2 . ' seconds'))->getTimestamp(),
                '192.0.2.7',
            ],
            [ // Same IP and last seen outside of the last access resolution
                true,
                (new DateTimeImmutable('- ' . 2 * self::LAST_ACCESS_RESOLUTION . ' seconds'))->getTimestamp(),
                self::IP_ADDRESS_REQUESTING_VERIFICATION,
            ],
            [ // Same IP and last seen inside of the last access resolution
                false,
                (new DateTimeImmutable(self::LAST_ACCESS_RESOLUTION / 2 . ' seconds'))->getTimestamp(),
                self::IP_ADDRESS_REQUESTING_VERIFICATION,
            ],
            [ // Access token never used before
                true,
                null,
                null,
            ],
        ];
    }

    public function testVerificationFailsWhenKeyCanNotBeFound(): void
    {
        $this->access_key->method('getID')->willReturn(1);
        $this->dao->method('searchAccessKeyVerificationAndTraceabilityDataByID')->willReturn(null);

        $this->expectException(AccessKeyNotFoundException::class);

        $this->verifier->getUser($this->access_key, $this->createMock(AuthenticationScope::class), '2001:db8::1777');
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $this->access_key->method('getID')->willReturn(1);
        $this->dao->method('searchAccessKeyVerificationAndTraceabilityDataByID')->willReturn(
            ['user_id' => 101, 'verifier' => 'invalid', 'last_usage' => 1538408328, 'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION, 'expiration_date' => null]
        );
        $this->access_key->method('getVerificationString')
            ->willReturn($this->createMock(SplitTokenVerificationString::class));
        $this->hasher->method('verifyHash')->willReturn(false);

        $this->expectException(InvalidAccessKeyException::class);

        $this->verifier->getUser($this->access_key, $this->createMock(AuthenticationScope::class), self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }

    public function testVerificationFailsWhenTheCorrespondingUserCanNotBeFound(): void
    {
        $this->access_key->method('getID')->willReturn(1);
        $this->dao->method('searchAccessKeyVerificationAndTraceabilityDataByID')->willReturn(
            ['user_id' => 101, 'verifier' => 'valid', 'last_usage' => 1538408328, 'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION, 'expiration_date' => null]
        );
        $this->access_key->method('getVerificationString')
            ->willReturn($this->createMock(SplitTokenVerificationString::class));
        $this->hasher->method('verifyHash')->willReturn(true);
        $scope = $this->createMock(AuthenticationScope::class);
        $scope->method('covers')->willReturn(true);
        $this->access_key_scope_retriever->method('getScopesByAccessKeyID')->willReturn([$scope]);
        $this->user_manager->method('getUserById')->willReturn(null);

        $this->expectException(AccessKeyMatchingUnknownUserException::class);

        $this->verifier->getUser($this->access_key, $this->createMock(AuthenticationScope::class), self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }

    public function testVerificationFailsWhenTheAccessKeyIsExpired(): void
    {
        $this->access_key->method('getID')->willReturn(1);
        $this->dao->method('searchAccessKeyVerificationAndTraceabilityDataByID')->willReturn(
            [
                'user_id' => 101,
                'verifier' => 'valid',
                'last_usage' => 1538408328,
                'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION,
                'expiration_date' => (new DateTimeImmutable("yesterday"))->getTimestamp(),
            ]
        );

        $this->expectException(ExpiredAccessKeyException::class);

        $this->verifier->getUser($this->access_key, $this->createMock(AuthenticationScope::class), self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }

    public function testVerificationFailsWhenNoneOfTheScopesAssociatedWithTheTokenCoversTheRequiredScope(): void
    {
        $this->access_key->method('getID')->willReturn(1);
        $this->dao->method('searchAccessKeyVerificationAndTraceabilityDataByID')->willReturn(
            ['user_id' => 101, 'verifier' => 'valid', 'last_usage' => 1538408328, 'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION, 'expiration_date' => null]
        );
        $this->access_key->method('getVerificationString')
            ->willReturn($this->createMock(SplitTokenVerificationString::class));
        $this->hasher->method('verifyHash')->willReturn(true);
        $scope = $this->createMock(AuthenticationScope::class);
        $scope->method('covers')->willReturn(false);
        $this->access_key_scope_retriever->method('getScopesByAccessKeyID')->willReturn([$scope]);

        $required_scope   = $this->createMock(AuthenticationScope::class);
        $scope_definition = $this->createMock(AuthenticationScopeDefinition::class);
        $scope_definition->method('getName')->willReturn('name');
        $required_scope->method('getDefinition')->willReturn($scope_definition);
        $required_scope->method('getIdentifier')->willReturn(AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar'));

        $this->expectException(AccessKeyDoesNotHaveRequiredScopeException::class);

        $this->verifier->getUser($this->access_key, $required_scope, self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }

    public function testVerificationFailsWhenNoScopeSeemsToBeAssociatedWithTheToken(): void
    {
        $this->access_key->method('getID')->willReturn(1);
        $this->dao->method('searchAccessKeyVerificationAndTraceabilityDataByID')->willReturn(
            ['user_id' => 101, 'verifier' => 'valid', 'last_usage' => 1538408328, 'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION, 'expiration_date' => null]
        );
        $this->access_key->method('getVerificationString')
            ->willReturn($this->createMock(SplitTokenVerificationString::class));
        $this->hasher->method('verifyHash')->willReturn(true);
        $this->access_key_scope_retriever->method('getScopesByAccessKeyID')->willReturn([]);

        $required_scope   = $this->createMock(AuthenticationScope::class);
        $scope_definition = $this->createMock(AuthenticationScopeDefinition::class);
        $scope_definition->method('getName')->willReturn('name');
        $required_scope->method('getDefinition')->willReturn($scope_definition);
        $required_scope->method('getIdentifier')->willReturn(AccessKeyScopeIdentifier::fromIdentifierKey('foo:bar'));

        $this->expectException(AccessKeyDoesNotHaveRequiredScopeException::class);

        $this->verifier->getUser($this->access_key, $required_scope, self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }
}
