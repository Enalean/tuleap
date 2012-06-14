<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once AGILEDASHBOARD_BASE_DIR .'/AgileDashboard/Pane.class.php';
require_once 'common/mustache/MustacheRenderer.class.php';
require_once 'PaneContentPresenter.class.php';
require_once 'SwimlineFactory.class.php';
require_once 'ColumnFactory.class.php';
require_once 'QrCode.class.php';
require_once 'Mapping.class.php';
require_once 'MappingCollection.class.php';
require_once 'InjectColumnIdVisitor.class.php';
require_once 'InjectDropIntoClassnamesVisitor.class.php';

/**
 * A pane to be displayed in AgileDashboard
 */
class Cardwall_Pane extends AgileDashboard_Pane {

    /**
     * @var array Accumulated array of Tracker_FormElement_Field_Selectbox
     */
    private $accumulated_status_fields = array();

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field;

    /**
     * @var bool
     */
    private $enable_qr_code;

    public function __construct(Planning_Milestone $milestone, $enable_qr_code) {
        $this->milestone      = $milestone;
        $this->enable_qr_code = $enable_qr_code;

        $column_id_visitor = new Cardwall_InjectColumnIdVisitor();
        $this->milestone->getPlannedArtifacts()->accept($column_id_visitor);
        $this->accumulated_status_fields = $column_id_visitor->getAccumulatedStatusFields();

        $this->column_factory = new Cardwall_ColumnFactory($this->getField());

        $drop_into_visitor = new Cardwall_InjectDropIntoClassnamesVisitor($this->getMapping());
        $this->milestone->getPlannedArtifacts()->accept($drop_into_visitor);
    }

    /**
     * @see AgileDashboard_Pane::getIdentifier()
     */
    public function getIdentifier() {
        return 'cardwall';
    }

    /**
     * @see AgileDashboard_Pane::getTitle()
     */
    public function getTitle() {
        return 'Card Wall';
    }

    /**
     * @see AgileDashboard_Pane::getContent()
     */
    public function getContent() {
        if (! $this->getField()) {
            return $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_miss_status');
        }

        $swimline_factory = new Cardwall_SwimlineFactory();

        $qrcode        = $this->getQrCode();
        $columns       = $this->column_factory->getColumns();
        $mappings      = $this->getMapping();
        $swimlines     = $swimline_factory->getSwimlines($columns, $this->milestone->getPlannedArtifacts()->getChildren());
        $backlog_title = $this->milestone->getPlanning()->getBacklogTracker()->getName();

        $renderer  = new MustacheRenderer(dirname(__FILE__).'/../templates');
        $presenter = new Cardwall_PaneContentPresenter($backlog_title, $swimlines, $columns, $mappings, $qrcode);
        ob_start();
        $renderer->render('agiledashboard-pane', $presenter);
        return ob_get_clean();
    }

    /**
     * @return Cardwall_QrCode
     */
    private function getQrCode() {
        if ($this->enable_qr_code) {
            return new Cardwall_QrCode($_SERVER['REQUEST_URI'] .'&pv=2');
        }
        return false;
    }

    /**
     * @return Tracker_FormElement_Field_Selectbox
     */
    private function getField() {
        if (! $this->field) {
            $tracker     = $this->milestone->getPlanning()->getBacklogTracker();
            $this->field = Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
        }
        return $this->field;
    }

    /**
     * @return Cardwall_MappingCollection
     */
    private function getMapping() {
        $columns  = $this->column_factory->getColumns();
        $mappings = new Cardwall_MappingCollection();
        foreach ($this->accumulated_status_fields as $status_field) {
            foreach ($status_field->getVisibleValuesPlusNoneIfAny() as $value) {
                foreach ($columns as $column) {
                    if ($column->label == $value->getLabel()) {
                        $mappings->add(new Cardwall_Mapping($column->id, $status_field->getId(), $value->getId()));
                    }
                }
            }
        }
        return $mappings;
    }
}
?>
