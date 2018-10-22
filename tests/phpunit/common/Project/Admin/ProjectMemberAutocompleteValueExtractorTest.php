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

namespace Tuleap\Project\Admin\ProjectMembers;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use \UserManager;

class ProjectMemberAutocompleteValueExtractorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var ProjectMemberAutocompleteValueExtractor
     */
    private $extractor;
    /**
     * @var UserManager|MockInterface
     */
    private $user_manager;

    protected function setUp()
    {
        parent::setUp();

        $this->user_manager = \Mockery::mock(UserManager::class);
        $this->extractor = new ProjectMemberAutocompleteValueExtractor($this->user_manager);
    }

    public function testItReturnsTuleapUserWhenUserIsFoundInTuleap()
    {
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->andReturn(102);

        $user = \Mockery::mock(\PFUser::class);
        $this->user_manager->shouldReceive('getUserById')->andReturn($user);

        $this->assertEquals($user, $this->extractor->getUserFromRequest($request));
    }

    public function testItReturnsNullWhenUserIsNotFoundInTuleap()
    {
        $user_name = 'ldap user+(ldap)';
        $request = \Mockery::mock(\HTTPRequest::class);
        $request->shouldReceive('get')->andReturn($user_name);

        $this->user_manager->shouldReceive('getUserById')->andReturn(null);

        $this->assertEquals(null, $this->extractor->getUserFromRequest($request));
    }
}
