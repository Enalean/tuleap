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

class PermissionsNormalizerOverrideCollection
{
    private $override_by = [];

    public function addOverrideBy($override_id, $catch_all_id)
    {
        if (! isset($this->override_by[$catch_all_id])) {
            $this->override_by[$catch_all_id] = [];
        }
        $this->override_by[$catch_all_id][] = $override_id;
    }

    public function addArrayOverrideBy(array $override_ids, $catch_all_id)
    {
        foreach ($override_ids as $override_id) {
            if ($override_id != $catch_all_id) {
                $this->addOverrideBy($override_id, $catch_all_id);
            }
        }
    }

    public function getOverrideBy($catch_all)
    {
        return $this->override_by[$catch_all];
    }

    public function emitFeedback($permission_type)
    {
        foreach ($this->override_by as $catch_all_ugroup_id => $override_ids) {
            $GLOBALS['Response']->addFeedback(
                Feedback::WARN,
                $GLOBALS['Language']->getText(
                    'project_admin_permissions',
                    'override',
                    [
                        permission_get_name($permission_type),
                        $this->getUGroupNameImplode($override_ids),
                        $this->getUGroupNameById($catch_all_ugroup_id)
                    ]
                )
            );
        }
    }

    private function getUGroupNameImplode(array $ugroup_ids)
    {
        return implode(', ', array_map([$this, 'getUGroupNameById'], $ugroup_ids));
    }

    private function getUGroupNameById($ugroup_id)
    {
        $crap = new User_ForgeUGroup($ugroup_id, ugroup_get_name_from_id($ugroup_id), '');
        return $crap->getName();
    }
}
