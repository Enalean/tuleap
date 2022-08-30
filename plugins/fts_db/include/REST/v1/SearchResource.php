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

namespace Tuleap\FullTextSearchDB\REST\v1;

use Tuleap\REST\AuthenticatedResource;
use Tuleap\REST\Header;

final class SearchResource extends AuthenticatedResource
{
    public const ROUTE      = 'search';
    private const MAX_LIMIT = 50;

    /**
     * @url OPTIONS /
     */
    public function options(): void
    {
        Header::allowOptionsPost();
    }

    /**
     * Get items related to a search
     *
     * @url POST /
     * @access hybrid
     *
     * @param SearchQueryRepresentation $search_query {@from body}
     * @param int $limit {@from query}{@min 1}{@max 50}
     * @param int $offset {@from query}{@min 0}
     *
     * @status 200
     */
    public function getSearchItems(SearchQueryRepresentation $search_query, int $limit = self::MAX_LIMIT, int $offset = 0): array
    {
        $this->checkAccess();

        $search_items = [];

        Header::sendPaginationHeaders($limit, $offset, count($search_items), self::MAX_LIMIT);

        return $search_items;
    }
}
