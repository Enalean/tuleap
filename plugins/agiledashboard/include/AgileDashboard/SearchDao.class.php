<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'common/dao/include/DataAccessObject.class.php';
require_once 'SharedField.class.php';

class AgileDashboard_SearchDao extends DataAccessObject {
    
    public function searchMatchingArtifacts(array $trackerIds, array $sharedFields) {
        $trackerIds = $this->da->quoteSmartImplode(',', $trackerIds);
        $sql = "
            SELECT artifact.id, artifact.last_changeset_id, CVT.value AS title
            FROM tracker_artifact AS artifact
            INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
            " . $this->getSharedFieldsSqlFragment($sharedFields) . "
            LEFT JOIN (
                tracker_changeset_value AS CV
                    INNER JOIN tracker_semantic_title       AS ST  ON (CV.field_id = ST.field_id)
                    INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id       = CVT.changeset_value_id)
            ) ON (c.id = CV.changeset_id)
            WHERE artifact.use_artifact_permissions = 0
            AND   artifact.tracker_id IN ($trackerIds)
            ORDER BY title
        ";
        return $this->retrieve($sql);
    }
    
    protected function getSharedFieldsSqlFragment(array $sharedFields) {
        $fragmentNumber = 0;
        $sqlFragments   = array();
        
        foreach ($sharedFields as $sharedField) {
            $sqlFragments[] = $this->getSharedFieldFragment($fragmentNumber++, $sharedField);
        }
        
        return implode(' ', $sqlFragments);
    }
    
    protected function getSharedFieldFragment($fragmentNumber, AgileDashboard_SharedField $sharedField) {
        $fieldIds = implode(',', $sharedField->getFieldIds());
        $valueIds = implode(',', $sharedField->getValueIds());
        
        // Table aliases
        $changeset_value      = "CV_$fragmentNumber";
        $changeset_value_list = "CVL_$fragmentNumber";
        
        $sqlFragment = "
            INNER JOIN tracker_changeset_value AS $changeset_value ON (
                    $changeset_value.changeset_id = c.id
                AND $changeset_value.field_id IN ($fieldIds)
            )
            INNER JOIN tracker_changeset_value_list AS $changeset_value_list ON (
                    $changeset_value_list.changeset_value_id = $changeset_value.id
                AND $changeset_value_list.bindvalue_id       IN ($valueIds)
            )
        ";
        
        return $sqlFragment;
    }
    
    public function searchArtifactsFromTrackers(array $trackerIds) {
        $trackerIds = implode(',', $trackerIds);
        $sql = "
            SELECT artifact.id,
                   artifact.last_changeset_id,
                   CVT.value AS title
        
            FROM       tracker_artifact  AS artifact
            INNER JOIN tracker_changeset AS c ON c.id = artifact.last_changeset_id
            LEFT JOIN (
                           tracker_changeset_value      AS CV
                INNER JOIN tracker_changeset_value_text AS CVT ON CVT.changeset_value_id = CV.id
                INNER JOIN tracker_semantic_title       AS ST  ON ST.field_id            = CV.field_id
        
            ) ON CV.changeset_id = c.id

            WHERE artifact.use_artifact_permissions = 0
            AND   artifact.tracker_id IN ($trackerIds)
            ORDER BY title
        ";
        return $this->retrieve($sql);
    }
}
?>
