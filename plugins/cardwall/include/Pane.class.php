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
require_once 'Column.class.php';
require_once 'Swimline.class.php';

class Cardwall_Pane implements AgileDashboard_Pane {

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    public function __construct(Planning_Milestone $milestone) {
        $this->milestone = $milestone;
    }

    public function getIdentifier() {
        return 'cardwall';
    }

    public function getTitle() {
        return 'Card Wall';
    }

    public function getContent() {
        $tracker = $this->milestone->getPlanning()->getBacklogTracker();
        $field   = Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
        if (!$field) {
            return 'Y u no configure the status semantic of ur tracker?';
        }

        $columns   = $this->getColumns($field);
        $swimlines = $this->getSwimlines($columns, $this->milestone->getPlannedArtifacts()->getChildren());

        $renderer = new MustacheRenderer(dirname(__FILE__).'/../templates');
        $presenter = new Cardwall_PaneContentPresenter($swimlines, $columns);
        ob_start();
        $renderer->render('pane-content', $presenter);
        return ob_get_clean();
    }

    private function getColumns(Tracker_FormElement_Field_Selectbox $field) {
        $values = $field->getAllValues();
        foreach ($values as $key => $value) {
            if ($value->isHidden()) {
                unset($values[$key]);
            }
        }
        if ($values) {
            if (! $field->isRequired()) {
                $none = new Tracker_FormElement_Field_List_Bind_StaticValue(100, $GLOBALS['Language']->getText('global','none'), '', 0, false);
                $values = array_merge(array($none), $values);
            }
        }

        $decorators = $field->getBind()->getDecorators();
        $columns    = array();
        foreach ($values as $value) {
            $id = (int)$value->getId();
            $bgcolor = 'white';
            $fgcolor = 'black';
            if (isset($decorators[$id])) {
                $r = $decorators[$id]->r;
                $g = $decorators[$id]->g;
                $b = $decorators[$id]->b;
                if ($r !== null && $g !== null && $b !== null ) {
                    //choose a text color to have right contrast (black on dark colors is quite useless)
                    $bgcolor = 'rgb('. (int)$r .', '. (int)$g .', '. (int)$b .');';
                    $fgcolor = (0.3 * $r + 0.59 * $g + 0.11 * $b) < 128 ? 'white' : 'black';
                }
            }
            $columns[] = new Cardwall_Column($id, $value->getLabel(), $bgcolor, $fgcolor);
        }
        return $columns;
    }

    private function getSwimlines(array $columns, array $nodes) {
        $swimlines = array();
        foreach ($nodes as $child) {
            $data  = $child->getData();
            $title = $data['artifact']->fetchTitle();
            $cells = $this->getCells($columns, $child->getChildren());
            $swimlines[] = new Cardwall_Swimline($title, $cells);
        }
        return $swimlines;
    }

    private function getCells(array $columns, array $nodes) {
        $cells = array();
        foreach ($columns as $column) {
            $cells[] = $this->getCell($column, $nodes);
        }
        return $cells;
    }

    private function getCell(Cardwall_Column $column, array $nodes) {
        $artifacts = array();
        foreach ($nodes as $node) {
            $this->addNodeToCell($node, $column, $artifacts);
        }
        return array('artifacts' => $artifacts);;
    }

    private function addNodeToCell(TreeNode $node, Cardwall_Column $column, array &$artifacts) {
        $data            = $node->getData();
        $artifact        = $data['artifact'];
        $artifact_status = $artifact->getStatus();
        if ($this->isArtifactInCell($artifact, $column)) {
            $artifacts[] = $node;
        }
    }

    private function isArtifactInCell(Tracker_Artifact $artifact, Cardwall_Column $column) {
        $artifact_status = $artifact->getStatus();
        return $artifact_status === $column->label || $artifact_status === null && $column->id == 100;
    }
}
?>
