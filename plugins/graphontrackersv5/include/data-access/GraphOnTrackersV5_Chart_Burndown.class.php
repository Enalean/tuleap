<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox, 2008. All Rights Reserved.
 *
 * Originally written by Nicolas Terray, 2008. Xerox Codendi Team.
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

use Tuleap\GraphOnTrackersV5\Chart\Visitor;

require_once('GraphOnTrackersV5_Chart.class.php');
require_once(dirname(__FILE__) . '/../data-transformation/GraphOnTrackersV5_Burndown_DataBuilder.class.php');
require_once(dirname(__FILE__) . '/../graphic-library/GraphOnTrackersV5_Engine_Burndown.class.php');
require_once('GraphOnTrackersV5_Chart_BurndownDao.class.php');
require_once(dirname(__FILE__) . '/../common/HTML_Element_Selectbox_TrackerFields_NumericFieldsV5.class.php');

/**
 * Base class to provide a Scrum Burndown Chart
 */
class GraphOnTrackersV5_Chart_Burndown extends GraphOnTrackersV5_Chart
{
    /**
     * The date (timestamp) the sprint start
     */
    protected $start_date;
    public function getStartDate()
    {
        return $this->start_date;
    }
    public function setStartDate($start_date)
    {
        return $this->start_date = $start_date;
    }

    /**
     * The duration of the sprint
     */
    protected $duration;
    public function getDuration()
    {
        return $this->duration;
    }
    public function setDuration($duration)
    {
        return $this->duration = $duration;
    }

    /**
     * The effort field id
     */
    protected $field_id;
    public function getFieldId()
    {
        return $this->field_id;
    }
    public function setFieldId($field_id)
    {
        return $this->field_id = $field_id;
    }

    /**
     * class constructor: use parent one
     *
     */

    /**
     * Load object from session
     */
    public function loadFromSession()
    {
        $this->report_session = self::getSession($this->renderer->report->id, $this->renderer->id);
        $chart_in_session = $this->report_session->get($this->id);
        if (isset($chart_in_session['field_id']) && $chart_in_session['field_id'] !== '') {
            $this->field_id   = $chart_in_session['field_id'];
            $this->start_date = $chart_in_session['start_date'];
            $this->duration   = $chart_in_session['duration'];
        } else {
            $this->loadFromDb();
            $this->registerInSession();
        }
    }

    /**
     * Load object from DB
     */
    public function loadFromDb()
    {
        $arr = $this->getDao()->searchById($this->id)->getRow();
        $this->field_id   = $arr['field_id'];
        $this->start_date = $arr['start_date'];
        $this->duration   = $arr['duration'];
    }

    public function registerInSession()
    {
        parent::registerInSession();
        $this->report_session->set("$this->id.field_id", $this->field_id);
        $this->report_session->set("$this->id.start_date", $this->start_date);
        $this->report_session->set("$this->id.duration", $this->duration);
    }

