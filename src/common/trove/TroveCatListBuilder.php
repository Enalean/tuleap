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

namespace Tuleap\Trove;

use TroveCatDao;

class TroveCatListBuilder
{
    const DESCRIPTION_SIZE_DISPLAY = 70;
    /**
     * @var TroveCatDao
     */
    private $trove_dao;

    public function __construct(TroveCatDao $trove_dao)
    {
        $this->trove_dao = $trove_dao;
    }

    public function build($node_id, array &$last_parent, array &$already_seen, array &$trove_cat_list)
    {
        $already_seen[$node_id] = true;
        $hierarchy = $last_parent;

        foreach ($this->trove_dao->getCategoryChildren($node_id) as $row_child) {
            $hierarchy[] = $row_child['fullname'];

            $node = array(
                'trove_cat_id'                    => $row_child['trove_cat_id'],
                'parent'                          => $row_child['parent'],
                'description'                     => $row_child['description'],
                'spacing'                         => (count($last_parent)) * 30,
                'has_spacing'                     => count($last_parent) > 0,
                'is_deletable'                    => ! $this->isNodeARootNode($row_child['parent']),
                'fullname'                        => $row_child['fullname'],
                'shortname'                       => $row_child['shortname'],
                'is_mandatory'                    => (boolean) $row_child['mandatory'],
                'display_during_project_creation' => (boolean) $row_child['display_during_project_creation'],
                'hierarchy'                       => implode(' :: ', $hierarchy),
                'is_top_level_id'                 => (int) $row_child['parent'] === 0,
                'is_parent_mandatory'             => (int) $row_child['parent_mandatory'] === 1 && $row_child['parent_mandatory'] !== null
            );

            $last_parent[] = $row_child['fullname'];

            if (! isset($already_seen[$row_child['trove_cat_id']])) {
                $trove_cat_list[] = $node;
                $this->build($row_child['trove_cat_id'], $last_parent, $already_seen, $trove_cat_list);
            }

            array_pop($last_parent);
            if (! is_array($last_parent)) {
                $last_parent = array();
            }
        }
    }

    private function isNodeARootNode($parent)
    {
        return (int) $parent === 0;
    }
}
