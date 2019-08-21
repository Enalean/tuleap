<?php
/**
* Copyright Enalean (c) 2013. All rights reserved.
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

/**
 * I am responsible of duplicating the various fields that compose a semantic for a new one
 */
class Tracker_Semantic_CollectionOfFieldsDuplicator implements \Tuleap\Tracker\Semantic\IDuplicateSemantic
{
    /** @var Tracker_Semantic_IRetrieveSemanticDARByTracker */
    private $dao;

    public function __construct(Tracker_Semantic_IRetrieveSemanticDARByTracker $dao)
    {
        $this->dao = $dao;
    }

    /**
     * Duplicate the semantic from tracker source to tracker target
     *
     * @param int   $from_tracker_id The Id of the tracker source
     * @param int   $to_tracker_id   The Id of the tracker target
     * @param array $field_mapping   The mapping of the fields of the tracker
     *
     * @return void
     */
    public function duplicate($from_tracker_id, $to_tracker_id, array $field_mapping)
    {
        $rank = 1;
        foreach ($this->dao->searchByTrackerId($from_tracker_id) as $row) {
            $from_field_id = $row['field_id'];
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $from_field_id) {
                    $to_field_id = $mapping['to'];
                    $this->dao->add($to_tracker_id, $to_field_id, $rank);
                }
            }
            $rank++;
        }
    }
}
