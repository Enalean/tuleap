<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\Date;

use ForgeConfig;
use Tuleap\Config\ConfigKey;
use Tuleap\Config\ConfigKeyHelp;
use Tuleap\Config\ConfigKeyString;

final class OpeningDaysRetriever
{
    #[ConfigKey('Define which days are considered as worked day')]
    #[ConfigKeyHelp('Must be a comma separated list of day index: Monday-Sunday 1-7)')]
    #[ConfigKeyString('1,2,3,4,5')]
    public const CONFIG_KEY = 'opening_days';

    /**
     * @return int[]
     */
    public static function getListOfOpenDays(): array
    {
        $value = ForgeConfig::get(self::CONFIG_KEY);
        if ($value === false) {
            return [1, 2, 3, 4, 5];
        }

        return array_map(
            static fn(string $day) => (int) trim($day),
            explode(',', $value)
        );
    }
}
