<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Kanban;

use Tuleap\Tracker\Artifact\Artifact;

final class KanbanItemManager
{
    public function __construct(private readonly KanbanItemDao $item_dao)
    {
    }

    public function isKanbanItemInBacklog(Artifact $artifact): bool
    {
        return count($this->item_dao->isKanbanItemInBacklog($artifact->getTrackerId(), $artifact->getId())) > 0;
    }

    public function isKanbanItemInArchive(Artifact $artifact): bool
    {
        return count($this->item_dao->isKanbanItemInArchive($artifact->getTrackerId(), $artifact->getId())) > 0;
    }

    public function getColumnIdOfKanbanItem(Artifact $artifact): ?int
    {
        return $this->item_dao->getColumnIdOfKanbanItem($artifact->getTrackerId(), $artifact->getId());
    }
}
