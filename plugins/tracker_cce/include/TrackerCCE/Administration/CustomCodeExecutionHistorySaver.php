<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\TrackerCCE\Administration;


final class CustomCodeExecutionHistorySaver implements LogModuleRemoved, LogModuleUploaded
{
    private const MODULE_REMOVED  = 'plugin_tracker_cce_module_removed';
    private const MODULE_UPLOADED = 'plugin_tracker_cce_module_uploaded';

    public function __construct(private readonly \ProjectHistoryDao $dao)
    {
    }

    public static function getLabelFromKey(string $key): ?string
    {
        return match ($key) {
            self::MODULE_REMOVED => dgettext(
                'tuleap-baseline',
                'Custom code execution module removed'
            ),
            self::MODULE_UPLOADED => dgettext(
                'tuleap-baseline',
                'Custom code execution module uploaded'
            ),
            default => null,
        };
    }

    public static function fillProjectHistorySubEvents(array $params): void
    {
        $params['subEvents']['event_others'][] = self::MODULE_REMOVED;
        $params['subEvents']['event_others'][] = self::MODULE_UPLOADED;
    }

    public function logModuleRemoved(\PFUser $user, \Tracker $tracker): void
    {
        $this->dao->addHistory(
            $tracker->getProject(),
            $user,
            new \DateTimeImmutable(),
            self::MODULE_REMOVED,
            $tracker->getName() . " (" . $tracker->getItemName() . ")",
        );
    }

    public function logModuleUploaded(\PFUser $user, \Tracker $tracker): void
    {
        $this->dao->addHistory(
            $tracker->getProject(),
            $user,
            new \DateTimeImmutable(),
            self::MODULE_UPLOADED,
            $tracker->getName() . " (" . $tracker->getItemName() . ")",
        );
    }
}
