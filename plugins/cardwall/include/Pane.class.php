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
require_once 'common/templating/TemplateRendererFactory.class.php';
require_once 'BoardFactory.class.php';
require_once 'PaneContentPresenter.class.php';
require_once 'QrCode.class.php';
require_once 'CreateCardPresenterCallback.class.php';
require_once 'CardInCellPresenterCallback.class.php';

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
        $tracker = $this->milestone->getArtifact()->getTracker();
        $field   = Tracker_Semantic_StatusFactory::instance()->getByTracker($tracker)->getField();
        if (! $field) {
            return $GLOBALS['Language']->getText('plugin_cardwall', 'on_top_miss_status');
        }
        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../templates');
        
        return $renderer->renderToString('agiledashboard-pane', $this->getPresenter($field, $tracker));
    }

    /**
     * @return Cardwall_PaneContentPresenter
     */
    private function getPresenter(Tracker_FormElement_Field_Selectbox $field, $tracker) {
        $board_factory      = new Cardwall_BoardFactory();
        $planned_artifacts  = $this->milestone->getPlannedArtifacts();

        $field_retriever    = new Cardwall_FieldProviders_SemanticStatusFieldRetriever();
        
        $tracker_factory  = TrackerFactory::instance();
        $element_factory  = Tracker_FormElementFactory::instance();
        $config           = $this->getOnTopConfig($tracker, $tracker_factory, $element_factory);


        $board              = $board_factory->getBoard($field_retriever, $field, $planned_artifacts, $config);
        $backlog_title      = $this->milestone->getPlanning()->getBacklogTracker()->getName();
        $redirect_parameter = 'cardwall[agile]['. $this->milestone->getPlanning()->getId() .']='. $this->milestone->getArtifactId();
        $configure_url      = TRACKER_BASE_URL .'/?tracker='. $this->milestone->getTrackerId() .'&func=admin-cardwall';

        return new Cardwall_PaneContentPresenter($board, $this->getQrCode(), $redirect_parameter, $backlog_title, $configure_url);
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
    
    
    private function getOnTopConfig(Tracker $tracker, TrackerFactory $tracker_factory, Tracker_FormElementFactory $element_factory) {
        require_once 'OnTop/Config.class.php';
        require_once 'OnTop/Config/ColumnFactory.class.php';
        require_once 'OnTop/Config/TrackerMappingFactory.class.php';
        require_once 'OnTop/Config/ValueMappingFactory.class.php';
        $column_factory = new Cardwall_OnTop_Config_ColumnFactory($this->getOnTopColumnDao());

        $value_mapping_factory = new Cardwall_OnTop_Config_ValueMappingFactory(
            $element_factory,
            $this->getOnTopColumnMappingFieldValueDao()
        );

        $tracker_mapping_factory = new Cardwall_OnTop_Config_TrackerMappingFactory(
            $tracker_factory,
            $element_factory,
            $this->getOnTopColumnMappingFieldDao(),
            $value_mapping_factory
        );

        $config = new Cardwall_OnTop_Config(
            $tracker,
            $this->getOnTopDao(),
            $column_factory,
            $tracker_mapping_factory
        );
        return $config;
    }

        /**
     * @return Cardwall_OnTop_Dao
     */
    private function getOnTopDao() {
        require_once 'OnTop/Dao.class.php';
        return new Cardwall_OnTop_Dao();
    }

    /**
     * @return Cardwall_OnTop_ColumnDao
     */
    private function getOnTopColumnDao() {
        require_once 'OnTop/ColumnDao.class.php';
        return new Cardwall_OnTop_ColumnDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldDao
     */
    private function getOnTopColumnMappingFieldDao() {
        require_once 'OnTop/ColumnMappingFieldDao.class.php';
        return new Cardwall_OnTop_ColumnMappingFieldDao();
    }

    /**
     * @return Cardwall_OnTop_ColumnMappingFieldValueDao
     */
    private function getOnTopColumnMappingFieldValueDao() {
        require_once 'OnTop/ColumnMappingFieldValueDao.class.php';
        return new Cardwall_OnTop_ColumnMappingFieldValueDao();
    }


}
?>
