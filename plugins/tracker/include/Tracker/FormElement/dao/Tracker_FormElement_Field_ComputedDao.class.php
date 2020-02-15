<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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
 *  Data Access Object for Tracker_FormElement_Field
 */
class Tracker_FormElement_Field_ComputedDao extends Tracker_FormElement_SpecificPropertiesDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_field_computed';
    }

    public function save($field_id, $row)
    {
        $field_id = $this->da->escapeInt($field_id);

        $target_field_name = '';
        if (isset($row['target_field_name'])) {
            $target_field_name = $row['target_field_name'];
        }
        $target_field_name = $this->da->quoteSmart($target_field_name);

        $fast_compute = 0;
        if (isset($row['fast_compute'])) {
            $fast_compute = $row['fast_compute'];
        }
        $fast_compute = $this->da->escapeInt($fast_compute);

        $default_value = $this->da->escapeFloat($row['default_value'] ?? '');

        $sql = "REPLACE INTO tracker_field_computed (field_id, default_value, target_field_name, fast_compute)
                VALUES ($field_id, $default_value, $target_field_name, $fast_compute)";

        return $this->retrieve($sql);
    }

    /**
     * Duplicate specific properties of field
     *
     * @param int $from_field_id the field id source
     * @param int $to_field_id the field id target
     *
     * @return bool true if ok, false otherwise
     */
    public function duplicate($from_field_id, $to_field_id)
    {
        $from_field_id = $this->da->escapeInt($from_field_id);
        $to_field_id   = $this->da->escapeInt($to_field_id);

        $sql = "REPLACE INTO $this->table_name (field_id, target_field_name, fast_compute)
                SELECT $to_field_id, target_field_name, fast_compute FROM $this->table_name WHERE field_id = $from_field_id";

        return $this->update($sql);
    }

    /**
     * This method will fetch in 1 pass, for a given artifact all linked artifact
     * $target_name field values (values can be either float, int or computed)
     * If it's computed, the caller must continue its journey and call getComputedValue
     *
     * @param int[] $source_ids
     * @param String $target_name
     * @return DataAccessResult
     */
    public function getFieldValues(array $source_ids, $target_name)
    {
        $source_ids  = $this->da->escapeIntImplode($source_ids);
        $target_name = $this->da->quoteSmart($target_name);

        $sql = "SELECT linked_art.*,
                    f_compute.formElement_type as type,
                    cv_compute_i.value as int_value,
                    cv_compute_f.`value` as float_value,
                    tracker_field_list_bind_static_value.label as sb_value
                FROM tracker_artifact parent_art
                    INNER JOIN tracker_field                        f           ON (f.tracker_id = parent_art.tracker_id AND f.formElement_type = 'art_link' AND f.use_it = 1)
                    INNER JOIN tracker_changeset_value              cv          ON (cv.changeset_id = parent_art.last_changeset_id AND cv.field_id = f.id)
                    INNER JOIN tracker_changeset_value_artifactlink artlink     ON (artlink.changeset_value_id = cv.id)
                    INNER JOIN tracker_artifact                     linked_art  ON (linked_art.id = artlink.artifact_id)
                    INNER JOIN tracker_field                        f_compute   ON (f_compute.tracker_id = linked_art.tracker_id AND f_compute.name = $target_name AND f_compute.use_it = 1)
                    LEFT JOIN (
                        tracker_changeset_value cs_compute_i
                        INNER JOIN tracker_changeset_value_int cv_compute_i ON (cv_compute_i.changeset_value_id = cs_compute_i.id)
                    ) ON (cs_compute_i.changeset_id = linked_art.last_changeset_id AND cs_compute_i.field_id = f_compute.id)
                    LEFT JOIN (
                        tracker_changeset_value cs_compute_f
                        INNER JOIN tracker_changeset_value_float cv_compute_f ON (cv_compute_f.changeset_value_id = cs_compute_f.id)
                    ) ON (cs_compute_f.changeset_id = linked_art.last_changeset_id AND cs_compute_f.field_id = f_compute.id)
                    LEFT JOIN (
                        tracker_changeset_value AS cs_compute_list
                        INNER JOIN tracker_changeset_value_list AS cv_compute_list ON (cv_compute_list.changeset_value_id = cs_compute_list.id)
                        INNER JOIN tracker_field_list_bind_static_value
                            ON (cv_compute_list.bindvalue_id = tracker_field_list_bind_static_value.id
                                AND tracker_field_list_bind_static_value.label REGEXP '^[[:digit:]]+([.][[:digit:]]+)?$'
                            )
                    ) ON (cs_compute_list.changeset_id = linked_art.last_changeset_id AND cs_compute_list.field_id = f_compute.id)
                WHERE parent_art.id IN ($source_ids)";

        return $this->retrieve($sql);
    }

    /**
     * This method will fetch in 1 pass, for a given artifact all linked artifact
     * $target_name field values (values can be either float, int or computed or manual)
     *
     * @param int[] $source_ids
     * @param String $target_name
     * @return DataAccessResult
     */
    public function getComputedFieldValues(array $source_ids, $target_name, $field_id, $stop_on_manual_value)
    {
        $source_ids  = $this->da->escapeIntImplode($source_ids);
        $target_name = $this->da->quoteSmart($target_name);
        $field_id    = $this->da->escapeInt($field_id);

        $manual_selection = 'null';
        $manual_condition = "";
        if ($stop_on_manual_value) {
            $manual_selection = "manual.value";

            $manual_condition = "
                LEFT JOIN tracker_field manual_field
                ON (
                  manual_field.tracker_id = parent_art.tracker_id
                    AND manual_field.name   = $target_name
                    AND manual_field.use_it = 1
                )
                LEFT JOIN (
                    tracker_changeset_value value
                    INNER JOIN tracker_changeset_value_computedfield_manual_value manual
                        ON (manual.changeset_value_id = value.id )
                ) ON (
                    value.changeset_id  = parent_art.last_changeset_id
                 AND value.field_id  = manual_field.id
                )";
        }

        $sql = "SELECT linked_art.id                           AS id,
                    artifact_link_field.formElement_type       AS type,
                    integer_value.value                        AS int_value,
                    float_value.value                          AS float_value,
                    tracker_field_list_bind_static_value.label AS sb_value,
                    $manual_selection                          AS value,
                    linked_art.tracker_id                      AS tracker_id,
                    parent_art.id                              AS parent_id,
                    linked_art.id                              AS artifact_link_id,
                    parent_art.last_changeset_id
                FROM tracker_artifact parent_art
                INNER JOIN tracker_field parent_field ON (
                    parent_field.tracker_id            = parent_art.tracker_id
                    AND (parent_field.formElement_type = 'art_link' OR parent_field.formElement_type = 'computed')
                    AND parent_field.use_it            = 1
                )
                INNER JOIN tracker_changeset_value parent_value ON (
                    parent_value.changeset_id = parent_art.last_changeset_id
                    AND parent_value.field_id = parent_field.id
                )
                LEFT JOIN tracker_changeset_value_artifactlink      artifact_link_value
                    INNER JOIN tracker_artifact                     linked_art
                        ON (linked_art.id = artifact_link_value.artifact_id)
                ON (
                    artifact_link_value.changeset_value_id = parent_value.id
                    AND parent_value.changeset_id          = parent_art.last_changeset_id
                    AND parent_value.field_id              = parent_field.id
                    AND parent_field.tracker_id            = parent_art.tracker_id
                )
                LEFT JOIN tracker_field                        artifact_link_field
                ON (
                    artifact_link_field.tracker_id = linked_art.tracker_id
                    AND artifact_link_field.name   = $target_name
                    AND artifact_link_field.use_it = 1
                )
                $manual_condition
                LEFT JOIN (
                    tracker_changeset_value integer_changeset
                    INNER JOIN tracker_changeset_value_int integer_value
                        ON (integer_value.changeset_value_id = integer_changeset.id)
                ) ON (
                    integer_changeset.changeset_id = linked_art.last_changeset_id
                    AND integer_changeset.field_id = artifact_link_field.id
                )
                LEFT JOIN (
                    tracker_changeset_value float_changeset
                    INNER JOIN tracker_changeset_value_float float_value
                        ON (float_value.changeset_value_id = float_changeset.id)
                ) ON (
                    float_changeset.changeset_id = linked_art.last_changeset_id
                    AND float_changeset.field_id = artifact_link_field.id
                )
                LEFT JOIN (
                    tracker_changeset_value AS cs_compute_list
                    INNER JOIN tracker_changeset_value_list AS cv_compute_list ON (cv_compute_list.changeset_value_id = cs_compute_list.id)
                    INNER JOIN tracker_field_list_bind_static_value
                        ON (cv_compute_list.bindvalue_id = tracker_field_list_bind_static_value.id
                            AND tracker_field_list_bind_static_value.label REGEXP '^[[:digit:]]+([.][[:digit:]]+)?$'
                        )
                ) ON (
                    cs_compute_list.changeset_id = linked_art.last_changeset_id
                    AND cs_compute_list.field_id = artifact_link_field.id
                )
                WHERE parent_art.id IN ($source_ids)
                ORDER BY value DESC, parent_id DESC";

        return $this->retrieve($sql);
    }

    public function getBurndownManualValueAtGivenTimestamp($artifact_id, $timestamp)
    {
        $artifact_id               = $this->da->escapeInt($artifact_id);
        $timestamp                 = $this->da->escapeInt($timestamp);

        $sql = "SELECT
                    tracker_changeset.submitted_on           AS last_changeset_date,
                    manual_value.value                       AS value
                FROM tracker_artifact artifact
                INNER JOIN tracker_field AS remaining_effort_field
                    ON (
                        remaining_effort_field.tracker_id = artifact.tracker_id
                        AND remaining_effort_field.formElement_type = 'computed'
                        AND remaining_effort_field.use_it = 1
                        AND remaining_effort_field.name = 'remaining_effort'
                    )
                INNER JOIN tracker_changeset
                    ON (
                        tracker_changeset.artifact_id = artifact.id
                        AND tracker_changeset.submitted_on <= $timestamp
                    )
                INNER JOIN tracker_changeset_value
                    ON (
                        tracker_changeset_value.changeset_id = tracker_changeset.id
                        AND tracker_changeset_value.field_id = remaining_effort_field.id
                    )
                LEFT JOIN tracker_changeset_value_computedfield_manual_value manual_value
                    ON (
                        manual_value.changeset_value_id = tracker_changeset_value.id
                        AND tracker_changeset_value.changeset_id = tracker_changeset.id
                    )
                WHERE artifact.id = $artifact_id
                ORDER BY last_changeset_date DESC
                LIMIT 1";

        return $this->retrieveFirstRow($sql);
    }

    public function getBurndownComputedValueAtGivenTimestamp(array $artifacts_id, $timestamp)
    {
        $artifacts_id = $this->da->escapeIntImplode($artifacts_id);
        $timestamp    = $this->da->escapeInt($timestamp);

        $sql = "SELECT parent_art.id                               AS id,
                        computed_field.formElement_type            AS type,
                        cv_compute_i.value                         AS int_value,
                        cv_compute_f.value                         AS float_value,
                        linked_art.id                              AS artifact_link_id,
                        tracker_field_list_bind_static_value.label AS sb_value
                    FROM tracker_artifact parent_art
                    INNER JOIN tracker_changeset                    AS cs_parent_art1
                      ON (
                        cs_parent_art1.artifact_id = parent_art.id
                        AND cs_parent_art1.submitted_on <= $timestamp
                      )
                    LEFT JOIN  tracker_changeset                    AS cs_parent_art2
                        ON (
                            cs_parent_art2.artifact_id = parent_art.id
                            AND cs_parent_art1.id < cs_parent_art2.id
                            AND cs_parent_art2.submitted_on <= $timestamp
                        )
                    LEFT JOIN tracker_field                        AS field_artifact_link
                        ON (
                            field_artifact_link.tracker_id = parent_art.tracker_id
                            AND field_artifact_link.formElement_type = 'art_link'
                            AND field_artifact_link.use_it = 1
                        )
                    LEFT JOIN tracker_changeset_value              AS changeset_artifact_link
                        ON (
                            changeset_artifact_link.changeset_id = cs_parent_art1.id
                            AND changeset_artifact_link.field_id = field_artifact_link.id
                        )
                    LEFT JOIN tracker_changeset_value_artifactlink AS artlink
                        ON (artlink.changeset_value_id = changeset_artifact_link.id)
                    LEFT JOIN tracker_artifact                     AS linked_art
                        ON (linked_art.id = artlink.artifact_id)
                    LEFT JOIN tracker_changeset                    AS cs_linked_art1
                        ON (
                            cs_linked_art1.artifact_id = linked_art.id
                            AND cs_linked_art1.submitted_on <= $timestamp
                        )
                    LEFT JOIN  tracker_changeset                    AS cs_linked_art2
                        ON (
                            cs_linked_art2.artifact_id = linked_art.id
                            AND cs_linked_art1.id < cs_linked_art2.id
                            AND cs_linked_art2.submitted_on <= $timestamp
                        )
                    INNER JOIN tracker_field                        AS computed_field
                    ON (
                        computed_field.tracker_id = parent_art.tracker_id
                        AND computed_field.name = 'remaining_effort'
                        AND computed_field.use_it = 1
                    )
                    LEFT JOIN tracker_changeset_value              AS computed_changeset
                    ON (
                        computed_changeset.changeset_id = cs_parent_art1.id
                        AND computed_changeset.field_id = computed_field.id
                    )
                    LEFT JOIN tracker_changeset_value_int cv_compute_i
                    ON (
                        cv_compute_i.changeset_value_id = computed_changeset.id
                        AND computed_changeset.field_id = computed_field.id
                    )
                    LEFT JOIN tracker_changeset_value_float cv_compute_f
                    ON (
                        cv_compute_f.changeset_value_id = computed_changeset.id
                        AND computed_changeset.field_id = computed_field.id
                    )
                    LEFT JOIN tracker_changeset_value_list AS cv_compute_list
                      ON (
                        cv_compute_list.changeset_value_id = computed_changeset.id
                        AND computed_changeset.field_id = computed_field.id
                    )
                    LEFT JOIN tracker_field_list_bind_static_value
                        ON (cv_compute_list.bindvalue_id = tracker_field_list_bind_static_value.id
                            AND tracker_field_list_bind_static_value.label REGEXP '^[[:digit:]]+([.][[:digit:]]+)?$'
                        )
                WHERE parent_art.id IN ($artifacts_id)
                AND cs_parent_art2.id IS NULL
                AND cs_linked_art2.id IS NULL";

        return $this->retrieve($sql);
    }

    public function getCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);

        $date = new DateTime();
        $date->setTimestamp($timestamp);
        $date->setTime(0, 0, 0);
        $start_date = $this->da->escapeInt($date->getTimestamp());
        $date->setTime(23, 59, 59);
        $end_date = $this->da->escapeInt($date->getTimestamp());

        $sql = "SELECT value FROM tracker_field_computed_cache
                WHERE  artifact_id= $artifact_id
                    AND timestamp BETWEEN $start_date AND $end_date
                    AND field_id  = $field_id";

        return $this->retrieveFirstRow($sql);
    }

    public function saveCachedFieldValueAtTimestamp($artifact_id, $field_id, $timestamp, $value)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);
        $timestamp   = $this->da->escapeInt($timestamp);

        if ($value === null) {
            $sql = "REPLACE INTO tracker_field_computed_cache (artifact_id, field_id, timestamp)
                        VALUES ($artifact_id, $field_id, $timestamp)";
        } else {
            $value = $this->da->quoteSmart($value);
            $sql   = "REPLACE INTO tracker_field_computed_cache (artifact_id, field_id, timestamp, value)
                    VALUES ($artifact_id, $field_id, $timestamp, $value)";
        }

        return $this->update($sql);
    }

    public function getCachedDays($artifact_id, $field_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);
        $sql         = "SELECT count(artifact_id) AS cached_days
                          FROM tracker_field_computed_cache
                          WHERE artifact_id = $artifact_id
                          AND field_id      = $field_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchCachedDays($artifact_id, $field_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);
        $sql         = "SELECT *
                          FROM tracker_field_computed_cache
                          WHERE artifact_id = $artifact_id
                          AND field_id      = $field_id";

        return $this->retrieve($sql);
    }

    public function deleteArtifactCacheValue($artifact_id, $field_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $field_id    = $this->da->escapeInt($field_id);
        $sql         = "DELETE FROM tracker_field_computed_cache
                          WHERE artifact_id = $artifact_id
                          AND field_id      = $field_id";

        return $this->update($sql);
    }

    public function deleteAllArtifactCacheValues($artifact_id)
    {
        $artifact_id = $this->da->escapeInt($artifact_id);
        $sql         = "DELETE FROM tracker_field_computed_cache
                        WHERE artifact_id = $artifact_id";

        return $this->update($sql);
    }
}
