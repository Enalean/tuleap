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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsSection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[DisableReturnValueGenerationForTestDoubles]
final class TrackerCreationBreadCrumbsBuilderTest extends TestCase
{
    private Project $project;
    private TrackerCreationBreadCrumbsBuilder $builder;

    #[\Override]
    protected function setUp(): void
    {
        $this->builder = new TrackerCreationBreadCrumbsBuilder();
        $this->project = ProjectTestBuilder::aProject()->withId(101)->withUnixName('my-project')->build();
    }

    public function testRegularUserDoesNotHaveADirectLinkToAdministration(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $breadcrumbs = $this->builder->build($this->project, $user);

        $expected_breadcrumbs = new BreadCrumbCollection();
        $expected_breadcrumbs->addBreadCrumb($this->getTrackerBreadcrumb());
        $expected_breadcrumbs->addBreadCrumb($this->getNewTrackerBreadcrumb());

        self::assertEquals($expected_breadcrumbs, $breadcrumbs);
    }

    public function testAdminUserHaveAAllInclusiveBreadcrumb(): void
    {
        $user = UserTestBuilder::anActiveUser()->withAdministratorOf($this->project)->build();

        $breadcrumbs = $this->builder->build($this->project, $user);

        $expected_breadcrumbs = new BreadCrumbCollection();
        $tracker_breadcrumb   = $this->getTrackerBreadcrumb();
        $expected_breadcrumbs->addBreadCrumb($tracker_breadcrumb);
        $expected_breadcrumbs->addBreadCrumb($this->getNewTrackerBreadcrumb());

        $tracker_breadcrumb->setSubItems($this->getAdministrationBreadcrumb());

        self::assertEquals($expected_breadcrumbs, $breadcrumbs);
    }

    private function getTrackerBreadcrumb(): BreadCrumb
    {
        return new BreadCrumb(
            new BreadCrumbLink(
                'Trackers',
                TRACKER_BASE_URL . '/?group_id=101',
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
