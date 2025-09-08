<?php
/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Service;

use Tuleap\DB\DataAccessObject;

final class MediawikiFlavorUsageDao extends DataAccessObject implements MediawikiFlavorUsage
{
    #[\Override]
    public function wasLegacyMediawikiUsed(\Project $project): bool
    {
        return $this->getDB()->exists(
            'SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ? AND TABLE_SCHEMA = ?',
            'plugin_mediawiki_database',
            \ForgeConfig::get('sys_dbname')
        ) &&
               $this->getDB()->exists(
                   'SELECT 1 FROM plugin_mediawiki_database WHERE project_id = ?',
                   $project->getID()
               );
    }

    #[\Override]
    public function wasStandaloneMediawikiUsed(\Project $project): bool
    {
        if (
            $this->getDB()->cell('SELECT 1 FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', 'plugin_mediawiki_standalone_farm') === 1 &&
            $this->getDB()->cell('SELECT 1 FROM plugin_mediawiki_standalone_farm.tuleap_instances WHERE ti_id = ?', $project->getID()) === 1
        ) {
            return true;
        }
        return false;
    }
}
