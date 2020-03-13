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
use LDAPResultIterator;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use UserManager;

require_once __DIR__ . '/bootstrap.php';

final class UserManagerAuthenticateTest extends TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox, GlobalLanguageMock;

    private $username    = 'toto';
    private $password    = 'welcome0';
    private $ldap_params = array(
        'dn'          => 'dc=tuleap,dc=local',
        'mail'        => 'mail',
        'cn'          => 'cn',
        'uid'         => 'uid',
        'eduid'       => 'uuid',
        'search_user' => '(|(uid=%words%)(cn=%words%)(mail=%words%))',
    );

    private $empty_ldap_result_iterator;
    private $john_mc_lane_result_iterator;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;

    /**
     * @var Mockery\Mock
     */
    private $ldap;

    /**
     * @var \LDAP_UserSync|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $user_sync;

    /**
     * @var \LDAP_UserManager|Mockery\Mock
     */
    private $ldap_user_manager;

    protected function setUp(): void
    {
        parent::setUp();
        ForgeConfig::set('sys_logger_level', 'debug');
        $this->empty_ldap_result_iterator   = $this->buildLDAPIterator([], []);
        $this->john_mc_lane_result_iterator = $this->buildLDAPIterator(
            [
                [
                    'cn'   => 'John Mac Lane',
                    'uid'  => 'john_lane',
                    'mail' => 'john.mc.lane@nypd.gov',
                    'uuid' => 'ed1234',
                    'dn'   => 'uid=john_lane,ou=people,dc=tuleap,dc=local'
                ]
            ],
            $this->ldap_params
        );

        $this->ldap = \Mockery::mock(
            \LDAP::class,
            [$this->ldap_params, Mockery::mock(\Psr\Log\LoggerInterface::class)]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->user_sync         = \Mockery::spy(\LDAP_UserSync::class);
        $this->user_manager      = \Mockery::spy(UserManager::class);
        $this->ldap_user_manager = \Mockery::mock(
            \LDAP_UserManager::class,
            [$this->ldap, $this->user_sync]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->ldap_user_manager->shouldReceive('getUserManager')->andReturns($this->user_manager);
    }

    public function testItDelegatesAuthenticateToLDAP(): void
    {
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->john_mc_lane_result_iterator);

        $this->ldap->shouldReceive('authenticate')->with($this->username, $this->password)->once()->andReturnTrue();

        $this->user_manager->shouldReceive('getUserByLdapId')->once()->andReturn(Mockery::mock(PFUser::class));
        $this->ldap_user_manager->shouldReceive('synchronizeUser')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItRaisesAnExceptionIfAuthenticationFailed(): void
    {
        $this->expectException('LDAP_AuthenticationFailedException');

        $this->ldap->shouldReceive('authenticate')->andReturns(false);

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItFetchLDAPUserInfoBasedOnLogin(): void
    {
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);

        $this->ldap->shouldReceive('searchLogin')
            ->with($this->username, \Mockery::any())
            ->once()
            ->andReturns($this->john_mc_lane_result_iterator);

        $this->user_manager->shouldReceive('getUserByLdapId')->once()->andReturn(Mockery::mock(PFUser::class));
        $this->ldap_user_manager->shouldReceive('synchronizeUser')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItFetchesLDAPUserInfoWithExtendedAttributesDefinedInUserSync(): void
    {
        $attributes = array('mail', 'cn', 'uid', 'uuid', 'dn', 'employeeType');
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns($attributes);
        $this->ldap->shouldReceive('authenticate')->andReturns(true);

        $this->ldap->shouldReceive('searchLogin')
            ->with(\Mockery::any(), $attributes)
            ->once()
            ->andReturns($this->john_mc_lane_result_iterator);

        $this->user_manager->shouldReceive('getUserByLdapId')->once()->andReturn(Mockery::mock(PFUser::class));
        $this->ldap_user_manager->shouldReceive('synchronizeUser')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItFetchesStandardLDAPInfosEvenWhenNotSpecifiedInSyncAttributes(): void
    {
        $attributes = array('employeeType');
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns($attributes);
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('authenticate')->andReturns(true);

        $this->ldap->shouldReceive('searchLogin')
            ->with(\Mockery::any(), array('mail', 'cn', 'uid', 'uuid', 'dn', 'employeeType'))
            ->once()
            ->andReturns($this->john_mc_lane_result_iterator);

        $this->user_manager->shouldReceive('getUserByLdapId')->once()->andReturn(Mockery::mock(PFUser::class));
        $this->ldap_user_manager->shouldReceive('synchronizeUser')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItTriesToFindTheTuleapUserBasedOnLdapId(): void
    {
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->john_mc_lane_result_iterator);

        $this->user_manager->shouldReceive('getUserByLdapId')
            ->with('ed1234')
            ->once()
            ->andReturn(Mockery::mock(PFUser::class));

        $this->ldap_user_manager->shouldReceive('synchronizeUser')->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItRaisesAnExceptionWhenLDAPUserIsNotFound(): void
    {
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->empty_ldap_result_iterator);

        $this->user_manager->shouldReceive('getUserByLdapId')->never();
        $this->ldap_user_manager->shouldReceive('synchronizeUser')->never();

        $this->expectException('LDAP_UserNotFoundException');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItRaisesAnExceptionWhenSeveralLDAPUsersAreFound(): void
    {
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->buildLDAPIterator(
            [
                [
                    'cn'   => 'John Mac Lane',
                    'uid'  => 'john_lane',
                    'mail' => 'john.mc.lane@nypd.gov',
                    'uuid' => 'ed1234',
                    'dn'   => 'uid=john_lane,ou=people,dc=tuleap,dc=local'
                ],
                [
                    'cn'   => 'William Wallas',
                    'uid'  => 'will_wall',
                    'mail' => 'will_wall@edimburgh.co.uk',
                    'uuid' => 'ed5432',
                    'dn'   => 'uid=will_wall,ou=people,dc=tuleap,dc=local'
                ]
            ],
            $this->ldap_params
        ));

        $this->expectException('LDAP_UserNotFoundException');

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItCreatesUserAccountWhenUserDoesntExist(): void
    {
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->john_mc_lane_result_iterator);
        $this->user_manager->shouldReceive('getUserByLdapId')->andReturns(null);

        $this->ldap_user_manager->shouldReceive('createAccountFromLdap')->with(\Mockery::any())->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItReturnsUserOnAccountCreation(): void
    {
        $expected_user = $this->buildUser();
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->john_mc_lane_result_iterator);
        $this->user_manager->shouldReceive('getUserByLdapId')->andReturns(null);

        $this->ldap_user_manager->shouldReceive('createAccountFromLdap')->andReturns($expected_user);
        $this->ldap_user_manager->shouldReceive('synchronizeUser')
            ->with($expected_user, Mockery::any(), $this->password)
            ->once();

        $user = $this->ldap_user_manager->authenticate($this->username, $this->password);
        $this->assertSame($expected_user, $user);
    }

    public function testItUpdateUserAccountsIfAlreadyExists(): void
    {
        $expected_user = $this->buildUser();
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->john_mc_lane_result_iterator);
        $this->user_manager->shouldReceive('getUserByLdapId')->andReturns($expected_user);

        $this->ldap_user_manager->shouldReceive('synchronizeUser')
            ->with($expected_user, Mockery::any(), $this->password)
            ->once();

        $this->ldap_user_manager->authenticate($this->username, $this->password);
    }

    public function testItReturnsUserOnAccountUpdate(): void
    {
        $expected_user = $this->buildUser();
        $this->user_sync->shouldReceive('getSyncAttributes')->andReturns(array());
        $this->ldap->shouldReceive('authenticate')->andReturns(true);
        $this->ldap->shouldReceive('searchLogin')->andReturns($this->john_mc_lane_result_iterator);
        $this->user_manager->shouldReceive('getUserByLdapId')->andReturns($expected_user);

        $this->ldap_user_manager->shouldReceive('synchronizeUser')->andReturns(true);

        $user = $this->ldap_user_manager->authenticate($this->username, $this->password);
        $this->assertSame($expected_user, $user);
    }

    private function buildUser(): PFUser
    {
        return new PFUser(['user_id' => 123]);
    }

    private function buildLDAPIterator(array $info, array $ldap_params): LDAPResultIterator
    {
        $ldap_info = array(
            'count' => count($info),
        );
        $i = 0;
        foreach ($info as $people) {
            $nb_params_excluding_dn = count($people) - 1;
            $ldap_info[$i] = array(
                'dn'    => $people['dn'],
                'count' => $nb_params_excluding_dn
            );
            $j = 0;
            foreach ($people as $param => $value) {
                if ($param == 'dn') {
                    continue;
                }
                $ldap_info[$i][$param] = array(
                    'count' => 1,
                    0       => $value,
                );
                $ldap_info[$i][$j] = $param;
                $j++;
            }
            $i++;
        }

        return new LDAPResultIterator($ldap_info, $ldap_params);
    }
}
