<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Project\REST;

use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;

final class UserRESTReferenceRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \UserManager&MockObject $user_manager;

    protected function setUp(): void
    {
        $this->user_manager = $this->createMock(\UserManager::class);
    }

    /**
     * @dataProvider userReferenceProvider
     * @param \PFUser|\PFUser[]|null $user_manager_return_value
     */
    public function testGetUserFromReference(
        string $reference_used,
        string $expected_user_manager_call,
        \PFUser|array|null $user_manager_return_value,
        ?\PFUser $expected_user = null,
    ): void {
        $representation                  = new UserRESTReferenceRepresentation();
        $representation->$reference_used = 'value';

        $this->user_manager->method($expected_user_manager_call)->willReturn($user_manager_return_value);

        $retriever = new UserRESTReferenceRetriever($this->user_manager);

        self::assertSame(
            $expected_user,
            $retriever->getUserFromReference($representation)
        );
    }

    public static function userReferenceProvider(): array
    {
        $user = UserTestBuilder::buildWithDefaults();
        return [
            ['id', 'getUserById', $user, $user],
            ['id', 'getUserById', null, null],
            ['username', 'getUserByUserName', $user, $user],
            ['username', 'getUserByUserName', null, null],
            ['email', 'getAllUsersByEmail', [$user], $user],
            ['email', 'getAllUsersByEmail', [], null],
            ['ldap_id', 'getUserByIdentifier', $user, $user],
            ['ldap_id', 'getUserByIdentifier', null, null],
        ];
    }

    public function testMultipleUsersMatchingASameEmailAreRejected(): void
    {
        $representation        = new UserRESTReferenceRepresentation();
        $representation->email = 'user@example.com';

        $this->user_manager->method('getAllUsersByEmail')
            ->willReturn([UserTestBuilder::buildWithDefaults(), UserTestBuilder::buildWithDefaults()]);

        $retriever = new UserRESTReferenceRetriever($this->user_manager);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage('More than one user use the email');

        $retriever->getUserFromReference($representation);
    }

    public function testOnlyKeyOfTheRepresentationCanBeSet(): void
    {
        $representation           = new UserRESTReferenceRepresentation();
        $representation->id       = 101;
        $representation->username = 'username';

        $retriever = new UserRESTReferenceRetriever($this->user_manager);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage('Only one key can be passed');

        $retriever->getUserFromReference($representation);
    }

    public function testRepresentationMustHaveAtLeastOneValue(): void
    {
        $retriever = new UserRESTReferenceRetriever($this->user_manager);

        self::expectException(RestException::class);
        self::expectExceptionCode(400);
        self::expectExceptionMessage('At least one key must');

        $retriever->getUserFromReference(new UserRESTReferenceRepresentation());
    }
}
