<?php
/**
 * Copyright (c) Enalean, 2017-Present. All Rights Reserved.
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

namespace Tuleap\SVN\REST\v1;

use Tuleap\SVN\Admin\ImmutableTag;

/**
 * @psalm-immutable
 */
class ImmutableTagRepresentation
{
    /**
     * @var array {@type string} {@required true}
     */
    public $paths;

    /**
     * @var array {@type string} {@required true}
     */
    public $whitelist;

    /**
     * @param string[] $paths
     * @param string[] $whitelist
     */
    private function __construct(array $paths, array $whitelist)
    {
        $this->paths     = $paths;
        $this->whitelist = $whitelist;
    }

    public static function build(ImmutableTag $immutable_tag): self
    {
        return new self(
            $immutable_tag->getPaths(),
            $immutable_tag->getWhitelist()
        );
    }
}
