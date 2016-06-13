<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\Git\REST\v1;

class BuildStatusPOSTRepresentation
{
    const BUILD_STATUS_UNKNOWN = 'U';
    const BUILD_STATUS_SUCCESS = 'S';
    const BUILD_STATUS_FAIL    = 'F';

    /**
     * @var string {@type string}
     */
    public $branch;

    /**
     * @var string {@type string}
     */
    public $commit_reference;

    /**
     * @var string {@type string}
     */
    public $status;

    /**
     * @var string {@type string}
     */
    public $token;

    public function build($branch, $commit_reference, $status, $token)
    {
        $this->branch           = $branch;
        $this->commit_reference = $commit_reference;
        $this->status           = $status;
        $this->token            = $token;
    }

    public function isStatusValid()
    {
        return  $this->status == self::BUILD_STATUS_FAIL
            ||  $this->status == self::BUILD_STATUS_SUCCESS
            ||  $this->status == self::BUILD_STATUS_UNKNOWN;
    }
}
