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

use Project;
use ProjectUGroup;

class NewsPermissionsRepresentationBuilder
{
    private $news_permissions_manager;

    public function __construct(
        NewsPermissionsManager $news_permissions_manager
    ) {
        $this->news_permissions_manager = $news_permissions_manager;
    }

    public function build(Project $project, $selected_ugroup_id = null)
    {
        $news         = [];
        $project_news = $this->news_permissions_manager->getAccessibleProjectNews($project);

        foreach ($project_news as $new) {
            $is_public = $this->news_permissions_manager->isProjectNewsPublic($new);

            if ($selected_ugroup_id
                && !$is_public
                && !$this->isUGroupAuthorizedToSeePrivateNews($selected_ugroup_id)
            ) {
                continue;
            }

            $news[] = new NewsVisibilityRepresentation(
                $new['summary'],
                $this->getNewAdminQuickLink($new),
                $is_public
            );
        }

        return $news;
    }

    private function isUGroupAuthorizedToSeePrivateNews($selected_ugroup_id)
    {
        return (int) $selected_ugroup_id === ProjectUGroup::PROJECT_ADMIN
               || (int) $selected_ugroup_id === ProjectUGroup::PROJECT_MEMBERS
               || (int) $selected_ugroup_id === ProjectUGroup::NEWS_ADMIN
               || (int) $selected_ugroup_id === ProjectUGroup::NEWS_WRITER;
    }

    private function getNewAdminQuickLink($new)
    {
        $query_params = http_build_query(
            [
                'approve'  => $new['is_approved'],
                'id'       => $new['id'],
                'group_id' => $new['group_id']
            ]
        );

        return '/news/admin/?' . $query_params;
    }
}
