<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class Mail_RecipientListBuilderTest extends TuleapTestCase
{

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
    private $regular_user_name       = 'Regular User';
    private $bob_suspended_user_name = 'Suspended Bob';
    private $bob_active_user_name    = 'Active Bob';

    private $bob_identifier = 'bdylan';

    private $active_user;
    private $deleted_user;
    private $bob_suspended_user;
    private $bob_active_user;

    /** @var Mail_RecipientListBuilder */
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->active_user = aUser()
            ->withStatus(PFUser::STATUS_ACTIVE)
            ->withEmail($this->active_user_address)
            ->withRealName($this->active_user_name)
            ->build();

        $suspended_user = aUser()
            ->withStatus(PFUser::STATUS_SUSPENDED)
            ->withEmail($this->suspended_user_address)
            ->withRealName($this->suspended_user_name)
            ->build();

        $this->deleted_user = aUser()
            ->withStatus(PFUser::STATUS_DELETED)
            ->withEmail($this->deleted_user_address)
            ->withRealName($this->deleted_user_name)
            ->build();

        $this->bob_suspended_user = aUser()
            ->withStatus(PFUser::STATUS_SUSPENDED)
            ->withEmail($this->bob_suspended_user_address)
            ->withRealName($this->bob_suspended_user_name)
            ->build();

        $this->bob_active_user = aUser()
            ->withStatus(PFUser::STATUS_ACTIVE)
            ->withEmail($this->bob_active_user_address)
            ->withRealName($this->bob_active_user_name)
            ->build();

        $user_manager = mock('UserManager');
        stub($user_manager)
            ->getAllUsersByEmail($this->active_user_address)
            ->returns(array($this->active_user));
        stub($user_manager)
            ->getAllUsersByEmail($this->suspended_user_address)
            ->returns(array($suspended_user));
        stub($user_manager)
            ->getAllUsersByEmail($this->deleted_user_address)
            ->returns(array($this->deleted_user));
        stub($user_manager)
            ->getAllUsersByEmail($this->bob_active_user_address)
            ->returns(array($this->bob_suspended_user, $this->bob_active_user));
        stub($user_manager)
            ->findUser($this->bob_identifier)
            ->returns($this->bob_active_user);

        $this->builder = new Mail_RecipientListBuilder($user_manager);
    }

    public function itReturnsAnExternalAddress()
    {
        $list = $this->builder->getValidRecipientsFromAddresses(array($this->external_address));

        $expected = array(
            array('email' => $this->external_address, 'real_name' => '')
        );

        $this->assertEqual($expected, $list);
    }

    public function itReturnsAListOfExternalAddresses()
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->external_address, $this->external_address2)
        );

        $expected = array(
            array('email' => $this->external_address,  'real_name' => ''),
            array('email' => $this->external_address2, 'real_name' => '')
        );

        $this->assertEqual($expected, $list);
    }

    public function itLooksForAUser()
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->active_user_address)
        );

        $expected = array(
            array('email' => $this->active_user_address, 'real_name' => $this->active_user_name),
        );

        $this->assertEqual($expected, $list);
    }

    public function itDoesNotOutputSuspendedNorDeletedUsers()
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->suspended_user_address, $this->deleted_user_address)
        );

        $expected = array(
        );

        $this->assertEqual($expected, $list);
    }

    public function itTakesTheFirstUserAccountWithAllowedStatus()
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->bob_active_user_address)
        );

        $expected = array(
            array('email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name),
        );

        $this->assertEqual($expected, $list);
    }

    public function itFallbacksOnFindUserIfEmailNotFound()
    {
        $list = $this->builder->getValidRecipientsFromAddresses(
            array($this->bob_identifier)
        );

        $expected = array(
            array('email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name),
        );

        $this->assertEqual($expected, $list);
    }

    public function itValidatesAListOfUsers()
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

        $this->assertEqual($expected, $list);
    }
}
