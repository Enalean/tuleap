<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once __DIR__ . '/../bootstrap.php';

class Tracker_UgroupMappingBuilderTest extends TuleapTestCase
{

    protected $template_tracker;
    protected $target_project;
    protected $permissions_retriever;
    protected $ugroup_manager;
    protected $template_ugroup_dev;
    protected $template_ugroup_support;
    protected $target_ugroup_dev;
    protected $target_ugroup_support;

    protected $tracker_id                 = 101;

    public function itBuildsAMappingBasedOnTheNames()
    {
        $this->template_ugroup_dev     = mockery_stub(\ProjectUGroup::class)->getName()->returns('dev');
        $this->template_ugroup_support = mockery_stub(\ProjectUGroup::class)->getName()->returns('support');
        $this->template_ugroup_staff   = mockery_stub(\ProjectUGroup::class)->getName()->returns('staff');
        $this->target_ugroup_dev       = mockery_stub(\ProjectUGroup::class)->getName()->returns('DEV');
        $this->target_ugroup_support   = mockery_stub(\ProjectUGroup::class)->getName()->returns('support');
        $this->target_ugroup_client    = mockery_stub(\ProjectUGroup::class)->getName()->returns('client');

        stub($this->template_ugroup_support)->getId()->returns(1001);
        stub($this->target_ugroup_support)->getId()->returns(1002);

        $template_project            = mockery_stub(\Project::class)->getId()->returns(103);
        $this->template_tracker      = aTracker()->withProject($template_project)->build();
        $this->target_project        = mockery_stub(\Project::class)->getId()->returns(104);
        $this->ugroup_manager        = \Mockery::spy(\UGroupManager::class);
        $this->permissions_retriever = \Mockery::spy(\Tracker_UgroupPermissionsGoldenRetriever::class);

        stub($this->permissions_retriever)->getListOfInvolvedStaticUgroups($this->template_tracker)->returns(
            array($this->template_ugroup_dev, $this->template_ugroup_support, $this->template_ugroup_staff)
        );
        stub($this->ugroup_manager)->getStaticUGroups($this->target_project)->returns(
            array($this->target_ugroup_dev, $this->target_ugroup_support, $this->target_ugroup_client)
        );

        $builder = new Tracker_UgroupMappingBuilder($this->permissions_retriever, $this->ugroup_manager);

        $mapping = $builder->getMapping($this->template_tracker, $this->target_project);

        $this->assertEqual($mapping, array(1001 => 1002));
    }
}
