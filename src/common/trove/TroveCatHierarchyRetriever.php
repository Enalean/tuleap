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

use Codendi_HTMLPurifier;
use TroveCatDao;

class TroveCatHierarchyRetriever
{
    /**
     * @var TroveCatDao
     */
    private $trove_dao;

    public function __construct(TroveCatDao $trove_dao)
    {
        $this->trove_dao = $trove_dao;
    }

    public function retrieveFullHierarchy(
        $node_id,
        array &$last_parent,
        array &$already_seen,
        array &$trove_cat_list,
        array &$last_parent_ids
    ) {
        $this->readTree(
            $node_id,
            $last_parent,
            $already_seen,
            $trove_cat_list,
            $last_parent_ids,
            false
        );
    }

    public function retrieveChildren(
        $node_id,
        array &$last_parent,
        array &$already_seen,
        array &$trove_cat_list,
        array &$last_parent_ids
    ) {
        $this->readTree(
            $node_id,
            $last_parent,
            $already_seen,
            $trove_cat_list,
            $last_parent_ids,
            true
        );
    }

    private function readTree(
        $node_id,
        array &$last_parent,
        array &$already_seen,
        array &$trove_cat_list,
        array &$last_parent_ids,
        $retrieve_only_direct_child
    ) {
        $already_seen[$node_id] = true;

        foreach ($this->trove_dao->getCategoryChildren($node_id) as $row_child) {
            $last_parent[]     = $row_child['fullname'];
            $last_parent_ids[] = $row_child['trove_cat_id'];

            $node = array(
                'trove_cat_id'                    => $row_child['trove_cat_id'],
                'parent'                          => $row_child['parent'],
                'description'                     => $row_child['description'],
                'spacing'                         => (count($last_parent) - 1) * 30,
                'has_spacing'                     => count($last_parent) > 1,
                'fullname'                        => $row_child['fullname'],
                'shortname'                       => $row_child['shortname'],
                'is_mandatory'                    => (bool) $row_child['mandatory'],
                'display_during_project_creation' => (bool) $row_child['display_during_project_creation'],
                'hierarchy'                       => implode(' :: ', $last_parent),
                'hierarchy_ids'                   => implode(' :: ', $last_parent_ids),
                'is_top_level_id'                 => (int) $row_child['parent'] === 0,
                'is_parent_mandatory'             => (int) $row_child['parent_mandatory'] === 1 && $row_child['parent_mandatory'] !== null,
                'nb_max_values'                   => (int) $row_child['nb_max_values'],
                'is_project_flag'                 => (bool) $row_child['is_project_flag'],
                'purified_delete_message'         => Codendi_HTMLPurifier::instance()->purify(
                    $GLOBALS['Language']->getText(
                        'admin_trove_cat_delete',
                        'alert_description_delete_modal',
                        array(Codendi_HTMLPurifier::instance()->purify($row_child['fullname'], CODENDI_PURIFIER_FULL))
                    ),
                    CODENDI_PURIFIER_LIGHT
                )
            );

            if (! isset($already_seen[$row_child['trove_cat_id']])) {
                $trove_cat_list[] = $node;
                $this->readTree(
                    $row_child['trove_cat_id'],
                    $last_parent,
                    $already_seen,
                    $trove_cat_list,
                    $last_parent_ids,
                    $retrieve_only_direct_child
                );
            }

            array_pop($last_parent);
            array_pop($last_parent_ids);
            if (! is_array($last_parent) && $retrieve_only_direct_child === false) {
                $last_parent     = array();
                $last_parent_ids = array();
            } elseif (! is_array($last_parent) && $retrieve_only_direct_child === true) {
                return;
            }
        }
    }

    public function retrieveFathers(
        $node_id,
        array &$last_parent,
        array &$already_seen,
        array &$trove_cat_list,
        array &$last_parent_ids
    ) {
        $already_seen[$node_id] = true;

        foreach ($this->trove_dao->getCategoryParent($node_id) as $row_child) {
            $last_parent[]     = $row_child['fullname'];
            $last_parent_ids[] = $row_child['trove_cat_id'];

            $node = array(
                'trove_cat_id' => $row_child['trove_cat_id'],
                'hierarchy'    => implode(' :: ', array_reverse($last_parent)),
                'hierarchy_id' => implode(' :: ', array_reverse($last_parent_ids)),
            );

            if (! isset($already_seen[$row_child['parent_id']])) {
                $trove_cat_list = $node;
                $this->retrieveFathers(
                    $row_child['parent_id'],
                    $last_parent,
                    $already_seen,
                    $trove_cat_list,
                    $last_parent_ids
                );
            }

            array_pop($last_parent);
            array_pop($last_parent_ids);
            if (! is_array($last_parent)) {
                $last_parent     = array();
                $last_parent_ids = array();
            }
        }
    }
}
