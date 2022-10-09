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

namespace Tuleap\Baseline\Adapter\Administration;

use Tuleap\Baseline\Domain\Role;
use Tuleap\Baseline\Domain\RoleAssignment;
use Tuleap\Project\UGroupRetriever;

final class ProjectHistory implements ISaveProjectHistory
{
    private const PERM_RESET_FOR_BASELINE_READERS          = 'perm_reset_for_baseline_readers';
    private const PERM_RESET_FOR_BASELINE_ADMINISTRATORS   = 'perm_reset_for_baseline_administrators';
    private const PERM_GRANTED_FOR_BASELINE_READERS        = 'perm_granted_for_baseline_readers';
    private const PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS = 'perm_granted_for_baseline_administrators';

    public static function getLabelFromKey(string $key): ?string
    {
        return match ($key) {
            self::PERM_RESET_FOR_BASELINE_READERS => dgettext(
                'tuleap-baseline',
                'Permission reset for baseline readers'
            ),
            self::PERM_RESET_FOR_BASELINE_ADMINISTRATORS => dgettext(
                'tuleap-baseline',
                'Permission reset for baseline administrators'
            ),
            self::PERM_GRANTED_FOR_BASELINE_READERS => dgettext(
                'tuleap-baseline',
                'Permission granted for baseline readers'
            ),
            self::PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS => dgettext(
                'tuleap-baseline',
                'Permission granted for baseline administrators'
            ),
            default => null,
        };
    }

    public static function fillProjectHistorySubEvents(array $params): void
    {
        array_push(
            $params['subEvents']['event_permission'],
            self::PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS,
            self::PERM_GRANTED_FOR_BASELINE_READERS,
            self::PERM_RESET_FOR_BASELINE_ADMINISTRATORS,
            self::PERM_RESET_FOR_BASELINE_READERS,
        );
    }

    public function __construct(private \ProjectHistoryDao $dao, private UGroupRetriever $ugroup_retriever)
    {
    }

    public function saveHistory(\Project $project, RoleAssignment ...$assignments): void
    {
        $ugroup_names = [
            Role::READER => [],
            Role::ADMIN  => [],
        ];
        foreach ($assignments as $assignment) {
            $ugroup = $this->ugroup_retriever->getUGroup($project, $assignment->getUserGroupId());
            if (! $ugroup) {
                throw new \LogicException('Unable to find ugroup ' . $assignment->getUserGroupId());
            }
            $ugroup_names[$assignment->getRole()][] = $ugroup->getName();
        }

        if (empty($ugroup_names[Role::READER])) {
            $this->dao->groupAddHistory(self::PERM_RESET_FOR_BASELINE_READERS, '', (int) $project->getID());
        } else {
            $this->dao->groupAddHistory(
                self::PERM_GRANTED_FOR_BASELINE_READERS,
                implode(',', $ugroup_names[Role::READER]),
                (int) $project->getID()
            );
        }

        if (empty($ugroup_names[Role::ADMIN])) {
            $this->dao->groupAddHistory(self::PERM_RESET_FOR_BASELINE_ADMINISTRATORS, '', (int) $project->getID());
        } else {
            $this->dao->groupAddHistory(
                self::PERM_GRANTED_FOR_BASELINE_ADMINISTRATORS,
                implode(',', $ugroup_names[Role::ADMIN]),
                (int) $project->getID()
            );
        }
    }
}
