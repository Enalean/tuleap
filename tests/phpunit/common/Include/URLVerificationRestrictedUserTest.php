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
 *
 * @codingStandardsIgnoreFile
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class URLVerificationRestrictedUserTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function tearDown()
    {
        unset($_SERVER['REQUEST_URI']);
        parent::tearDown();
    }

    /**
     * @test
     */
    public function it_allows_restricted_user_to_access_project_dashboard()
    {
        $_SERVER['REQUEST_URI'] = '/projects/demo-pr';
        $user = \Mockery::mock(PFUser::class, ['isSuperUser' => false, 'isMember' => false, 'isRestricted' => true]);
        $project = \Mockery::mock(Project::class, ['isError' => false, 'isActive' => true, 'getID' => 101, 'allowsRestricted' => true]);
        $url_verification = new URLVerification();
        $url_verification->userCanAccessProject($user, $project);
        $this->assertTrue(true);
    }
}
