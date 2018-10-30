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

namespace Tuleap\SystemEvent;

use SystemEvent;
use Tuleap\SVN\SVNAuthenticationCacheInvalidator;

class SystemEventSVNAuthenticationCacheRefresh extends SystemEvent
{
    /**
     * @var SVNAuthenticationCacheInvalidator
     */
    private $svn_authentication_cache_invalidator;

    public function injectDependencies(
        SVNAuthenticationCacheInvalidator $svn_authentication_cache_invalidator
    ) {
        $this->svn_authentication_cache_invalidator = $svn_authentication_cache_invalidator;
    }

    /**
     * Verbalize the parameters so they are readable and much user friendly in
     * notifications
     *
     * @param bool $with_link true if you want links to entities. The returned
     *                        string will be html instead of plain/text
     *
     * @return string
     */
    public function verbalizeParameters($with_link)
    {
        return 'project: ' . $this->verbalizeProjectId($this->getIdFromParam(), $with_link);
    }

    public function process()
    {
        $project = $this->getProject($this->getIdFromParam());
        $this->svn_authentication_cache_invalidator->invalidateProjectCache($project);
        $this->done();
    }
}
