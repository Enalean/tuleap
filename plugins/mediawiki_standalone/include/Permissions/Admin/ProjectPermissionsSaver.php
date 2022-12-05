<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Permissions\Admin;

use Tuleap\MediawikiStandalone\Permissions\ISaveProjectPermissions;

final class ProjectPermissionsSaver
{
    private const PERM_RESET_FOR_READERS   = 'perm_reset_for_mediawiki_standalone_readers';
    private const PERM_GRANTED_FOR_READERS = 'perm_granted_for_mediawiki_standalone_readers';
    private const PERM_RESET_FOR_WRITERS   = 'perm_reset_for_mediawiki_standalone_writers';
    private const PERM_GRANTED_FOR_WRITERS = 'perm_granted_for_mediawiki_standalone_writers';

    public function __construct(
        private ISaveProjectPermissions $permissions_dao,
        private \ProjectHistoryDao $history_dao,
    ) {
    }

    public static function getLabelFromKey(string $key): ?string
    {
        return match ($key) {
            self::PERM_RESET_FOR_READERS => dgettext(
                'tuleap-mediawiki_standalone',
                'Permission reset for MediaWiki readers'
            ),
            self::PERM_GRANTED_FOR_READERS => dgettext(
                'tuleap-mediawiki_standalone',
                'Permission granted for MediaWiki readers'
            ),
            self::PERM_RESET_FOR_WRITERS => dgettext(
                'tuleap-mediawiki_standalone',
                'Permission reset for MediaWiki writers'
            ),
            self::PERM_GRANTED_FOR_WRITERS => dgettext(
                'tuleap-mediawiki_standalone',
                'Permission granted for MediaWiki writers'
            ),
            default => null,
        };
    }

    public static function fillProjectHistorySubEvents(array $params): void
    {
        array_push(
            $params['subEvents']['event_permission'],
            self::PERM_GRANTED_FOR_READERS,
            self::PERM_RESET_FOR_READERS,
            self::PERM_GRANTED_FOR_WRITERS,
            self::PERM_RESET_FOR_WRITERS,
        );
    }

    /**
     * @param \ProjectUGroup[] $readers
     */
    public function save(\Project $project, array $readers, array $writers): void
    {
        $this->permissions_dao->saveProjectPermissions($project, $readers, $writers);
        $this->saveHistory(
            $project,
            $readers,
            empty($readers) ? self::PERM_RESET_FOR_READERS : self::PERM_GRANTED_FOR_READERS
        );
        $this->saveHistory(
            $project,
            $writers,
            empty($writers) ? self::PERM_RESET_FOR_WRITERS : self::PERM_GRANTED_FOR_WRITERS
        );
    }

    /**
     * @param \ProjectUGroup[] $ugroups
     */
    private function saveHistory(\Project $project, array $ugroups, string $history_key): void
    {
        $this->history_dao->groupAddHistory(
            $history_key,
            implode(
                ',',
                array_map(static fn(\ProjectUGroup $user_group) => $user_group->getName(), $ugroups)
            ),
            (int) $project->getID()
        );
    }
}
