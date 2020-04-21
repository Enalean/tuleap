<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
final class Mail_RecipientListBuilderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $external_address           = 'toto@example.com';
    private $external_address2          = 'toto2@example.com';
    private $active_user_address        = 'active@tuleap.example.com';
    private $suspended_user_address     = 'suspended@tuleap.example.com';
    private $deleted_user_address       = 'deleted@tuleap.example.com';
    private $bob_suspended_user_address = 'bob@tuleap.example.com';
    private $bob_active_user_address    = 'bob@tuleap.example.com';

    private $active_user_name        = 'Valid User';
    private $suspended_user_name     = 'Suspended User';
    private $deleted_user_name       = 'Deleted User';
    private $bob_suspended_user_name = 'Suspended Bob';
    private $bob_active_user_name    = 'Active Bob';

    private $bob_identifier = 'bdylan';

    private $active_user;
    private $deleted_user;
    private $bob_suspended_user;
    private $bob_active_user;

    /** @var Mail_RecipientListBuilder */
    private $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->active_user        = $this->buildUser(PFUser::STATUS_ACTIVE, $this->active_user_address, $this->active_user_name);
        $suspended_user           = $this->buildUser(PFUser::STATUS_SUSPENDED, $this->suspended_user_address, $this->suspended_user_name);
        $this->deleted_user       = $this->buildUser(PFUser::STATUS_DELETED, $this->deleted_user_address, $this->deleted_user_name);
        $this->bob_suspended_user = $this->buildUser(PFUser::STATUS_SUSPENDED, $this->bob_suspended_user_address, $this->bob_suspended_user_name);
        $this->bob_active_user    = $this->buildUser(PFUser::STATUS_ACTIVE, $this->bob_active_user_address, $this->bob_active_user_name);

        $user_manager = \Mockery::spy(\UserManager::class);
        $user_manager->shouldReceive('getAllUsersByEmail')->with($this->active_user_address)->andReturns(array($this->active_user));
        $user_manager->shouldReceive('getAllUsersByEmail')->with($this->suspended_user_address)->andReturns(array($suspended_user));
        $user_manager->shouldReceive('getAllUsersByEmail')->with($this->deleted_user_address)->andReturns(array($this->deleted_user));
        $user_manager->shouldReceive('getAllUsersByEmail')->with($this->bob_active_user_address)->andReturns(array($this->bob_suspended_user, $this->bob_active_user));
        $user_manager->shouldReceive('findUser')->with($this->bob_identifier)->andReturns($this->bob_active_user);

        $this->builder = new Mail_RecipientListBuilder($user_manager);
    }

    public function testItReturnsAnExternalAddress(): void
    {
        $list = $this->builder->getValidRecipientsFromAddresses(array($this->external_address));

        $expected = array(
            array('email' => $this->external_address, 'real_name' => '')
        );

        $this->assertEquals($expected, $list);
    }

    public function testItReturnsAListOfExternalAddresses(): void
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->external_address, $this->external_address2)
        );

        $expected = array(
            array('email' => $this->external_address,  'real_name' => ''),
            array('email' => $this->external_address2, 'real_name' => '')
        );

        $this->assertEquals($expected, $list);
    }

    public function testItLooksForAUser(): void
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->active_user_address)
        );

        $expected = array(
            array('email' => $this->active_user_address, 'real_name' => $this->active_user_name),
        );

        $this->assertEquals($expected, $list);
    }

    public function testItDoesNotOutputSuspendedNorDeletedUsers(): void
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->suspended_user_address, $this->deleted_user_address)
        );

        $expected = array(
        );

        $this->assertEquals($expected, $list);
    }

    public function testItTakesTheFirstUserAccountWithAllowedStatus(): void
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->bob_active_user_address)
        );

        $expected = array(
            array('email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name),
        );

        $this->assertEquals($expected, $list);
    }

    public function testItFallbacksOnFindUserIfEmailNotFound(): void
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->bob_identifier)
        );

        $expected = array(
            array('email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name),
        );

        $this->assertEquals($expected, $list);
    }

    public function testItValidatesAListOfUsers(): void
    {
        $list = $this->builder->getValidRecipientsFromUsers(
            array(
                $this->active_user,
                $this->deleted_user,
                $this->bob_suspended_user,
                $this->bob_active_user
            )
        );

        $expected = array(
            array('email' => $this->active_user_address,     'real_name' => $this->active_user_name),
            array('email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name),
        );

        $this->assertEquals($expected, $list);
    }

    private function buildUser(string $status, string $email, string $realname): PFUser
    {
        return new PFUser(['status' => $status, 'email' => $email, 'realname' => $realname, 'language_id' => 'en']);
    }
}
