<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Hierarchy;

use ParagonIE\EasyDB\EasyStatement;
use Tracker_FormElement_Field_ArtifactLink;
use Tuleap\DB\DataAccessObject;

class HierarchyDAO extends DataAccessObject
{
    public function updateChildren(int $parent_id, array $child_ids): void
    {
        $this->getDB()->tryFlatTransaction(
            function () use ($parent_id, $child_ids): void {
                $this->removeExistingIsChildNatures($parent_id, $child_ids);
                $this->changeTrackerHierarchy($parent_id, $child_ids);
                $this->addIsChildNature($parent_id, $child_ids);
            }
        );
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
    private function removeExistingIsChildNatures(int $parent_id, array $child_ids): void
    {
        $this->removeIsChildNatureForTrackersThatAreNotAnymoreChildren($parent_id, $child_ids);
        $this->removeIsChildNatureForArtifactsThatWasManuallySetAsChildren($parent_id, $child_ids);
        $this->removeIsChildNatureForTrackersThatHaveANewParent($parent_id, $child_ids);
    }

    private function changeTrackerHierarchy(int $parent_id, array $child_ids): void
    {
        $this->deleteAllChildren($parent_id);

        foreach ($child_ids as $child_id) {
            $this->getDB()->run(
                'REPLACE INTO tracker_hierarchy(parent_id, child_id) VALUES (?,?)',
                $parent_id,
                $child_id
            );
        }
    }

    private function addIsChildNature(int $parent_id, array $child_ids): void
    {
        if (empty($child_ids)) {
            return;
        }
        $child_tracker_ids_in_condition = EasyStatement::open()->in('child_art.tracker_id IN (?*)', $child_ids);
        $this->getDB()->safeQuery(
            "UPDATE tracker_changeset_value_artifactlink AS artlink
                        INNER JOIN tracker_artifact AS child_art
                            ON (child_art.id = artlink.artifact_id
                                AND $child_tracker_ids_in_condition)
                        INNER JOIN tracker_changeset_value AS cv
                            ON (cv.id = artlink.changeset_value_id)
                        INNER JOIN tracker_changeset AS c
                            ON (cv.changeset_id = c.id)
                        INNER JOIN tracker_artifact AS parent_art
                            ON (parent_art.id = c.artifact_id
                                AND parent_art.tracker_id = ?)
                        INNER JOIN tracker_hierarchy AS hierarchy
                            ON (hierarchy.parent_id = parent_art.tracker_id
                                AND hierarchy.child_id = child_art.tracker_id)
                        SET nature = ?",
            array_merge($child_tracker_ids_in_condition->values(), [$parent_id, Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD])
        );
    }

    public function searchChildTrackerIds(int $tracker_id): array
    {
        return $this->getDB()->run(
            'SELECT t.id FROM tracker AS t
                       INNER JOIN tracker_hierarchy AS h ON (h.child_id  = t.id AND h.parent_id = ?)
                       WHERE t.deletion_date IS NULL',
            $tracker_id
        );
    }

    public function searchAncestorIds(int $tracker_id): array
    {
        return $this->getDB()->column('SELECT parent_id FROM tracker_hierarchy WHERE child_id = ?', [$tracker_id]);
    }

    private function deleteAllChildren($parent_id): void
    {
        $this->getDB()->run('DELETE FROM tracker_hierarchy WHERE parent_id = ?', $parent_id);
    }

    private function removeIsChildNatureForTrackersThatAreNotAnymoreChildren(int $parent_id, array $child_ids): void
    {
        $hierarchy_join_condition = EasyStatement::open()->with(
            'hierarchy.child_id = child_art.tracker_id AND hierarchy.parent_id = ?',
            $parent_id
        );
        if (! empty($child_ids)) {
            $hierarchy_join_condition->andIn('hierarchy.child_id NOT IN (?*)', $child_ids);
        }

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
                        ON ($hierarchy_join_condition)
                SET nature = NULL";

        $this->getDB()->safeQuery($sql, $hierarchy_join_condition->values());
    }

    private function removeIsChildNatureForArtifactsThatWasManuallySetAsChildren(int $parent_id, array $child_ids): void
    {
        $where_condition = EasyStatement::open()->with('nature = ?', Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD);
        if (! empty($child_ids)) {
            $where_condition->andIn('child_art.tracker_id NOT IN (?*)', $child_ids);
        }

        $sql = "UPDATE tracker_changeset_value_artifactlink AS artlink
                    INNER JOIN tracker_artifact AS child_art
                        ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker_changeset_value AS cv
                        ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_changeset AS c
                        ON (cv.changeset_id = c.id)
                    INNER JOIN tracker_artifact AS parent_art
                        ON (parent_art.id = c.artifact_id
                            AND parent_art.tracker_id = ?
                        )
                SET nature = NULL
                WHERE $where_condition";

        $this->getDB()->safeQuery($sql, array_merge([$parent_id], $where_condition->values()));
    }

    private function removeIsChildNatureForTrackersThatHaveANewParent(int $parent_id, array $child_ids): void
    {
        if (empty($child_ids)) {
            return;
        }

        $hierarchy_join_condition = EasyStatement::open()
            ->with('hierarchy.child_id = child_art.tracker_id')
            ->andIn('hierarchy.child_id IN (?*)', $child_ids)
            ->andWith('hierarchy.parent_id != ?', $parent_id);

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
                        ON ($hierarchy_join_condition)
                SET nature = NULL";

        $this->getDB()->safeQuery($sql, $hierarchy_join_condition->values());
    }

    public function getChildren($tracker_id): array
    {
        return $this->getDB()->column('SELECT child_id FROM tracker_hierarchy WHERE parent_id = ?', [$tracker_id]);
    }

    public function searchTrackerHierarchy(array $tracker_ids): array
    {
        if (empty($tracker_ids)) {
            return [];
        }
        $condition = EasyStatement::open()
            ->in('parent_id IN (?*)', $tracker_ids)
            ->orIn('child_id IN (?*)', $tracker_ids);
        $sql = "SELECT parent_id, child_id
                FROM tracker_hierarchy
                WHERE $condition";

        return (array) $this->getDB()->safeQuery($sql, $condition->values());
    }

    public function searchParentChildAssociations(int $group_id): array
    {
        $sql = 'SELECT h.*
                FROM       tracker_hierarchy AS h
                INNER JOIN tracker           AS t ON (t.id = h.parent_id)
                WHERE t.group_id = ?';

        return $this->getDB()->run($sql, $group_id);
    }

    public function isAHierarchySetInProject(int $project_id): bool
    {
        $rows = $this->searchParentChildAssociations($project_id);
        return count($rows) > 0;
    }

    public function deleteParentChildAssociationsForTracker(int $tracker_id): void
    {
        $sql = 'DELETE h.*
                FROM tracker_hierarchy AS h
                WHERE h.parent_id = ?
                OR    h.child_id  = ?';

        $this->getDB()->run($sql, $tracker_id, $tracker_id);
    }

    public function duplicate(int $parent_id, int $child_id, array $tracker_mapping): void
    {
        if (isset($tracker_mapping[$parent_id], $tracker_mapping[$child_id])) {
            $sql = 'INSERT INTO tracker_hierarchy (parent_id, child_id)
                    VALUES (?, ?)';

            $this->getDB()->run($sql, $tracker_mapping[$parent_id], $tracker_mapping[$child_id]);
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
     */
    public function getParentsInHierarchy(int $artifact_id): array
    {
        $sql = "SELECT parent_art.*
                FROM           tracker_changeset_value_artifactlink artlink
                    INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.id = artlink.changeset_value_id)
                    INNER JOIN tracker_artifact                     parent_art ON (parent_art.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_hierarchy                    hierarchy  ON (hierarchy.parent_id = parent_art.tracker_id AND hierarchy.child_id = child_art.tracker_id)
                WHERE artlink.artifact_id = ?";

        return $this->getDB()->run($sql, $artifact_id);
    }

    public function isProjectUsingTrackerHierarchy(int $project_id): bool
    {
        $sql = "SELECT NULL
                FROM tracker_hierarchy
                    INNER JOIN tracker ON (parent_id = tracker.id OR child_id = tracker.id)
                WHERE tracker.group_id = ?
                LIMIT 1";

        return count($this->getDB()->run($sql, $project_id)) > 0;
    }
}
