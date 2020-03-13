<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
 *
 * Originally written by Yoann CELTON, 2013. Jtekt Europe SAS.
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

use Tuleap\GraphOnTrackersV5\Chart\Visitor;

require_once('GraphOnTrackersV5_Chart.class.php');
require_once(dirname(__FILE__) . '/../data-transformation/GraphOnTrackersV5_CumulativeFlow_DataBuilder.class.php');
require_once(dirname(__FILE__) . '/../graphic-library/GraphOnTrackersV5_Engine_CumulativeFlow.class.php');
require_once('GraphOnTrackersV5_Chart_CumulativeFlowDao.class.php');
require_once(dirname(__FILE__) . '/../common/HTML_Element_Selectbox_TrackerFields_NumericFieldsV5.class.php');

/**
 * Base class to provide a cumulative flow Chart
 */
class GraphOnTrackersV5_Chart_CumulativeFlow extends GraphOnTrackersV5_Chart
{
    public const SCALE_DAY = 0;
    public const SCALE_WEEK = 1;
    public const SCALE_MONTH = 2;
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
     * The unit of the duration
     */
    protected $scale;
    public function getScale()
    {
        return $this->scale;
    }
    public function setScale($scale)
    {
        return $this->scale = $scale;
    }

    /**
     * The date (timestamp) the sprint stop
     */
    protected $stop_date;
    public function getStopDate()
    {
        return $this->stop_date;
    }
    public function setStopDate($stop_date)
    {
        return $this->stop_date = $stop_date;
    }

    /**
     * The observed field id
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
            $this->scale      = $chart_in_session['scale'];
            $this->stop_date  = $chart_in_session['stop_date'];
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
        $this->scale      = $arr['scale'];
        $this->stop_date  = $arr['stop_date'];
    }

    public function registerInSession()
    {
        parent::registerInSession();
        $this->report_session->set("$this->id.field_id", $this->field_id);
        $this->report_session->set("$this->id.start_date", $this->start_date);
        $this->report_session->set("$this->id.scale", $this->scale);
        $this->report_session->set("$this->id.stop_date", $this->stop_date);
    }

    protected function getDao()
    {
        return new GraphOnTrackersV5_Chart_CumulativeFlowDao();
    }

    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height)
    {
        $session = self::getSession($graphic_report->report->id, $graphic_report->id);

        $session->set("$id.field_id", 0);
        $session->set("$id.start_date", 0);
        $session->set("$id.stop_date", 0);
        $session->set("$id.scale", 0);
        $c = new GraphOnTrackersV5_Chart_CumulativeFlow($graphic_report, $id, $rank, $title, $description, $width, $height);
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
            'stop_date'  => $this->getStopDate(),
            'scale'      => $this->getScale(),
        );
    }

    /**
     * Return the chart type (gantt, bar, pie, ...)
     */
    public function getChartType()
    {
        return "cumulative_flow";
    }

    /**
     * @return GraphOnTrackersV5_Engine The engine associated to the concrete chart
     */
    protected function getEngine()
    {
        return new GraphOnTrackersV5_Engine_CumulativeFlow();
    }

    /**
     * @return ChartDataBuilderV5 The data builder associated to the concrete chart
     */
    protected function getChartDataBuilder($artifacts)
    {
        return new GraphOnTrackersV5_CumulativeFlow_DataBuilder($this, $artifacts);
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
        $session->set("$this->id.stop_date", strtotime($row['stop_date']));
        $session->set("$this->id.scale", $row['scale']);

        $session->setHasChanged();

        $this->setFieldId($row['field_id']);
        $this->setStartDate(strtotime($row['start_date']));
        $this->setScale($row['scale']);
        $this->setStopDate(strtotime($row['stop_date']));

        return true;
    }

    /**
     * User as permission to visualize the chart
     */
    public function userCanVisualize()
    {
        $ff = Tracker_FormElementFactory::instance();
        $observed_field = $ff->getFormElementById($this->field_id);
        if ($observed_field && $observed_field->userCanRead()) {
            return true;
        }
        return false;
    }

    /**
     * @return array of HTML_Element for properties
     */
    public function getProperties()
    {
        $scaleSelect = new HTML_Element_Selectbox(
            $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'cumulative_flow_property_scale'),
            'chart[scale]',
            'value'
        );
        $scaleSelect->addMultipleOptions(array(
                                              GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_DAY => $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'cumulative_flow_property_day'),
                                              GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_WEEK => $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'cumulative_flow_property_week'),
                                              GraphOnTrackersV5_Chart_CumulativeFlow::SCALE_MONTH => $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'cumulative_flow_property_month'),
                                              ), $this->getScale());
        return array_merge(
            parent::getProperties(),
            array(
                'field_id'   => new HTML_Element_Selectbox_TrackerFields_SelectboxesV5(
                    $this->getTracker(),
                    $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'cumulative_flow_property_field'),
                    'chart[field_id]',
                    $this->getFieldId()
                ),
                'start_date' => new HTML_Element_Input_Date(
                    $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'cumulative_flow_property_start_date'),
                    'chart[start_date]',
                    $this->getStartDate()
                ),
                    'scale'   => ( $scaleSelect),
                'stop_date'   => new HTML_Element_Input_Date(
                    $GLOBALS['Language']->getText('plugin_graphontrackersv5_cumulative_flow', 'cumulative_flow_property_stop_date'),
                    'chart[stop_date]',
                    $this->getStopDate()
                ),

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
        $scale      = $this->getScale();
        $stop_date  = $this->getStopDate();
        return $this->getDao()->save($id, $field_id, $start_date, $stop_date, $scale);
    }

    public function updateDb()
    {
        $field_id   = $this->getFieldId();
        $start_date = $this->getStartDate();
        $scale      = $this->getScale();
        $stop_date  = $this->getStopDate();
        return $this->getDao()->save($this->id, $field_id, $start_date, $stop_date, $scale);
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
        if ($xml['scale']) {
            $this->setScale((int) $xml['scale']);
        }
        if ($xml['stop_date']) {
            $this->setStopDate((int) $xml['stop_date']);
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
                     'field_id'   => $this->getFieldId(),
                     'scale'      => $this->getScale(),
                     'stop_date'  => $this->getStopDate());
    }

    public function exportToXml(SimpleXMLElement $root, $formsMapping)
    {
        parent::exportToXML($root, $formsMapping);
        if ($this->start_date) {
            $root->addAttribute('start_date', $this->start_date);
        }
        if ($this->stop_date) {
            $root->addAttribute('stop_date', $this->stop_date);
        }
        if ($this->scale) {
            $root->addAttribute('scale', $this->scale);
        }
        if ($this->field_id) {
            $root->addAttribute('effort_field', array_search($this->field_id, $formsMapping));
        }
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitCumulativeFlowChart($this);
    }
}
