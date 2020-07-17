<?php
/**
 * Copyright (c) Enalean, 2014-Present. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
 * @psalm-immutable
 */
class BacklogItemReference
{

    /**
     * @var object Identification of the backlog item {@required true} {@type array}
     * @psalm-var array
     * <br>
     * E.g. {"id" : 458}
     *
     */
    public $artifact;

    public function getArtifactId()
    {
        return isset($this->artifact['id']) ? $this->artifact['id'] : null;
    }
}
