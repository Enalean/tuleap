<?php
/**
 * Copyright (c) Enalean, 2013 - present. All Rights Reserved.
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

final class Tracker_Permission_PermissionRequestTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var array
     */
    private $minimal_ugroup_list;

    protected function setUp(): void
    {
        $this->minimal_ugroup_list = array(
            ProjectUGroup::ANONYMOUS,
            ProjectUGroup::REGISTERED,
            ProjectUGroup::PROJECT_MEMBERS,
            ProjectUGroup::PROJECT_ADMIN
        );
    }

    public function testItHasPermissionsBasedOnGroupIds(): void
    {
        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('get')
            ->withArgs([Tracker_Permission_Command::PERMISSION_PREFIX . ProjectUGroup::ANONYMOUS])
            ->andReturn(Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY);
        $request->shouldReceive('get')
            ->withArgs([Tracker_Permission_Command::PERMISSION_PREFIX . ProjectUGroup::REGISTERED])
            ->andReturn(Tracker_Permission_Command::PERMISSION_FULL);
        $request->shouldReceive('get')
            ->andReturn(Tracker_Permission_Command::PERMISSION_FULL);

        $set_permission_request = new Tracker_Permission_PermissionRequest(array());
        $set_permission_request->setFromRequest($request, $this->minimal_ugroup_list);

        $this->assertEquals(Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY, $set_permission_request->getPermissionType(ProjectUGroup::ANONYMOUS));
        $this->assertEquals(Tracker_Permission_Command::PERMISSION_FULL, $set_permission_request->getPermissionType(ProjectUGroup::REGISTERED));
    }

    public function testItRevokesPermissions(): void
    {
        $request = Mockery::mock(Codendi_Request::class);
        $request->shouldReceive('get')
            ->withArgs([Tracker_Permission_Command::PERMISSION_PREFIX . ProjectUGroup::ANONYMOUS])
            ->andReturn(Tracker_Permission_Command::PERMISSION_SUBMITTER_ONLY);
        $request->shouldReceive('get')
            ->withArgs([Tracker_Permission_Command::PERMISSION_PREFIX . ProjectUGroup::REGISTERED])
            ->andReturn(Tracker_Permission_Command::PERMISSION_FULL);
        $request->shouldReceive('get')
            ->andReturn(Tracker_Permission_Command::PERMISSION_FULL);

        $set_permission_request = new Tracker_Permission_PermissionRequest(array());
        $set_permission_request->setFromRequest($request, $this->minimal_ugroup_list);

        $set_permission_request->revoke(ProjectUGroup::REGISTERED);
        $this->assertNull($set_permission_request->getPermissionType(ProjectUGroup::REGISTERED));
    }
}