    protected function getDao()
    {
        return new GraphOnTrackersV5_Chart_BurndownDao();
    }

    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height)
    {
        $session = self::getSession($graphic_report->report->id, $graphic_report->id);

        $session->set("$id.field_id", 0);
        $session->set("$id.start_date", 0);
        $session->set("$id.duration", 0);
        $c = new GraphOnTrackersV5_Chart_Burndown($graphic_report, $id, $rank, $title, $description, $width, $height);
        $c->registerInSession();
        return $c;
    }

    /**
     * Return the specific properties as a row
     * array('prop1' => 'value', 'prop2' => 'value', ...)
     * @return array
     */
    public function getSpecificRow()
    {
        return array(
            'field_id'   => $this->getFieldId(),
            'start_date' => $this->getStartDate(),
            'duration'   => $this->getDuration(),
        );
    }

    /**
     * Return the chart type (gantt, bar, pie, ...)
     */
    public function getChartType()
    {
        return "burndown";
    }

    /**
     * @return GraphOnTrackersV5_Engine The engine associated to the concrete chart
     */
    protected function getEngine()
    {
        return new GraphOnTrackersV5_Engine_Burndown();
    }

    /**
     * @return ChartDataBuilderV5 The data builder associated to the concrete chart
     */
    protected function getChartDataBuilder($artifacts)
    {
        return new GraphOnTrackersV5_Burndown_DataBuilder($this, $artifacts);
    }

    /**
     * Allow update of the specific properties of the concrete chart
     * @return bool true if the update is successful
     */
    protected function updateSpecificProperties($row)
    {
        $session = self::getSession($this->renderer->report->id, $this->renderer->id);

        $session->set("$this->id.field_id", $row['field_id']);
        $session->set("$this->id.start_date", strtotime($row['start_date']));
        $session->set("$this->id.duration", $row['duration']);

        $session->setHasChanged();

        $this->setFieldId($row['field_id']);
        $this->setStartDate(strtotime($row['start_date']));
        $this->setDuration($row['duration']);

        return true;
    }

    /**
     * User as permission to visualize the chart
     */
    public function userCanVisualize()
    {
        $ff = Tracker_FormElementFactory::instance();
        $effort_field = $ff->getFormElementById($this->field_id);
        if ($effort_field && $effort_field->userCanRead()) {
            return true;
        }
        return false;
    }

    /**
     * @return array of HTML_Element for properties
     */
    public function getProperties()
    {
        return array_merge(
            parent::getProperties(),
            array(
                'field_id'   => new HTML_Element_Selectbox_TrackerFields_NumericFieldsV5(
                    $this->getTracker(),
                    $GLOBALS['Language']->getText('plugin_graphontrackersv5_scrum', 'burndown_property_effort'),
                    'chart[field_id]',
                    $this->getFieldId()
                ),
                'start_date' => new HTML_Element_Input_Date(
                    $GLOBALS['Language']->getText('plugin_graphontrackersv5_scrum', 'burndown_property_start_date'),
                    'chart[start_date]',
                    $this->getStartDate()
                ),
                'duration'   => new HTML_Element_Input_Text(
                    $GLOBALS['Language']->getText('plugin_graphontrackersv5_scrum', 'burndown_property_duration'),
                    'chart[duration]',
                    $this->getDuration(),
                    4
                )
            )
        );
    }

    public function createDb($id)
    {
        $field_id   = $this->getFieldId();
        if (!is_int($field_id) && !is_string($field_id) && $field_id) {
            $field_id = $field_id->getid();
        }
        $start_date = $this->getStartDate();
        $duration   = $this->getDuration();
        return $this->getDao()->save($id, $field_id, $start_date, $duration);
    }

    public function updateDb()
    {
        $field_id   = $this->getFieldId();
        $start_date = $this->getStartDate();
        $duration   = $this->getDuration();
        return $this->getDao()->save($this->id, $field_id, $start_date, $duration);
    }

    /**
     * Sets the specific properties of the concrete chart from XML
     *
     * @param SimpleXMLElement $xml characterising the chart
     * @param array $formsMapping associating xml IDs to real fields
     */
    public function setSpecificPropertiesFromXML($xml, $formsMapping)
    {
        if ($xml['start_date']) {
            $this->setStartDate((int) $xml['start_date']);
        }
        if ($xml['duration']) {
            $this->setDuration((int) $xml['duration']);
        }
        if (isset($formsMapping[(int) $xml['field_id']])) {
            $this->setFieldId($formsMapping[(int) $xml['field_id']]);
        }
    }

    /**
     * Creates an array of specific properties of this chart
     *
     * @return array containing the properties
     */
    public function arrayOfSpecificProperties()
    {
        return array('start_date' => $this->getStartDate(),
                     'field_id' => $this->getFieldId(),
                     'duration' => $this->getDuration());
    }

    public function exportToXml(SimpleXMLElement $root, $formsMapping)
    {
        parent::exportToXML($root, $formsMapping);
        if ($this->start_date) {
            $root->addAttribute('start_date', $this->start_date);
        }
        if ($this->duration) {
            $root->addAttribute('duration', $this->duration);
        }
        if ($this->field_id) {
            $root->addAttribute('effort_field', array_search($this->field_id, $formsMapping));
        }
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitBurndown($this);
    }
}
