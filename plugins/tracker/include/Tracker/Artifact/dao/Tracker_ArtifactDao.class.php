<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;

class Tracker_ArtifactDao extends DataAccessObject
{
    public const MAX_RETRY_CREATION = 10;
    public const STATUS_OPEN        = 'open';
    public const STATUS_CLOSED      = 'closed';

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_artifact';
    }

    public function searchById($id)
    {
        $id  = $this->da->escapeInt($id);
        $sql = "SELECT *
                FROM $this->table_name
                WHERE id = $id ";
        return $this->retrieve($sql);
    }

    public function searchByIds(array $ids)
    {
        $ids = $this->da->escapeIntImplode($ids);

        $sql = "SELECT * FROM tracker_artifact WHERE id IN ($ids)";

        return $this->retrieve($sql);
    }

    public function searchByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT A.*, CVT.value AS title, CVT.body_format AS title_format
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV
                        INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                    ) ON (A.last_changeset_id = CV.changeset_id)";
        return $this->retrieve($sql);
    }

    public function searchPaginatedByTrackerId($tracker_id, $limit, $offset, $reverse_order)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);
        $order      = ($reverse_order) ? 'DESC' : 'ASC';

        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*, CVT.value AS title, CVT.body_format AS title_format
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV
                        INNER JOIN tracker_semantic_title as ST ON (CV.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV.id = CVT.changeset_value_id)
                    ) ON (A.last_changeset_id = CV.changeset_id)
                    ORDER BY A.id $order
                    LIMIT $limit OFFSET $offset";
        return $this->retrieve($sql);
    }

    /**
     * @param string $artifact_ids "2,14,15"
     */
    public function searchLastChangesetIds($artifact_ids, array $ugroups, $user_is_admin)
    {
        $artifact_ids = $this->da->escapeIntImplode(explode(',', $artifact_ids));

        $sql   = " SELECT tracker_id, GROUP_CONCAT(id) AS id, GROUP_CONCAT(last_changeset_id) AS last_changeset_id";
        $from  = " FROM $this->table_name AS artifact";
        $where = " WHERE id IN (" . $artifact_ids . ")";
        $group = " GROUP BY tracker_id";

        if (! $user_is_admin) {
            $ugroups = $this->da->escapeIntImplode($ugroups);
            $from   .= " LEFT JOIN permissions ON (permissions.object_id = CAST(artifact.id AS CHAR CHARACTER SET utf8) AND permissions.permission_type = 'PLUGIN_TRACKER_ARTIFACT_ACCESS')";
            $where  .= " AND (artifact.use_artifact_permissions = 0 OR  (permissions.ugroup_id IN (" . $ugroups . ")))";
        }

        $sql .= $from . $where . $group;

        return $this->retrieve($sql);
    }

    public function searchOpenByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)          -- Last changeset is needed (no need of history)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                WHERE (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                ORDER BY A.id DESC";
        //TODO: Scalability
        return $this->retrieve($sql);
    }

    public function searchOpenByTrackerIdWithTitle($tracker_id, $limit, $offset)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*, CVT_title.value as title, CVT_title.body_format AS title_format
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)          -- Last changeset is needed (no need of history)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    LEFT JOIN (                         -- For the /title/ if any
                        tracker_changeset_value AS CV_title
                        INNER JOIN tracker_semantic_title as ST ON (CV_title.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT_title ON (CV_title.id = CVT_title.changeset_value_id)
                    ) ON (C.id = CV_title.changeset_id)
                WHERE (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                ORDER BY A.id DESC";

        if ($limit !== '0' || $offset !== '0') {
            $sql .= PHP_EOL . "LIMIT $limit OFFSET $offset";
        }

        return $this->retrieve($sql);
    }

    public function searchClosedByTrackerId($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT a.*
                FROM tracker_artifact AS a
                    INNER JOIN tracker AS t ON (a.tracker_id = t.id)
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND a.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id)
                    INNER JOIN tracker_changeset AS tc ON (tc.artifact_id = a.id)
                    LEFT JOIN tracker_semantic_status AS open_values ON (cvl.bindvalue_id = open_values.open_value_id AND open_values.tracker_id = t.id)
                WHERE open_values.open_value_id IS NULL
                    AND t.id = $tracker_id
                GROUP BY id";

        return $this->retrieve($sql);
    }

    /**
     * Search open artifact (see tracker semantic for a definition of open)
     * submitted by user $user_id in all projects and trackers.
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|false The result of the query
     */
    public function searchOpenSubmittedByUserId(PFUser $user, ?int $offset = null, ?int $limit = null)
    {
        $user_id                 = $this->da->escapeInt($user->getId());
        $order_and_paginate_stmt = $this->getMyArtifactOrderAndPaginateStatement($offset, $limit);
        $sql                     = "SELECT SQL_CALC_FOUND_ROWS A.id AS id, A.tracker_id, A.use_artifact_permissions, C.id AS changeset_id, CVT.value AS title, CVT.body_format AS title_format, A.submitted_by, A.submitted_on
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)          -- Last changeset is needed (no need of history)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    LEFT JOIN (                         -- For the /title/ if any
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE A.submitted_by = $user_id
                  AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                  AND G.status = 'A'
                  AND T.deletion_date IS NULL
               $order_and_paginate_stmt";
        return $this->retrieve($sql);
    }

    /**
     * Search open artifact (see tracker semantic for a definition of open)
     * assigned to user $user_id in all projects and trackers (see tracker semantic for a definition of assigned to).
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|false The result of the query
     */
    public function searchOpenAssignedToUserId(PFUser $user, ?int $offset = null, ?int $limit = null)
    {
        $user_id                 = $this->da->escapeInt($user->getId());
        $order_and_paginate_stmt = $this->getMyArtifactOrderAndPaginateStatement($offset, $limit);
        // The [SC|SS|ST.tracker_id = A.tracker_id is not mandatory but it gives a small perf boost
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.id AS id, A.tracker_id, A.use_artifact_permissions, A.last_changeset_id AS changeset_id, CVT.value AS title, CVT.body_format AS title_format, A.submitted_by, A.submitted_on
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN (
                        tracker_semantic_contributor as SC
                        INNER JOIN tracker_changeset_value AS CV1 ON (CV1.field_id = SC.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CVL.changeset_value_id = CV1.id)
                    ) ON (SC.tracker_id = A.tracker_id AND CV1.changeset_id = A.last_changeset_id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (CV3.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CVL2.changeset_value_id = CV3.id)
                    ) ON (SS.tracker_id = A.tracker_id AND CV3.changeset_id = A.last_changeset_id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (ST.field_id = CV2.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CVT.changeset_value_id = CV2.id)
                    ) ON (ST.tracker_id = A.tracker_id AND CV2.changeset_id = A.last_changeset_id)
                WHERE CVL.bindvalue_id = $user_id
                  AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                  AND G.status = 'A'
                  AND T.deletion_date IS NULL
               $order_and_paginate_stmt";
        return $this->retrieve($sql);
    }

    /**
     * Search open artifact (see tracker semantic for a definition of open)
     * submitted by or assigned to user $user_id in all projects and trackers (see tracker semantic for a definition of assigned to).
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface|false The result of the query
     */
    public function searchOpenSubmittedByOrAssignedToUserId(PFUser $user, ?int $offset = null, ?int $limit = null)
    {
        $user_id                 = $this->da->escapeInt($user->getId());
        $order_and_paginate_stmt = $this->getMyArtifactOrderAndPaginateStatement($offset, $limit);
        // The [SC|SS|ST.tracker_id = A.tracker_id is not mandatory but it gives a small perf boost
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.id AS id, A.tracker_id, A.use_artifact_permissions, A.last_changeset_id AS changeset_id, CVT.value AS title, CVT.body_format AS title_format, A.submitted_by, A.submitted_on, G.group_name
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    LEFT JOIN (                                                                -- Look if there is any status /open/ semantic defined
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND A.last_changeset_id = CV3.changeset_id)
                    LEFT JOIN (                         -- For the /title/ if any
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (A.last_changeset_id = CV2.changeset_id)
                WHERE A.submitted_by = $user_id
                  AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                  AND G.status = 'A'
                  AND T.deletion_date IS NULL
                UNION
                SELECT A.id AS id, A.tracker_id, A.use_artifact_permissions, A.last_changeset_id AS changeset_id, CVT.value AS title, CVT.body_format AS title_format, A.submitted_by, A.submitted_on, G.group_name
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN (
                        tracker_semantic_contributor as SC
                        INNER JOIN tracker_changeset_value AS CV1 ON (CV1.field_id = SC.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL ON (CVL.changeset_value_id = CV1.id)
                    ) ON (SC.tracker_id = A.tracker_id AND CV1.changeset_id = A.last_changeset_id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (CV3.field_id = SS.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CVL2.changeset_value_id = CV3.id)
                    ) ON (SS.tracker_id = A.tracker_id AND CV3.changeset_id = A.last_changeset_id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (ST.field_id = CV2.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CVT.changeset_value_id = CV2.id)
                    ) ON (ST.tracker_id = A.tracker_id AND CV2.changeset_id = A.last_changeset_id)
                WHERE CVL.bindvalue_id = $user_id
                  AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                  AND G.status = 'A'
                  AND T.deletion_date IS NULL
               $order_and_paginate_stmt";
        return $this->retrieve($sql);
    }

    private function getMyArtifactOrderAndPaginateStatement(?int $offset = null, ?int $limit = null)
    {
        if ($offset !== null && $limit !== null) {
            return sprintf(' ORDER BY id ASC LIMIT %d OFFSET %d', $limit, $offset);
        }
        return ' ORDER BY group_name ASC, tracker_id ASC, id DESC';
    }

    public function searchStatsForTracker($tracker_id)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $sql        = "SELECT R1.t AS last_creation, R2.t AS last_update, R3.t as nb_total, R4.t as nb_open
                FROM (SELECT MAX(a.submitted_on) AS t
                    FROM tracker_artifact AS a
                    WHERE a.tracker_id = $tracker_id) AS R1,

                    (SELECT MAX(c.submitted_on) AS t
                    FROM tracker_artifact AS b INNER JOIN tracker_changeset AS c ON (b.last_changeset_id = c.id)
                    WHERE b.tracker_id = $tracker_id) AS R2,

                    (SELECT count(*) as t
                    FROM tracker_artifact
                    WHERE tracker_id = $tracker_id) AS R3,

                    (SELECT count(distinct(a.id)) AS t
                    FROM tracker_artifact AS a
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND a.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id AND ss.open_value_id = cvl.bindvalue_id)
                    WHERE a.tracker_id = $tracker_id) AS R4
                ";
        return $this->retrieve($sql);
    }

    public function searchSubmittedArtifactBetweenTwoDates($start_date, $end_date)
    {
        $start_date = $this->da->escapeInt($start_date);
        $end_date   = $this->da->escapeInt($end_date);

        $sql = "SELECT group_id, count(distinct(a.id)) AS result
                    FROM tracker_artifact AS a
                    INNER JOIN tracker AS t ON (a.tracker_id = t.id)
                WHERE a.submitted_on >= $start_date AND a.submitted_on <= $end_date
                    GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function searchClosedArtifactBetweenTwoDates($start_date, $end_date)
    {
        $start_date = $this->da->escapeInt($start_date);
        $end_date   = $this->da->escapeInt($end_date);

        $sql = "SELECT group_id, count(distinct(a.id)) AS result
                FROM tracker_artifact AS a
                    INNER JOIN tracker AS t ON (a.tracker_id = t.id)
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND a.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id)
                    INNER JOIN tracker_changeset AS tc ON (tc.artifact_id = a.id)
                    LEFT JOIN tracker_semantic_status AS open_values ON (cvl.bindvalue_id = open_values.open_value_id AND open_values.tracker_id = t.id)
                WHERE tc.submitted_on >= $start_date AND tc.submitted_on <= $end_date
                    AND open_values.open_value_id IS NULL
                GROUP BY group_id";

        return $this->retrieve($sql);
    }

    public function create($tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions)
    {
        $transaction_executor = new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection());
        for ($tentative = 0; $tentative < self::MAX_RETRY_CREATION; $tentative++) {
            $artifact_id = $transaction_executor->execute(function () use ($tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions) {
                $id_sharing  = new TrackerIdSharingDao();
                $artifact_id = $id_sharing->generateArtifactId();

                return $this->createWithId(
                    $artifact_id,
                    $tracker_id,
                    $submitted_by,
                    $submitted_on,
                    $use_artifact_permissions
                );
            });
            if ($artifact_id !== null) {
                return $artifact_id;
            }
        }

        return false;
    }

    /**
     * @throws DataAccessException
     * @throws DataAccessQueryException
     */
    public function createWithId(
        $artifact_id,
        $tracker_id,
        $submitted_by,
        $submitted_on,
        $use_artifact_permissions,
    ) {
        $artifact_id              = $this->da->escapeInt($artifact_id);
        $tracker_id               = $this->da->escapeInt($tracker_id);
        $use_artifact_permissions = $this->da->escapeInt($use_artifact_permissions);
        $submitted_on             = $this->da->escapeInt($submitted_on);
        $submitted_by             = $this->da->escapeInt($submitted_by);

        $sql            = "SELECT IFNULL(MAX(per_tracker_artifact_id), 0) + 1 as per_tracker_artifact_id
                           FROM tracker_artifact
                           WHERE tracker_id = $tracker_id";
        $row            = $this->retrieveFirstRow($sql);
        $per_tracker_id = $row['per_tracker_artifact_id'];

        if ($artifact_id && $this->getPriorityDao()->putArtifactAtTheEndWithoutTransaction($artifact_id)) {
            // We do not keep trace of the history change here because it doesn't have any sense to say
            // the newly created artifact has less priority than the one at the bottom of the priority chain.
            $sql = "INSERT INTO $this->table_name
                (id, tracker_id, per_tracker_artifact_id, submitted_by, submitted_on, use_artifact_permissions)
                VALUES ($artifact_id, $tracker_id, $per_tracker_id, $submitted_by, $submitted_on, $use_artifact_permissions)";
            if ($this->update($sql)) {
                return (int) $artifact_id;
            }
        }

        throw new DataAccessException();
    }

    public function save($id, $tracker_id, $use_artifact_permissions)
    {
        $id                       = $this->da->escapeInt($id);
        $tracker_id               = $this->da->escapeInt($tracker_id);
        $use_artifact_permissions = $this->da->escapeInt($use_artifact_permissions);

        $sql = "UPDATE $this->table_name SET
                   tracker_id               = $tracker_id,
                   use_artifact_permissions = $use_artifact_permissions
                WHERE id = $id ";
        return $this->update($sql);
    }

    public function delete($id)
    {
        $sql = "DELETE FROM $this->table_name WHERE id = " . $this->da->escapeInt($id);
        return $this->update($sql);
    }

    public function deleteArtifactLinkReference($id)
    {
        $dao = new ArtifactLinkFieldValueDao();
        return $dao->deleteReference($id);
    }

    private function getPriorityDao()
    {
        return new Tracker_Artifact_PriorityDao();
    }

    /**
     * Retrieve the list of artifact id corresponding to a submitted on date having a specific value
     *
     * @param int $trackerId Tracker id
     * @param int $date Submitted on date
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getArtifactsBySubmittedOnDate($trackerId, $date)
    {
        $trackerId = $this->da->escapeInt($trackerId);
        $halfDay   = 60 * 60 * 12;
        $minDate   = $this->da->escapeInt($date - $halfDay);
        $maxDate   = $this->da->escapeInt($date + $halfDay);
        $sql       = "SELECT id AS artifact_id FROM
                       tracker_artifact
                       WHERE DATE(FROM_UNIXTIME(submitted_on)) BETWEEN DATE(FROM_UNIXTIME(" . $minDate . ")) AND DATE(FROM_UNIXTIME(" . $maxDate . "))
                         AND tracker_id = " . $trackerId;
        return $this->retrieve($sql);
    }

    /**
     * Retrieve the hidden field per_tracker_artifact_id
     *
     * @param int $aid Artifact ID
     *
     * @return int per_tracker_artifact_id if Artifact exist, else 0
     */
    public function getPerTrackerArtifactId($aid)
    {
        $per_tracker_id = 0;
        $aid            = $this->da->escapeInt($aid);
        $sql            = "SELECT per_tracker_artifact_id FROM tracker_artifact where id = '" . $aid . "';";
        $res            = $this->retrieveFirstRow($sql);
        $per_tracker_id = $res['per_tracker_artifact_id'] | 0;

        return $per_tracker_id;
    }

    /**
     * It does not check permissions
     */
    public function getChildren(int $artifact_id)
    {
        $escaped_id         = $this->da->escapeInt($artifact_id);
        $is_child_shortname = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD);

        $sql = "SELECT child_art.*, parent_art.id as parent_id
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field AS f ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value AS cv ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact AS child_art ON (child_art.id = artlink.artifact_id)
                    INNER JOIN tracker AS child_tracker ON (child_art.tracker_id = child_tracker.id)
               WHERE artlink.nature=$is_child_shortname
                    AND child_tracker.deletion_date IS NULL
                    AND parent_art.id=$escaped_id";

        return $this->retrieve($sql);
    }

    /**
     * Return the number of children for each artifact.
     *
     * @param int[] $artifact_ids
     *
     * @psalm-return array{id:int, nb:int}
     * @return array
     */
    public function getChildrenCount(array $artifact_ids): array
    {
        $is_child_shortname = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD);

        $sql = "SELECT parent_art.id, count(*) AS nb
                FROM tracker_artifact parent_art
                     INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                     INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                     INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                     INNER JOIN tracker_artifact                     AS child_art  ON (child_art.id = artlink.artifact_id)
                     INNER JOIN tracker                              AS child_tracker ON (child_art.tracker_id = child_tracker.id)
                WHERE parent_art.id IN (" . $this->da->escapeIntImplode($artifact_ids) . ")
                    AND artlink.nature=$is_child_shortname
                    AND child_tracker.deletion_date IS NULL
                GROUP BY parent_art.id";

        $children_count = [];
        foreach ($this->retrieve($sql) as $row) {
            $children_count[$row['id']] = $row['nb'];
        }
        foreach ($artifact_ids as $id) {
            if (! isset($children_count[$id])) {
                $children_count[$id] = 0;
            }
        }
        return $children_count;
    }

    public function getChildrenCountInSameProjectOfParent(int $artifact_id): int
    {
        $is_child_shortname = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD);

        $sql = "SELECT count(*) AS nb
                FROM tracker_artifact parent_art
                     INNER JOIN tracker_field                        AS f              ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                     INNER JOIN tracker_changeset_value              AS cv             ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                     INNER JOIN tracker_changeset_value_artifactlink AS artlink        ON (artlink.changeset_value_id = cv.id)
                     INNER JOIN tracker_artifact                     AS child_art      ON (child_art.id = artlink.artifact_id)
                     INNER JOIN tracker                              AS parent_tracker ON (parent_art.tracker_id = parent_tracker.id)
                     INNER JOIN tracker                              AS child_tracker  ON (child_art.tracker_id = child_tracker.id AND child_tracker.group_id = parent_tracker.group_id)
                WHERE parent_art.id = " . $this->da->escapeInt($artifact_id) . "
                    AND artlink.nature=$is_child_shortname
                    AND child_tracker.deletion_date IS NULL
                GROUP BY parent_art.id";

        return $this->retrieveCount($sql);
    }

    /**
     * It does not check permissions
     */
    public function getPaginatedChildren(int $artifact_id, int $limit, int $offset)
    {
        $artifact_ids = $this->da->escapeIntImplode([$artifact_id]);
        $limit        = $this->da->escapeInt($limit);
        $offset       = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS child_art.*, parent_art.id as parent_id, tracker_artifact_priority_rank.`rank` as `rank`" .
            $this->getSortedFromStatementForChildrenOfArtifacts($artifact_ids) .
            "LIMIT $limit
             OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getChildrenForArtifacts(array $artifact_ids)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);

        $sql = "SELECT child_art.*, parent_art.id as parent_id " .
               $this->getSortedFromStatementForChildrenOfArtifacts($artifact_ids);

        return $this->retrieve($sql);
    }

    private function getSortedFromStatementForChildrenOfArtifacts(string $artifact_ids): string
    {
        $is_child_shortname = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD);

        return " FROM tracker_artifact parent_art
                     INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                     INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                     INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                     INNER JOIN tracker_artifact                     AS child_art  ON (child_art.id = artlink.artifact_id)
                     INNER JOIN tracker                              AS child_tracker ON (child_art.tracker_id = child_tracker.id)
                     INNER JOIN tracker_artifact_priority_rank ON (tracker_artifact_priority_rank.artifact_id = child_art.id)
                WHERE parent_art.id IN ($artifact_ids)
                    AND child_tracker.deletion_date IS NULL
                    AND artlink.nature=$is_child_shortname
                ORDER BY tracker_artifact_priority_rank.`rank` ASC ";
    }

    public function getParents(array $artifact_ids)
    {
        $artifact_ids       = $this->da->escapeIntImplode($artifact_ids);
        $is_child_shortname = $this->da->quoteSmart(Tracker_FormElement_Field_ArtifactLink::TYPE_IS_CHILD);

        $sql = "SELECT child_art.id child_id, parent_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker                              AS parent_tracker ON (parent_tracker.id = parent_art.tracker_id)
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     child_art  ON (child_art.id = artlink.artifact_id)
                WHERE child_art.id IN ($artifact_ids)
                    AND artlink.nature=$is_child_shortname
                    AND parent_tracker.deletion_date IS NULL
                    ORDER BY parent_art.id";

        return $this->retrieve($sql);
    }

    public function getTitles(array $artifact_ids)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);
        $sql          = "SELECT artifact.id, CVT.value as title, CVT.body_format AS title_format
                FROM tracker_artifact artifact
                    INNER JOIN tracker_changeset AS c ON (artifact.last_changeset_id = c.id)
                    LEFT JOIN (
                        tracker_changeset_value                 AS CV0
                        INNER JOIN tracker_semantic_title       AS ST  ON (
                            CV0.field_id = ST.field_id
                        )
                        INNER JOIN tracker_changeset_value_text AS CVT ON (
                            CV0.id       = CVT.changeset_value_id
                        )
                    ) ON (c.id = CV0.changeset_id)
                WHERE artifact.id IN ($artifact_ids)";
        return $this->retrieve($sql);
    }

    /**
     * Filters a list of artifact IDs.
     * For each artifact, checks if it is linked by another artifact belonging
     * to a set of trackers.
     *
     * @param array $artifact_ids
     * @param array $tracker_ids
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface | false
     */
    public function getArtifactIdsLinkedToTrackers($artifact_ids, $tracker_ids)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);
        $tracker_ids  = $this->da->escapeIntImplode($tracker_ids);

         $sql = "SELECT
                    back_item.id
                FROM tracker_artifact AS milestone
                    INNER JOIN tracker_changeset_value
                        ON tracker_changeset_value.changeset_id = milestone.last_changeset_id
                    INNER JOIN tracker_changeset_value_artifactlink
                        ON tracker_changeset_value_artifactlink.changeset_value_id = tracker_changeset_value.id
                    INNER JOIN tracker_artifact AS back_item
                        ON tracker_changeset_value_artifactlink.artifact_id = back_item.id
                    INNER JOIN tracker_field
                        ON (tracker_field.tracker_id = milestone.tracker_id AND tracker_field.formElement_type = 'art_link' AND use_it = 1 AND tracker_changeset_value.field_id = tracker_field.id)
                WHERE
                    back_item.id IN ($artifact_ids)
                AND
                    milestone.tracker_id IN ($tracker_ids)";

        return $this->retrieve($sql);
    }

    /**
     * Retrieve all artifacts linked by the given one
     *
     * @param int $artifact_id
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getLinkedArtifacts($artifact_id)
    {
        return $this->getLinkedArtifactsByIds([$artifact_id]);
    }

    public function getLinkedOpenArtifactsOfTrackersNotLinkedToOthers($artifact_id, array $tracker_ids, array $excluded_linked_ids, array $additional_artifacts)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);

        $additional_artifacts_sql = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_sql = 'OR linked_art.id IN (' . $this->da->escapeIntImplode($additional_artifacts) . ')';
        }

        $exclude      = '';
        $submile_null = '';
        if (count($excluded_linked_ids) > 0) {
            $excluded_linked_ids = $this->da->escapeIntImplode($excluded_linked_ids);

            $exclude =
                    "-- exlude all those linked to wrong artifacts
                    LEFT JOIN (
                        tracker_artifact as submile
                        INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                        INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                        INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
                    ) ON (linked_art.id = artlink2.artifact_id AND submile.id IN ($excluded_linked_ids))";

            $submile_null = "AND submile.id IS NULL";
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_sql)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                    $exclude
                        -- only those with open status
                    INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE parent_art.id = $artifact_id
                    AND (
                        SS.field_id IS NULL
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )
                    $submile_null
                    AND linked_art.tracker_id IN ($tracker_ids)
                GROUP BY (linked_art.id)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC";

        return $this->retrieve($sql);
    }

    public function getLinkedArtifactsOfTrackersNotLinkedToOthers($artifact_id, array $tracker_ids, array $excluded_linked_ids, array $additional_artifacts)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);

        $additional_artifacts_sql = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_sql = 'OR linked_art.id IN (' . $this->da->escapeIntImplode($additional_artifacts) . ')';
        }

        $exclude       = '';
        $exclude_where = '';
        if (count($excluded_linked_ids) > 0) {
            $exclude       = 'AND submile.id IN (' . $this->da->escapeIntImplode($excluded_linked_ids) . ')';
            $exclude_where = 'AND submile.id IS NULL';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_sql)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                    -- exlude all those linked to wrong artifacts
                    LEFT JOIN (
                        tracker_artifact as submile
                        INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                        INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                        INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
                    ) ON (linked_art.id = artlink2.artifact_id $exclude)
                        -- only those with open status
                    INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE parent_art.id = $artifact_id
                    $exclude_where
                    AND linked_art.tracker_id IN ($tracker_ids)
                GROUP BY (linked_art.id)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC";

        return $this->retrieve($sql);
    }

    public function getArtifactsWithOpenStatusForSubmilestonesForMonoMilestoneConfiguration(
        $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
    ) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);

        $additional_artifacts_sql = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_sql = 'OR linked_art.id IN (' . $this->da->escapeIntImplode($additional_artifacts) . ')';
        }

        $exclude       = '';
        $exclude_where = '';
        if (count($excluded_linked_ids) > 0) {
            $exclude = 'LEFT JOIN (
                        tracker_artifact as submile
                        INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = \'art_link\' AND f2.use_it = 1)
                        INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                        INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
                    ) ON (linked_art.id = artlink2.artifact_id AND submile.id IN (' . $this->da->escapeIntImplode($excluded_linked_ids) . '))';

            $exclude_where = "AND submile.id IS NULL";
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_sql)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                    -- exlude all those linked to wrong artifacts
                    $exclude
                        -- only those with open status
                    INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE parent_art.id = $artifact_id
                    $exclude_where
                    AND linked_art.tracker_id IN ($tracker_ids)
                GROUP BY (linked_art.id)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC";

        return $this->retrieve($sql);
    }

    public function getLinkedArtifactsOfTrackersConcatenatedToCustomList($artifact_id, array $tracker_ids, array $additional_artifacts)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);

        $additional_artifacts_sql = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_sql = 'OR linked_art.id IN (' . $this->da->escapeIntImplode($additional_artifacts) . ')';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_sql)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                WHERE parent_art.id = $artifact_id
                    AND linked_art.tracker_id IN ($tracker_ids)
                GROUP BY (linked_art.id)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC";

        return $this->retrieve($sql);
    }

    /**
     * Retrieve all artifacts linked by the given one
     *
     * @param int $artifact_id
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getLinkedArtifactsOfTrackers($artifact_id, array $tracker_ids)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                WHERE parent_art.id = $artifact_id
                    AND linked_art.tracker_id IN ($tracker_ids)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC";

        return $this->retrieve($sql);
    }

    /**
     * Retrieve all artifacts linked by the given one that are of a specific tracker type
     *
     * @param int $artifact_id
     * @return array|false
     * @psalm-ignore-falsable-return
     */
    public function getLinkedArtifactsOfTrackerTypeAsString($artifact_id, $tracker_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_id  = $this->da->escapeInt($tracker_id);

        $sql = "SELECT GROUP_CONCAT(DISTINCT linked_art.id) AS artifact_ids
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.id = $artifact_id
                    AND linked_art.tracker_id = $tracker_id";

        return $this->retrieveFirstRow($sql);
    }

    /**
     * Retrieve all artifacts linked to any of the given ones that are of a specific tracker type
     *
     * @param string $artifact_ids string of comma separated list of artifact IDs e.g '12,568,12,4589'
     * @return array|false
     * @psalm-ignore-falsable-return
     */
    public function getLinkedArtifactsOfArtifactsOfTrackerTypeAsString($artifact_ids, $tracker_id)
    {
        $artifact_ids = explode(',', $artifact_ids);
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);
        $tracker_id   = $this->da->escapeInt($tracker_id);

        $sql = "SELECT GROUP_CONCAT(DISTINCT linked_art.id) AS artifact_ids
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.id IN ($artifact_ids)
                    AND linked_art.tracker_id = $tracker_id";

        return $this->retrieveFirstRow($sql);
    }

    public function getLinkedArtifactsOfTrackersWithLimitAndOffset($artifact_id, array $tracker_ids, $limit, $offset)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                WHERE parent_art.id = $artifact_id
                    AND linked_art.tracker_id IN ($tracker_ids)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getLinkedOpenArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
        $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        $limit,
        $offset,
    ) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $exclude      = '';
        $submile_null = '';
        if (count($excluded_linked_ids) > 0) {
            $excluded_linked_ids = $this->da->escapeIntImplode($excluded_linked_ids);

            $exclude =
                    "-- exlude all those linked to wrong artifacts
                    LEFT JOIN (
                        tracker_artifact as submile
                        INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                        INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                        INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
                    ) ON (linked_art.id = artlink2.artifact_id AND submile.id IN ($excluded_linked_ids))";

            $submile_null = "AND submile.id IS NULL";
        }

        $additional_artifacts_sql = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_sql = 'OR linked_art.id IN (' . $this->da->escapeIntImplode($additional_artifacts) . ')';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_sql)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                     $exclude
                    INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                    -- only those with open status
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3       ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                WHERE parent_art.id = $artifact_id
                    $submile_null
                    AND linked_art.tracker_id IN ($tracker_ids)
                    AND (
                        SS.field_id IS NULL -- Use the status semantic only if it is defined
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                    )
                GROUP BY (linked_art.id)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    public function getLinkedArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
        $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        $limit,
        $offset,
    ) {
        $filter = 'AND (
                        SS.field_id IS NULL
                        OR
                        CVL2.bindvalue_id = SS.open_value_id
                     )';

        return $this->getLinkedArtifactsToTrackerWithWhereConditionAndLimitAndOffset(
            $artifact_id,
            $tracker_ids,
            $excluded_linked_ids,
            $additional_artifacts,
            $limit,
            $offset,
            $filter
        );
    }

    /**
     * @return LegacyDataAccessResultInterface|false
     */
    public function getLinkedOpenClosedArtifactsOfTrackersNotLinkedToOthersWithLimitAndOffset(
        int $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        ?int $limit,
        ?int $offset,
    ) {
        return $this->getLinkedArtifactsToTrackerWithWhereConditionAndLimitAndOffset(
            $artifact_id,
            $tracker_ids,
            $excluded_linked_ids,
            $additional_artifacts,
            $limit,
            $offset,
            ''
        );
    }

    /**
     * @return LegacyDataAccessResultInterface|false
     */
    private function getLinkedArtifactsToTrackerWithWhereConditionAndLimitAndOffset(
        int $artifact_id,
        array $tracker_ids,
        array $excluded_linked_ids,
        array $additional_artifacts,
        ?int $limit,
        ?int $offset,
        string $filter,
    ) {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $tracker_ids = $this->da->escapeIntImplode($tracker_ids);
        $limit       = $this->da->escapeInt($limit);
        $offset      = $this->da->escapeInt($offset);

        $exclude       = '';
        $exclude_where = '';
        if (count($excluded_linked_ids) > 0) {
            $exclude       = 'AND submile.id IN (' . $this->da->escapeIntImplode($excluded_linked_ids) . ')';
            $exclude_where = 'AND submile.id IS NULL';
        }

        $additional_artifacts_sql = '';
        if (! empty($additional_artifacts)) {
            $additional_artifacts_sql = 'OR linked_art.id IN (' . $this->da->escapeIntImplode($additional_artifacts) . ')';
        }

        $sql = "SELECT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $additional_artifacts_sql)
                    INNER JOIN tracker_artifact_priority_rank                       ON (tracker_artifact_priority_rank.artifact_id = linked_art.id)
                    -- exlude all those linked to wrong artifacts
                    LEFT JOIN (
                        tracker_artifact as submile
                        INNER JOIN tracker_field AS f2 ON (f2.tracker_id = submile.tracker_id AND f2.formElement_type = 'art_link' AND f2.use_it = 1)
                        INNER JOIN tracker_changeset_value AS excluded_cv ON (excluded_cv.changeset_id = submile.last_changeset_id AND excluded_cv.field_id = f2.id)
                        INNER JOIN tracker_changeset_value_artifactlink AS artlink2 ON (artlink2.changeset_value_id = excluded_cv.id)
                    ) ON (linked_art.id = artlink2.artifact_id $exclude)
                        -- only those with open status
                    INNER JOIN tracker AS T ON (linked_art.tracker_id = T.id)
                    INNER JOIN `groups` AS G ON (G.group_id = T.group_id)
                    INNER JOIN tracker_changeset AS C ON (linked_art.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                    LEFT JOIN (
                        tracker_changeset_value AS CV2
                        INNER JOIN tracker_semantic_title as ST ON (CV2.field_id = ST.field_id)
                        INNER JOIN tracker_changeset_value_text AS CVT ON (CV2.id = CVT.changeset_value_id)
                    ) ON (C.id = CV2.changeset_id)
                WHERE parent_art.id = $artifact_id
                    $filter
                    $exclude_where
                    AND linked_art.tracker_id IN ($tracker_ids)
                GROUP BY (linked_art.id)
                ORDER BY tracker_artifact_priority_rank.`rank` ASC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    /**
     * Return all artifacts linked by the given artifact (possible exclusion)
     *
     * @param array $artifact_ids Artifact ids to inspect
     * @param array $excluded_ids Exclude those ids from the results
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getLinkedArtifactsByIds(array $artifact_ids, array $excluded_ids = [])
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);
        $exclude      = '';
        if (count($excluded_ids) > 0) {
            $exclude = 'AND linked_art.id NOT IN (' . $this->da->escapeIntImplode($excluded_ids) . ')';
        }
        $sql = "SELECT linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id $exclude)
                    INNER JOIN tracker                              linked_tracker ON (linked_art.tracker_id = linked_tracker.id)
                WHERE parent_art.id IN ($artifact_ids)
                    AND linked_tracker.deletion_date IS NULL";

        return $this->retrieve($sql);
    }

    /**
     * Return artifact status (open/closed)
     *
     * @param int[] $artifact_ids
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getArtifactsStatusByIds(array $artifact_ids)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);
        $sql          = "SELECT A.id, IF(CVL.bindvalue_id IS NULL, '" . Artifact::STATUS_CLOSED . "', '" . Artifact::STATUS_OPEN . "') AS status
                FROM tracker_artifact AS A
                LEFT JOIN (
                    tracker_changeset_value AS CV
                    INNER JOIN tracker_semantic_status SS ON (CV.field_id = SS.field_id)
                    INNER JOIN tracker_changeset_value_list CVL ON (CV.id = CVL.changeset_value_id AND CVL.bindvalue_id = SS.open_value_id)
                ) ON (A.last_changeset_id = CV.changeset_id)
                WHERE A.id IN ($artifact_ids)";
        return $this->retrieve($sql);
    }

    /** @return array */
    public function getIdsSortedByPriority(array $artifact_ids)
    {
        $artifact_ids = $this->da->escapeIntImplode($artifact_ids);

        $this->setGroupConcatLimit();
        $sql = "SELECT GROUP_CONCAT(artifact_id) as sorted_ids
                FROM (
                    SELECT artifact_id
                    FROM tracker_artifact_priority_rank
                    WHERE artifact_id IN ($artifact_ids)
                    ORDER BY `rank` ASC
                    ) AS R";
        $row = $this->retrieve($sql)->getRow();
        if ($row && $row['sorted_ids'] != null) {
            return explode(',', $row['sorted_ids']);
        }
        return [];
    }

    public function doesUserHaveUnsubscribedFromArtifactNotifications($artifact_id, $user_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $user_id     = $this->da->escapeInt($user_id);

        $sql = "SELECT user_id
                FROM tracker_artifact_unsubscribe
                WHERE artifact_id = $artifact_id
                  AND user_id = $user_id";

        return $this->retrieve($sql)->count() > 0;
    }

    public function createUnsubscribeNotification($artifact_id, $user_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $user_id     = $this->da->escapeInt($user_id);

        $sql = "REPLACE INTO tracker_artifact_unsubscribe (artifact_id, user_id)
                VALUE ($artifact_id, $user_id)";

        $this->update($sql);
    }

    public function deleteUnsubscribeNotification($artifact_id, $user_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $user_id     = $this->da->escapeInt($user_id);

        $sql = "DELETE FROM tracker_artifact_unsubscribe
                WHERE artifact_id = $artifact_id
                    AND user_id = $user_id";

        $this->update($sql);
    }

    public function deleteUnsubscribeNotificationForArtifact(int $artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);

        $sql = "DELETE FROM tracker_artifact_unsubscribe
                WHERE artifact_id = $artifact_id";

        $this->update($sql);
    }

    public function searchLatestUpdatedArtifactsInProject(int $project_id, int $nb_max, int ...$excluded_tracker_ids)
    {
        $project_id = $this->da->escapeInt($project_id);
        $nb_max     = $this->da->escapeInt($nb_max);

        $where_condition = '';
        if (count($excluded_tracker_ids) > 0) {
            $where_condition = 'WHERE tracker.id NOT IN (' . $this->da->escapeIntImplode($excluded_tracker_ids) . ')';
        }

        $sql = "SELECT artifact.*, changeset.submitted_on AS last_update_date
                FROM tracker_artifact AS artifact
                    INNER JOIN tracker ON(
                        artifact.tracker_id = tracker.id
                        AND tracker.group_id = $project_id
                        AND tracker.deletion_date IS NULL
                    )
                    INNER JOIN tracker_changeset AS changeset ON(
                        artifact.last_changeset_id = changeset.id
                    )
                $where_condition
                ORDER BY last_update_date DESC
                LIMIT $nb_max";

        return $this->retrieve($sql);
    }

    public function countArtifactsRegisteredBefore($timestamp)
    {
        $timestamp = $this->da->escapeInt($timestamp);

        $sql = "SELECT count(*) AS nb FROM tracker_artifact WHERE submitted_on >= $timestamp";

        $row = $this->retrieve($sql)->getRow();

        return $row['nb'];
    }

    /**
     * @return int
     */
    public function countArtifacts()
    {
        $sql = "SELECT count(*) AS nb FROM tracker_artifact";

        $row = $this->retrieve($sql)->getRow();

        return (int) $row['nb'];
    }

    public function updateLastChangsetId(int $changeset_id, int $artifact_id): void
    {
        $changeset_id = $this->da->escapeInt($changeset_id);
        $artifact_id  = $this->da->escapeInt($artifact_id);

        $sql = "UPDATE tracker_artifact
                SET last_changeset_id = $changeset_id
                WHERE id = $artifact_id";
        $this->update($sql);
    }
}
