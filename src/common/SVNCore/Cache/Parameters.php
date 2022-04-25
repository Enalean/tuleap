<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\SVNCore\Cache;

/**
 * @psalm-immutable
 */
class Parameters
{
    /**
     * @var int
     */
    private $maximum_credentials;
    /**
     * @var int
     */
    private $lifetime;

    public function __construct(int $maximum_credentials, int $lifetime)
    {
        $this->maximum_credentials = $maximum_credentials;
        $this->lifetime            = $lifetime;
    }

    public function getMaximumCredentials(): int
    {
        return $this->maximum_credentials;
    }

    public function getLifetime(): int
    {
        return $this->lifetime;
    }
}
