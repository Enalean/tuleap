<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

class Tracker_Hierarchy_Dao extends DataAccessObject {

    public function updateChildren($parent_id, array $child_ids) {
        $this->da->startTransaction();

        if ($this->removeExistingIsChildNatures($parent_id, $child_ids)
            && $this->changeTrackerHierarchy($parent_id, $child_ids)
            && $this->addIsChildNature($parent_id, $child_ids)
        ) {
            return $this->da->commit();
        }

        return $this->da->rollback();
    }

    /**
     * Given than I have the following hierarchy:
     *   Release
     *    `- Sprint
     *   UserStory
     *    `- Task
     *
     * When I edit the release hierarchy, deselect Sprint
     * and select UserStory and Task as children,
     *
     * Then I got the following hierarchy:
     *   Release
     *    +- UserStory
     *    `- Task
     *   Sprint
     *
     * As you can see, Sprint is not anymore child of Release, we
     * must remove corresponding nature _is_child.
     *
     * Furthermore, Taskhas a new parent which is Release instead
     * of UserStory, we must remove corresponding nature _is_child.
     */
    private function removeExistingIsChildNatures($parent_id, array $child_ids) {
        return $this->removeIsChildNatureForTrackersThatAreNotAnymoreChildren($parent_id, $child_ids)
            && $this->removeIsChildNatureForTrackersThatHaveANewParent($parent_id, $child_ids);
    }

    private function changeTrackerHierarchy($parent_id, array $child_ids) {
        if (! $this->deleteAllChildren($parent_id)) {
            return;
        }

        foreach($child_ids as $child_id) {
            $child_id = $this->da->escapeInt($child_id);
            $insert_values[] = "($parent_id, $child_id)";
        }

        $sql = "REPLACE INTO tracker_hierarchy(parent_id, child_id)
                VALUES ".implode(',', $insert_values);

        return $this->update($sql);
    }

    private function addIsChildNature($parent_id, array $child_ids) {
        $child_ids = $this->da->escapeIntImplode($child_ids);
        $parent_id = $this->da->escapeInt($parent_id);
        $is_child  = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD);

        $sql = "UPDATE tracker_changeset_value_artifactlink AS artlink
                    INNER JOIN tracker_artifact AS child_art
                        ON (child_art.id = artlink.artifact_id
                            AND child_art.tracker_id IN ($child_ids))
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_changeset AS c
                        ON (cv.changeset_id = c.id)
                    INNER JOIN tracker_artifact AS parent_art
                        ON (parent_art.id = c.artifact_id
                            AND parent_art.tracker_id = $parent_id)
                    INNER JOIN tracker_hierarchy AS hierarchy
                        ON (hierarchy.parent_id = parent_art.tracker_id
                            AND hierarchy.child_id = child_art.tracker_id)
                SET nature = '_is_child'";

