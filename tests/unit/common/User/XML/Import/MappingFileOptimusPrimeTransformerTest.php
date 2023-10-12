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
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;

final class MappingFileOptimusPrimeTransformerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    protected MappingFileOptimusPrimeTransformer $transformer;
    protected UsersToBeImportedCollection $collection;

    protected string $filename;
    /**
     * @var \UserManager&MockObject
     */
    private $user_manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = vfsStream::setup()->url() . '/users.csv';

        $this->user_manager = $this->createMock(\UserManager::class);

        $cstevens         = $this->buildUser('cstevens');
        $to_be_activated  = $this->buildUser('to.be.activated');
        $already_existing = $this->buildUser('already.existing');

        $this->user_manager->method('getUserByUserName')->willReturnMap([
            ['cstevens', $cstevens],
            ['to.be.activated', $to_be_activated],
            ['already.existing', $already_existing],
        ]);

        $this->transformer = new MappingFileOptimusPrimeTransformer($this->user_manager);
        $this->collection  = new UsersToBeImportedCollection();
    }

    private function buildUser(string $username): PFUser
    {
        return new PFUser(['user_name' => $username, 'language_id' => 'en']);
    }

    private function addAlreadyExistingUserToCollection(): void
    {
        $this->collection->add(
            new AlreadyExistingUser(
                $this->buildUser('already.existing'),
                104,
                'ldap1234'
            )
        );
    }

    private function addToBeActivatedUserToCollection(): void
    {
        $this->collection->add(
            new ToBeActivatedUser(
                $this->buildUser('to.be.activated'),
                104,
                'ldap1234'
            )
        );
    }

    private function addToBeCreatedUserToCollection($id = 104): void
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

    private function addEmailDoesNotMatchUserToCollection(): void
    {
        $this->collection->add(
            new EmailDoesNotMatchUser(
                $this->buildUser('email.does.not.match'),
                'email.does.not.match@example.com',
                104,
                'ldap1234'
            )
        );
    }

    private function addToBeMappedUserToCollection(): void
    {
        $this->collection->add(
            new ToBeMappedUser(
                'to.be.mapped',
                'To Be Mapped',
                [
                    $this->buildUser('cstevens'),
                ],
                104,
                'ldap1234'
            )
        );
    }

    private function generateCSV($name, $action): void
    {
        $content = <<<EOS
name,action,comments
$name,$action,"Osef joseph"

EOS;
        file_put_contents($this->filename, $content);
    }

    private function appendToCSV($name, $action): void
    {
        $content = <<<EOS
$name,$action,"Osef joseph"

EOS;
        file_put_contents($this->filename, $content, FILE_APPEND);
    }

    public function testItTransformsAToBeMappedToAWillBeMappedUser(): void
    {
        $cstevens  = $this->user_manager->getUserByUserName('cstevens');
        $cstevens2 = $this->buildUser('cstevens2');

        $this->collection->add(
            new ToBeMappedUser(
                'to.be.mapped',
                'To Be Mapped',
                [$cstevens, $cstevens2],
                104,
                'ldap1234'
            )
        );
        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);

        $user = $new_collection->getUserByUserName('to.be.mapped');
        self::assertInstanceOf(\User\XML\Import\WillBeMappedUser::class, $user);
        self::assertEquals($cstevens, $user->getMappedUser());
    }

    public function testItTransformsAnEmailDoesnotMatchToAWillBeMappedUser(): void
    {
        $cstevens = $this->user_manager->getUserByUserName('cstevens');
        $this->addEmailDoesNotMatchUserToCollection();
        $this->generateCSV('email.does.not.match', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('email.does.not.match');

        self::assertInstanceOf(\User\XML\Import\WillBeMappedUser::class, $user);
        self::assertEquals($cstevens, $user->getMappedUser());
    }

    public function testItTransformsAToBeCreatedToAWillBeMappedUser(): void
    {
        $cstevens = $this->user_manager->getUserByUserName('cstevens');
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        self::assertInstanceOf(\User\XML\Import\WillBeMappedUser::class, $user);
        self::assertEquals($cstevens, $user->getMappedUser());
    }

    public function testItTransformsAToBeActivatedToAWillBeMappedUser(): void
    {
        $cstevens = $this->user_manager->getUserByUserName('cstevens');
        $this->addToBeActivatedUserToCollection();
        $this->generateCSV('to.be.activated', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.activated');

        self::assertInstanceOf(\User\XML\Import\WillBeMappedUser::class, $user);
        self::assertEquals($cstevens, $user->getMappedUser());
    }

    public function testItTransformsAnAlreadyExistingToAWillBeMappedUser(): void
    {
        $cstevens = $this->user_manager->getUserByUserName('cstevens');
        $this->addAlreadyExistingUserToCollection();
        $this->generateCSV('already.existing', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('already.existing');

        self::assertInstanceOf(\User\XML\Import\WillBeMappedUser::class, $user);
        self::assertEquals($cstevens, $user->getMappedUser());
    }

    public function testItTransformsAToBeCreatedToAWillBeCreatedUserInActiveStatus(): void
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:A');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        self::assertInstanceOf(\User\XML\Import\WillBeCreatedUser::class, $user);
        self::assertEquals('to.be.created', $user->getUserName());
        self::assertEquals('To Be Created', $user->getRealName());
        self::assertEquals('to.be.created@example.com', $user->getEmail());
        self::assertEquals(PFUser::STATUS_ACTIVE, $user->getStatus());
    }

    public function testItTransformsAToBeCreatedToAWillBeCreatedUserInARestrictedStatus(): void
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:R');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        self::assertEquals(PFUser::STATUS_RESTRICTED, $user->getStatus());
    }

    public function testItTransformsAToBeCreatedToAWillBeCreatedUserInASuspendedStatus(): void
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:S');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        self::assertEquals(PFUser::STATUS_SUSPENDED, $user->getStatus());
    }

    public function testItTransformsAToBeCreatedToAWillBeCreatedUserInDefaultStatusSuspended(): void
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.created');

        self::assertEquals(PFUser::STATUS_SUSPENDED, $user->getStatus());
    }

    public function testItThrowsAnExceptionWhenGivenStatusIsInvalid(): void
    {
        $this->addToBeCreatedUserToCollection();
        $this->generateCSV('to.be.created', 'create:D');

        $this->expectException(\User\XML\Import\InvalidMappingFileException::class);

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItTransformsAToBeActivatedToAWillBeActivatedUser(): void
    {
        $to_be_activated = $this->user_manager->getUserByUserName('to.be.activated');
        $this->addToBeActivatedUserToCollection();
        $this->generateCSV('to.be.activated', 'noop');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('to.be.activated');

        self::assertInstanceOf(\User\XML\Import\WillBeActivatedUser::class, $user);
        self::assertEquals($to_be_activated, $user->getUser());
        self::assertEquals('to.be.activated', $user->getUserName());
    }

    public function testItTransformsAnAlreadyExistingToAWillBeActivatedUser(): void
    {
        $already_existing = $this->user_manager->getUserByUserName('already.existing');
        $this->addAlreadyExistingUserToCollection();
        $this->generateCSV('already.existing', 'noop');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);
        $user           = $new_collection->getUserByUserName('already.existing');

        self::assertInstanceOf(\User\XML\Import\WillBeActivatedUser::class, $user);
        self::assertEquals($already_existing, $user->getUser());
        self::assertEquals('already.existing', $user->getUserName());
    }

    public function testItThrowsAnExceptionWhenAUserInCollectionIsNotTransformedOrKept(): void
    {
        $this->addToBeActivatedUserToCollection();
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.activated', 'noop');

        $this->expectException(\User\XML\Import\MissingEntryInMappingFileException::class);

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItThrowsAnExceptionIfUsernameAppearsMultipleTimesInCSVFile(): void
    {
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.mapped', 'map:cstevens');
        $this->appendToCSV('to.be.mapped', 'map:already.existing');

        $this->expectException(\User\XML\Import\InvalidMappingFileException::class);

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItSkipsAlreadyExistingUsersNotFoundInMapping(): void
    {
        $this->addAlreadyExistingUserToCollection();
        $this->addToBeMappedUserToCollection();
        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $new_collection = $this->transformer->transform($this->collection, $this->filename);

        self::assertEquals(
            $this->collection->getUser('already.existing'),
            $new_collection->getUserByUserName('already.existing')
        );
    }

    public function testItThrowsAnExceptionIfMappingFileDoesNotExist(): void
    {
        $this->expectException(\User\XML\Import\MappingFileDoesNotExistException::class);

        $this->transformer->transform($this->collection, '/path/to/inexisting/file');
    }

    public function testItDoesNotThrowAnExceptionIfUserInMappingIsUnknownInCollectionSoThatWeCanReuseTheMappingFileInAnotherImport(): void
    {
        $this->expectNotToPerformAssertions();
        $this->generateCSV('unknown.user', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItDoesNotThrowAnExceptionWhenMapIsFilledWithAKnownUser(): void
    {
        $this->expectNotToPerformAssertions();
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItDoesNotThrowAnExceptionWhenEmailDoesNotMatch(): void
    {
        $this->expectNotToPerformAssertions();
        $this->addEmailDoesNotMatchUserToCollection();

        $this->generateCSV('email.does.not.match', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItDoesNotThrowExceptionWhenEntryInTheCollectionIsAlreadyExistingUser(): void
    {
        $this->expectNotToPerformAssertions();
        $this->addAlreadyExistingUserToCollection();

        $this->generateCSV('already.existing', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItDoesNotThrowExceptionWhenEntryInTheCollectionToBeActivatedUser(): void
    {
        $this->expectNotToPerformAssertions();
        $this->addToBeActivatedUserToCollection();

        $this->generateCSV('to.be.activated', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeCreatedUser(): void
    {
        $this->expectNotToPerformAssertions();
        $this->addToBeCreatedUserToCollection();

        $this->generateCSV('to.be.created', 'map:cstevens');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItThrowsExceptionWhenThereIsATypoInTheAction(): void
    {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'mat:cstevens');

        $this->expectException(\User\XML\Import\InvalidMappingFileException::class);

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItThrowsExceptionWhenMapIsNotFilled(): void
    {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:');

        $this->expectException(\User\XML\Import\InvalidMappingFileException::class);

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItThrowsExceptionWhenMapIsFilledWithAnUnknownUser(): void
    {
        $this->addToBeMappedUserToCollection();

        $this->generateCSV('to.be.mapped', 'map:unknown_user');

        $this->expectException(\User\XML\Import\InvalidMappingFileException::class);

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeCreatedSuspendedUser(): void
    {
        $this->expectNotToPerformAssertions();
        $this->addToBeCreatedUserToCollection();

        $this->generateCSV('to.be.created', 'create:S');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItDoesNotThrowExceptionWhenEntryInTheCollectionIsToBeActivatedUser(): void
    {
        $this->expectNotToPerformAssertions();
        $this->addToBeActivatedUserToCollection();

        $this->generateCSV('to.be.activated', 'noop');

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItThrowsAnExceptionWhenEmailDoesNotMatch(): void
    {
        $this->addEmailDoesNotMatchUserToCollection();

        $this->generateCSV('email.does.not.match', 'noop');

        $this->expectException(\User\XML\Import\InvalidMappingFileException::class);

        $this->transformer->transform($this->collection, $this->filename);
    }

    public function testItThrowsAnExceptionWhenUserIsNotSupported(): void
    {
        $this->addEmailDoesNotMatchUserToCollection();

        $this->expectException(\User\XML\Import\InvalidUserTypeException::class);

        $this->transformer->transformWithoutMap($this->collection, 'create:A');
    }

    public function testItActivatesUserThatWasSuspended(): void
    {
        $this->addToBeActivatedUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        self::assertInstanceOf(\User\XML\Import\WillBeActivatedUser::class, $user);
    }

    public function testItCreatesMissingUsers(): void
    {
        $this->addToBeCreatedUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        self::assertInstanceOf(\User\XML\Import\WillBeCreatedUser::class, $user);
    }

    public function testItDoesNothingForUsersThatAreAlreadyActive(): void
    {
        $this->addAlreadyExistingUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        self::assertInstanceOf(\User\XML\Import\AlreadyExistingUser::class, $user);
    }

    public function testItDoesNothingForAlreadyExistingUsers(): void
    {
        $this->addAlreadyExistingUserToCollection();

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        $user = $collection_for_import->getUserById(104);

        self::assertInstanceOf(\User\XML\Import\AlreadyExistingUser::class, $user);
    }

    public function testItManageSeveralUsersWithoutOveralpingResponsabilities(): void
    {
        $this->addToBeActivatedUserToCollection(104);
        $this->addToBeCreatedUserToCollection(105);

        $collection_for_import = $this->transformer->transformWithoutMap($this->collection, 'create:A');

        self::assertInstanceOf(\User\XML\Import\WillBeActivatedUser::class, $collection_for_import->getUserById(104));
        self::assertInstanceOf(\User\XML\Import\WillBeCreatedUser::class, $collection_for_import->getUserById(105));
    }
}
