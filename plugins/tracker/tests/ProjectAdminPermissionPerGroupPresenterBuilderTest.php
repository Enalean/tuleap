<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

namespace Tuleap\Tracker\Tests;

use Tuleap\Project\Admin\PerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Tracker\ProjectAdminPermissionPerGroupPresenterBuilder;
use TuleapTestCase;

require_once('bootstrap.php');

class ProjectAdminPermissionPerGroupPresenterBuilderTest extends TuleapTestCase
{
    /**
     * @var ProjectAdminPermissionPerGroupPresenterBuilder
     */
    private $presenter_builder;

    public function setUp()
    {
        parent::setUp();

        $this->ugroup_manager    = mock('UGroupManager');
        $this->tracker_factory   = mock('TrackerFactory');
        $this->badge_formatter   = new PermissionPerGroupUGroupFormatter($this->ugroup_manager);
        $this->presenter_builder = new ProjectAdminPermissionPerGroupPresenterBuilder(
            $this->ugroup_manager,
            $this->tracker_factory,
            $this->badge_formatter
        );
    }

    public function itBuildsAPresenterWithANullUGroupNameWhenNoGroupIsSelectedAndProjectHasNoTrackerToList()
    {
        $project = aMockProject()->build();

        stub($this->tracker_factory)->getTrackersByGroupId()->returns([]);

        $presenter = $this->presenter_builder->buildPresenter(
            $project,
            null
        );

        $this->assertEqual($presenter->user_group_name, '');
        $this->assertArrayEmpty($presenter->permissions);
        $this->assertFalse($presenter->has_permissions);
    }
}
