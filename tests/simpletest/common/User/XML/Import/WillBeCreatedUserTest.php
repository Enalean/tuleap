<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
namespace User\XML\Import;

use TuleapTestCase;

class WillBeCreatedUser_processTest extends TuleapTestCase
{

    /** @var ToBeCreatedUser */
    private $user;

    /** @var UserManager */
    private $user_manager;

    /** @var Logger */
    private $logger;

    public function setUp()
    {
        parent::setUp();

        $this->user_manager = mock('UserManager');
        $this->logger       = mock('Logger');

        $this->user = new WillBeCreatedUser(
            'cstevens',
            'Cat Stevens',
            'cstevens@example.com',
            'S',
            'ed143'
        );
    }

    public function itCreatesANewUserInDatabase()
    {
        stub($this->user_manager)->createAccount()->returns(aUser()->build());

        expect($this->user_manager)->createAccount()->once();

        $this->user->process($this->user_manager, $this->logger);
    }

    public function itThrowsAnExceptionIfUserCannotBeCreated()
    {
        stub($this->user_manager)->createAccount()->returns(false);

        $this->expectException('User\XML\Import\UserCannotBeCreatedException');

        $this->user->process($this->user_manager, $this->logger);
    }
}
