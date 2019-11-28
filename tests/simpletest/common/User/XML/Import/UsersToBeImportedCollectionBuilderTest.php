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
use PFUser;
use XML_Security;
use Tuleap\Project\XML\Import\ArchiveInterface;

class MockArchive implements ArchiveInterface
{

    /** @var SimpleXMLElement */
    private $user_xml;

    public function __construct($user_xml)
    {
        $this->user_xml = $user_xml;
    }

    public function cleanUp()
    {
    }

    public function extractFiles()
    {
    }

    public function getExtractionPath()
    {
    }

    public function getProjectXML()
    {
    }

    public function getUsersXML()
    {
        return $this->user_xml;
    }
}

class UsersToBeImportedCollectionBuilderTestBase extends TuleapTestCase
{
    /** @var UsersToBeImportedCollectionBuilder */
    protected $builder;
    protected $user_manager;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->user_manager = \Mockery::spy(\UserManager::class);
        $this->builder = new UsersToBeImportedCollectionBuilder(
            $this->user_manager,
            \Mockery::spy(\Logger::class),
            new XML_Security(),
            \Mockery::spy(\XML_RNGValidator::class)
        );
    }

    protected function createUser($id, $username, $realname, $email, $ldapid, $status)
    {
        return aUser()
            ->withId($id)
            ->withUserName($username)
            ->withRealName($realname)
            ->withEmail($email)
            ->withLdapId($ldapid)
            ->withStatus($status)
            ->build();
    }
}

class UsersToBeImportedCollectionBuilderTest extends UsersToBeImportedCollectionBuilderTestBase
{

    private $active_user_in_ldap;
    private $suspended_user_in_ldap;
    private $active_user_in_db;
    private $suspended_user_in_db;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->active_user_in_ldap = $this->createUser(
            1001,
            'jdoe',
            'John Doe',
            'jdoe@example.com',
            'jd3456',
            PFUser::STATUS_ACTIVE
        );
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:jd3456')->andReturns($this->active_user_in_ldap);

        $this->suspended_user_in_ldap = $this->createUser(
            1002,
            'doe',
            'John Doe',
            'jdoe@example.com',
            'sus1234',
            PFUser::STATUS_SUSPENDED
        );
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:sus1234')->andReturns($this->suspended_user_in_ldap);

        $this->active_user_in_db = $this->createUser(
            1002,
            'cstevens',
            'Cat Stevens',
            'cstevens@example.com',
            '',
            PFUser::STATUS_ACTIVE
        );
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('cstevens')->andReturns($this->active_user_in_db);

        $this->suspended_user_in_db = $this->createUser(
            1002,
            'kperry',
            'Katy Perry',
            'kperry@example.com',
            '',
            PFUser::STATUS_SUSPENDED
        );
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('kperry')->andReturns($this->suspended_user_in_db);

        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('mmanson@example.com')->andReturns(array());
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('jdoe@example.com')->andReturns(array(
            $this->active_user_in_ldap,
            $this->suspended_user_in_ldap
        ));
    }

    public function itReturnsACollection()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?><users />');

        $collection = $this->builder->build($xml);

        $this->assertIsA($collection, 'User\\XML\\Import\\UsersToBeImportedCollection');
    }

    public function itReturnsACollectionWithAliveUserInLDAP()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>107</id>
                    <username>jdoe</username>
                    <realname>John Doe</realname>
                    <email>jdoe@example.com</email>
                    <ldapid>jd3456</ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'jdoe' => new AlreadyExistingUser($this->active_user_in_ldap, 107, 'jd3456')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsACollectionWithToBeActivatedWhenUserInLDAPIsNotAlive()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>107</id>
                    <username>doe</username>
                    <realname>John Doe</realname>
                    <email>jdoe@example.com</email>
                    <ldapid>sus1234</ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'doe' => new ToBeActivatedUser($this->suspended_user_in_ldap, 107, 'sus1234')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsACollectionWithAliveUserNotInLDAP()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>108</id>
                    <username>cstevens</username>
                    <realname>Cat Stevens</realname>
                    <email>cstevens@example.com</email>
                    <ldapid></ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'cstevens' => new AlreadyExistingUser($this->active_user_in_db, 108, '')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsACollectionWithUserNotInLDAPToBeActivated()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>109</id>
                    <username>kperry</username>
                    <realname>Katy Perry</realname>
                    <email>kperry@example.com</email>
                    <ldapid></ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'kperry' => new ToBeActivatedUser($this->suspended_user_in_db, 109, '')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsACollectionWithUserNotInLDAPWhenLdapIdDoesNotMatch()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>108</id>
                    <username>cstevens</username>
                    <realname>Cat Stevens</realname>
                    <email>cstevens@example.com</email>
                    <ldapid>no_matching_ldap_id</ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'cstevens' => new AlreadyExistingUser($this->active_user_in_db, 108, 'no_matching_ldap_id')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsACollectionWithUserToBeMappedWhenEmailDoesNotMatch()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>108</id>
                    <username>cstevens</username>
                    <realname>Cat Stevens</realname>
                    <email>bogossdu38@example.com</email>
                    <ldapid></ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'cstevens' => new EmailDoesNotMatchUser($this->active_user_in_db, 'bogossdu38@example.com', 108, '')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itTrustsLDAPEvenIfEmailDoesNotMatch()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>107</id>
                    <username>jdoe</username>
                    <realname>John Doe</realname>
                    <email>bogossdu73@example.com</email>
                    <ldapid>jd3456</ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'jdoe' => new AlreadyExistingUser($this->active_user_in_ldap, 107, 'jd3456')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsACollectionWithUserToBeCreatedWhenNotFoundInLDAPByUsernameOrByEmail()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>111</id>
                    <username>mmanson</username>
                    <realname>Marylin Manson</realname>
                    <email>mmanson@example.com</email>
                    <ldapid></ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'mmanson' => new ToBeCreatedUser(
                'mmanson',
                'Marylin Manson',
                'mmanson@example.com',
                111,
                ''
            )
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsACollectionWithUserToBeMappedWhenUserIsFoundByMail()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>109</id>
                    <username>john.doe</username>
                    <realname>John Doe</realname>
                    <email>jdoe@example.com</email>
                    <ldapid></ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->build($xml);
        $expected   = array(
            'john.doe' => new ToBeMappedUser(
                'john.doe',
                'John Doe',
                array(
                    $this->active_user_in_ldap,
                    $this->suspended_user_in_ldap
                ),
                109,
                ''
            )
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }
}

