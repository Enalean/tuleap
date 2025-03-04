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
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SearchIndexedItemMetricCollectorTest extends TestCase
{
    public function testCollectMetricsWhenSearchingItems(): void
    {
        $prometheus = Prometheus::getInMemory();
        $searcher   = new SearchIndexedItemMetricCollector(
            new NullIndexHandler(),
            $prometheus,
        );

        $searcher->searchItems('foo', 50, 0);
        $searcher->searchItems('bar', 50, 0);

        $prometheus_rendered_text = $prometheus->renderText();
        $this->assertStringContainsString('tuleap_fts_search_requests_total 2', $prometheus_rendered_text);
        $this->assertStringContainsString('tuleap_fts_search_requests_duration_count 2', $prometheus_rendered_text);
    }
}
