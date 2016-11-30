<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\Login\Registration;

use TuleapTestCase;

require_once(__DIR__ . '/../../bootstrap.php');

class UsernameGeneratorTest extends TuleapTestCase
{
    public function itGeneratesUsernameFromPreferredUsername()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returns(true);
        stub($rule)->isValid()->returns(true);
        $username_generator = new UsernameGenerator($rule);

        $generated_username = $username_generator->getUsername(
            array(
                'preferred_username' => 'mypreferredusername'
            )
        );
        $this->assertEqual('mypreferredusername', $generated_username);
    }

    public function itGeneratesUsernameFromGivenAndFamilyNames()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returns(true);
        stub($rule)->isValid()->returns(true);
        $username_generator = new UsernameGenerator($rule);

        $generated_username = $username_generator->getUsername(
            array(
                'given_name'  => 'Given Name',
                'family_name' => 'Family Name'
            )
        );
        $this->assertEqual('gfamilyname', $generated_username);
    }

    public function itGeneratesUsernameFromFamilyName()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returns(true);
        stub($rule)->isValid()->returns(true);
        $username_generator = new UsernameGenerator($rule);

        $generated_username = $username_generator->getUsername(
            array(
                'family_name' => 'Family Name'
            )
        );
        $this->assertEqual('familyname', $generated_username);
    }

    public function itGeneratesUsernameFromGivenName()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returns(true);
        stub($rule)->isValid()->returns(true);
        $username_generator = new UsernameGenerator($rule);

        $generated_username = $username_generator->getUsername(
            array(
                'given_name' => 'Given Name'
            )
        );
        $this->assertEqual('givenname', $generated_username);
    }

    public function itGeneratesUsernameWhenASimilarOneAlreadyExist()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returns(true);
        stub($rule)->isValid()->returnsAt(0, false);
        stub($rule)->isValid()->returnsAt(1, true);
        $username_generator = new UsernameGenerator($rule);

        $generated_username = $username_generator->getUsername(
            array(
                'preferred_username' => 'mypreferredusername'
            )
        );
        $this->assertEqual('mypreferredusername1', $generated_username);
    }

    public function itNeedsAtLeastGivenOrFamilyNamesToGenerateUsername()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returns(true);
        stub($rule)->isValid()->returns(true);
        $username_generator = new UsernameGenerator($rule);

        $this->expectException('Tuleap\OpenIDConnectClient\Login\Registration\NotEnoughDataToGenerateUsernameException');
        $username_generator->getUsername(array());
    }

    public function itNeedsDataCompatibleWithUnixUsername()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returns(false);
        stub($rule)->isValid()->returns(true);
        $username_generator = new UsernameGenerator($rule);

        $this->expectException('Tuleap\OpenIDConnectClient\Login\Registration\DataIncompatibleWithUsernameGenerationException');
        $username_generator->getUsername(
            array(
                'given_name'  => 'IncompatibleGivenName',
                'family_name' => 'IncompatibleFamilyName'
            )
        );
    }

    public function itTriesToUseGivenAndFamilyNamesEvenIfPreferredUsernameIsNotCompatible()
    {
        $rule = mock('Rule_UserName');
        stub($rule)->isUnixValid()->returnsAt(0, false);
        stub($rule)->isUnixValid()->returnsAt(1, true);
        stub($rule)->isValid()->returns(true);
        $username_generator = new UsernameGenerator($rule);

        $generated_username = $username_generator->getUsername(
            array(
                'preferred_username' => 'incompatiblepreferredusername',
                'given_name'         => 'Given Name',
                'family_name'        => 'Family Name'
            )
        );
        $this->assertEqual('gfamilyname', $generated_username);
    }
}
