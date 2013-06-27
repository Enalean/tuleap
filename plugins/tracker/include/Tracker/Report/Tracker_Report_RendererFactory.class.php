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

require_once('common/event/EventManager.class.php');


class Tracker_Report_RendererFactory {
    
    
    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct() {

    }

    /**
     * Get Event Manager instance
     *
     * @return EventManager
     */
     private function getEventManager() {
         return EventManager::instance();
     }

    /**
     * Hold an instance of the class
     */
    protected static $_instance;
    
    /**
     * The singleton method
     */
    public static function instance() {
        if (!isset(self::$_instance)) {
            $c = __CLASS__;
            self::$_instance = new $c;
        }
        return self::$_instance;
    }

    
    // {{{ Public
    
    /**
     * @param int $id the id of the report renderer to retrieve
     * @param Report $report the report of the renderer
     * @return ReportRenderer identified by id (null if not found)
     */
    public function getReportRendererById($id, $report, $store_in_session = true) {
        $row = $this->getDao()
                    ->searchById($id)
                    ->getRow();
        if ($row) {
            if (!$report) {
                //try to dynamically load it
                $arf = Tracker_ReportFactory::instance();
                $report = $arf->getReportById($row['report_id'], UserManager::instance()->getCurrentUser()->getId());
            }
            if ($report) {
                return $this->getInstanceFromRow($row, $report, $store_in_session);
            }
        }
        return null;
    }
    
    /**
     * Get all renderers belonging to a report
     *
     * @param Tracker_Report $report the id of the tracker
     *
     * @param array of Tracker_Report_Renderer
     */
    public function getReportRenderersByReport($report) {
        $renderers = array();
        //Check that renderers are already in the session
        $renderers_data = $report->report_session->get('renderers');
        if ( ! $renderers_data ) {
            //if not, load the renderers from the db
            $renderers_data = $this->getDao()->searchByReportId($report->id);
        }
        if ($renderers_data) {
            foreach ($renderers_data as $row) {
                if ($r = $this->getInstanceFromRow($row, $report)) {
                    $renderers[$row['id']] = $r;
                }
            }
        }
        return $renderers;
    }
    
    /**
     * Get all renderers belonging to a report saved in db
     *
     * @param Tracker_Report $report the id of the tracker
     *
     * @param array of Tracker_Report_Renderer
     */
    public function getReportRenderersByReportFromDb($report) {
        $renderers = array();
        foreach ($this->getDao()->searchByReportId($report->id) as $row) {
            if ($r = $this->getInstanceFromRow($row, $report)) {
                $renderers[$row['id']] = $r;
            }
        }
        return $renderers;
    }
    
    /**
     * @param Report $report the id of the report
     * @param array
     */
    public function getReportRendererByReportAndId($report, $renderer_id, $store_in_session = true) {
        $renderer = null;
        $row = null;
        if ($store_in_session) {
            $session = new Tracker_Report_Session($report->id);
            $session->changeSessionNamespace('renderers');
            $row = $session->get($renderer_id);
        }
        if (!$row) {
            $row = $this->getDao()->searchByIdAndReportId($renderer_id, $report->id)->getRow();
        }
        if ($row) {
            $renderer = $this->getInstanceFromRow($row, $report, $store_in_session);
        }
        return $renderer;
    }
    
    /**
     * Delete a renderer
     * @param int $id the id of the renderer to delete
     */
    public function delete($id) {
        return $this->getDao()->delete($id);
    }
    
    /**
     * Rename renderer
     * @param int $id
     * @param string $new_name
     * @param string $new_description
     */
    public function rename($id, $new_name, $new_description) {
        return $this->getDao()->rename($id, $new_name, $new_description);
    }
    
    /**
     * Move a renderer
     *
     * @param int $id
     * @param Report $report the report
     * @param mixed $new_rank a position: an int or 'beginning' or 'end'
     *
     * @return boolean true on success or false on failure
     */
    public function move($id, $report, $new_rank) {
        return $this->getDao()->move($id, $report->id, $new_rank);
    }

    /**
     * Rename renderer
     * @param Report $report the id of the report
     * @param string $name
     * @param string $description
     */
    public function create($report, $name, $description, $type) {
        $renderer_id = false;
        $type = $type ? $type : Tracker_Report_Renderer::TABLE;
        $types = $this->getTypes();
        if (isset($types[$type])) {
            if ($renderer_id = $this->getDao()->create($report->id, $type, $name, $description, 'end')) {
                switch ($type) {
                    case Tracker_Report_Renderer::TABLE:
                        //default chunksz is 15
                        $this->getTableDao()->create($renderer_id, 15);
                        break;
                    case Tracker_Report_Renderer::BOARD:
                        //Not yet implemented
                        break;
                    default:
                        $this->getEventManager()
                                    ->processEvent('tracker_report_create_renderer',
                                                   array('renderer_id' => $renderer_id,
                                                         'type'        => $type,
                                                         'report'      => $report)
                        );
                        break;
                }
            }
        }
        return $renderer_id;
    }


