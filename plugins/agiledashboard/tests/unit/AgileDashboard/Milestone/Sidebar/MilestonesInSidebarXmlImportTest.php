<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\AgileDashboard\AgileDashboard\Milestone\Sidebar;

use Tuleap\AgileDashboard\Stub\Milestone\Sidebar\UpdateMilestonesInSidebarConfigStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class MilestonesInSidebarXmlImportTest extends TestCase
{
    public function testDoesNothingWhenAttributeIsNotPresent(): void
    {
        $milestones_in_sidebar_config = UpdateMilestonesInSidebarConfigStub::build();

        (new MilestonesInSidebarXmlImport($milestones_in_sidebar_config))
            ->import(
                new \SimpleXMLElement('<agiledashboard />'),
                ProjectTestBuilder::aProject()->build(),
            );

        self::assertFalse($milestones_in_sidebar_config->hasDeactivateBeenCalled());
        self::assertFalse($milestones_in_sidebar_config->hasActivateBeenCalled());
    }

    public function testDoesNothingWhenWeAskToActivateMilestonesBecauseItIsTheDefault(): void
    {
        $milestones_in_sidebar_config = UpdateMilestonesInSidebarConfigStub::build();

        (new MilestonesInSidebarXmlImport($milestones_in_sidebar_config))
            ->import(
                new \SimpleXMLElement('<agiledashboard should_sidebar_display_last_milestones="1" />'),
                ProjectTestBuilder::aProject()->build(),
            );

        self::assertFalse($milestones_in_sidebar_config->hasDeactivateBeenCalled());
        self::assertFalse($milestones_in_sidebar_config->hasActivateBeenCalled());
    }

    public function testDeactivationWhenWeAskToDectivateMilestones(): void
    {
        $milestones_in_sidebar_config = UpdateMilestonesInSidebarConfigStub::build();

        (new MilestonesInSidebarXmlImport($milestones_in_sidebar_config))
            ->import(
                new \SimpleXMLElement('<agiledashboard should_sidebar_display_last_milestones="0" />'),
                ProjectTestBuilder::aProject()->build(),
            );

        self::assertTrue($milestones_in_sidebar_config->hasDeactivateBeenCalled());
        self::assertFalse($milestones_in_sidebar_config->hasActivateBeenCalled());
    }
}
