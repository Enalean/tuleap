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

use Tuleap\MediawikiStandalone\Instance\LogUsersOutInstanceTask;
use Tuleap\MediawikiStandalone\Permissions\ISaveProjectPermissions;
use Tuleap\Queue\EnqueueTaskInterface;

final class ProjectPermissionsSaver
{
    private const string PERM_RESET_FOR_READERS   = 'perm_reset_for_mediawiki_standalone_readers';
    private const string PERM_GRANTED_FOR_READERS = 'perm_granted_for_mediawiki_standalone_readers';
    private const string PERM_RESET_FOR_WRITERS   = 'perm_reset_for_mediawiki_standalone_writers';
    private const string PERM_GRANTED_FOR_WRITERS = 'perm_granted_for_mediawiki_standalone_writers';
    private const string PERM_RESET_FOR_ADMINS    = 'perm_reset_for_mediawiki_standalone_admins';
    private const string PERM_GRANTED_FOR_ADMINS  = 'perm_granted_for_mediawiki_standalone_admins';

    public function __construct(
        private ISaveProjectPermissions $permissions_dao,
        private \ProjectHistoryDao $history_dao,
        private EnqueueTaskInterface $enqueue_task,
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
            self::PERM_RESET_FOR_ADMINS => dgettext(
                'tuleap-mediawiki_standalone',
                'Permission reset for MediaWiki administrators'
            ),
            self::PERM_GRANTED_FOR_ADMINS => dgettext(
                'tuleap-mediawiki_standalone',
                'Permission granted for MediaWiki administrators'
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
    public function save(\Project $project, array $readers, array $writers, array $admins): void
    {
        $this->permissions_dao->saveProjectPermissions($project, $readers, $writers, $admins);
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
        $this->saveHistory(
            $project,
            $admins,
            empty($admins) ? self::PERM_RESET_FOR_ADMINS : self::PERM_GRANTED_FOR_ADMINS
        );

        $event = LogUsersOutInstanceTask::logsOutUserOfAProject($project);
        if ($event) {
            $this->enqueue_task->enqueue($event);
        }
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
