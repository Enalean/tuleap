<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

namespace Tracker\Tests\Rest;

use Tuleap\Tracker\Tests\REST\TrackerBase;

class SemanticTimeframeImportTest extends TrackerBase
{
    public function testImportSemanticTimeframe(): void
    {
        $response = $this->getResponse(
            $this->client->get('trackers/' . $this->tracker_with_timeframe_semantic_id)
        );

        $tracker = $response->json();

        $this->assertArrayHasKey('timeframe', $tracker['semantics']);
        $this->assertArrayHasKey('start_date_field_id', $tracker['semantics']['timeframe']);
        $this->assertArrayHasKey('duration_field_id', $tracker['semantics']['timeframe']);
    }
}
