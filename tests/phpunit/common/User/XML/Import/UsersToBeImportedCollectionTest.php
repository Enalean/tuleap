<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace User\XML\Import;

use org\bovigo\vfs\vfsStream;

final class UsersToBeImportedCollectionTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var UsersToBeImportedCollection */
    private $collection;

    private $output_filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->output_filename = vfsStream::setup()->url() . '/output.csv';

        $this->collection = new UsersToBeImportedCollection();
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

    public function testItGeneratesTheHeader(): void
    {
        $this->collection->toCSV($this->output_filename);

        $header = $this->getCSVHeader();
        $this->assertEquals(array('name', 'action', 'comments'), $header);
    }

    public function testItDoesNotDumpAlreadyExistingUser(): void
    {
        $this->collection->add(new AlreadyExistingUser(\Mockery::spy(\PFUser::class), 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertFalse($data);
    }

    public function testItDumpsToBeActivatedUser(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('jdoe');
        $user->shouldReceive('getStatus')->andReturns('S');

        $this->collection->add(new ToBeActivatedUser($user, 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertEquals(array('jdoe', 'noop', 'Status of existing user jdoe is [S]'), $data);
    }

    public function testItDumpsToBeCreatedUser(): void
    {
        $this->collection->add(new ToBeCreatedUser('jdoe', 'John Doe', 'jdoe@example.com', 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertEquals(array('jdoe', 'create:S', 'John Doe (jdoe) <jdoe@example.com> must be created'), $data);
    }

    public function testItDumpsEmailDoesNotMatchUser(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getUserName')->andReturns('jdoe');
        $user->shouldReceive('getEmail')->andReturns('john.doe@example.com');
        $user->shouldReceive('getStatus')->andReturns('S');

        $this->collection->add(new EmailDoesNotMatchUser($user, 'jdoe@example.com', 104, 'ldap1234'));
        $this->collection->toCSV($this->output_filename);

        $data = $this->getCSVFirstData();
        $this->assertEquals(
            array(
                'jdoe',
                'map:',
                'There is an existing user jdoe but its email <john.doe@example.com> does not match <jdoe@example.com>. Use action "map:jdoe" to confirm the mapping.'
            ),
            $data
        );
    }

    public function testItDumpsToBeMappedUser(): void
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
        $this->assertEquals(
            array(
                'jdoe',
                'map:',
                'User John Doe (jdoe) has the same email address than following users: John Doe (john) [A], John Doe (admin) (admin_john) [A].'
                . ' Use one of the following actions to confirm the mapping: "map:john", "map:admin_john".'
            ),
            $data
        );
    }
}
