<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Campaign;

use HTTPRequest;
use Tuleap\Test\Builders\ProjectTestBuilder;
use function PHPUnit\Framework\assertSame;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class StatusChangedRedirectURLBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItBuildsRedirectToCampaignURL(): void
    {
        $request = new HTTPRequest();
        $project = ProjectTestBuilder::aProject()->build();

        assertSame(
            '/plugins/testmanagement/?group_id=101#!/campaigns/1234',
            StatusChangedRedirectURLBuilder::buildRedirectURL(
                $request,
                $project,
                1234
            )
        );
    }

    public function testItBuildsRedirectToCampaignwithMilestoneURL(): void
    {
        $request = new HTTPRequest();
        $request->set('milestone_id', '3');
        $project = ProjectTestBuilder::aProject()->build();

        assertSame(
            '/plugins/testmanagement/?group_id=101&milestone_id=3#!/campaigns/1234',
            StatusChangedRedirectURLBuilder::buildRedirectURL(
                $request,
                $project,
                1234
            )
        );
    }
}
