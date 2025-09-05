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

final class SearchIndexedItemMetricCollector implements SearchIndexedItem
{
    public function __construct(
        private SearchIndexedItem $searcher,
        private Prometheus $prometheus,
    ) {
    }

    #[\Override]
    public function searchItems(string $keywords, int $limit, int $offset): SearchResultPage
    {
        $this->prometheus->increment('fts_search_requests_total', 'Total number of full-text search requests');
        $start_time    = microtime(true);
        $search_result = $this->searcher->searchItems($keywords, $limit, $offset);
        $this->prometheus->histogram(
            'fts_search_requests_duration',
            'Duration of search requests in microseconds',
            microtime(true) - $start_time,
            [],
            [0.05, 0.1, 0.2, 0.5, 1, 2, 5, 10, 30],
        );
        return $search_result;
    }
}
