<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class PermissionsUGroupMapper
{

    private $mapping = array(
        ProjectUGroup::ANONYMOUS     => ProjectUGroup::ANONYMOUS,
        ProjectUGroup::AUTHENTICATED => ProjectUGroup::REGISTERED,
        ProjectUGroup::REGISTERED    => ProjectUGroup::REGISTERED,
    );

    public function __construct(Project $project)
    {
        if (! $project->isPublic()) {
            $this->mapping = array(
                ProjectUGroup::ANONYMOUS     => ProjectUGroup::PROJECT_MEMBERS,
                ProjectUGroup::AUTHENTICATED => ProjectUGroup::PROJECT_MEMBERS,
                ProjectUGroup::REGISTERED    => ProjectUGroup::PROJECT_MEMBERS,
            );
        } else {
            if (! ForgeConfig::areAnonymousAllowed()) {
                if (ForgeConfig::areRestrictedUsersAllowed() && $project->allowsRestricted()) {
                    $this->mapping[ProjectUGroup::ANONYMOUS] = ProjectUGroup::AUTHENTICATED;
                } else {
                    $this->mapping[ProjectUGroup::ANONYMOUS] = ProjectUGroup::REGISTERED;
                }
            }

            if (ForgeConfig::areRestrictedUsersAllowed() && $project->allowsRestricted()) {
                $this->mapping[ProjectUGroup::AUTHENTICATED] = ProjectUGroup::AUTHENTICATED;
            }
        }
    }

    public function getUGroupIdAccordingToMapping($ugroup_id)
    {
        if (isset($this->mapping[$ugroup_id])) {
            return $this->mapping[$ugroup_id];
        }
        return $ugroup_id;
    }
}
