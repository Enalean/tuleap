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

namespace Tuleap\Layout;

use Project;
use Tuleap\Event\Dispatchable;

class ServiceUrlCollector implements Dispatchable
{
    const NAME = "serviceUrlCollector";
    /**
     * @var \Project
     */
    private $project;

    /**
     * @var string
     */
    private $url;
    /**
     * @var string
     */
    private $service_shortname;

    public function __construct(Project $project, $shortname)
    {
        $this->project           = $project;
        $this->service_shortname = $shortname;
    }

    public function hasUrl()
    {
        return $this->url !== null;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return \Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return string
     */
    public function getServiceShortname()
    {
        return $this->service_shortname;
    }
}