        return $this->update($sql);
    }

    public function searchChildTrackerIds($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);

        $sql = "
            SELECT t.id
            FROM       tracker           AS t
            INNER JOIN tracker_hierarchy AS h ON (h.child_id  = t.id AND
                                                  h.parent_id = $tracker_id)
            WHERE t.deletion_date IS NULL
        ";

        return $this->retrieve($sql);
    }

    public function searchAncestorIds($tracker_id) {
        $ancestor_ids = array();
        while($tracker_id = $this->searchAncestorId($tracker_id)) {
            $ancestor_ids[] = $tracker_id;
        }
        return $ancestor_ids;
    }

    private function searchAncestorId($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT parent_id FROM tracker_hierarchy WHERE child_id= " . $tracker_id. " LIMIT 1";
        $dar = $this->retrieve($sql);
        $result = array('parent_id'=>null);
        foreach($dar as $result) {}
        return $result['parent_id'];
    }

    private function deleteAllChildren($parent_id) {
        $parent_id = $this->da->escapeInt($parent_id);

        $sql = "DELETE FROM tracker_hierarchy
                WHERE parent_id = $parent_id";

        return $this->update($sql);
    }

    public function deleteAllChildrenWithNature($parent_id) {
        $this->da->startTransaction();

        if ($this->removeAllIsChildNature($parent_id) && $this->deleteAllChildren($parent_id)) {
            return $this->da->commit();
        }

        return $this->da->rollback();
    }

    private function removeAllIsChildNature($parent_id) {
        $parent_id = $this->da->escapeInt($parent_id);

        $sql = "UPDATE tracker_changeset_value_artifactlink AS artlink
                    INNER JOIN tracker_artifact AS child_art
                        ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_changeset AS c
                        ON (cv.changeset_id = c.id)
                    INNER JOIN tracker_artifact AS parent_art
                        ON (parent_art.id = c.artifact_id)
                    INNER JOIN tracker_hierarchy AS hierarchy
                        ON (hierarchy.child_id = child_art.tracker_id
                            AND hierarchy.parent_id = $parent_id)
                SET nature = NULL";

        return $this->update($sql);
    }

    private function removeIsChildNatureForTrackersThatAreNotAnymoreChildren($parent_id, array $child_ids) {
        $child_ids = $this->da->escapeIntImplode($child_ids);
        $parent_id = $this->da->escapeInt($parent_id);

        $sql = "UPDATE tracker_changeset_value_artifactlink AS artlink
                    INNER JOIN tracker_artifact AS child_art
                        ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_changeset AS c
                        ON (cv.changeset_id = c.id)
                    INNER JOIN tracker_artifact AS parent_art
                        ON (parent_art.id = c.artifact_id)
                    INNER JOIN tracker_hierarchy AS hierarchy
                        ON (hierarchy.child_id = child_art.tracker_id
                            AND hierarchy.child_id NOT IN ($child_ids)
                            AND hierarchy.parent_id = $parent_id)
                SET nature = NULL";

        return $this->update($sql);
    }

    private function removeIsChildNatureForTrackersThatHaveANewParent($parent_id, array $child_ids) {
        $child_ids = $this->da->escapeIntImplode($child_ids);
        $parent_id = $this->da->escapeInt($parent_id);

        $sql = "UPDATE tracker_changeset_value_artifactlink AS artlink
                    INNER JOIN tracker_artifact AS child_art
                        ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_changeset AS c
                        ON (cv.changeset_id = c.id)
                    INNER JOIN tracker_artifact AS parent_art
                        ON (parent_art.id = c.artifact_id)
                    INNER JOIN tracker_hierarchy AS hierarchy
                        ON (hierarchy.child_id = child_art.tracker_id
                            AND hierarchy.child_id IN ($child_ids)
                            AND hierarchy.parent_id != $parent_id)
                SET nature = NULL";

        return $this->update($sql);
    }

    public function getChildren($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "SELECT child_id FROM tracker_hierarchy WHERE parent_id = $tracker_id";
        return $this->retrieve($sql);
    }

    public function searchTrackerHierarchy(array $tracker_ids) {
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);
        $sql = "SELECT parent_id, child_id
                FROM tracker_hierarchy
                WHERE parent_id IN ($tracker_ids)
                   OR child_id  IN ($tracker_ids)";
        return $this->retrieve($sql);
    }

    public function searchParentChildAssociations($group_id) {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "SELECT h.*
                FROM       tracker_hierarchy AS h
                INNER JOIN tracker           AS t ON (t.id = h.parent_id)
                WHERE t.group_id = $group_id";

        return $this->retrieve($sql);
    }

    public function deleteParentChildAssociationsForTracker($tracker_id) {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql = "DELETE h.*
                FROM tracker_hierarchy AS h
                WHERE h.parent_id = $tracker_id
                OR    h.child_id  = $tracker_id";
        return $this->update($sql);
    }

    public function duplicate($parent_id, $child_id, $tracker_mapping){
        if (isset($tracker_mapping[$parent_id]) && isset($tracker_mapping[$child_id])) {
            $parent_id = $this->da->escapeInt($tracker_mapping[$parent_id]);
            $child_id  = $this->da->escapeInt($tracker_mapping[$child_id]);

            $sql = "INSERT INTO tracker_hierarchy (parent_id, child_id)
                    VALUES ($parent_id, $child_id)";

            return $this->update($sql);
        }
    }

    /**
     * Return all artifacts parents of given artifact_id
     *
     * Given artifact 112 linked artifact 345 (112 -> 345)
     * And artifact 112 tracker is parent of artifact 345 tracker
     * When I getParentsInHierarchy(345)
     * Then I get artifact 112
     *
     * Note: this method might return serveral rows but it should be considered
     * as a defect (one shall have only one parent)
     *
     * @param type $artifact_id
     *
     * @return DataAccessResult
     */
    public function getParentsInHierarchy($artifact_id) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql = "SELECT parent_art.*
                FROM           tracker_changeset_value_artifactlink artlink
                    INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_artifact                     parent_art ON (parent_art.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_hierarchy                    hierarchy  ON (hierarchy.parent_id = parent_art.tracker_id AND hierarchy.child_id = child_art.tracker_id)
                WHERE artlink.artifact_id = $artifact_id";
        return $this->retrieve($sql);
    }

    public function isProjectUsingTrackerHierarchy($project_id) {
        $project_id = $this->da->escapeInt($project_id);

        $sql = "SELECT NULL
                FROM tracker_hierarchy
                    INNER JOIN tracker ON (parent_id = tracker.id OR child_id = tracker.id)
                WHERE tracker.group_id = $project_id
                LIMIT 1";

        return $this->retrieve($sql)->count() > 0;
    }
}
