<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

namespace Tuleap\Project\REST;
use \ProjectUGroup;
use Tuleap\User\UserGroup\NameTranslator;
use \User_ForgeUGroup;
use \Exception;

class UserGroupRepresentation {

    const ROUTE = 'user_groups';

    const SIMPLE_REST_ID_PATTERN  = '/^\d+$/';
    const COMPLEX_REST_ID_PATTERN = '/^(\d+)_(\d+)$/';

    /**
     * @var int
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var String
     */
    public $label;

    /**
     * @var String
     */
    public $users_uri;

    /**
     * @var String
     */
    public $short_name;

    public function build($project_id, ProjectUGroup $ugroup) {
        $this->id         = self::getRESTIdForProject($project_id, $ugroup->getId());
        $this->uri        = UserGroupRepresentation::ROUTE . '/' . $this->id ;
        $this->label      = NameTranslator::getUserGroupDisplayName($ugroup->getName());
        $this->key        = $ugroup->getName();
        $this->users_uri  = self::ROUTE . '/'. $this->id .'/users';
        $this->short_name = $ugroup->getNormalizedName();
    }

    static function getRESTIdForProject($project_id, $user_group_id) {
        if ($user_group_id > ProjectUGroup::DYNAMIC_UPPER_BOUNDARY
            || in_array($user_group_id, ProjectUGroup::$forge_user_groups)
        ) {
            return $user_group_id;
        }

        return $project_id.'_'.$user_group_id;
    }

    static function getProjectAndUserGroupFromRESTId($identifier) {
        if (preg_match(self::SIMPLE_REST_ID_PATTERN, $identifier)) {
            return array(
                'project_id'    => null,
                'user_group_id' => $identifier
            );
        }

        if (preg_match(self::COMPLEX_REST_ID_PATTERN, $identifier, $complex_id)) {
            return array(
                'project_id'    => $complex_id[1],
                'user_group_id' => $complex_id[2]
            );
        }
    }

    static public function checkRESTIdIsAppropriate($identifier) {
        if (preg_match(self::SIMPLE_REST_ID_PATTERN, $identifier, $simple_id)) {
            $id = $simple_id[0];
            if ($id > ProjectUGroup::DYNAMIC_UPPER_BOUNDARY
                || in_array($id, ProjectUGroup::$forge_user_groups)
            ) {
                return;
            }

            throw new Exception("Invalid ID for user group ('".$simple_id[0]."'), format must be: projectId_ugroupId");
        } elseif (preg_match(self::COMPLEX_REST_ID_PATTERN, $identifier, $complex_id)) {
            return;
        }

        throw new Exception('Invalid ID format('.$identifier.')');
    }
}
