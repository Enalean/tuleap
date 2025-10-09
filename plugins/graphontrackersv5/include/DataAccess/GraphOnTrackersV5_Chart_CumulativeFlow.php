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

namespace Tuleap\GraphOnTrackersV5\DataAccess;

use HTML_Element_Input_Date;
use HTML_Element_Selectbox;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\GraphOnTrackersV5\Chart\Visitor;
use Tuleap\GraphOnTrackersV5\Common\HTMLSelectboxElementWithTrackerListFieldsV5;
use Tuleap\GraphOnTrackersV5\DataTransformation\ChartDataBuilderV5;
use Tuleap\GraphOnTrackersV5\DataTransformation\GraphOnTrackersV5_CumulativeFlow_DataBuilder;
use Tuleap\GraphOnTrackersV5\GraphicLibrary\GraphOnTrackersV5_Engine;
use Tuleap\GraphOnTrackersV5\GraphicLibrary\GraphOnTrackersV5_Engine_CumulativeFlow;

/**
 * Base class to provide a cumulative flow Chart
 */
class GraphOnTrackersV5_Chart_CumulativeFlow extends GraphOnTrackersV5_Chart //phpcs:ignore Squiz.Classes.ValidClassName.NotPascalCase
{
    public const int SCALE_DAY   = 0;
    public const int SCALE_WEEK  = 1;
    public const int SCALE_MONTH = 2;
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
    #[\Override]
    public function loadFromSession()
    {
        $this->report_session = self::getSession($this->renderer->report->id, $this->renderer->id);
        $chart_in_session     = $this->report_session->get($this->id);
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
    #[\Override]
    public function loadFromDb(): void
    {
        $arr              = $this->getDao()->searchById($this->id)->getRow();
        $this->field_id   = $arr['field_id'] ?? null;
        $this->start_date = $arr['start_date'] ?? null;
        $this->scale      = $arr['scale'] ?? null;
        $this->stop_date  = $arr['stop_date'] ?? null;
    }

    #[\Override]
    public function registerInSession()
    {
        parent::registerInSession();
        $this->report_session->set("$this->id.field_id", $this->field_id);
        $this->report_session->set("$this->id.start_date", $this->start_date);
        $this->report_session->set("$this->id.scale", $this->scale);
        $this->report_session->set("$this->id.stop_date", $this->stop_date);
    }

    #[\Override]
    protected function getDao()
    {
        return new GraphOnTrackersV5_Chart_CumulativeFlowDao();
    }

    #[\Override]
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
    #[\Override]
    public function getSpecificRow()
    {
        return [
            'field_id'   => $this->getFieldId(),
            'start_date' => $this->getStartDate(),
            'stop_date'  => $this->getStopDate(),
            'scale'      => $this->getScale(),
        ];
    }

    /**
     * Return the chart type (gantt, bar, pie, ...)
     */
    #[\Override]
    public function getChartType()
    {
        return 'cumulative_flow';
    }

    /**
     * @return GraphOnTrackersV5_Engine The engine associated to the concrete chart
     */
    #[\Override]
    protected function getEngine()
    {
        return new GraphOnTrackersV5_Engine_CumulativeFlow();
    }

    /**
     * @return ChartDataBuilderV5 The data builder associated to the concrete chart
     */
    #[\Override]
    protected function getChartDataBuilder($artifacts)
    {
        return new GraphOnTrackersV5_CumulativeFlow_DataBuilder($this, $artifacts);
    }

    /**
     * Allow update of the specific properties of the concrete chart
     * @return bool true if the update is successful
     */
    #[\Override]
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
    #[\Override]
    public function userCanVisualize()
    {
        $ff             = Tracker_FormElementFactory::instance();
        $observed_field = $ff->getFormElementById($this->field_id);
        if ($observed_field && $observed_field->userCanRead()) {
            return true;
        }
        return false;
    }

    /**
     * @return array of HTML_Element for properties
     */
    #[\Override]
    public function getProperties()
    {
        $scaleSelect = new HTML_Element_Selectbox(
            dgettext('tuleap-graphontrackersv5', 'Time scale'),
            'chart[scale]',
            'value'
        );
        $scaleSelect->addMultipleOptions([
            self::SCALE_DAY => dgettext('tuleap-graphontrackersv5', 'Day'),
            self::SCALE_WEEK => dgettext('tuleap-graphontrackersv5', 'Week'),
            self::SCALE_MONTH => dgettext('tuleap-graphontrackersv5', 'Month'),
        ], $this->getScale());
        return array_merge(
            parent::getProperties(),
            [
                'field_id'   => new HTMLSelectboxElementWithTrackerListFieldsV5(
                    $this->getTracker(),
                    dgettext('tuleap-graphontrackersv5', 'Source data'),
                    'chart[field_id]',
                    $this->getFieldId()
                ),
                'start_date' => new HTML_Element_Input_Date(
                    dgettext('tuleap-graphontrackersv5', 'Start date'),
                    'chart[start_date]',
                    $this->getStartDate()
                ),
                'scale'   => ( $scaleSelect),
                'stop_date'   => new HTML_Element_Input_Date(
                    dgettext('tuleap-graphontrackersv5', 'Finish date (optionnal)'),
                    'chart[stop_date]',
                    $this->getStopDate()
                ),

            ]
        );
    }

    public function createDb($id)
    {
        $field_id = $this->getFieldId();
        if (! is_int($field_id) && ! is_string($field_id) && $field_id) {
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
    #[\Override]
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
    #[\Override]
    public function arrayOfSpecificProperties()
    {
        return ['start_date' => $this->getStartDate(),
            'field_id'   => $this->getFieldId(),
            'scale'      => $this->getScale(),
            'stop_date'  => $this->getStopDate(),
        ];
    }

    #[\Override]
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

    #[\Override]
    public function accept(Visitor $visitor)
    {
        return $visitor->visitCumulativeFlowChart($this);
    }
}
