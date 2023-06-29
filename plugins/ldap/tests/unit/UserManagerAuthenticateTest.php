<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Manuel VACELET, 2008.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\LDAP;

use ForgeConfig;
use LDAP_AuthenticationFailedException;
use LDAP_UserNotFoundException;
use LDAPResultIterator;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\User\PasswordVerifier;
use Tuleap\User\UserNameNormalizer;
use UserManager;

final class UserManagerAuthenticateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use ForgeConfigSandbox;
    use GlobalLanguageMock;

    private string $username = 'toto';
    private ConcealedString $password;
    private array $ldap_params = [
        'dn'          => 'dc=tuleap,dc=local',
        'mail'        => 'mail',
        'cn'          => 'cn',
        'uid'         => 'uid',
        'eduid'       => 'uuid',
        'search_user' => '(|(uid=%words%)(cn=%words%)(mail=%words%))',
    ];

    private LDAPResultIterator $empty_ldap_result_iterator;
    private LDAPResultIterator $john_mc_lane_result_iterator;
    private MockObject&UserManager $user_manager;
    private MockObject&\LDAP $ldap;
    private \LDAP_UserSync&MockObject $user_sync;
    private \LDAP_UserManager&MockObject $ldap_user_manager;
    private MockObject&UserNameNormalizer $username_normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        $this->password                     = new ConcealedString('welcome0');
        $this->empty_ldap_result_iterator   = $this->buildLDAPIterator([], []);
        $this->john_mc_lane_result_iterator = $this->buildLDAPIterator(
            [
                [
                    'cn'   => 'John Mac Lane',
                    'uid'  => 'john_lane',
                    'mail' => 'john.mc.lane@nypd.gov',
                    'uuid' => 'ed1234',
                    'dn'   => 'uid=john_lane,ou=people,dc=tuleap,dc=local',
                ],
            ],
            $this->ldap_params
        );

        $this->username_normalizer = $this->createMock(UserNameNormalizer::class);

        $this->ldap = $this->getMockBuilder(\LDAP::class)
            ->onlyMethods(['searchLogin', 'authenticate'])
            ->setConstructorArgs([$this->ldap_params, new NullLogger()])
            ->getMock();

        $this->user_sync    = $this->createMock(\LDAP_UserSync::class);
        $this->user_manager = $this->createMock(UserManager::class);
        $password_verifier  = new PasswordVerifier(
            new class implements \PasswordHandler {
                public function verifyHashPassword(ConcealedString $plain_password, string $hash_password): bool
                {
                    return true;
                }

                public function computeHashPassword(ConcealedString $plain_password): string
                {
                    return 'hash';
                }

                public function isPasswordNeedRehash(string $hash_password): bool
                {
                    return false;
                }

                public function computeUnixPassword(ConcealedString $plain_password): string
                {
                    return 'hash';
                }
            }
        );

        $this->ldap_user_manager = $this->getMockBuilder(\LDAP_UserManager::class)
            ->onlyMethods(['synchronizeUser', 'getUserManager', 'createAccountFromLdap'])
            ->setConstructorArgs([$this->ldap, $this->user_sync, $this->username_normalizer, $password_verifier])
            ->getMock();

        $this->ldap_user_manager->method('getUserManager')->willReturn($this->user_manager);

        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    public function testItDelegatesAuthenticateToLDAP(): void
    {
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('searchLogin')->willReturn($this->john_mc_lane_result_iterator);

        $this->ldap->expects(self::once())->method('authenticate')->with($this->username, $this->password)->willReturn(true);

        $this->user_manager->expects(self::once())->method('getUserByLdapId')->willReturn($this->createMock(PFUser::class));
        $this->ldap_user_manager->expects(self::once())->method('synchronizeUser');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItRaisesAnExceptionIfAuthenticationFailed(): void
    {
        $this->expectException(LDAP_AuthenticationFailedException::class);

        $this->ldap->method('authenticate')->willReturn(false);

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItFetchLDAPUserInfoBasedOnLogin(): void
    {
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);

        $this->ldap->expects(self::once())
            ->method('searchLogin')
            ->with($this->username, self::anything())
            ->willReturn($this->john_mc_lane_result_iterator);

        $this->user_manager->expects(self::once())->method('getUserByLdapId')->willReturn($this->createMock(PFUser::class));
        $this->ldap_user_manager->expects(self::once())->method('synchronizeUser');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItFetchesLDAPUserInfoWithExtendedAttributesDefinedInUserSync(): void
    {
        $attributes = ['mail', 'cn', 'uid', 'uuid', 'dn', 'employeeType'];
        $this->user_sync->method('getSyncAttributes')->willReturn($attributes);
        $this->ldap->method('authenticate')->willReturn(true);

        $this->ldap
            ->expects(self::once())
            ->method('searchLogin')
            ->with(self::anything(), $attributes)
            ->willReturn($this->john_mc_lane_result_iterator);

        $this->user_manager->expects(self::once())->method('getUserByLdapId')->willReturn($this->createMock(PFUser::class));
        $this->ldap_user_manager->expects(self::once())->method('synchronizeUser');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItFetchesStandardLDAPInfosEvenWhenNotSpecifiedInSyncAttributes(): void
    {
        $attributes = ['employeeType'];
        $this->user_sync->method('getSyncAttributes')->willReturn($attributes);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('authenticate')->willReturn(true);

        $this->ldap->expects(self::once())
            ->method('searchLogin')
            ->with(self::anything(), ['mail', 'cn', 'uid', 'uuid', 'dn', 'employeeType'])
            ->willReturn($this->john_mc_lane_result_iterator);

        $this->user_manager->expects(self::once())->method('getUserByLdapId')->willReturn($this->createMock(PFUser::class));
        $this->ldap_user_manager->expects(self::once())->method('synchronizeUser');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItTriesToFindTheTuleapUserBasedOnLdapId(): void
    {
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('searchLogin')->willReturn($this->john_mc_lane_result_iterator);

        $this->user_manager
            ->expects(self::once())
            ->method('getUserByLdapId')
            ->with('ed1234')
            ->willReturn($this->createMock(PFUser::class));

        $this->ldap_user_manager->expects(self::once())->method('synchronizeUser');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItRaisesAnExceptionWhenLDAPUserIsNotFound(): void
    {
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('searchLogin')->willReturn($this->empty_ldap_result_iterator);

        $this->user_manager->expects(self::never())->method('getUserByLdapId');
        $this->ldap_user_manager->expects(self::never())->method('synchronizeUser');

        $this->expectException(LDAP_UserNotFoundException::class);

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItRaisesAnExceptionWhenSeveralLDAPUsersAreFound(): void
    {
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('searchLogin')->willReturn($this->buildLDAPIterator(
            [
                [
                    'cn'   => 'John Mac Lane',
                    'uid'  => 'john_lane',
                    'mail' => 'john.mc.lane@nypd.gov',
                    'uuid' => 'ed1234',
                    'dn'   => 'uid=john_lane,ou=people,dc=tuleap,dc=local',
                ],
                [
                    'cn'   => 'William Wallas',
                    'uid'  => 'will_wall',
                    'mail' => 'will_wall@edimburgh.co.uk',
                    'uuid' => 'ed5432',
                    'dn'   => 'uid=will_wall,ou=people,dc=tuleap,dc=local',
                ],
            ],
            $this->ldap_params
        ));

        $this->expectException(LDAP_UserNotFoundException::class);

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItCreatesUserAccountWhenUserDoesntExist(): void
    {
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('searchLogin')->willReturn($this->john_mc_lane_result_iterator);
        $this->user_manager->method('getUserByLdapId')->willReturn(null);

        $this->ldap_user_manager->expects(self::once())->method('createAccountFromLdap')->with(self::anything());

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItReturnsUserOnAccountCreation(): void
    {
        $expected_user = $this->buildUser();
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('searchLogin')->willReturn($this->john_mc_lane_result_iterator);
        $this->user_manager->method('getUserByLdapId')->willReturn(null);

        $this->ldap_user_manager->method('createAccountFromLdap')->willReturn($expected_user);
        $this->ldap_user_manager
            ->expects(self::once())
            ->method('synchronizeUser')
            ->with($expected_user, self::anything(), $this->password);

        $user = $this->ldap_user_manager->authenticate($this->username, $this->password);
        self::assertSame($expected_user, $user);
    }

    public function testItUpdateUserAccountsIfAlreadyExists(): void
    {
        $expected_user = $this->buildUser();
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('searchLogin')->willReturn($this->john_mc_lane_result_iterator);
        $this->user_manager->method('getUserByLdapId')->willReturn($expected_user);

        $this->ldap_user_manager
            ->expects(self::once())
            ->method('synchronizeUser')
            ->with($expected_user, self::anything(), $this->password);

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItReturnsUserOnAccountUpdate(): void
    {
        $expected_user = $this->buildUser();
        $this->user_sync->method('getSyncAttributes')->willReturn([]);
        $this->ldap->method('authenticate')->willReturn(true);
        $this->ldap->method('searchLogin')->willReturn($this->john_mc_lane_result_iterator);
        $this->user_manager->method('getUserByLdapId')->willReturn($expected_user);

        $this->ldap_user_manager->method('synchronizeUser');

        $user = $this->ldap_user_manager->authenticate($this->username, $this->password);
        self::assertSame($expected_user, $user);
    }

    private function buildUser(): PFUser
    {
        return new PFUser(['user_id' => 123]);
    }

    private function buildLDAPIterator(array $info, array $ldap_params): LDAPResultIterator
    {
        $ldap_info = [
            'count' => count($info),
        ];
        $i         = 0;
        foreach ($info as $people) {
            $nb_params_excluding_dn = count($people) - 1;
            $ldap_info[$i]          = [
                'dn'    => $people['dn'],
                'count' => $nb_params_excluding_dn,
            ];
            $j                      = 0;
            foreach ($people as $param => $value) {
                if ($param == 'dn') {
                    continue;
                }
                $ldap_info[$i][$param] = [
                    'count' => 1,
                    0       => $value,
                ];
                $ldap_info[$i][$j]     = $param;
                $j++;
            }
            $i++;
        }

        return new LDAPResultIterator($ldap_info, $ldap_params);
    }
}
