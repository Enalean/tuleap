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

namespace Tuleap\Project\Admin\Permission;

use ForgeAccess;
use ForgeConfig;
use ProjectUGroup;
use TuleapTestCase;
use UGroupManager;

class PermissionPerGroupBuilderTest extends TuleapTestCase
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var PermissionPerGroupBuilder
     */
    private $builder;

    public function setUp()
    {
        parent::setUp();

        $this->ugroup_manager = mock('UGroupManager');
        $this->builder        = new PermissionPerGroupBuilder($this->ugroup_manager);

        ForgeConfig::store();
    }

    public function tearDown()
    {
        ForgeConfig::restore();

        parent::tearDown();
    }

    public function itAddsAnonymousUgroupIfPlatformAllowsThem()
    {
        $project = aMockProject()->build();
        $request = mock('HTTPRequest');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        stub($this->ugroup_manager)->getStaticUGroups($project)->returns(array());

        $ugroups = $this->builder->buildUGroup($project, $request);

        $this->assertEqual($ugroups['dynamic'][0]['id'], ProjectUGroup::ANONYMOUS);
    }

    public function itAddsAuthenticatedUgroupIfPlatformAllowsThem()
    {
        $project = aMockProject()->build();
        $request = mock('HTTPRequest');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);

        stub($this->ugroup_manager)->getStaticUGroups($project)->returns(array());

        $ugroups = $this->builder->buildUGroup($project, $request);

        $this->assertEqual($ugroups['dynamic'][0]['id'], ProjectUGroup::AUTHENTICATED);
    }

    public function itAlwaysAddRegisteredUgroup()
    {
        $project = aMockProject()->build();
        $request = mock('HTTPRequest');

        stub($this->ugroup_manager)->getStaticUGroups($project)->returns(array());

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        $ugroups = $this->builder->buildUGroup($project, $request);
        $this->assertEqual($ugroups['dynamic'][1]['id'], ProjectUGroup::REGISTERED);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        $ugroups = $this->builder->buildUGroup($project, $request);
        $this->assertEqual($ugroups['dynamic'][1]['id'], ProjectUGroup::REGISTERED);
    }
}