    public function duplicate($from_report, $to_report, $field_mapping) {
        foreach ($this->getDao()->searchByReportId($from_report->id) as $row) {
            if ($id = $this->getDao()->duplicate($row['id'], $to_report->id)) {
                switch ($row['renderer_type']) {
                    case Tracker_Report_Renderer::TABLE:
                        $this->getTableDao()->duplicate($row['id'], $id);
                        break;
                    case Tracker_Report_Renderer::BOARD:
                        //Not yet implemented
                        break;
                    default:
                        //no need to call plugins. it will be done below
                        break;
                }
                $this->getReportRendererById($id, $to_report)
                     ->duplicate($this->getReportRendererById($row['id'], $from_report),
                                                              $field_mapping);
            }
        }
    }
    
    /**
     * Add a new renderer in session
     * @param Report $report the id of the report
     * @param string $name
     * @param string $description
     * @param string $type
     */
    public function createInSession($report, $name, $description, $type) {
        $renderer_id = false;
        $type = $type ? $type : Tracker_Report_Renderer::TABLE;
        $types = $this->getTypes();
        if (isset($types[$type])) {
            $session = new Tracker_Report_Session($report->id);
            $session->changeSessionNamespace('renderers');
            $nb_renderers = count($report->getRenderers());
            $renderer_id = -$nb_renderers-1;
            $session->set(
                $renderer_id, 
                array(
                    'id'            => $renderer_id,
                    'name'          => $name,
                    'description'   => $description,
                    'rank'          => $nb_renderers,
                    'renderer_type' => $type,
                )
            );
            $this->report_session->setHasChanged();
            switch ($type) {
                case Tracker_Report_Renderer::TABLE:
                    $session->set("$renderer_id.chunksz", 15);
                    $session->set("$renderer_id.multisort", 0);
                    break;
                case Tracker_Report_Renderer::BOARD:
                    //Not yet implemented
                    break;
                default:
                    $this->getEventManager()
                                ->processEvent('tracker_report_create_renderer_in_session',
                                               array('renderer_id' => $renderer_id,
                                                     'type'        => $type,
                                                     'report'      => $report)
                    );
                    break;
            }
        }
        return $renderer_id;
    }
    
    public function saveRenderer($report, $name, $description, $type) {
        $renderer_id = false;
    
        $types = $this->getTypes();
        if (isset($types[$type])) {
            $renderer_id = $this->getDao()->create($report->id, $type, $name, $description, 'end');
        }
        return $renderer_id;

    }
    
    /**
     * Save a renderer
     *
     * @param Tracker_Report_Renderer $renderer the renderer to save
     *
     * @return boolean true on success or false on failure
     */
    public function save(Tracker_Report_Renderer $renderer) {
        return $this->getDao()->save(
            $renderer->id,
            $renderer->name,
            $renderer->description,
            $renderer->rank
        );
    }
    
    
    public function getTypes() {
        $types = array(Tracker_Report_Renderer::TABLE => $GLOBALS['Language']->getText('plugin_tracker_report','table'));
        $this->getEventManager()
                    ->processEvent('tracker_report_renderer_types', 
                                   array('types' => &$types));
        return $types;
    }
    /**
     * Force the order of renderers for a report
     *
     * @param Tracker_Report $report          The report
     * @param array          $renderers_order The ids of renderers in specified order
     *
     * @return bool true on success false on failure
     */
    public function forceOrder($report, $report_renderers) {
        $this->getDao()->forceOrder($report->id, $report_renderers);
    }
    // }}}
    
    
    
    
    
    // {{{ Protected
    
    protected $dao;
    /**
     * @return Tracker_Report_RendererDao
     */
    protected function getDao() {
        if (!$this->dao) {
            $this->dao = new Tracker_Report_RendererDao();
        }
        return $this->dao;
    }
    
    protected $table_dao;
    /**
     * @return Tracker_Report_RendererTableDao
     */
    protected function getTableDao() {
        if (!$this->table_dao) {
            $this->table_dao = new Tracker_Report_Renderer_TableDao();
        }
        return $this->table_dao;
    }
    
