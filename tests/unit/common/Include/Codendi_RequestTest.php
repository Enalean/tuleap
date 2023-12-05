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
}
