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

namespace Tuleap\TrackerFunctions\Administration;

final class CustomCodeExecutionHistorySaver implements LogFunctionRemoved, LogFunctionUploaded, LogFunctionDeactivated, LogFunctionActivated
{
    private const FUNCTION_REMOVED     = 'plugin_tracker_functions_function_removed';
    private const FUNCTION_UPLOADED    = 'plugin_tracker_functions_function_uploaded';
    private const FUNCTION_ACTIVATED   = 'plugin_tracker_functions_function_activated';
    private const FUNCTION_DEACTIVATED = 'plugin_tracker_functions_function_deactivated';

    public function __construct(private readonly \ProjectHistoryDao $dao)
    {
    }

    public static function getLabelFromKey(string $key): ?string
    {
        return match ($key) {
            self::FUNCTION_REMOVED => dgettext(
                'tuleap-tracker_functions',
                'Tuleap function removed'
            ),
            self::FUNCTION_UPLOADED => dgettext(
                'tuleap-tracker_functions',
                'Tuleap function uploaded'
            ),
            self::FUNCTION_ACTIVATED => dgettext(
                'tuleap-tracker_functions',
                'Tuleap function activated'
            ),
            self::FUNCTION_DEACTIVATED => dgettext(
                'tuleap-tracker_functions',
                'Tuleap function deactivated'
            ),
            default => null,
        };
    }

    public static function fillProjectHistorySubEvents(array $params): void
    {
        $params['subEvents']['event_others'][] = self::FUNCTION_REMOVED;
        $params['subEvents']['event_others'][] = self::FUNCTION_UPLOADED;
        $params['subEvents']['event_others'][] = self::FUNCTION_ACTIVATED;
        $params['subEvents']['event_others'][] = self::FUNCTION_DEACTIVATED;
    }

    public function logFunctionRemoved(\PFUser $user, \Tracker $tracker): void
    {
        $this->logActionOnTracker(self::FUNCTION_REMOVED, $user, $tracker);
    }

    public function logFunctionUploaded(\PFUser $user, \Tracker $tracker): void
    {
        $this->logActionOnTracker(self::FUNCTION_UPLOADED, $user, $tracker);
    }

    public function logFunctionActivated(\PFUser $user, \Tracker $tracker): void
    {
        $this->logActionOnTracker(self::FUNCTION_ACTIVATED, $user, $tracker);
    }

    public function logFunctionDeactivated(\PFUser $user, \Tracker $tracker): void
    {
        $this->logActionOnTracker(self::FUNCTION_DEACTIVATED, $user, $tracker);
    }

    /**
     * @param self::FUNCTION_* $action
     */
    private function logActionOnTracker(string $action, \PFUser $user, \Tracker $tracker): void
    {
        $this->dao->addHistory(
            $tracker->getProject(),
            $user,
            new \DateTimeImmutable(),
            $action,
            $tracker->getName() . " (" . $tracker->getItemName() . ")",
        );
    }
}
