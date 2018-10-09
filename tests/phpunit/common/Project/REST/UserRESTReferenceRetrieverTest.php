<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UserRESTReferenceRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\MockInterface
     */
    private $user_manager;

    protected function setUp()
    {
        $this->user_manager = \Mockery::mock(\UserManager::class);
    }

    /**
     * @dataProvider userReferenceProvider
     */
    public function testGetUserFromReference(
        $reference_used,
        $expected_user_manager_call,
        $user_manager_return_value,
        \PFUser $expected_user = null
    ) {
        $representation                  = new UserRESTReferenceRepresentation();
        $representation->$reference_used = 'value';

        $this->user_manager->shouldReceive($expected_user_manager_call)->andReturns($user_manager_return_value);

        $retriever = new UserRESTReferenceRetriever($this->user_manager);

        $this->assertSame(
            $expected_user,
            $retriever->getUserFromReference($representation)
        );
    }

    public function userReferenceProvider()
    {
        $user = \Mockery::mock(\PFUser::class);
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

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage More than one user use the email
     */
    public function testMultipleUsersMatchingASameEmailAreRejected()
    {
        $representation        = new UserRESTReferenceRepresentation();
        $representation->email = 'user@example.com';

        $this->user_manager->shouldReceive('getAllUsersByEmail')
            ->andReturns([\Mockery::mock(\PFUser::class), \Mockery::mock(\PFUser::class)]);

        $retriever = new UserRESTReferenceRetriever($this->user_manager);
        $retriever->getUserFromReference($representation);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage Only one key can be passed
     */
    public function testOnlyKeyOfTheRepresentationCanBeSet()
    {
        $representation           = new UserRESTReferenceRepresentation();
        $representation->id       = 101;
        $representation->username = 'username';

        $retriever = new UserRESTReferenceRetriever($this->user_manager);
        $retriever->getUserFromReference($representation);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     * @expectedExceptionMessage At least one key must
     */
    public function testRepresentationMustHaveAtLeastOneValue()
    {
        $retriever = new UserRESTReferenceRetriever($this->user_manager);
        $retriever->getUserFromReference(new UserRESTReferenceRepresentation);
    }
}
