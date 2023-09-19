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

use Psr\Log\LoggerInterface;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Option\Option;

final class LegacyMediawikiDBPrimerStub implements LegacyMediawikiDBPrimer
{
    /**
     * @psalm-var Option<string>
     */
    public Option $db_name_used;
    /**
     * @psalm-var Option<string>
     */
    public Option $db_prefix_used;

    public function __construct()
    {
        $this->db_name_used   = Option::nothing(\Psl\Type\string());
        $this->db_prefix_used = Option::nothing(\Psl\Type\string());
    }

    public function prepareDBForMigration(LoggerInterface $logger, \Project $project, string $db_name, string $db_prefix): Ok
    {
        $this->db_name_used   = Option::fromValue($db_name);
        $this->db_prefix_used = Option::fromValue($db_prefix);
        return Result::ok(null);
    }
}
