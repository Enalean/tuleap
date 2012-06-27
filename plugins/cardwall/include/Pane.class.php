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

require_once 'common/TreeNode/TreeNodeMapper.class.php';
require_once AGILEDASHBOARD_BASE_DIR .'/AgileDashboard/Pane.class.php';
require_once 'common/mustache/MustacheRenderer.class.php';
require_once 'BoardFactory.class.php';
require_once 'PaneContentPresenter.class.php';
require_once 'QrCode.class.php';
require_once 'AccumulatedStatusFieldsProvider.class.php';
require_once 'CreateCardPresenterCallback.class.php';
require_once 'ColumnPresenterCallback.class.php';

/**
 * A pane to be displayed in AgileDashboard
 */
class Cardwall_Pane extends AgileDashboard_Pane {

    /**
     * @var Planning_Milestone
     */
    private $milestone;

    /**
     * @var bool
     */
    private $enable_qr_code;

    public function __construct(Planning_Milestone $milestone, $enable_qr_code) {
        $this->milestone      = $milestone;
        $this->enable_qr_code = $enable_qr_code;
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
        $tracker = $this->milestone->getPlanning()->getBacklogTracker();
        $field   = Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
        if (! $field) {
            return $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_miss_status');
        }
        $renderer  = new MustacheRenderer(dirname(__FILE__).'/../templates');
        ob_start();
        $renderer->render('agiledashboard-pane', $this->getPresenter($field));

        return ob_get_clean();
    }

    /**
     * @return Cardwall_PaneContentPresenter
     */
    private function getPresenter(Tracker_FormElement_Field_Selectbox $field = null) {
        $board_factory      = new Cardwall_BoardFactory();
        $planned_artifacts  = $this->milestone->getPlannedArtifacts();

        $visitor  = new TreeNodeMapper(new Cardwall_CreateCardPresenterCallback());
        $card_presenter_tree = $planned_artifacts->accept($visitor);

        $acc_field_provider = new Cardwall_AccumulatedStatusFieldsProvider();
        $column_factory     = new Cardwall_ColumnFactory($field);
        $mapping_collection = $column_factory->getMappings($acc_field_provider->accumulateStatusFields($card_presenter_tree));
        $col_visitor        = new TreeNodeMapper(
                                new ColumnPresenterCallback(
                                    new Tracker_Artifact_Semantic_Status_Field_Retriever(),
                                    $mapping_collection));
        $board              = $board_factory->getBoard($col_visitor, $mapping_collection, $card_presenter_tree, $field);
        $backlog_title      = $this->milestone->getPlanning()->getBacklogTracker()->getName();
        $redirect_parameter = 'cardwall[agile]['. $this->milestone->getPlanning()->getId() .']='. $this->milestone->getArtifactId();

        return new Cardwall_PaneContentPresenter($board, $this->getQrCode(), $redirect_parameter, $backlog_title);
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
}
?>
