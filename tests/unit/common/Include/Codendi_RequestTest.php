<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

final class Codendi_RequestTest extends \PHPUnit\Framework\TestCase // phpcs:ignore
{
    public function testItReturnsTheProject(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withId(123)
            ->build();

        $project_manager = ProjectByIDFactoryStub::buildWith($project);

        $request = new Codendi_Request(['group_id' => '123'], $project_manager);

        self::assertEquals($project, $request->getProject());
    }

    public function testItReturnsTheRightProjectWhenGroupIdInParamsIsChanged(): void
    {
        $not_a_valid_project = ProjectTestBuilder::aProject()
            ->withId(0)
            ->build();

        $project = ProjectTestBuilder::aProject()
            ->withId(123)
            ->build();

        $project_manager = ProjectByIDFactoryStub::buildWith($not_a_valid_project, $project);

        // Given we use urls like /projects/acme
        // When we get the project from the request (for exemple a plugin in pre.php)
        // Then request return a not valid project
        // Because there is no group_id parameter
        $request = new Codendi_Request([], $project_manager);
        self::assertEquals($not_a_valid_project, $request->getProject());

        // Somewhere in the stack, for example in the home of the project,
        // we can assume that /projects/acme is linked to a valid project acme
        // and might want to manually set the group_id parameter so that
        // subsequent queries to request return the right project
        // and not the project in error
        $request->params['group_id'] = "123";
        self::assertEquals($project, $request->getProject());
    }
}
