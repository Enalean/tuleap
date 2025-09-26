<?php
/**
 * Copyright Enalean (c) 2018 - present. All rights reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\PermissionsPerGroup\ProjectAdminPermissionPerGroupPresenterBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectAdminPermissionPerGroupPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProjectAdminPermissionPerGroupPresenterBuilder $presenter_builder;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->presenter_builder = new ProjectAdminPermissionPerGroupPresenterBuilder(
            $this->createMock(\UGroupManager::class)
        );
    }

    public function testItBuildsAPresenterWithANullUGroupNameWhenNoGroupIsSelected(): void
    {
        $project = ProjectTestBuilder::aProject()->build();

        $presenter = $this->presenter_builder->buildPresenter(
            $project,
            null
        );

        $this->assertEquals($presenter->selected_ugroup_name, '');
    }
}
