<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\REST\v1\Representation;

/**
 * @psalm-immutable
 */
final class CrossTrackerQueryPutRepresentation
{
    /**
     * @var string The TQL query {@required true}
     */
    public string $tql_query;
    /**
     * @var string The query title {@required true}
     */
    public string $title;
    /**
     * @var string The query description {@required false}
     */
    public string $description;
    /**
     * @var int The id of the widget the query belongs to {@required true}
     */
    public int $widget_id;
    /**
     * @var bool The query is displayed by default or not {@required false}
     */
    public bool $is_default = false;
}
