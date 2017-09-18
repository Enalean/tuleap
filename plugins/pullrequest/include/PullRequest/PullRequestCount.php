<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\PullRequest;

class PullRequestCount
{
    /** @var int */
    private $nb_open;

    /** @var int */
    private $nb_closed;

    public function __construct($nb_open, $nb_closed)
    {
        $this->nb_open   = $nb_open;
        $this->nb_closed = $nb_closed;
    }

    /**
     * @return int
     */
    public function getNbOpen()
    {
        return $this->nb_open;
    }

    /**
     * @return int
     */
    public function getNbClosed()
    {
        return $this->nb_closed;
    }

    public function isThereAtLeastOnePullRequest()
    {
        return $this->nb_open > 0
            || $this->nb_closed > 0;
    }
}
