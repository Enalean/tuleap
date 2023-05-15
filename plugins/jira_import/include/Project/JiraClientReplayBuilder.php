<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\JiraImport\Project;

use Tuleap\Tracker\Creation\JiraImporter\JiraClientReplay;

final class JiraClientReplayBuilder
{
    public static function buildReplayClientWithCommandOptions(
        string $server_flavor,
        ?string $server_major_version,
        string $log_path,
    ): JiraClientReplay {
        if ($server_flavor !== 'server') {
            return JiraClientReplay::buildJiraCloud($log_path);
        }

        $major_version = $server_major_version;
        if ($major_version === null || (int) $major_version < 9) {
            return JiraClientReplay::buildJira7And8Server($log_path);
        }

        return JiraClientReplay::buildJira9Server($log_path);
    }
}
