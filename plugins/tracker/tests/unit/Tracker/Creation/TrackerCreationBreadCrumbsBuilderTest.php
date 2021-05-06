<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Creation;

use Mockery;
use PFUser;
use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkWithIcon;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsSection;

class TrackerCreationBreadCrumbsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;

    /**
     * @var TrackerCreationBreadCrumbsBuilder
     */
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new TrackerCreationBreadCrumbsBuilder();

        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getId')->andReturn("101");
        $this->project->shouldReceive('getUnixNameLowerCase')->andReturn('my-project');

        $this->user = Mockery::mock(PFUser::class);
    }

    public function testRegularUserDoesNotHaveADirectLinkToAdministration(): void
    {
        $this->user->shouldReceive('isAdmin')->andReturn(false);

        $breadcrumbs = $this->builder->build($this->project, $this->user);

        $expected_breadcrumbs = new BreadCrumbCollection();
        $expected_breadcrumbs->addBreadCrumb($this->getTrackerBreadcrumb());
        $expected_breadcrumbs->addBreadCrumb($this->getNewTrackerBreadcrumb());

        $this->assertEquals($expected_breadcrumbs, $breadcrumbs);
    }

    public function testAdminUserHaveAAllInclusiveBreadcrumb(): void
    {
        $this->user->shouldReceive('isAdmin')->andReturn(true);

        $breadcrumbs = $this->builder->build($this->project, $this->user);

        $expected_breadcrumbs = new BreadCrumbCollection();
        $tracker_breadcrumb   = $this->getTrackerBreadcrumb();
        $expected_breadcrumbs->addBreadCrumb($tracker_breadcrumb);
        $expected_breadcrumbs->addBreadCrumb($this->getNewTrackerBreadcrumb());

        $tracker_breadcrumb->setSubItems($this->getAdministrationBreadcrumb());

        $this->assertEquals($expected_breadcrumbs, $breadcrumbs);
    }

    private function getTrackerBreadcrumb(): BreadCrumb
    {
        return new BreadCrumb(
            new BreadCrumbLinkWithIcon(
                'Trackers',
                TRACKER_BASE_URL . '/?group_id=101',
                'fa-list-ol'
            )
        );
    }

    private function getNewTrackerBreadcrumb(): BreadCrumb
    {
        return new BreadCrumb(
            new BreadCrumbLink(
                'New tracker',
                '/plugins/tracker/my-project/new'
            )
        );
    }

    private function getAdministrationBreadcrumb(): BreadCrumbSubItems
    {
        $global_admin_link = new BreadCrumbLink(
            'Administration',
            '/plugins/tracker/global-admin/101'
        );

        $link_collection = new BreadCrumbLinkCollection();
        $link_collection->add($global_admin_link);

        $section = new SubItemsSection(
            '',
            $link_collection
        );

        $sub_items = new BreadCrumbSubItems();
        $sub_items->addSection($section);

        return $sub_items;
    }
}
