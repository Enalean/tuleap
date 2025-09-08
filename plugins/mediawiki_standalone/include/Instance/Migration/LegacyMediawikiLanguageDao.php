<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\MediawikiStandalone\Instance\Migration;

use Tuleap\DB\DataAccessObject;

final class LegacyMediawikiLanguageDao extends DataAccessObject implements LegacyMediawikiLanguageRetriever
{
    #[\Override]
    public function getLanguageFor(int $project_id): string|false
    {
        return $this->getDB()->cell(
            'SELECT language
            FROM plugin_mediawiki_admin_options
            WHERE project_id = ?',
            $project_id
        );
    }
}
