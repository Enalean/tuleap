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
 */

declare(strict_types=1);

namespace Tuleap\Http\Client;

use ParagonIE\EasyDB\EasyDB;
use Tuleap\DB\DataAccessObject;

class FilteredOutboundHTTPResponseAlerterDAO extends DataAccessObject
{
    public function markNewFilteredRequest(): void
    {
        $this->getDB()->tryFlatTransaction(
            function (EasyDB $db): void {
                $db->run('DELETE FROM filtered_outbound_http_requests');
                $db->run('INSERT INTO filtered_outbound_http_requests(last_blocked,seen_by_system_check) VALUES (UNIX_TIMESTAMP(), FALSE)');
            }
        );
    }

    public function hasFilteredAnOutboundHTTPRequestSinceLastSystemCheck(): bool
    {
        return $this->getDB()->tryFlatTransaction(
            function (EasyDB $db): bool {
                $rows = $db->run('SELECT 1 FROM filtered_outbound_http_requests WHERE seen_by_system_check = FALSE');
                if (count($rows) === 0) {
                    return false;
                }

                $db->run('UPDATE filtered_outbound_http_requests SET seen_by_system_check = TRUE');
                return true;
            }
        );
    }

    public function hasAnOutboundHTTPRequestBeenFilteredRecently(): bool
    {
        $rows = $this->getDB()->run('SELECT 1 FROM filtered_outbound_http_requests WHERE NOW() < (FROM_UNIXTIME(last_blocked) + INTERVAL 1 DAY)');

        return count($rows) !== 0;
    }
}
