<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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

namespace Tuleap\GraphOnTrackersV5\DataAccess;

use GraphOnTrackersV5_Chart_BarDataBuilder;
use GraphOnTrackersV5_Engine_Bar;
use SimpleXMLElement;
use Tracker_FormElementFactory;
use Tuleap\GraphOnTrackersV5\Chart\Visitor;
use Tuleap\GraphOnTrackersV5\Common\HTML_Element_Selectbox_TrackerFields_SelectboxesV5;

class GraphOnTrackersV5_Chart_Bar extends GraphOnTrackersV5_Chart
{
    protected $field_base;
    protected $field_group;

    public function loadFromSession()
    {
        $this->report_session = self::getSession($this->renderer->report->id, $this->renderer->id);
        $chart_in_session     = $this->report_session->get($this->id);
        if (isset($chart_in_session['field_base']) && $chart_in_session['field_base'] !== '') {
            $this->field_base  = $chart_in_session['field_base'];
            $this->field_group = $chart_in_session['field_group'];
        } else {
            $this->loadFromDb();
            $this->registerInSession();
        }
    }

    public function loadFromDb()
    {
        $arr               = $this->getDao()->searchById($this->id)->getRow();
        $this->field_base  = $arr['field_base'] ?? null;
        $this->field_group = $arr['field_group'] ?? null;
    }

    public function registerInSession()
    {
        parent::registerInSession();
        $this->report_session->set("$this->id.field_base", $this->field_base);
        $this->report_session->set("$this->id.field_group", $this->field_group);
    }

    protected function getDao()
    {
        return new GraphOnTrackersV5_Chart_BarDao();
    }

    public static function create($graphic_report, $id, $rank, $title, $description, $width, $height)
    {
        $session = self::getSession($graphic_report->report->id, $graphic_report->id);

        $session->set("$id.field_base", 0);
        $session->set("$id.field_group", 0);
        $c = new GraphOnTrackersV5_Chart_Bar($graphic_report, $id, $rank, $title, $description, $width, $height);
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

    public function getField_group()
    {
        return $this->field_group;
    }

    public function setField_group($field_group)
    {
        return $this->field_group = $field_group;
    }

    protected function getEngine()
    {
        return new GraphOnTrackersV5_Engine_Bar();
    }

    protected function getChartDataBuilder($artifacts)
    {
        return new GraphOnTrackersV5_Chart_BarDataBuilder($this, $artifacts);
    }

    public function getProperties()
    {
        return array_merge(
            parent::getProperties(),
            [
                new HTML_Element_Selectbox_TrackerFields_SelectboxesV5($this->getTracker(), dgettext('tuleap-graphontrackersv5', 'Source Data'), 'chart[field_base]', $this->getField_base(), false),

                new HTML_Element_Selectbox_TrackerFields_SelectboxesV5($this->getTracker(), dgettext('tuleap-graphontrackersv5', 'Group by'), 'chart[field_group]', $this->getField_group(), true),
            ]
        );
    }

    public function createDb($id)
    {
        $field_base = $this->getField_base();
        if (! is_int($field_base) && ! is_string($field_base) && $field_base) {
            $field_base = $field_base->getid();
        }
        $field_group = $this->getField_group();
        if (! is_int($field_group) && ! is_string($field_group) && $field_group) {
            $field_group = $field_group->getid();
        }
        return $this->getDao()->save($id, $field_base, $field_group);
    }

    public function updateDb()
    {
        return $this->getDao()->save($this->id, $this->getField_base(), $this->getField_group());
    }

    protected function updateSpecificProperties($row)
    {
        $session = self::getSession($this->renderer->report->id, $this->renderer->id);

        $session->set("$this->id.field_base", $row['field_base']);
        $session->set("$this->id.field_group", $row['field_group']);

        $session->setHasChanged();

        $this->setField_base($row['field_base']);
        $this->setField_group($row['field_group']);

        return true;
    }

    public function userCanVisualize()
    {
        $ff                  = Tracker_FormElementFactory::instance();
        $artifact_field_base = $ff->getFormElementById($this->field_base);
        if ($artifact_field_base && $artifact_field_base->userCanRead()) {
            if ($this->field_group) {
                $artifact_field_group = $ff->getFormElementById($this->field_group);
                if (! $artifact_field_group || ! $artifact_field_group->userCanRead()) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    public function getChartType()
    {
        return 'bar';
    }

    public function getSpecificRow()
    {
        return [
            'field_base'  => $this->getField_base(),
            'field_group' => $this->getField_group(),
        ];
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
        if (isset($formsMapping[(string) $xml['group']])) {
            $this->setField_group($formsMapping[(string) $xml['group']]);
        }
    }

    /**
     * Creates an array of specific properties of this chart
     *
     * @return array containing the properties
     */
    public function arrayOfSpecificProperties()
    {
        return [
            'field_base'  => $this->getField_base(),
            'field_group' => $this->getField_group(),
        ];
    }

    public function exportToXml(SimpleXMLElement $root, $formsMapping)
    {
        parent::exportToXML($root, $formsMapping);
        if ($base = (string) array_search($this->field_base, $formsMapping)) {
            $root->addAttribute('base', $base);
        }
        if ($group = (string) array_search($this->field_group, $formsMapping)) {
            $root->addAttribute('group', $group);
        }
    }

    public function accept(Visitor $visitor)
    {
        if ($this->field_group !== null) {
            return $visitor->visitGroupedBarChart($this);
        }

        return $visitor->visitBarChart($this);
    }
}
