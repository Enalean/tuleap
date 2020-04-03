<?php
/**
 * Copyright (c) Enalean, 2016 - 2017. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use DataAccessObject;
use Tuleap\TestManagement\Nature\NatureCoveredByPresenter;

class ArtifactDao extends DataAccessObject
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_artifact';
    }

    /**
     *
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function searchPaginatedByTrackerId(int $tracker_id, ?int $milestone_id, int $limit, int $offset, bool $reverse_order)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $milestone_id = $this->da->escapeInt($milestone_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);
        $order      = ($reverse_order) ? 'DESC' : 'ASC';

        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                $milestone_filter
                WHERE A.tracker_id = $tracker_id
                ORDER BY A.id $order
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    /**
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function searchPaginatedOpenByTrackerId(int $tracker_id, ?int $milestone_id, int $limit, int $offset)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $milestone_id = $this->da->escapeInt($milestone_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS T ON (A.tracker_id = T.id AND T.id = $tracker_id)
                    INNER JOIN tracker_changeset AS C ON (A.last_changeset_id = C.id)
                    LEFT JOIN (
                        tracker_semantic_status as SS
                        INNER JOIN tracker_changeset_value AS CV3 ON (SS.field_id = CV3.field_id)
                        INNER JOIN tracker_changeset_value_list AS CVL2 ON (CV3.id = CVL2.changeset_value_id)
                    ) ON (T.id = SS.tracker_id AND C.id = CV3.changeset_id)
                $milestone_filter
                WHERE (
                    SS.field_id IS NULL
                    OR
                    CVL2.bindvalue_id = SS.open_value_id
                )
                ORDER BY A.id DESC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    /**
     * @return \DataAccessResult|false
     * @psalm-ignore-falsable-return
     */
    public function searchPaginatedClosedByTrackerId(int $tracker_id, ?int $milestone_id, int $limit, int $offset)
    {
        $tracker_id = $this->da->escapeInt($tracker_id);
        $milestone_id = $this->da->escapeInt($milestone_id);
        $limit      = $this->da->escapeInt($limit);
        $offset     = $this->da->escapeInt($offset);

        $milestone_filter = $this->milestoneSQLFilter($milestone_id);
        $sql = "SELECT SQL_CALC_FOUND_ROWS A.*
                FROM tracker_artifact AS A
                    INNER JOIN tracker AS t ON (A.tracker_id = t.id)
                    INNER JOIN tracker_semantic_status AS ss USING(tracker_id)
                    INNER JOIN tracker_changeset_value AS cv ON(cv.field_id = ss.field_id AND A.last_changeset_id = cv.changeset_id)
                    INNER JOIN tracker_changeset_value_list AS cvl ON(cvl.changeset_value_id = cv.id)
                    INNER JOIN tracker_changeset AS tc ON (tc.artifact_id = A.id)
                    LEFT JOIN tracker_semantic_status AS open_values ON (cvl.bindvalue_id = open_values.open_value_id AND open_values.tracker_id = t.id)
                $milestone_filter
                WHERE open_values.open_value_id IS NULL AND t.id = $tracker_id
                GROUP BY A.id
                ORDER BY A.id DESC
                LIMIT $limit OFFSET $offset";

        return $this->retrieve($sql);
    }

    private function milestoneSQLFilter(int $milestone_id): string
    {
        if ($milestone_id === 0) {
            return '';
        }

        return "INNER JOIN (
                   tracker_field AS milestone_f
               ) ON (milestone_f.tracker_id = A.tracker_id AND milestone_f.formElement_type = 'art_link' AND use_it = 1)
               INNER JOIN (
                   tracker_changeset_value AS milestone_cv
               ) ON (milestone_cv.changeset_id = A.last_changeset_id AND milestone_cv.field_id = milestone_f.id)
               INNER JOIN (
                   tracker_changeset_value_artifactlink AS milestone_artlink
               ) ON (milestone_artlink.changeset_value_id = milestone_cv.id AND milestone_artlink.artifact_id = $milestone_id)";
    }

    /**
     * @param false|int $target_tracker_id
     *
     * @return \DataAccessResult|false
     *
     * @psalm-ignore-falsable-return
     */
    public function searchPaginatedLinkedArtifactsByLinkNatureAndTrackerId(
        array $artifacts_ids,
        string $nature,
        $target_tracker_id,
        int $limit,
        int $offset
    ) {
        $artifact_id_list  = $this->da->escapeIntImplode($artifacts_ids);
        $target_tracker_id = $this->da->escapeInt($target_tracker_id);
        $limit             = $this->da->escapeInt($limit);
        $offset            = $this->da->escapeInt($offset);
        $nature            = $this->da->quoteSmart($nature);

        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        AS f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              AS cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink AS artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     AS linked_art ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker                              AS t          ON (linked_art.tracker_id = t.id AND t.id = $target_tracker_id)
                WHERE parent_art.id IN ($artifact_id_list)
                    AND IFNULL(artlink.nature, '') = $nature
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieve($sql);
    }

    /**
     * @param false|int $campaign_tracker_id
     *
     * @return array|false
     */
    public function searchCampaignArtifactForExecution(int $execution_artifact_id, $campaign_tracker_id)
    {
        $execution_artifact_id = $this->da->escapeInt($execution_artifact_id);
        $campaign_tracker_id   = $this->da->escapeInt($campaign_tracker_id);

        $sql = "SELECT parent_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.tracker_id = $campaign_tracker_id
                  AND linked_art.id = $execution_artifact_id";

        return $this->retrieveFirstRow($sql);
    }

    /**
     * @return \DataAccessResult|false
     *
     * @psalm-ignore-falsable-return
     */
    public function searchPaginatedExecutionArtifactsForCampaign(
        int $campaign_artifact_id,
        int $execution_tracker_id,
        int $limit,
        int $offset
    ) {
        $campaign_artifact_id = $this->da->escapeInt($campaign_artifact_id);
        $execution_tracker_id = $this->da->escapeInt($execution_tracker_id);
        $limit                = $this->da->escapeInt($limit);
        $offset               = $this->da->escapeInt($offset);

        $sql = "SELECT DISTINCT SQL_CALC_FOUND_ROWS linked_art.*
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f          ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND use_it = 1)
                    INNER JOIN tracker_changeset_value              cv         ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink    ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art ON (linked_art.id = artlink.artifact_id)
                WHERE parent_art.id = $campaign_artifact_id
                  AND linked_art.tracker_id = $execution_tracker_id
                LIMIT $limit
                OFFSET $offset";

        return $this->retrieve($sql);
    }

    /**
     * @param false|int $test_exec_tracker_id
     *
     * @return array|false
     * @psalm-ignore-falsable-return
     */
    public function searchFirstRequirementId(int $test_definition_id, $test_exec_tracker_id)
    {
        $test_definition_id   = $this->da->escapeInt($test_definition_id);
        $test_exec_tracker_id = $this->da->escapeInt($test_exec_tracker_id);
        $nature               = $this->da->quoteSmart(NatureCoveredByPresenter::NATURE_COVERED_BY);

        $sql = "SELECT DISTINCT a.id
                FROM tracker_changeset_value_artifactlink AS artlink
                    JOIN tracker_changeset_value          AS cv ON (cv.id = artlink.changeset_value_id)
                    JOIN tracker_artifact                 AS a  ON (a.last_changeset_id = cv.changeset_id)
                    JOIN tracker                          AS t  ON (t.id = a.tracker_id)
                WHERE artlink.artifact_id = $test_definition_id
                  AND artlink.nature = $nature
                  AND t.id != $test_exec_tracker_id
                ORDER BY a.id ASC
                LIMIT 1";

        return $this->retrieveFirstRow($sql);
    }
}
