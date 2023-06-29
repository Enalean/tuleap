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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Kanban;

use PFUser;

final class KanbanUserPreferences
{
    public const COLLAPSE_COLUMN_PREFERENCE_PREFIX  = 'kanban_collapse_column_';
    public const COLLAPSE_BACKLOG_PREFERENCE_PREFIX = 'kanban_collapse_backlog_';
    public const COLLAPSE_ARCHIVE_PREFERENCE_PREFIX = 'kanban_collapse_archive_';

    public const EXPAND   = "0";
    public const COLLAPSE = "1";

    public function isColumnOpen(Kanban $kanban, int $column_id, PFUser $user): bool
    {
        return ! $user->getPreference($this->getCollapseColumnPreferenceName($kanban, $column_id));
    }

    public function openColumn(Kanban $kanban, int $column_id, PFUser $user): void
    {
        $user->setPreference($this->getCollapseColumnPreferenceName($kanban, $column_id), self::EXPAND);
    }

    public function closeColumn(Kanban $kanban, int $column_id, PFUser $user): void
    {
        $user->setPreference($this->getCollapseColumnPreferenceName($kanban, $column_id), self::COLLAPSE);
    }

    public function isArchiveOpen(Kanban $kanban, PFUser $user): bool
    {
        $user_preference = $user->getPreference(self::COLLAPSE_ARCHIVE_PREFERENCE_PREFIX . $kanban->getId());

        return $user_preference === self::EXPAND;
    }

    public function openArchive(Kanban $kanban, PFUser $user): void
    {
        $user->setPreference(self::COLLAPSE_ARCHIVE_PREFERENCE_PREFIX . $kanban->getId(), self::EXPAND);
    }

    public function closeArchive(Kanban $kanban, PFUser $user): void
    {
        $user->setPreference(self::COLLAPSE_ARCHIVE_PREFERENCE_PREFIX . $kanban->getId(), self::COLLAPSE);
    }

    public function isBacklogOpen(Kanban $kanban, PFUser $user): bool
    {
        $user_preference = $user->getPreference(self::COLLAPSE_BACKLOG_PREFERENCE_PREFIX . $kanban->getId());

        return $user_preference === false || $user_preference === self::EXPAND;
    }

    public function openBacklog(Kanban $kanban, PFUser $user): void
    {
        $user->setPreference(self::COLLAPSE_BACKLOG_PREFERENCE_PREFIX . $kanban->getId(), self::EXPAND);
    }

    public function closeBacklog(Kanban $kanban, PFUser $user): void
    {
        $user->setPreference(self::COLLAPSE_BACKLOG_PREFERENCE_PREFIX . $kanban->getId(), self::COLLAPSE);
    }

    private function getCollapseColumnPreferenceName(Kanban $kanban, int $column_id): string
    {
        return self::COLLAPSE_COLUMN_PREFERENCE_PREFIX . $kanban->getId() . '_' . $column_id;
    }
}
