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
use UserManager;

class MappingFileOptimusPrimeTransformer_BaseTest extends TuleapTestCase
{

    /** @var MappingFileOptimusPrimeTransformer */
    protected $transformer;

    /** @var UsersToBeImportedCollection */
    protected $collection;

    protected $filename;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->filename = $this->getTmpDir() .'/users.csv';

        $this->user_manager = \Mockery::spy(\UserManager::class);

        $cstevens         = aUser()->withUserName('cstevens')->build();
        $to_be_activated  = aUser()->withUserName('to.be.activated')->build();
        $already_existing = aUser()->withUserName('already.existing')->build();

        $this->user_manager->shouldReceive('getUserByUserName')->with('cstevens')->andReturns($cstevens);
        $this->user_manager->shouldReceive('getUserByUserName')->with('to.be.activated')->andReturns($to_be_activated);
        $this->user_manager->shouldReceive('getUserByUserName')->with('already.existing')->andReturns($already_existing);

        $this->transformer = new MappingFileOptimusPrimeTransformer($this->user_manager);
        $this->collection  = new UsersToBeImportedCollection();
    }

    protected function addAlreadyExistingUserToCollection()
    {
        $this->collection->add(
            new AlreadyExistingUser(
                aUser()->withUserName('already.existing')->build(),
                104,
                'ldap1234'
            )
        );
    }

    protected function addToBeActivatedUserToCollection()
    {
        $this->collection->add(
            new ToBeActivatedUser(
                aUser()->withUserName('to.be.activated')->build(),
                104,
                'ldap1234'
            )
        );
    }

    protected function addToBeCreatedUserToCollection($id = 104)
    {
        $this->collection->add(
            new ToBeCreatedUser(
                'to.be.created',
                'To Be Created',
                'to.be.created@example.com',
                $id,
                'ldap1234'
            )
        );
    }

    protected function addEmailDoesNotMatchUserToCollection()
    {
        $this->collection->add(
            new EmailDoesNotMatchUser(
                aUser()->withUserName('email.does.not.match')->build(),
                'email.does.not.match@example.com',
                104,
                'ldap1234'
            )
        );
    }

    protected function addToBeMappedUserToCollection()
    {
        $this->collection->add(
            new ToBeMappedUser(
                'to.be.mapped',
                'To Be Mapped',
                array(
                    aUser()->withUserName('cstevens')->build()
                ),
                104,
                'ldap1234'
            )
        );
    }

    public function tearDown()
    {
        if (is_file($this->filename)) {
            unlink($this->filename);
        }
        parent::tearDown();
    }

    protected function generateCSV($name, $action)
    {
        $content = <<<EOS
name,action,comments
$name,$action,"Osef joseph"

EOS;
        file_put_contents($this->filename, $content);
    }

    public function appendToCSV($name, $action)
    {
        $content = <<<EOS
$name,$action,"Osef joseph"

EOS;
        file_put_contents($this->filename, $content, FILE_APPEND);
    }
}

class MappingFileOptimusPrimeTransformer_transformTest extends MappingFileOptimusPrimeTransformer_BaseTest
{

    public function itTransformsAToBeMappedToAWillBeMappedUser()
    {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $cstevens2 = aUser()->withUserName('cstevens2')->build();

        $this->collection->add(
            new ToBeMappedUser(
                'to.be.mapped',
                'To Be Mapped',
                array($cstevens, $cstevens2),
                104,
                'ldap1234'
            )
        );
        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);

