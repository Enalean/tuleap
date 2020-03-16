<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) cjt Systemsoftware AG, 2017. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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

class GraphOnTrackersV5_Chart_Gantt extends GraphOnTrackersV5_Chart
{
    protected $field_start;
    protected $field_due;
    protected $field_finish;
    protected $field_percentage;
    protected $field_righttext;
    protected $scale;
    protected $as_of_date;
    protected $summary;

    public function loadFromSession()
    {
        $this->report_session = self::getSession($this->renderer->report->id, $this->renderer->id);
        $chart_in_session = $this->report_session->get($this->id);
        if (isset($chart_in_session['field_start']) && $chart_in_session['field_start'] !== '') {
                $this->field_start      = $chart_in_session['field_start'];
                $this->field_due        = $chart_in_session['field_due'];
                $this->field_finish     = $chart_in_session['field_finish'];
                $this->field_percentage = $chart_in_session['field_percentage'];
                $this->field_righttext  = $chart_in_session['field_righttext'];
                $this->scale            = $chart_in_session['scale'];
                $this->as_of_date       = $chart_in_session['as_of_date'];
                $this->summary          = $chart_in_session['summary'];
        } else {
            $this->loadFromDb();
            $this->registerInSession();
        }
    }

    public function loadFromDb()
    {
        $arr = $this->getDao()->searchById($this->id)->getRow();
        $this->field_start      = $arr['field_start'];
        $this->field_due        = $arr['field_due'];
        $this->field_finish     = $arr['field_finish'];
        $this->field_percentage = $arr['field_percentage'];
        $this->field_righttext  = $arr['field_righttext'];
        $this->scale            = $arr['scale'];
        $this->as_of_date       = 0;
        if ($arr['as_of_date'] != 0) {
            $this->as_of_date       = date('Y-m-d', $arr['as_of_date']);
        }
        $this->summary          = $arr['summary'];
    }

    public function registerInSession()
    {
        parent::registerInSession();
        $this->report_session->set("$this->id.field_start", $this->field_start);
        $this->report_session->set("$this->id.field_due", $this->field_due);
        $this->report_session->set("$this->id.field_finish", $this->field_finish);
        $this->report_session->set("$this->id.field_percentage", $this->field_percentage);
        $this->report_session->set("$this->id.field_righttext", $this->field_righttext);
        $this->report_session->set("$this->id.scale", $this->scale);
        $this->report_session->set("$this->id.as_of_date", $this->as_of_date);
        $this->report_session->set("$this->id.summary", $this->summary);
    }

