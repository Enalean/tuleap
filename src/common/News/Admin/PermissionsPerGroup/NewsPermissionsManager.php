<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\News\Admin\PermissionsPerGroup;

use PermissionsManager;
use Project;
use ProjectUGroup;
use Tuleap\News\Admin\AdminNewsDao;

class NewsPermissionsManager
{
    public const NEWS_READ = 'NEWS_READ';

    /**
     * @var PermissionsManager
     */
    private $permissions_manager;

    /**
     * @var AdminNewsDao
     */
    private $dao;

    public function __construct(
        PermissionsManager $permissions_manager,
        AdminNewsDao $dao
    ) {
        $this->permissions_manager = $permissions_manager;
        $this->dao                 = $dao;
    }

    public function getAccessibleProjectNews(Project $project)
    {
        return $this->dao->searchAllPublishedNewsFromProject($project->getID());
    }

    public function isProjectNewsPublic($project_news)
    {
        $granted_ugroup = $this->permissions_manager->getAuthorizedUgroups(
            $project_news['forum_id'],
            self::NEWS_READ
        )->getRow();

        return ((int) $granted_ugroup['ugroup_id'] === ProjectUGroup::ANONYMOUS);
    }
}
