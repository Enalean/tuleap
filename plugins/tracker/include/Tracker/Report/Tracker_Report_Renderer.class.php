<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__).'/../Widget/Tracker_Widget_MyRenderer.class.php';
require_once dirname(__FILE__).'/../Widget/Tracker_Widget_ProjectRenderer.class.php';

abstract class Tracker_Report_Renderer {
    
    public $id;
    public $report;
    public $name;
    public $description;
    public $rank;
    
    /**
     * A table renderer. This is the legacy display of the results
     */
    const TABLE = 'table';
    
    /**
     * A "Board" renderer. Display artifacts grouped by columns.
     */
    const BOARD = 'board';
    
    /**
     * Constructor
     *
     * @param int $id the id of the renderer
     * @param Report $report the id of the report
     * @param string $name the name of the renderer
     * @param string $description the description of the renderer
     * @param int $rank the rank
     */
     public function __construct($id, $report, $name, $description, $rank) {
        $this->id          = $id;
        $this->report      = $report;
        $this->name        = $name;
        $this->description = $description;
        $this->rank        = $rank;
    }
    
    /**
     * Return the id of the renderer
     *
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Delete the renderer
     */
    public abstract function delete();
    
    /**
     * Fetch content of the renderer
     *
     * @param array   $matching_ids
     * @param Request $request
     * @param bool    $report_can_be_modified
     * @param User    $user
     *
     * @return string
     */
    public abstract function fetch($matching_ids, $request, $report_can_be_modified, User $user);
    
    /**
     * Process the request
     * @param Request $request
     */
    public abstract function processRequest(TrackerManager $tracker_manager, $request, $current_user);
    
    /**
     * Fetch content to be displayed in widget
     */
    public abstract function fetchWidget(User $user);
    
    /**
     * Returns the type of this renderer
     */
    public abstract function getType();
    
    public abstract function initiateSession();
    /**
     * Update the renderer
     *
     * @return bool true if success, false if failure
     */
    public abstract function update();
    
    /**
     * Finishes import by saving specific properties
     * 
     * @param Object $renderer containig the parameters to save
     */
    public abstract function afterSaveObject($renderer);
    
    public function process(TrackerManager $tracker_manager, $request, $current_user) {
        $this->processRequest($tracker_manager, $request, $current_user);
        $this->afterProcessRequest($tracker_manager, $request, $current_user);
    }
    
    public function afterProcessRequest(TrackerManager $tracker_manager, $request, $current_user) {
        if (!$request->isAjax()) {
            $params = array(
                'report'   => $this->report->id,
                'renderer' => $this->id
            );
            if ($request->existAndNonEmpty('pv')) {
                $params['pv'] = (int)$request->get('pv');
            }
            $GLOBALS['Response']->redirect('?'. http_build_query($params));
        }
    }
    
    /**
     * Get the item of the menu options. 
     *
     * If no items is returned, the menu won't be displayed.
     *
     * @return array of 'item_key' => {url: '', icon: '', label: ''}
     */
    public function getOptionsMenuItems() {
        $items = array(
            'printer_version' => array(
                'url'   => TRACKER_BASE_URL.'/?'.http_build_query(
                    array(
                        'report'   => $this->report->id,
                        'renderer' => $this->id,
                        'pv'       => 1,
                    )
                ),
                'icon'  => $GLOBALS['HTML']->getImage('ic/printer.png', array('border' => 0, 'alt' => '', 'style' => 'vertical-align:middle;')),
                'label' => $GLOBALS['Language']->getText('global', 'printer_version'),
            )
        );

        if ($this->id > 0 && (!isset($this->report_session) || !$this->report_session->hasChanged())) {
            $user = UserManager::instance()->getCurrentUser();
            if ($user->isLoggedIn()) {
                $items['addto_my_dashboard'] = array(
                     'url'    => '/widgets/updatelayout.php?'.http_build_query(
                         array(
                             'owner'    => 'u'. $user->getId(),
                             'action'   => 'widget',
                             'renderer' => array(
                                 'title'       => $this->name .' for '. $this->report->name,
                                 'renderer_id' => $this->id
                             ),
                             'name'     => array(
                                 Tracker_Widget_MyRenderer::ID => array (
                                     'add' => 1
                                 )
                             )
                         )
                     ),
                    'icon'  => $GLOBALS['HTML']->getImage('ic/layout_user.png'),
                    'label' => $GLOBALS['Language']->getText('plugin_tracker_report','my_dashboard'),
                );
               
                
                $project = $this->report->getTracker()->getProject();
                if ($project->userIsAdmin($user)) {
                    $items['addto_project_dashboard'] = array(
                        'url'    => '/widgets/updatelayout.php?'.http_build_query(
                            array(
                                'owner'    => 'g'. $project->getGroupId(),
                                'action'   => 'widget',
                                'renderer' => array(
                                    'title'       => $this->name .' for '. $this->report->name,
                                    'renderer_id' => $this->id
                                ),
                                'name'     => array(
                                    Tracker_Widget_ProjectRenderer::ID => array (
                                        'add' => 1
                                    )
                                )
                            )
                        ),
                        'icon'  => $GLOBALS['HTML']->getImage('ic/layout_project.png'),
                        'label' => $GLOBALS['Language']->getText('plugin_tracker_report','project_dashboard'),
                    );
                }
            }
        }
        return $items;
    }
    
    /**
     * Create a renderer - add in db
     *     
     * @return bool true if success, false if failure
     */
    public abstract function create();
    
    /**
     * Duplicate the renderer
     */
    public abstract function duplicate($from_report_id, $field_mapping);
    
    /**
     * Display a link to let the user go back to report
     * Main usage is in widget
     *
     * @see fetchLinkGoTo
     *
     * @return string html
     */
    public function fetchWidgetGoToReport() {
        return $this->fetchLinkGoTo('['. $GLOBALS['Language']->getText('plugin_tracker_report_widget','go_to_report') .']');
    }
    
    /**
     * Display a link to let the user go to the tracker
     * Used in ArtifactLink
     *
     * @see fetchLinkGoTo
     *
     * @return string html
     */
    public function fetchArtifactLinkGoToTracker() {
        $html = '';
        $html .= '<div class="tracker-form-element-artifactlink-gototracker">';
        $html .=  $this->fetchLinkGoTo($GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'go_to_tracker'), array('target' => '_blank'));
        $html .= '</div>';
        return $html;
    }
    
    /**
     * Display a link to let the user go to the tracker
     *
     * @param string $msg A sanitized string to display as a link
     *
     * @return string html
     */
    protected function fetchLinkGoTo($msg, $params = array()) {
        $html = '';
        $html .= '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'report'   => $this->report->id,
                'renderer' => $this->id
            )
        );
        $html .= '"';
        foreach ($params as $key => $value) {
            $html .= ' '. $key .'="'. $value .'"';
        }
        $html .= '>'. $msg .'</a>';
        return $html;
    }
    
    
    /**
     * Transforms Tracker_Renderer into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root the node to which the renderer is attached (passed by reference)
     */
    public function exportToXML($root, $xmlMapping) {
        $root->addAttribute('type', $this->getType());
        $root->addAttribute('rank', $this->rank);    
        // if old ids are important, modify code here 
        if (false) {
            $root->addAttribute('id', $this->id);
            $root->addAttribute('report', $this->report->id);
        }
        $root->addChild('name', $this->name);
        if ($this->description) {
            $root->addChild('description', $this->description);
        }
    }
}

?>
