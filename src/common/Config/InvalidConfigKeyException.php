<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Config;

use Tuleap\CLI\Events\GetWhitelistedKeys;

final class InvalidConfigKeyException extends \Exception
{
    /**
     * @var GetWhitelistedKeys
     */
    private $white_listed_keys;

    public function __construct(GetWhitelistedKeys $white_listed_keys)
    {
        $this->white_listed_keys = $white_listed_keys;
    }

    /**
     * @return string[]
     */
    public function getWhiteListedKeys(): array
    {
        return $this->white_listed_keys->getWhiteListedKeys();
    }
}
