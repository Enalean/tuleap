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
 
require_once('GraphOnTrackersV5_ChartDao.class.php');
require_once(TRACKER_BASE_DIR .'/Tracker/Report/Tracker_Report_Session.class.php');

class GraphOnTrackersV5_ChartFactory {
    
    protected $charts;
    protected $chart_factories;
    
    protected function __construct() {
        $this->charts        = null;
        $this->chart_factories = array();
        $em = EventManager::instance();
        $em->processEvent('graphontrackersv5_load_chart_factories', array('factories' => &$this->chart_factories));
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
    
    public function getCharts($renderer, $store_in_session = true) {
        if (!isset($this->charts[$renderer->id])) {
            $charts_data = array();
            $this->charts[$renderer->id] = array();
            if ($store_in_session) {
                $this->report_session = new Tracker_Report_Session($renderer->report->id);
                $this->report_session->changeSessionNamespace("renderers.{$renderer->id}");
                
                $charts_data = $this->report_session->get("charts");
            }
            //do we have charts in session?
            if (empty($charts_data)) {
                //No?! Retrieve them from the db!
                $charts_data = $this->getChartsFromDb($renderer);
                //$dao = new GraphOnTrackers_ChartDao(CodendiDataAccess::instance());
                //$charts_data = $dao->searchByReportId($renderer->id);
            } else {
                uasort($charts_data, array($this, 'sortArrayByRank'));
                if ($store_in_session) {
                    $this->report_session->set("charts", $charts_data);
                }
            }
            if ($charts_data) {
                foreach($charts_data as $row) {
                    if ($row !== 'removed') {
                        if ($c = $this->instanciateChart($row, $renderer, $store_in_session)) {
                            $this->charts[$renderer->id][$row['id']] = $c;
                        }
                        if ($store_in_session) {
                            //Add in session
                            foreach ($row as $key => $value) {
                                $this->report_session->set("charts.{$row['id']}.$key", $value);
                            }
                        }
                    }
                }
            }
        }
        return $this->charts[$renderer->id];
    }
    
    public function forceChartsRankInSession($renderer) {  
        $session = new Tracker_Report_Session($renderer->report->id);
        $session->changeSessionNamespace("renderers.{$renderer->id}");
        $i = 0;
        foreach ($this->getCharts($renderer) as $id => $chart) {
            $session->set("charts.$id.rank", $i);
            $chart->setRank($i);
            ++$i;
        }
    }
    
    public function sortArrayByRank($a, $b) {
        return $a['rank'] - $b['rank'];
    }
    
    public function getChartsFromDb($renderer) {
        $charts = array();
        $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
        $charts = $dao->searchByReportId($renderer->id);
        return $charts;
    }
    
    
    public function getReportRenderersByReportFromDb($report) {
        $renderers = array();
        foreach ($this->getDao()->searchByReportId($report->id) as $row) {
            if ($r = $this->getInstanceFromRow($row, $report)) {
                $renderers[$row['id']] = $r;
            }
        }
        return $renderers;
    }
    
    
    public function getChartFactories() {
        return $this->chart_factories;
    }
    
    public function createChart($renderer, $chart_type) {
        $chart = null;
        if ($chart_classname = $this->getChartClassname($chart_type)) {
            
            $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
            $default_title       = 'Untitled '.$chart_type;
            $default_description = '';
            $default_width       = call_user_func(array($chart_classname, 'getDefaultWidth'));
            $default_height      = call_user_func(array($chart_classname, 'getDefaultHeight'));

            $session = new Tracker_Report_Session($renderer->report->id);
            $session->changeSessionNamespace("renderers.{$renderer->id}");
            $id = -count($session->charts) - 1;
            $rank = 0;
            
            //Add new chart in session
            $session->set("charts.$id.chart_type", $chart_type);
            $session->setHasChanged();
            $chart = call_user_func(array($chart_classname, 'create'), $renderer, $id, $rank, $default_title, $default_description, $default_width, $default_height);
        }
        return $chart;
    }
    
    public function deleteChart($renderer,  $id) {
        $ok = false;
        $session = new Tracker_Report_Session($renderer->report->id);
        $session->changeSessionNamespace("renderers.{$renderer->id}");
        if (isset($session->charts[$id])) {
            $session->set("charts.$id", 'removed');
            $session->setHasChanged();
        } else {
            $this->deleteDb($renderer,  $id);
        }
    }
    
    public function deleteDb($renderer,  $id) {
        //not in session, but in db cause removed in session
        if ($c = $this->getChartFromDb($renderer, $id)) {
            $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
            $dao->delete($id);
            $c->delete();
        }
    }
    
    
    public function updateDb($renderer_id, $chart) {
        $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
        $dao->updateById(
            $renderer_id, 
            $chart->getId(), 
            $chart->getRank(), 
            $chart->getTitle(), 
            $chart->getDescription(), 
            $chart->getWidth(), 
            $chart->getHeight()
        );
        $chart->updateDb();
    }
    
    public function createDb($renderer_id, $chart) {
        $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
        $id = $dao->create(
            $renderer_id, 
            $chart->getChartType(),
            $chart->getRank(),
            $chart->getTitle(), 
            $chart->getDescription(),
            $chart->getWidth(), 
            $chart->getHeight()
        );
        $chart->createDb($id);
    }
    
    /**
     * retrieve a specific chart by its id
     */
    public function getChart($renderer, $id, $store_in_session = true) {
        $c = null;
        $chart_data = null;
        if ($renderer != null && $store_in_session) {
            $session = new Tracker_Report_Session($renderer->report->id);
            $session->changeSessionNamespace("renderers.{$renderer->id}");
            
            // look for the chart in the session
            $chart_data = $session->get("charts.$id");
        }
        
        if ( ! $chart_data ){
            // not found. look in the db
            $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
            $chart_data = $dao->searchById($id)->getRow();
        }
        
        if ($chart_data) {
            if (!$renderer) {
                $report = null; //We don't know the report
                $renderer = Tracker_Report_RendererFactory::instance()->getReportRendererById($chart_data['report_graphic_id'], $report, $store_in_session);
            }
            if ($renderer) {
                $c = $this->instanciateChart($chart_data, $renderer, $store_in_session);
            }
        }
        return $c;
    }
    
    /**
     * retrieve a specific chart by its id from db only
     */
    public function getChartFromDb($renderer, $id) {
        //not add in session
        $c = null;
        $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
        $chart_data = $dao->searchById($id)->getRow();
        if ($chart_data) {
            if (!$renderer) {
                $report = null; //We don't know the report
                $renderer = Tracker_Report_RendererFactory::instance()->getReportRendererById($chart_data['report_graphic_id'], $report);
            }
            if ($renderer) {                
                if ($chart_classname = $this->getChartClassname($chart_data['chart_type'])) { 
                    $c = new $chart_classname($renderer, $chart_data['id'], $chart_data['rank'], $chart_data['title'], $chart_data['description'], $chart_data['width'], $chart_data['height']);
                }
            }
        }
        return $c;
    }
    
    protected function getChartClassname($chart_type) {
        $chart_classname = null;
        if (isset($this->chart_factories[$chart_type])) {
            $chart_classname = $this->chart_factories[$chart_type]['chart_classname'];
        }
        return $chart_classname;
    }
    
    protected function instanciateChart($row, $renderer, $store_in_session = true) {
        $c = null;
        if ($chart_classname = $this->getChartClassname($row['chart_type'])) {
            if ($store_in_session) {
                $session = new Tracker_Report_Session($renderer->report->id);
                $session->changeSessionNamespace("renderers.{$renderer->id}");
                $session->set("charts.{$row['id']}.chart_type", $row['chart_type']);
            }
            $c = new $chart_classname($renderer, $row['id'], $row['rank'], $row['title'], $row['description'], $row['width'], $row['height']);
            if ($store_in_session) {
                $c->loadFromSession();
            } else {
                $c->loadFromDb();
            }
        }
        return $c;
    }
    
    /**
     * Duplicate the charts
     */
    public function duplicate($from_renderer, $to_renderer, $field_mapping) {
        $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
        foreach($this->getCharts($from_renderer) as $chart) {
            if ($id = $dao->duplicate($chart->getId(), $to_renderer->id)) {
                $this->getChart($to_renderer, $id)->duplicate($chart, $field_mapping);
            }
        }
    }
    
    public function getInstanceFromXML($xml, $renderer, $formsMapping, $store_in_session = true) {
        $att = $xml->attributes();
        $row = array(
            'id'          => 0,
            'chart_type'  => (string)$att->type,
            'height'      => (int)$att->height,
            'width'       => (int)$att->width,
            'rank'        => 'end',
            'title'       => (string)$xml->title,
            'description' => (string)$xml->description,
        );
        
        $chart = $this->instanciateChart($row, $renderer, $store_in_session);
        $chart->setSpecificPropertiesFromXML($xml, $formsMapping);
        return $chart;
    }

}
?>
