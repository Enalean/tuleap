<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\User\REST\v1;

/**
 * @psalm-immutable
 */
class UserPreferenceRepresentation
{

    /**
     * @var string {@type string}
     */
    public $key;

    /**
     * @var string | false
     */
    public $value;

    /**
     * @param string|false $value
     */
    private function __construct(string $key, $value)
    {
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     * @param string|false $value
     */
    public static function build(string $key, $value): self
    {
        return new self($key, $value);
    }
}
