<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2006. STMicroelectronics.
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

class GraphOnTrackersV5_Chart_Pie extends GraphOnTrackersV5_Chart
{
    protected $field_base;

    public function loadFromSession()
    {
        $this->report_session = self::getSession($this->renderer->report->id, $this->renderer->id);
        $chart_in_session = $this->report_session->get($this->id);
        if (isset($chart_in_session['field_base']) && $chart_in_session['field_base'] !== '') {
            $this->field_base = $chart_in_session['field_base'];
        } else {
            $this->loadFromDb();
            $this->registerInSession();
        }
    }

    public function loadFromDb()
    {
        $arr = $this->getDao()->searchById($this->id)->getRow();
        $this->field_base  = $arr['field_base'];
    }

    public function registerInSession()
    {
        parent::registerInSession();
        $this->report_session->set("$this->id.field_base", $this->field_base);
    }

    protected function getDao()
    {
        return new GraphOnTrackersV5_Chart_PieDao();
    }

    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height)
    {
        $session = self::getSession($graphic_report->report->id, $graphic_report->id);
        $session->set("$id.field_base", 0);
        $c = new GraphOnTrackersV5_Chart_Pie($graphic_report, $id, $rank, $title, $description, $width, $height);
        $c->registerInSession();
        return $c;
    }

    public function getField_base()
    {
        return $this->field_base;
    }
    public function setField_base($field_base)
    {
        return $this->field_base = $field_base;
    }

    protected function getEngine()
    {
        return new GraphOnTrackersV5_Engine_Pie();
    }
    protected function getChartDataBuilder($artifacts)
    {
        return new GraphOnTrackersV5_Chart_PieDataBuilder($this, $artifacts);
    }

    public function getProperties()
    {
        return array_merge(
            parent::getProperties(),
            array(
                'field_base' => new HTML_Element_Selectbox_TrackerFields_SelectboxesV5(
                    $this->getTracker(),
                    $GLOBALS['Language']->getText('plugin_graphontrackersv5_pie_property', 'pie_field_base'),
                    'chart[field_base]',
                    $this->getField_base()
                ),
            )
        );
    }

    public function createDb($id)
    {
        $field_base = $this->getField_base();
        if (!is_string($field_base) && !is_int($field_base) && $field_base) {
            $field_base = $field_base->getid();
        }
        return $this->getDao()->save($id, $field_base);
    }

    public function updateDb()
    {
        return $this->getDao()->save($this->id, $this->getField_base());
    }

    protected function updateSpecificProperties($row)
    {
        $session = self::getSession($this->renderer->report->id, $this->renderer->id);

        $session->set("$this->id.field_base", $row['field_base']);
        $session->setHasChanged();

        $this->setField_base($row['field_base']);

        return true;
    }

    public function userCanVisualize()
    {
        $ff = Tracker_FormElementFactory::instance();
        $artifact_field_base = $ff->getFormElementById($this->field_base);
        if ($artifact_field_base && $artifact_field_base->userCanRead()) {
            return true;
        } else {
            return false;
        }
    }


    public function getChartType()
    {
        return 'pie';
    }

    public function getSpecificRow()
    {
        return array(
            'field_base'  => $this->getField_base(),
        );
    }

    public function getGraphicReport()
    {
        return $this->graphic_report;
    }

    /**
     * Creates an array of specific properties of this chart
     *
     * @return array containing the properties
     */
    public function arrayOfSpecificProperties()
    {
        return array(
            'field_base'  => $this->getField_base(),
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
        if (isset($formsMapping[(string) $xml['base']])) {
            $this->setField_base($formsMapping[(string) $xml['base']]);
        }
    }

    public function exportToXml(SimpleXMLElement $root, $formsMapping)
    {
        parent::exportToXML($root, $formsMapping);
        if ($mapping = (string) array_search($this->field_base, $formsMapping)) {
            $root->addAttribute('base', $mapping);
        }
    }

    public function accept(Visitor $visitor)
    {
        return $visitor->visitPieChart($this);
    }
}