class UsersToBeImportedCollectionBuilder_AutomapTest extends UsersToBeImportedCollectionBuilderTestBase
{

    private $john_doe;
    private $cat_steven;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->john_doe = $this->createUser(
            1001,
            'jdoe',
            'John Doe',
            'jdoe@example.com',
            'jd3456',
            PFUser::STATUS_ACTIVE
        );
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('ldapId:jd3456')->andReturns($this->john_doe);
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('jdoe')->andReturns($this->john_doe);
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('jdoe@example.com')->andReturns(array($this->john_doe));

        $this->cat_steven = $this->createUser(
            1002,
            'cstevens',
            'Cat Stevens',
            'cstevens@example.com',
            '',
            PFUser::STATUS_ACTIVE
        );
        $this->user_manager->shouldReceive('getUserByIdentifier')->with('cstevens')->andReturns($this->cat_steven);
        $this->user_manager->shouldReceive('getAllUsersByEmail')->with('cstevens@example.com')->andReturns(array($this->john_doe));
    }

    public function itReturnsAlreadyActiveUserWhenUserIsValidInLdap()
    {
        $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>109</id>
                    <username>john.doe</username>
                    <realname>John Doe</realname>
                    <email>jdoe@example.com</email>
                    <ldapid>jd3456</ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->buildWithoutEmail($xml);
        $expected   = array(
            'jdoe' => new AlreadyExistingUser($this->john_doe, 109, 'jd3456')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itReturnsAnAlreadyExistingUserWhenUsernameAreEqualsAndEmailAreDifferent()
    {
         $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>110</id>
                    <username>cstevens</username>
                    <realname>Cat Stevens</realname>
                    <email>cs@example.com</email>
                    <ldapid>cs3456</ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->buildWithoutEmail($xml);

        $expected   = array(
            'cstevens' => new AlreadyExistingUser($this->cat_steven, 110, 'cs3456')
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }

    public function itCreatesUserWhenNeitherLdapNorUserNameMatchEvenIfEmailExists()
    {
         $xml = new MockArchive('<?xml version="1.0" encoding="UTF-8"?>
            <users>
                <user>
                    <id>111</id>
                    <username>ci_bot_manathan</username>
                    <realname>Continuous Integration Bot</realname>
                    <email>cstevens@example.com</email>
                    <ldapid></ldapid>
                </user>
            </users>
        ');

        $collection = $this->builder->buildWithoutEmail($xml);

        $expected   = array(
            'ci_bot_manathan' => new ToBeCreatedUser(
                'ci_bot_manathan',
                'Continuous Integration Bot',
                'cstevens@example.com',
                111,
                ''
            )
        );

        $this->assertEqual(
            $collection->toArray(),
            $expected
        );
    }
}