    protected function getDao()
    {
        return new GraphOnTrackersV5_Chart_GanttDao();
    }

    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height)
    {
        $session = self::getSession($graphic_report->report->id, $graphic_report->id);

        $session->set("$id.field_start", '');
        $session->set("$id.field_due", '');
        $session->set("$id.field_finish", '');
        $session->set("$id.field_percentage", '');
        $session->set("$id.field_righttext", '');
        $session->set("$id.scale", '');
        $session->set("$id.as_of_date", '');
        $session->set("$id.summary", '');

        $c = new GraphOnTrackersV5_Chart_Gantt($graphic_report, $id, $rank, $title, $description, $width, $height);
        $c->registerInSession();
        return $c;
    }

    public function setSession($graphic_report, $title, $description, $width, $height)
    {
        //TODO
    }

    public function getField_start()
    {
        return $this->field_start;
    }
    public function setField_start($field_start)
    {
        return $this->field_start = $field_start;
    }
    public function getField_due()
    {
        return $this->field_due;
    }
    public function setField_due($field_due)
    {
        return $this->field_due = $field_due;
    }
    public function getField_finish()
    {
        return $this->field_finish;
    }
    public function setField_finish($field_finish)
    {
        return $this->field_finish = $field_finish;
    }
    public function getField_percentage()
    {
        return $this->field_percentage;
    }
    public function setField_percentage($field_percentage)
    {
        return $this->field_percentage = $field_percentage;
    }
    public function getField_righttext()
    {
        return $this->field_righttext;
    }
    public function setField_righttext($field_righttext)
    {
        return $this->field_righttext = $field_righttext;
    }
    public function getScale()
    {
        return $this->scale;
    }
    public function setScale($scale)
    {
        return $this->scale = $scale;
    }
    public function getAs_of_date()
    {
        return $this->as_of_date;
    }
    public function setAs_of_date($as_of_date)
    {
        return $this->as_of_date = $as_of_date;
    }
    public function getSummary()
    {
        return $this->summary;
    }
    public function setSummary($summary)
    {
        return $this->summary = $summary;
    }
    public static function getDefaultHeight()
    {
        return 0;
    }
    public static function getDefaultWidth()
    {
        return 0;
    }

    protected function getEngine()
    {
        return new GraphOnTrackersV5_Engine_Gantt();
    }
    protected function getChartDataBuilder($artifacts)
    {
        return new GraphOnTrackersV5_Chart_GanttDataBuilder($this, $artifacts);
    }

    public function getProperties()
    {
        $parent_properties = parent::getProperties();
        unset($parent_properties['dimensions']);
        return array_merge(
            $parent_properties,
            array(
                new HTML_Element_Columns(
                    new HTML_Element_Selectbox_TrackerFields_DatesV5($this->getTracker(), $GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_field_start'), 'chart[field_start]', $this->getField_start()),
                    new HTML_Element_Selectbox_TrackerFields_DatesV5($this->getTracker(), $GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_field_due'), 'chart[field_due]', $this->getField_due(), true),
                    new HTML_Element_Selectbox_TrackerFields_DatesV5($this->getTracker(), $GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_field_finish'), 'chart[field_finish]', $this->getField_finish())
                ),
                new HTML_Element_Columns(
                    new HTML_Element_Selectbox_TrackerFields_SelectboxesAndTextsV5($this->getTracker(), $GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_summary'), 'chart[summary]', $this->getSummary()),
                    new HTML_Element_Selectbox_TrackerFields_Int_TextFieldsV5($this->getTracker(), $GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_field_percentage'), 'chart[field_percentage]', $this->getField_percentage(), true),
                    new HTML_Element_Selectbox_Scale($GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_scale'), 'chart[scale]', $this->getScale())
                ),

                new HTML_Element_Columns(
                    new HTML_Element_Input_Date($GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_as_of_date'), 'chart[as_of_date]', strtotime($this->getAs_of_date())),
                    new HTML_Element_Selectbox_TrackerFields_SelectboxesAndTextsV5($this->getTracker(), $GLOBALS['Language']->getText('plugin_graphontrackersv5_gantt_property', 'gantt_field_righttext'), 'chart[field_righttext]', $this->getField_righttext(), true)
                ),
            )
        );
    }

    public function createDb($id)
    {
        $field_start = $this->getField_start();
        if (!is_string($field_start) && !is_int($field_start) && $field_start) {
            $field_start = $field_start->getid();
        }

        $field_due = $this->getField_due();
        if (!is_string($field_due) && !is_int($field_due) && $field_due) {
            $field_due = $field_due->getid();
        }

        $field_finish = $this->getField_finish();
        if (!is_string($field_finish) && !is_int($field_finish) && $field_finish) {
            $field_finish = $field_finish->getid();
        }

        $field_percentage = $this->getField_percentage();
        if (!is_string($field_percentage) && !is_int($field_percentage) && $field_percentage) {
                $field_percentage = $field_percentage->getid();
        }

        $field_righttext = $this->getField_righttext();
        if (!is_string($field_righttext) && !is_int($field_righttext) && $field_righttext) {
                $field_righttext = $field_righttext->getid();
        }

        $field_summary = $this->getSummary();
        if (!is_string($field_summary) && !is_int($field_summary) && $field_summary) {
            $field_summary = $field_summary->getid();
        }

        $as_of_date = $this->getAs_of_date();

        return $this->getDao()->save(
            $id,
            $field_start,
            $field_due,
            $field_finish,
            $field_percentage,
            $field_righttext,
            $this->getScale(),
            $this->getAs_of_date(),
            $field_summary
        );
    }

    public function updateDb()
    {
        return $this->getDao()->save(
            $this->id,
            $this->getField_start(),
            $this->getField_due(),
            $this->getField_finish(),
            $this->getField_percentage(),
            $this->getField_righttext(),
            $this->getScale(),
            $this->getAs_of_date(),
            $this->getSummary()
        );
    }

    protected function updateSpecificProperties($row)
    {
        $session = self::getSession($this->renderer->report->id, $this->renderer->id);

        $session->set("$this->id.field_start", $row['field_start']);
        $session->set("$this->id.field_due", $row['field_due']);
        $session->set("$this->id.field_finish", $row['field_finish']);
        $session->set("$this->id.field_percentage", $row['field_percentage']);
        $session->set("$this->id.field_righttext", $row['field_righttext']);
        $session->set("$this->id.scale", $row['scale']);
        $session->set("$this->id.as_of_date", $row['as_of_date']);
        $session->set("$this->id.summary", $row['summary']);

        $session->setHasChanged();

        $this->setField_start($row['field_start']);
        $this->setField_due($row['field_due']);
        $this->setField_finish($row['field_finish']);
        $this->setField_percentage($row['field_percentage']);
        $this->setField_righttext($row['field_righttext']);
        $this->setScale($row['scale']);
        $this->setAs_of_date($row['as_of_date']);
        $this->setSummary($row['summary']);
        return true;
    }

    public function userCanVisualize()
    {
        $ff = Tracker_FormElementFactory::instance();
        $artifact_field_start = $ff->getFormElementById($this->field_start);
        $artifact_field_finish = $ff->getFormElementById($this->field_finish);
        $artifact_summary = $ff->getFormElementById($this->summary);
        $artifact_scale = $ff->getFormElementById($this->scale);

        if (($artifact_field_start && $artifact_field_start->userCanRead()) ||
            ($artifact_field_finish && $artifact_field_finish->userCanRead()) ||
            ($artifact_summary && $artifact_summary->userCanRead()) ||
            ($artifact_scale && $artifact_scale->userCanRead())) {
            return true;
        } else {
            return false;
        }
    }

    public function getChartType()
    {
        return 'gantt';
    }

    public function getSpecificRow()
    {
        return array(
            'field_start'      => $this->getField_start(),
            'field_due'        => $this->getField_due(),
            'field_finish'     => $this->getField_finish(),
            'field_percentage' => $this->getField_percentage(),
            'field_righttext'  => $this->getField_righttext(),
            'scale'            => $this->getScale(),
            'as_of_date'       => $this->getAs_of_date(),
            'summary'          => $this->getSummary(),
        );
    }

    /**
     * Sets the specific properties of the concrete chart from XML
     *
     * @param SimpleXMLElement $xml characterising the chart
     * @param array $formsMapping associating xml IDs to real fields
     */
    public function setSpecificPropertiesFromXML($xml, $formsMapping)
    {
        if (isset($formsMapping[(string) $xml['start']])) {
            $this->setField_start($formsMapping[(string) $xml['start']]);
        }
        if (isset($formsMapping[(string) $xml['due']])) {
            $this->setField_due($formsMapping[(string) $xml['due']]);
        }
        if (isset($formsMapping[(string) $xml['finish']])) {
            $this->setField_finish($formsMapping[(string) $xml['finish']]);
        }
        if (isset($formsMapping[(string) $xml['righttext']])) {
            $this->setField_righttext($formsMapping[(string) $xml['righttext']]);
        }
        if (isset($formsMapping[(string) $xml['summary']])) {
            $this->setSummary($formsMapping[(string) $xml['summary']]);
        }
        if ((string) $xml['scale']) {
            $this->setScale((string) $xml['scale']);
        }
        if ((string) $xml['as_of_date']) {
            $this->setAs_of_date((string) $xml['as_of_date']);
        }
        if (isset($formsMapping[(string) $xml['percentage']])) {
            $this->setField_percentage($formsMapping[(string) $xml['percentage']]);
        }
    }

    /**
     * Creates an array of specific properties of this chart
     *
     * @return array containing the properties
     */
    public function arrayOfSpecificProperties()
    {
        return array(
            'field_start'      => $this->getField_start(),
            'field_due'        => $this->getField_due(),
            'field_finish'     => $this->getField_finish(),
            'field_percentage' => $this->getField_percentage(),
            'field_righttext'  => $this->getField_righttext(),
            'scale'            => $this->getScale(),
            'as_of_date'       => $this->getAs_of_date(),
            'summary'          => $this->getSummary(),
        );
    }

    public function exportToXml(SimpleXMLElement $root, $formsMapping)
    {
        parent::exportToXML($root, $formsMapping);
        if ($this->scale) {
            $root->addAttribute('scale', $this->scale);
        }
        if ($this->as_of_date) {
            $root->addAttribute('as_of_date', $this->as_of_date);
        }
        if ($this->field_start && ($start = (string) array_search($this->field_start, $formsMapping))) {
            $root->addAttribute('start', $start);
        }
        if ($this->field_due && ($due = (string) array_search($this->field_due, $formsMapping))) {
            $root->addAttribute('due', $due);
        }
        if ($this->field_finish && ($finish = (string) array_search($this->field_finish, $formsMapping))) {
            $root->addAttribute('finish', $finish);
        }
        if ($this->field_percentage && ($percentage = (string) array_search($this->field_percentage, $formsMapping))) {
            $root->addAttribute('percentage', $percentage);
        }
        if ($this->field_righttext && ($righttext = (string) array_search($this->field_righttext, $formsMapping))) {
            $root->addAttribute('righttext', $righttext);
        }
        if ($this->summary) {
            if ($res = array_search($this->summary, $formsMapping)) {
                $root->addAttribute('summary', $res);
            }
        }
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitGantt($this);
    }
}
