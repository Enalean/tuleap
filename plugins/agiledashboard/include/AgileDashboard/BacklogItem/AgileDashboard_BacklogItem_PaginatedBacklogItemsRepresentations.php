<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\BacklogItem;

use Tuleap\AgileDashboard\REST\v1\BacklogItemRepresentation;

final readonly class AgileDashboard_BacklogItem_PaginatedBacklogItemsRepresentations
{
    /**
     * @param list<BacklogItemRepresentation> $backlog_items_representations
     */
    public function __construct(
        public array $backlog_items_representations,
        public int $total_size,
    ) {
    }

    public function getBacklogItemsRepresentations(): array
    {
        return $this->backlog_items_representations;
    }

    public function getTotalSize(): int
    {
        return $this->total_size;
    }
}
