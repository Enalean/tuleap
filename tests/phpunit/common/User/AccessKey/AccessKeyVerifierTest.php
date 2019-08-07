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

namespace Tuleap\User\AccessKey;

use DateTimeImmutable;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class AccessKeyVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public const LAST_ACCESS_RESOLUTION             = 3600;
    public const IP_ADDRESS_REQUESTING_VERIFICATION = '2001:db8::1777';


    /**
     * @var \Mockery\MockInterface
     */
    private $dao;
    /**
     * @var \Mockery\MockInterface
     */
    private $hasher;
    /**
     * @var \Mockery\MockInterface
     */
    private $user_manager;
    /**
     * @var \Mockery\MockInterface
     */
    private $access_key;
    /**
     * @var AccessKeyVerifier
     */
    private $verifier;

    protected function setUp() : void
    {
        $this->dao          = \Mockery::mock(AccessKeyDAO::class);
        $this->hasher       = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->user_manager = \Mockery::mock(\UserManager::class);
        $this->access_key   = \Mockery::mock(SplitToken::class);
        $this->verifier     = new AccessKeyVerifier($this->dao, $this->hasher, $this->user_manager);
        \ForgeConfig::store();
    }

    protected function tearDown() : void
    {
        \ForgeConfig::restore();
    }

    /**
     * @dataProvider lastAccessValuesProvider
     */
    public function testAUserCanBeRetrievedFromItsAccessKey($expect_to_log_access, $last_usage, $last_ip)
    {
        \ForgeConfig::set('last_access_resolution', self::LAST_ACCESS_RESOLUTION);
        $this->access_key->shouldReceive('getID')->andReturns(1);
        $this->dao->shouldReceive('searchAccessKeyVerificationAndTraceabilityDataByID')->andReturns(
            ['user_id' => 101, 'verifier' => 'valid', 'last_usage' => $last_usage, 'last_ip' => $last_ip, 'expiration_date' => null]
        );
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $this->access_key->shouldReceive('getVerificationString')->andReturns($verification_string);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);
        $expected_user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserById')->with(101)->andReturns($expected_user);
        if ($expect_to_log_access) {
            $this->dao->shouldReceive('updateAccessKeyUsageByID')->once();
        } else {
            $this->dao->shouldReceive('updateAccessKeyUsageByID')->never();
        }

        $this->verifier->getUser($this->access_key, '2001:db8::1777');
    }

    public function lastAccessValuesProvider()
    {
        return [
            [ // Different IP and last seen outside of the last access resolution
                true,
                (new DateTimeImmutable('- ' . 2 * self::LAST_ACCESS_RESOLUTION . ' seconds'))->getTimestamp(),
                '192.0.2.7'
            ],
            [ // Different IP and last seen inside of the last access resolution
                true,
                (new DateTimeImmutable(self::LAST_ACCESS_RESOLUTION / 2 . ' seconds'))->getTimestamp(),
                '192.0.2.7'
            ],
            [ // Same IP and last seen outside of the last access resolution
                true,
                (new DateTimeImmutable('- ' . 2 * self::LAST_ACCESS_RESOLUTION . ' seconds'))->getTimestamp(),
                self::IP_ADDRESS_REQUESTING_VERIFICATION
            ],
            [ // Same IP and last seen inside of the last access resolution
                false,
                (new DateTimeImmutable(self::LAST_ACCESS_RESOLUTION / 2 . ' seconds'))->getTimestamp(),
                self::IP_ADDRESS_REQUESTING_VERIFICATION
            ],
            [ // Access token never used before
                true,
                null,
                null
            ],
        ];
    }

    public function testVerificationFailsWhenKeyCanNotBeFound()
    {
        $this->access_key->shouldReceive('getID')->andReturns(1);
        $this->dao->shouldReceive('searchAccessKeyVerificationAndTraceabilityDataByID')->andReturns(null);

        $this->expectException(AccessKeyNotFoundException::class);

        $this->verifier->getUser($this->access_key, '2001:db8::1777');
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch()
    {
        $this->access_key->shouldReceive('getID')->andReturns(1);
        $this->dao->shouldReceive('searchAccessKeyVerificationAndTraceabilityDataByID')->andReturns(
            ['user_id' => 101, 'verifier' => 'invalid', 'last_usage' => 1538408328, 'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION, 'expiration_date' => null]
        );
        $this->access_key->shouldReceive('getVerificationString')
            ->andReturns(\Mockery::mock(SplitTokenVerificationString::class));
        $this->hasher->shouldReceive('verifyHash')->andReturns(false);

        $this->expectException(InvalidAccessKeyException::class);

        $this->verifier->getUser($this->access_key, self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }

    public function testVerificationFailsWhenTheCorrespondingTuleapCanNotBeFound()
    {
        $this->access_key->shouldReceive('getID')->andReturns(1);
        $this->dao->shouldReceive('searchAccessKeyVerificationAndTraceabilityDataByID')->andReturns(
            ['user_id' => 101, 'verifier' => 'valid', 'last_usage' => 1538408328, 'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION, 'expiration_date' => null]
        );
        $this->access_key->shouldReceive('getVerificationString')
            ->andReturns(\Mockery::mock(SplitTokenVerificationString::class));
        $this->hasher->shouldReceive('verifyHash')->andReturns(true);
        $this->user_manager->shouldReceive('getUserById')->andReturns(null);

        $this->expectException(AccessKeyMatchingUnknownUserException::class);

        $this->verifier->getUser($this->access_key, self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }

    public function testVerificationFailsWhenTheAccessKeyIsExpired()
    {
        $this->access_key->shouldReceive('getID')->andReturns(1);
        $this->dao->shouldReceive('searchAccessKeyVerificationAndTraceabilityDataByID')->andReturns(
            [
                'user_id' => 101,
                'verifier' => 'valid',
                'last_usage' => 1538408328,
                'last_ip' => self::IP_ADDRESS_REQUESTING_VERIFICATION,
                'expiration_date' => (new DateTimeImmutable("yesterday"))->getTimestamp()
            ]
        );

        $this->expectException(ExpiredAccessKeyException::class);

        $this->verifier->getUser($this->access_key, self::IP_ADDRESS_REQUESTING_VERIFICATION);
    }
}
