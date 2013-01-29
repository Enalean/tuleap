<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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
require_once 'common/templating/TemplateRendererFactory.class.php';

class Cardwall_Renderer extends Tracker_Report_Renderer {
    
    /**
     * @var Plugin
     */
    protected $plugin;
    
    private $enable_qr_code = true;
    
    /** @var Cardwall_OnTop_Config */
    private $config;
    
    /**
     * Constructor
     *
     * @param Plugin $plugin         the parent cardwall plugin
     * @param int    $id             the id of the renderer
     * @param Report $report         the id of the report
     * @param string $name           the name of the renderer
     * @param string $description    the description of the renderer
     * @param int    $rank           the rank
     * @param int    $field_id       the field id
     * @param bool   $enable_qr_code Display the QR code to ease usage of tablets
     */
    public function __construct(Plugin $plugin, Cardwall_OnTop_IConfig $config,
                                $id, $report, $name, $description, $rank, $field_id, $enable_qr_code) {
        parent::__construct($id, $report, $name, $description, $rank);
        $this->plugin         = $plugin;
        $this->field_id       = $field_id;
        $this->enable_qr_code = $enable_qr_code;
        $this->config         = $config;
    }
    
    public function initiateSession() {
        $this->report_session = new Tracker_Report_Session($this->report->id);
        $this->report_session->changeSessionNamespace("renderers");
        $this->report_session->set("{$this->id}.field_id",   $this->field_id);
    }
    
    private function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }
    
    /**
     * @return Tracker_FormElement_Field
     */
    private function getField() {
        $field = $this->getFormElementFactory()->getFormElementById($this->field_id);
        if ($field) {
            if (!$field->userCanRead() || !is_a($field, 'Tracker_FormElement_Field_Selectbox')) {
                $field = null;
            }
        }
        return $field;
    }
    
    /**
     * @return int
     */
    private function getFieldId() {
        $field = $this->getField();
        
        if ($field) {
            return $field->getId();
        }
    }
    
    /**
     * Fetch content of the renderer
     *
     * @param array $matching_ids
     * @param Request $request
     *
     * @return string
     */
    public function fetch($matching_ids, $request, $report_can_be_modified, User $user) {
        $used_sb = $this->getFormElementFactory()->getUsedFormElementsByType($this->report->getTracker(), array('sb'));
        $form    = new Cardwall_Form($this->report->id, $this->id, $request->get('pv'), $this->getField(), $used_sb);
        return $this->fetchCards($matching_ids, $user, $form);
    }
    
    private function fetchCards($matching_ids, User $user, $form = null) {
        $total_rows = $matching_ids['id'] ? substr_count($matching_ids['id'], ',') + 1 : 0;
        if (!$total_rows) {
            return '<p>'. $GLOBALS['Language']->getText('plugin_tracker', 'no_artifacts') .'</p>';
        }

        $artifact_ids     = explode(',', $matching_ids['id']);
        $artifacts = $this->getForestsOfArtifacts($artifact_ids, Tracker_ArtifactFactory::instance());
        $presenter = $this->getPresenter($artifacts, $user, $form);

        $renderer  = TemplateRendererFactory::build()->getRenderer(dirname(__FILE__).'/../templates');
        
        return $renderer->renderToString('renderer', $presenter);
    }

    /**
     * @return TreeNode
     */
    public function getForestsOfArtifacts(array $artifact_ids, Tracker_ArtifactFactory $artifact_factory) {
        $provider = new Cardwall_ArtifactNodeTreeProvider();
        return $provider->flatForestOfArtifacts($artifact_ids, $artifact_factory);
    }
    
    /**
     * @return Cardwall_RendererPresenter
     */
    private function getPresenter(TreeNode $forest_of_artifacts, User $user, $form = null) {
        $redirect_parameter = 'cardwall[renderer]['. $this->report->id .']='. $this->id;
        
        $field              = $this->getField();
        
        if (! $field) {
            $board = new Cardwall_Board(array(), new Cardwall_OnTop_Config_ColumnCollection(), new Cardwall_MappingCollection());
        } else {
            $board_factory      = new Cardwall_BoardFactory();
            $field_retriever    = new Cardwall_FieldProviders_CustomFieldRetriever($field);
            $columns            = $this->config->getRendererColumns($field);
            $board              = $board_factory->getBoard($field_retriever, $columns, $forest_of_artifacts, $this->config, $user);
        }

        return new Cardwall_RendererPresenter($board, $this->getQrCode(), $redirect_parameter, $field, $form);
    }

    /**
     * @return Cardwall_QrCode
     */
    private function getQrCode() {
        if ($this->enable_qr_code) {
            return new Cardwall_QrCode(TRACKER_BASE_URL .'/?'. http_build_query(
                    array(
                        'report'   => $this->report->id,
                        'renderer' => $this->id,
                        'pv'       => 2,
                    )
                )
            );
        }
        return false;
    }
    
    /*----- Implements below some abstract methods ----*/

    public function delete() {}

    public function getType() {
        return 'plugin_cardwall';
    }

    public function processRequest(TrackerManager $tracker_manager, $request, $current_user) {
        $renderer_parameters = $request->get('renderer_cardwall');
        $this->initiateSession();
        if ($renderer_parameters && is_array($renderer_parameters)) {
            
            //Update the field_id parameter
            if (isset($renderer_parameters['columns'])) {
                $new_columns_field = (int)$renderer_parameters['columns'];
                if ($new_columns_field && ($this->field_id != $new_columns_field)) {
                    $this->report_session->set("{$this->id}.field_id", $new_columns_field);
                    $this->report_session->setHasChanged();
                    $this->field_id = $new_columns_field;
                }
            }
            
        }
    }
    
    /**
     * Fetch content to be displayed in widget
     */
    public function fetchWidget(User $user) {
        $this->enable_qr_code = false;
        $html  = '';
        $html .= $this->fetchCards($this->report->getMatchingIds(), $user);
        $html .= $this->fetchWidgetGoToReport();
        return $html;
    }
    
    /**
     * Create a renderer - add in db
     *     
     * @return bool true if success, false if failure
     */
    public function create() {
        $success = true;
        $rrf = Tracker_Report_RendererFactory::instance();
        if ($renderer_id = $rrf->saveRenderer($this->report, $this->name, $this->description, $this->getType())) {
            //field_id
            $this->saveRendererProperties($renderer_id);
        }
        return $success;
    }
    
    /**
     * Update the renderer
     *     
     * @return bool true if success, false if failure
     */
    public function update() {
        $success = true;
        if ($this->id > 0) {
            //field_id
            $this->saveRendererProperties($this->id);
        }
        return $success;
    }   

    public function duplicate($from_renderer, $field_mapping) { }

    public function afterSaveObject($renderer) { }
    
    /**
     * Save field_id in db
     *     
     * @param int $renderer_id the id of the renderer
     */
    protected function saveRendererProperties($renderer_id) {
        $dao = $this->getDao();
        $dao->save($renderer_id, $this->field_id);
    }
    
    /** 
     * Wrapper for Cardwall_RendererDao
     */
    public function getDao() {
        return new Cardwall_RendererDao();
    }
    
    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     * @param $formsMapping the form elements mapping
     */
    public function exportToXml(SimpleXMLElement $root, $formsMapping) {
        parent::exportToXML($root, $formsMapping);
        if ($mapping = (string)array_search($this->field_id, $formsMapping)) {
            $root->addAttribute('field_id', $mapping);
        }
    }

}
?>
