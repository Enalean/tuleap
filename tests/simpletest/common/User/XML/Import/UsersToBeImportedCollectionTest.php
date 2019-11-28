<?php
/**
 * Copyright (c) Enalean, 2015 - 2017. All Rights Reserved.
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
use PFUser;

class UsersToBeImportedCollection_toCSVTest extends TuleapTestCase
{

    /** @var UsersToBeImportedCollection */
    private $collection;

    private $output_filename;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->output_filename = $this->getTmpDir() . '/output.csv';

        $this->collection = new UsersToBeImportedCollection();
    }

    public function tearDown()
    {
        if (is_file($this->output_filename)) {
            unlink($this->output_filename);
        }
        parent::tearDown();
    }

    private function getCSVHeader()
    {
        list($header,) = $this->parseCSVFile();

        return $header;
    }

    private function getCSVFirstData()
    {
        list(,$first_data) = $this->parseCSVFile();

        return $first_data;
    }

    private function parseCSVFile()
    {
        $csv    = fopen($this->output_filename, 'r');
        $header = fgetcsv($csv);
        $first_data = fgetcsv($csv);
        fclose($csv);

        return array($header, $first_data);
    }

    public function itGeneratesTheHeader()
    {
        $this->collection->toCSV($this->output_filename);

        $header = $this->getCSVHeader();
        $this->assertEqual($header, array('name', 'action', 'comments'));
    }

    public function itDoesNotDumpAlreadyExistingUser()
    {
        $this->collection->add(new AlreadyExistingUser(\Mockery::spy(\PFUser::class), 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertFalse($data);
    }

    public function itDumpsToBeActivatedUser()
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('jdoe');
        $user->shouldReceive('getStatus')->andReturns('S');

        $this->collection->add(new ToBeActivatedUser($user, 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertEqual($data, array('jdoe', 'noop', 'Status of existing user jdoe is [S]'));
    }

    public function itDumpsToBeCreatedUser()
    {
        $this->collection->add(new ToBeCreatedUser('jdoe', 'John Doe', 'jdoe@example.com', 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertEqual($data, array('jdoe', 'create:S', 'John Doe (jdoe) <jdoe@example.com> must be created'));
    }

    public function itDumpsEmailDoesNotMatchUser()
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('jdoe');
        $user->shouldReceive('getEmail')->andReturns('john.doe@example.com');
        $user->shouldReceive('getStatus')->andReturns('S');

        $this->collection->add(new EmailDoesNotMatchUser($user, 'jdoe@example.com', 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertEqual($data, array(
            'jdoe',
            'map:',
            'There is an existing user jdoe but its email <john.doe@example.com> does not match <jdoe@example.com>. Use action "map:jdoe" to confirm the mapping.'
        ));
    }

    public function itDumpsToBeMappedUser()
    {
        $user1 = \Mockery::spy(\PFUser::class);
        $user1->shouldReceive('getUserName')->andReturns('john');
        $user1->shouldReceive('getRealName')->andReturns('John Doe');
        $user1->shouldReceive('getEmail')->andReturns('john.doe@example.com');
        $user1->shouldReceive('getStatus')->andReturns('A');

        $user2 = \Mockery::spy(\PFUser::class);
        $user2->shouldReceive('getUserName')->andReturns('admin_john');
        $user2->shouldReceive('getRealName')->andReturns('John Doe (admin)');
        $user2->shouldReceive('getEmail')->andReturns('john.doe@example.com');
        $user2->shouldReceive('getStatus')->andReturns('A');

        $this->collection->add(new ToBeMappedUser('jdoe', 'John Doe', array($user1, $user2), 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertEqual($data, array(
            'jdoe',
            'map:',
            'User John Doe (jdoe) has the same email address than following users: John Doe (john) [A], John Doe (admin) (admin_john) [A].'
            . ' Use one of the following actions to confirm the mapping: "map:john", "map:admin_john".'
        ));
    }
}
