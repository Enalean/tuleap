<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\FullTextSearchCommon\Index;

use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Search\ItemToIndex;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InsertItemsIntoIndexMetricCollectorTest extends TestCase
{
    public function testCollectMetricWhenInsertingItemsIntoTheIndex(): void
    {
        $prometheus = Prometheus::getInMemory();
        $inserter   = new InsertItemsIntoIndexMetricCollector(
            new NullIndexHandler(),
            $prometheus
        );

        $inserter->indexItems(
            new ItemToIndex('a', 120, 'content', 'plaintext', ['A' => 'A']),
            new ItemToIndex('b', 120, 'content', 'plaintext', ['A' => 'A']),
        );

        $this->assertStringContainsString('tuleap_fts_index_requests_total 2', $prometheus->renderText());
    }
}
