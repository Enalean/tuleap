<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Git\Repository\View;

class CloneURLs
{
    /** @var string */
    private $gerrit_url;
    /** @var string */
    private $https_url;
    /** @var string */
    private $ssh_url;

    /**
     * @param string $gerrit_url
     */
    public function setGerritUrl($gerrit_url)
    {
        $this->gerrit_url = $gerrit_url;
    }

    /**
     * @param string $https_url
     */
    public function setHttpsUrl($https_url)
    {
        $this->https_url = $https_url;
    }

    /**
     * @param string $ssh_url
     */
    public function setSshUrl($ssh_url)
    {
        $this->ssh_url = $ssh_url;
    }

    /**
     * @return string
     */
    public function getGerritUrl()
    {
        return $this->gerrit_url;
    }

    /**
     * @return string
     */
    public function getHttpsUrl()
    {
        return $this->https_url;
    }

    /**
     * @return string
     */
    public function getSshUrl()
    {
        return $this->ssh_url;
    }

    /**
     * @return bool
     */
    public function hasGerritUrl()
    {
        return $this->gerrit_url !== null;
    }

    /**
     * @return bool
     */
    public function hasHttpsUrl()
    {
        return $this->https_url !== null;
    }

    /**
     * @return bool
     */
    public function hasSshUrl()
    {
        return $this->ssh_url !== null;
    }
}
