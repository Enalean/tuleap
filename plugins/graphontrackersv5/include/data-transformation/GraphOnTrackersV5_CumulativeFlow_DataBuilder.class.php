<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 *
 * Originally written by Yoann Celton, 2013. Jtekt Europe SAS.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\GraphOnTrackersV5\DataTransformation\CumulativeFlowColumnBuilder;

class GraphOnTrackersV5_CumulativeFlow_DataBuilder extends ChartDataBuilderV5
{

    public const MAX_STEPS = 75;
    protected $timeFiller;
    /**
     * @var int
     * (timestamp)
     */
    protected $startDate;
    protected $stopDate;
    /**
     * @var int
     * 0 - Day
     * 1 - Week
     * 2 - Month
     */
    protected $scale;
    /**
     * @var int
     */
    protected $nbSteps;
    protected $labels;
    protected $observed_field_id;
    protected $observed_field;

    /**
     * build cumulative_flow chart properties
     *
     * @param GraphOnTrackersV5_Engine_CumulativeFlow $engine object
     */
    public function buildProperties($engine)
    {
        parent::buildProperties($engine);

        $form_element_factory = Tracker_FormElementFactory::instance();
        $this->observed_field = $form_element_factory->getFormElementById($this->chart->getFieldId());
        $type                 = $form_element_factory->getType($this->observed_field);
        $this->observed_field_id = $this->observed_field->getId();
        $this->timeFiller = [GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_DAY => 3600 * 24,
            GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_WEEK => 3600 * 24 * 7,
            GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_MONTH => 3600 * 24 * 30.45
        ];
        $this->startDate = $this->chart->getStartDate();
        $this->stopDate = $this->chart->getStopDate() ? $this->chart->getStopDate() : time();
        $this->scale = $this->chart->getScale();
        $this->nbSteps = (int) ceil(($this->stopDate - $this->startDate) / $this->timeFiller[$this->scale]);

        if ($this->isValidObservedField($this->observed_field, $type) && $this->isValidType($type)) {
            $engine->data = $this->getCumulativeFlowData($engine);
        }

        $engine->legend     = null;
        $engine->start_date = $this->chart->getStartDate();
        $engine->scale      = (int) $this->chart->getScale();
        $engine->stop_date  = $this->chart->getStopDate();
    }

    private function getCumulativeFlowColumnBuilder(): CumulativeFlowColumnBuilder
    {
        return CumulativeFlowColumnBuilder::build();
    }

    protected function getCumulativeFlowData($engine)
    {
        if ($this->nbSteps > GraphOnTrackersV5_CumulativeFlow_DataBuilder::MAX_STEPS) {
            $engine->setError(
                dgettext('tuleap-graphontrackersv5', 'Please choose a smaller period, or increase the scale.')
            );

            return [];
        }

        $empty_columns = $this->getCumulativeFlowColumnBuilder()->initEmptyColumns(
            (int) $this->observed_field_id,
            $this->startDate,
            $this->nbSteps,
            $this->timeFiller,
            (int) $this->scale
        );

        for ($i = 0; $i <= $this->nbSteps; $i++) {
            $timestamp = $this->startDate + ($i * $this->timeFiller[$this->scale]);
            $changesets = $this->getLastChangesetsBefore($timestamp);

            // Count the number of occurence of each label of the source field at the given date.
            // Return {Label, count}
            $sql = "SELECT l.bindvalue_id, count(*) as count
                    FROM `tracker_changeset` as c
                    JOIN `tracker_changeset_value` v ON (v.changeset_id = c.id AND v.field_id = $this->observed_field_id )
                    JOIN tracker_changeset_value_list l ON (l.changeset_value_id = v.id)
                    WHERE artifact_id in (" . $this->artifacts['id'] . ")
                    AND c.id IN (" . implode(',', $changesets) . ")
                    GROUP BY l.bindvalue_id";

            $res = db_query($sql);
            while ($data = db_fetch_array($res)) {
                if (array_key_exists((int) $data['bindvalue_id'], $empty_columns)) {
                    $empty_columns[$data['bindvalue_id']]['values'][$timestamp]['count'] = (int) $data['count'];
                }
            }
        }

        return $this->getColumns($empty_columns);
    }

    public function getColumns(array $data)
    {
        $report_filter = $this->getReportFilter();

        $columns = [];
        foreach ($data as $column_id => $column) {
            if (count($report_filter) > 0 && ! in_array($column_id, $report_filter)) {
                continue;
            }

            $values = array_values($column['values']);

            if (! $this->isColumnEmpty($values)) {
                $column['values'] = $values;
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function getReportFilter(): array
    {
        $tracker_report = $this->chart->getRenderer()->getReport();
        assert($tracker_report instanceof Tracker_Report);

        $report_filter = [];
        foreach ($tracker_report->getCriteria() as $criterion) {
            $criterion_field = $criterion->getField();
            if ((int) $criterion_field->getId() === (int) $this->chart->getFieldId()) {
                $criterion_value = $criterion_field->getCriteriaValue($criterion);
                if (is_array($criterion_value)) {
                    $report_filter = $criterion_value;
                }
                break;
            }
        }

        return $report_filter;
    }

    private function isColumnEmpty(array $column_values)
    {
        $counts = array_map(
            function ($value) {
                return $value['count'];
            },
            $column_values
        );

        return array_sum($counts) === 0;
    }

    protected function isValidObservedField($observed_field, $type)
    {
        return $observed_field && $observed_field->userCanRead(UserManager::instance()->getCurrentUser());
    }

    /**
     * Autorized types for observed field type
     *
     * @var array
     */
    protected function isValidType($type)
    {
        return in_array($type, ['sb', 'msb', 'cb']);
    }

    /**
     *
     * Get the the last changeset BEFORE the timestamp for each artifact
     *
     * @param int $timestamp
     *
     * @return array $changesets array of changeset_id
     */
    private function getLastChangesetsBefore($timestamp)
    {
        $sql = "SELECT MAX(id) as id
            FROM `tracker_changeset` c
            WHERE c.submitted_on < $timestamp
            AND c.artifact_id IN (" . $this->artifacts['id'] . ")
            GROUP BY artifact_id";

        $res        = db_query($sql);
        $changesets = [];
        while ($data = db_fetch_array($res)) {
            $changesets[] = $data['id'];
        }

        return $changesets;
    }
}
