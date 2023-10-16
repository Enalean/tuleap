<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Project\XML\Import\ArchiveInterface;

final class UsersToBeImportedCollectionBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UsersToBeImportedCollectionBuilder $builder;
    private \UserManager&MockObject $user_manager;
    private PFUser $active_user_in_ldap;
    private PFUser $suspended_user_in_ldap;
    private PFUser $active_user_in_db;
    private PFUser $suspended_user_in_db;
    private PFUser $john_doe;
    private PFUser $cat_steven;

    protected function setUp(): void
    {
        parent::setUp();

        $xml_rng_validator = $this->createMock(\XML_RNGValidator::class);
        $xml_rng_validator->method('validate');

        $this->user_manager = $this->createMock(\UserManager::class);
        $this->builder      = new UsersToBeImportedCollectionBuilder(
            $this->user_manager,
            $xml_rng_validator,
        );

        $this->active_user_in_ldap = $this->createUser(
            1001,
            'jdoe',
            'John Doe',
            'jdoe@example.com',
            'jd3456',
            PFUser::STATUS_ACTIVE
        );

        $this->suspended_user_in_ldap = $this->createUser(
            1002,
            'doe',
            'John Doe',
            'jdoe@example.com',
            'sus1234',
            PFUser::STATUS_SUSPENDED
        );

        $this->active_user_in_db = $this->createUser(
            1002,
            'cstevens',
            'Cat Stevens',
            'cstevens@example.com',
            '',
            PFUser::STATUS_ACTIVE
        );

        $this->suspended_user_in_db = $this->createUser(
            1002,
            'kperry',
            'Katy Perry',
            'kperry@example.com',
            '',
            PFUser::STATUS_SUSPENDED
        );

        $this->john_doe = $this->createUser(
            1001,
            'jdoe',
            'John Doe',
            'jdoe@example.com',
            'jd3456',
            PFUser::STATUS_ACTIVE
        );

        $this->cat_steven = $this->createUser(
            1002,
            'cstevens',
            'Cat Stevens',
            'cstevens@example.com',
            '',
            PFUser::STATUS_ACTIVE
        );

        $this->user_manager->method('getUserByIdentifier')->willReturnMap([
            ['ldapId:jd3456', $this->active_user_in_ldap],
            ['ldapId:sus1234', $this->suspended_user_in_ldap],
            ['cstevens', $this->active_user_in_db],
            ['kperry', $this->suspended_user_in_db],
            ['ldapId:jd3456', $this->john_doe],
            ['jdoe', $this->john_doe],
            ['cstevens', $this->cat_steven],
        ]);

        $this->user_manager->method('getAllUsersByEmail')->willReturnCallback(
            function (string $email): array {
                if ($email === 'mmanson@example.com') {
                    return [];
                } elseif ($email === 'jdoe@example.com') {
                    return [
                        $this->active_user_in_ldap,
                        $this->suspended_user_in_ldap,
                    ];
                } elseif ($email === 'cstevens@example.com') {
                    return [$this->cat_steven];
                }

                var_dump($email);
                throw new \LogicException('must not be here');
            }
        );
    }

    private function createUser(int $id, string $username, string $realname, string $email, string $ldapid, string $status): PFUser
    {
        return new PFUser([
            'user_id'     => $id,
            'user_name'   => $username,
            'realname'    => $realname,
            'email'       => $email,
            'ldap_id'     => $ldapid,
            'status'      => $status,
            'language_id' => 'en',
        ]);
    }

    private function createArchiveXMLUsers(string $user_xml): ArchiveInterface
    {
        return new class ($user_xml) implements ArchiveInterface
        {
            /** @var string */
            private $user_xml;

            public function __construct(string $user_xml)
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
        };
    }

    public function testItReturnsACollection(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?><users />');

        $collection = $this->builder->build($xml);

        self::assertInstanceOf(\User\XML\Import\UsersToBeImportedCollection::class, $collection);
    }

    public function testItReturnsACollectionWithAliveUserInLDAP(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'jdoe' => new AlreadyExistingUser($this->active_user_in_ldap, 107, 'jd3456'),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsACollectionWithToBeActivatedWhenUserInLDAPIsNotAlive(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'doe' => new ToBeActivatedUser($this->suspended_user_in_ldap, 107, 'sus1234'),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsACollectionWithAliveUserNotInLDAP(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'cstevens' => new AlreadyExistingUser($this->active_user_in_db, 108, ''),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsACollectionWithUserNotInLDAPToBeActivated(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'kperry' => new ToBeActivatedUser($this->suspended_user_in_db, 109, ''),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsACollectionWithUserNotInLDAPWhenLdapIdDoesNotMatch(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'cstevens' => new AlreadyExistingUser($this->active_user_in_db, 108, 'no_matching_ldap_id'),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsACollectionWithUserToBeMappedWhenEmailDoesNotMatch(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'cstevens' => new EmailDoesNotMatchUser($this->active_user_in_db, 'bogossdu38@example.com', 108, ''),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItTrustsLDAPEvenIfEmailDoesNotMatch(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'jdoe' => new AlreadyExistingUser($this->active_user_in_ldap, 107, 'jd3456'),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsACollectionWithUserToBeCreatedWhenNotFoundInLDAPByUsernameOrByEmail(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'mmanson' => new ToBeCreatedUser(
                'mmanson',
                'Marylin Manson',
                'mmanson@example.com',
                111,
                ''
            ),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsACollectionWithUserToBeMappedWhenUserIsFoundByMail(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'john.doe' => new ToBeMappedUser(
                'john.doe',
                'John Doe',
                [
                    $this->active_user_in_ldap,
                    $this->suspended_user_in_ldap,
                ],
                109,
                ''
            ),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsAlreadyActiveUserWhenUserIsValidInLdap(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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
        $expected   = [
            'jdoe' => new AlreadyExistingUser($this->john_doe, 109, 'jd3456'),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItReturnsAnAlreadyExistingUserWhenUsernameAreEqualsAndEmailAreDifferent(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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

        $expected = [
            'cstevens' => new AlreadyExistingUser($this->cat_steven, 110, 'cs3456'),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }

    public function testItCreatesUserWhenNeitherLdapNorUserNameMatchEvenIfEmailExists(): void
    {
        $xml = $this->createArchiveXMLUsers('<?xml version="1.0" encoding="UTF-8"?>
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

        $expected = [
            'ci_bot_manathan' => new ToBeCreatedUser(
                'ci_bot_manathan',
                'Continuous Integration Bot',
                'cstevens@example.com',
                111,
                ''
            ),
        ];

        self::assertEquals(
            $expected,
            $collection->toArray(),
        );
    }
}
