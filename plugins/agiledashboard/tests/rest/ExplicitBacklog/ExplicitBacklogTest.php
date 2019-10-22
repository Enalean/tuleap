<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

declare(strict_types=1);

namespace Tuleap\AgileDashboard\REST;

require_once dirname(__FILE__).'/../bootstrap.php';

class ExplicitBacklogTest extends TestBase
{
    public function testTopBacklogInExplicitBacklogContextIsAlwaysEmpty(): void
    {
        $response          = $this->getResponse($this->client->get('projects/'. $this->explicit_backlog_project_id. '/backlog'));
        $top_backlog_items = $response->json();

        $this->assertEmpty($top_backlog_items);
    }
}