        $user = $new_collection->getUserByUserName('to.be.mapped');
        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAnEmailDoesnotMatchToAWillBeMappedUser()
    {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addEmailDoesNotMatchUserToCollection();
        $this->generateCSV('email.does.not.match', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('email.does.not.match');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAToBeCreatedToAWillBeMappedUser()
    {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAToBeActivatedToAWillBeMappedUser()
    {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addToBeActivatedUserToCollection();
        $this->generateCSV('to.be.activated', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.activated');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAnAlreadyExistingToAWillBeMappedUser()
    {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $this->addAlreadyExistingUserToCollection();
        $this->generateCSV('already.existing', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('already.existing');

        $this->assertIsA($user, 'User\XML\Import\WillBeMappedUser');
        $this->assertEqual($user->getMappedUser(), $cstevens);
    }

    public function itTransformsAToBeCreatedToAWillBeCreatedUserInActiveStatus()
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:A');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        $this->assertIsA($user, 'User\XML\Import\WillBeCreatedUser');
        $this->assertEqual($user->getUserName(), 'to.be.created');
        $this->assertEqual($user->getRealName(), 'To Be Created');
        $this->assertEqual($user->getEmail(), 'to.be.created@example.com');
        $this->assertEqual($user->getStatus(), PFUser::STATUS_ACTIVE);
    }

    public function itTransformsAToBeCreatedToAWillBeCreatedUserInARestrictedStatus()
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:R');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        $this->assertEqual($user->getStatus(), PFUser::STATUS_RESTRICTED);
    }

    public function itTransformsAToBeCreatedToAWillBeCreatedUserInASuspendedStatus()
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:S');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        $this->assertEqual($user->getStatus(), PFUser::STATUS_SUSPENDED);
    }

    public function itTransformsAToBeCreatedToAWillBeCreatedUserInDefaultStatusSuspended()
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        $this->assertEqual($user->getStatus(), PFUser::STATUS_SUSPENDED);
    }

    public function itThrowsAnExceptionWhenGivenStatusIsInvalid()
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:D');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
    }

    public function itTransformsAToBeActivatedToAWillBeActivatedUser()
    {
        $to_be_activated = $this->user_manager->getUserByUserName('to.be.activated');
        $this->addToBeActivatedUserToCollection();
        $this->generateCSV('to.be.activated', 'noop');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.activated');

        $this->assertIsA($user, 'User\XML\Import\WillBeActivatedUser');
        $this->assertEqual($user->getUser(), $to_be_activated);
        $this->assertEqual($user->getUserName(), 'to.be.activated');
    }

    public function itTransformsAnAlreadyExistingToAWillBeActivatedUser()
    {
        $already_existing = $this->user_manager->getUserByUserName('already.existing');
        $this->addAlreadyExistingUserToCollection();
        $this->generateCSV('already.existing', 'noop');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('already.existing');

        $this->assertIsA($user, 'User\XML\Import\WillBeActivatedUser');
        $this->assertEqual($user->getUser(), $already_existing);
        $this->assertEqual($user->getUserName(), 'already.existing');
    }

    public function itThrowsAnExceptionWhenAUserInCollectionIsNotTransformedOrKept()
    {
        $this->addToBeActivatedUserToCollection();
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.activated', 'noop');

        $this->expectException('User\XML\Import\MissingEntryInMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsAnExceptionIfUsernameAppearsMultipleTimesInCSVFile()
    {
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.mapped', 'map:cstevens');
        $this->appendToCSV('to.be.mapped', 'map:already.existing');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itSkipsAlreadyExistingUsersNotFoundInMapping()
    {
        $this->addAlreadyExistingUserToCollection();
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);

        $this->assertEqual(
            $this->collection->getUser('already.existing'),
            $new_collection->getUserByUserName('already.existing')
        );
    }

    public function itThrowsAnExceptionIfMappingFileDoesNotExist()
    {
        $this->expectException('User\XML\Import\MappingFileDoesNotExistException');

        $this->transformer->transform($this->collection, '/path/to/inexisting/file');
    }
}

class MappingFileOptimusPrimeTransformer_userUnknownInCollectionTest extends MappingFileOptimusPrimeTransformer_BaseTest
{

    public function itTDoesNotThrowAnExceptionIfUserInMappingIsUnknownInCollectionSoThatWeCanReuseTheMappingFileInAnotherImport()
    {
        $this->generateCSV('unknown.user', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }
}

class MappingFileOptimusPrimeTransformer_mapTest extends MappingFileOptimusPrimeTransformer_BaseTest
{

    public function itDoesNotThrowAnExceptionWhenMapIsFilledWithAKnownUser()
    {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowAnExceptionWhenEmailDoesNotMatch()
    {
        $this->addEmailDoesNotMatchUserToCollection();

        $this->generateCSV('email.does.not.match', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsAlreadyExistingUser()
    {
        $this->addAlreadyExistingUserToCollection();

        $this->generateCSV('already.existing', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionToBeActivatedUser()
    {
        $this->addToBeActivatedUserToCollection();

        $this->generateCSV('to.be.activated', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeCreatedUser()
    {
        $this->addToBeCreatedUserToCollection();

        $this->generateCSV('to.be.created', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsExceptionWhenThereIsATypoInTheAction()
    {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'mat:cstevens');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsExceptionWhenMapIsNotFilled()
    {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsExceptionWhenMapIsFilledWithAnUnknownUser()
    {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:unknown_user');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }
}

class MappingFileOptimusPrimeTransformer_createTest extends MappingFileOptimusPrimeTransformer_BaseTest
{

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeCreatedUser()
    {
        $this->addToBeCreatedUserToCollection();

        $this->generateCSV('to.be.created', 'create:S');

        $this->transformer->transform($this->collection, $this->filename);
    }
}

class MappingFileOptimusPrimeTransformer_activateTest extends MappingFileOptimusPrimeTransformer_BaseTest
{

    public function itDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeActivatedUser()
    {
        $this->addToBeActivatedUserToCollection();

        $this->generateCSV('to.be.activated', 'noop');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function itThrowsAnExceptionWhenEmailDoesNotMatch()
    {
        $this->addEmailDoesNotMatchUserToCollection();

        $this->generateCSV('email.does.not.match', 'noop');

        $this->expectException('User\XML\Import\InvalidMappingFileException');

        $this->transformer->transform($this->collection, $this->filename);
    }
}

class MappingFileOptimusPrimeTransformer_transformWithoutMapTest extends MappingFileOptimusPrimeTransformer_BaseTest
{

    public function itThrowsAnExceptionWhenUserIsNotSupported()
    {
        $this->addEmailDoesNotMatchUserToCollection();

        $this->expectException();

        $this->transformer->transformWithoutMap($this->collection, 'create:A');
    }

    public function itActivatesUserThatWasSuspended()
    {
        $this->addToBeActivatedUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        $this->assertIsA($user, 'User\XML\Import\WillBeActivatedUser');
    }

    public function itCreatesMissingUsers()
    {
        $this->addToBeCreatedUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        $this->assertIsA($user, 'User\XML\Import\WillBeCreatedUser');
    }

    public function itDoesNothingForUsersThatAreAlreadyActive()
    {
        $this->addAlreadyExistingUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        $this->assertIsA($user, 'User\XML\Import\AlreadyExistingUser');
    }

    public function itDoesNothingForAlreadyExistingUsers()
    {
        $this->addAlreadyExistingUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        $this->assertIsA($user, 'User\XML\Import\AlreadyExistingUser');
    }

    public function itManageSeveralUsersWithoutOveralpingResponsabilities()
    {
        $this->addToBeActivatedUserToCollection(104);
        $this->addToBeCreatedUserToCollection(105);

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $this->assertIsA($collection_for_import->getUserById(104), 'User\XML\Import\WillBeActivatedUser');
        $this->assertIsA($collection_for_import->getUserById(105), 'User\XML\Import\WillBeCreatedUser');
    }
}