    protected $renderers;
    /**
     * Build an instance of a renderer from a row data. 
     *
     * This row data comes from the session, the db, xml, ... and contains all 
     * data describing the renderer.
     *
     * @param array          $row    the row identifing a report
     * @param Tracker_Report $report the report of the renderer
     *
     * @return Tracker_Report_Renderer null if type is unknown
     */
    protected function getInstanceFromRow($row, $report, $store_in_session = true) {
        if ($store_in_session) {
            $this->report_session = new Tracker_Report_Session($report->id);
            $this->report_session->changeSessionNamespace('renderers');
        }

        if ( !isset($this->renderers[$row['id']]) || $row['id']== 0 ) {
            $instance = null;
            switch ($row['renderer_type']) {
                case Tracker_Report_Renderer::TABLE:
                    //First retrieve specific properties of the renderer that are not saved in the generic table
                    if ( !isset($row['chunksz']) || !isset($row['multisort']) ) {
                        $row['chunksz']   = 15;
                        $row['multisort'] = 0;
                        $table_row = $this->getTableDao()
                                    ->searchByRendererId($row['id'])
                                    ->getRow();
                        if ($table_row) {
                            $row['chunksz']   = $table_row['chunksz']; 
                            $row['multisort'] = $table_row['multisort'];
                        }
                    }
                    //Build the instance from the row
                    $instance = new Tracker_Report_Renderer_Table(
                        $row['id'],
                        $report,
                        $row['name'],
                        $row['description'],
                        $row['rank'],
                        $row['chunksz'],
                        $row['multisort']
                    );
                    
                    if ($store_in_session) {
                        $instance->initiateSession();
                    }
                    
                    //Add the columns info to the table if any
                    if (empty($row['columns'])) {
                        $instance->getColumns();
                    } else {
                        $instance->setColumns($row['columns']);
                    }
                    if ($store_in_session) {
                        $instance->storeColumnsInSession();
                    }
                    
                    //Add the sort info to the table if any
                    if (isset($row['sort'])) {
                        $instance->setSort($row['sort']);
                        if ($store_in_session) {
                            $this->report_session->set("{$row['id']}.sort", $row['sort']);
                        }
                    }
                    break;
                    
                case Tracker_Report_Renderer::BOARD:
                    //Not yet implemented
                    break;
                    
                default:
                    $this->getEventManager()->processEvent(
                        'tracker_report_renderer_instance', 
                        array(
                            'instance'         => &$instance,
                            'type'             => $row['renderer_type'],
                            'row'              => $row,
                            'report'           => $report,
                            'store_in_session' => $store_in_session,
                        )
                    );
                    break;
            }
            $this->renderers[$row['id']] = $instance;
            
            if ($instance) {
                if ($store_in_session) {
                    //override the row in the current session
                    //do not traverse the row with a foreach since some info should not be put in the session
                    // (like SimpleXMLElement during an xml import)
                    //Furthermore, let the plugins set their own properties in the session
                    $this->report_session->set("{$row['id']}.id",            $row['id']);
                    $this->report_session->set("{$row['id']}.name",          $row['name']);
                    $this->report_session->set("{$row['id']}.description",   $row['description']);
                    $this->report_session->set("{$row['id']}.rank",          $row['rank']);
                    $this->report_session->set("{$row['id']}.renderer_type", $row['renderer_type']);
                }
            }
        }
        return $this->renderers[$row['id']];
    }
    
    /**
     * Creates a Tracker_Report_Renderer Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported renderer
     * @param Tracker_Report   $report      to which the renderer is attached
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * 
     * @return Tracker_Report_Renderer Object 
     */
    public function getInstanceFromXML($xml, $report, &$xmlMapping) {
        $att = $xml->attributes();
        $row = array(
            'id'            => 0,
            'name'          => (string)$xml->name,
            'description'   => (string)$xml->description,
            'rank'          => (int)$att['type'],
            'renderer_type' => (string)$att['type'],
        );
        
        switch ($row['renderer_type']) {
        case Tracker_Report_Renderer::TABLE:
            // specific TABLE attributes
            $row['chunksz']   = (int)$att['chunksz'];
            $row['multisort'] = (int)$att['multisort'];
            
            //columns
            $cols = array();
            foreach ($xml->columns->field as $f) {
                $att = $f->attributes();
                $cols[] = array('field' => $xmlMapping[(string)$att['REF']]);
            }
            $row['columns'] = $cols;
            
            //sort
            $sort = array();
            if($xml->sort){
                foreach ($xml->sort->field as $f) {
                    $att = $f->attributes();
                    $sort[] = array('field' => $xmlMapping[(string)$att['REF']]);
                }
                $row['sort'] = $sort;
            }
            break;
            
        case Tracker_Report_Renderer::BOARD:
            //not yet implemented
            break;
            
        default:
            $this->getEventManager()->processEvent(
                'tracker_report_renderer_from_xml',
                array(
                    'row'     => &$row,
                    'type'    => $row['renderer_type'],
                    'xml'     => $xml,
                    'mapping' => $xmlMapping,
                    'report'  => $report,
                )
            );
        }
        return $this->getInstanceFromRow($row, $report);
    }
    
    // }}}
}

?>
