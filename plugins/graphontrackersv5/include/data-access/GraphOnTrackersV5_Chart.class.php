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


require_once('common/html/HTML_Element_Input_Hidden.class.php');
require_once('common/html/HTML_Element_Input_Text.class.php');
require_once('common/html/HTML_Element_Textarea.class.php');
require_once('common/html/HTML_Element_Columns.class.php');
require_once('common/html/HTML_Element_Selectbox_Rank.class.php');

/**
 * Describe a chart
 * 
 * This class must be overriden to provide your own concrete chart (Pie, Bar, ..)
 */
abstract class GraphOnTrackersV5_Chart {
    public $id;
    protected $rank;
    protected $title;
    protected $description;
    protected $width;
    protected $height;
    
    public $renderer;
    /**
     * @param Renderer The renderer wich contains the chart
     * @param int The id of the chart
     * @param int The rank of the chart
     * @param string The title of the chart
     * @param string The description of the chart
     * @param int The width of the chart
     * @param int The height of the chart
     */
    public function __construct($renderer, $id, $rank, $title, $description, $width, $height) {
        $this->renderer       = $renderer;
        $this->id             = $id;
        $this->rank           = $rank;
        $this->title          = $title;
        $this->description    = $description;
        $this->width          = $width;
        $this->height         = $height;
    }
    
    public function registerInSession() {
        $this->report_session = self::getSession($this->renderer->report->id, $this->renderer->id);
        $this->report_session->set("$this->id.id",                $this->id);
        $this->report_session->set("$this->id.rank",              $this->rank);
        $this->report_session->set("$this->id.title",             $this->title);
        $this->report_session->set("$this->id.description",       $this->description);
        $this->report_session->set("$this->id.width",             $this->width);
        $this->report_session->set("$this->id.height",            $this->height);
        $this->report_session->set("$this->id.report_graphic_id", $this->renderer->id);
    }
    
    public abstract function loadFromSession();
    public abstract function loadFromDb();
    
    /**
     *
     * @param int $report_id
     * @param int $renderer_id
     * @param int $chart_id
     *
     * @return Tracker_Report_Session
     */
    public static function getSession($report_id, $renderer_id) {
        $session = new Tracker_Report_Session($report_id);
        $session->changeSessionNamespace("renderers.{$renderer_id}.charts");
        return $session;
    }
    
    /* Getters and setters */
    public function getId() { return $this->id; }
    public function getRank() { return $this->rank; }
    public function setRank($rank) { $this->rank = $rank; }
    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; }
    public function getDescription() { return $this->description; }
    public function setDescription($description) { $this->description = $description; }
    public function getRenderer() { return $this->renderer; }
    public function setRenderer($renderer) { $this->renderer = $renderer; }
    public function getHeight() { return $this->height; }
    public function setHeight($height) { return $this->height = $height; }
    public function getWidth() { return $this->width; }
    public function setWidth($width) { return $this->width = $width; }
    public static function getDefaultHeight(){ return 400; }
    public static function getDefaultWidth(){ return 600; }
    /**
     * Display the html <img /> tag to embed the chart in a html page.
     */
    public function fetchImgTag($store_in_session = true) {
        $html = '';
        
        $urlimg = $this->getStrokeUrl($store_in_session);
        
        
        $html .= '<img  src="'. $urlimg .'"  ismap usemap="#map'. $this->getId() .'"  ';
        if ($this->width) {
            $html .= ' width="'. $this->width .'" ';
        }
        if ($this->height) {
            $html .= ' height="'. $this->height .'" ';
        }
        $html .= ' alt="'. $this->title .'" border="0">';
        return $html;
    }
    
    public function getStrokeUrl($store_in_session = true) {
        return TRACKER_BASE_URL.'/?' . http_build_query(array(
                     '_jpg_csimd' => '1',
                     'report'     => $this->renderer->report->id,
                     'renderer'   => $this->renderer->id,
                     'func'       => 'renderer',
                     'store_in_session' => $store_in_session,
                     'renderer_plugin_graphontrackersv5[stroke]' => $this->getId()));
    }
    
    /**
     * Display both <img /> and <map /> tags to embed the chart in a html page
     */
    public function display() {
        echo $this->fetch();
    }
    
    public function fetch($store_in_session = true) {
        $html = '';
        if($this->userCanVisualize()){
            
            $e = $this->buildGraph();
            if($e){
                $html .= $e->graph->GetHTMLImageMap("map".$this->getId());
                $html .= $this->fetchImgTag($store_in_session);
            }
        }
        return $html;
    }
    
    
    
    public function getRow() {
        return array_merge(array(
            'id'          => $this->getId(),
            'rank'        => $this->getRank(), 
            'title'       => $this->getTitle(), 
            'description' => $this->getDescription(),
            'width'       => $this->getWidth(), 
            'height'      => $this->getHeight(),
        ), $this->getSpecificRow());
    }
    
    /**
     * Stroke the chart.
     * Build the image and send it to the client
     */
    public function stroke() {
        $e = $this->buildGraph();
        if ($e && is_object($e->graph)) {
            $e->graph->StrokeCSIM(); 
        }
    }
    
    /**
     * Prepare the building of the graph
     * @return GraphOnTracker_Chart_Engine
     */
    protected function buildGraph() {
        //Define the artifacts which must be added to the chart
        $artifacts = $this->renderer->report->getMatchingIds();
        
        //Get the ChartDataBuilder for this chart
        $pcdb = $this->getChartDataBuilder($artifacts);
        
        //Get the chart engine
        $e = $this->getEngine();
        
        //prepare the propeties for the chart
        $pcdb->buildProperties($e);
        
        if ($e->validData()) {
            //build the chart
            $e->buildGraph();
            
            return $e;
        } else {
            return false;
        }      
        
    }
    
    protected function getTracker() {
        return TrackerFactory::instance()->getTrackerById($this->renderer->report->tracker_id);
    }
    
    /**
     * Get the properties of the chart as a HTML_Element array.
     * 
     * Default properties are id, title, description, rank and dimensions
     * 
     * Feel free to override this method to provide your own properties
     * @return array
     */
    public function getProperties() {
        $siblings = array();
        $dao = new GraphOnTrackersV5_ChartDao(CodendiDataAccess::instance());
        foreach($dao->getSiblings($this->getId()) as $row) {
            $siblings[] = array('id' => $row['id'], 'name' => $row['title'], 'rank' => $row['rank']);
        }
        return array(
            'id'          => new HTML_Element_Input_Hidden($GLOBALS['Language']->getText('plugin_graphontrackersv5_property','id'), 'chart[id]', $this->getId()),
            'title'       => new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackersv5_property','title'), 'chart[title]', $this->getTitle()),
            'description' => new HTML_Element_Textarea($GLOBALS['Language']->getText('plugin_graphontrackersv5_property','description'), 'chart[description]', $this->getDescription()),
            'rank'        => new HTML_Element_Selectbox_Rank($GLOBALS['Language']->getText('plugin_graphontrackersv5_property','rank'), 'chart[rank]', $this->getRank(), $this->getId(), $siblings),
            'dimensions'  => new HTML_Element_Columns(
                                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackersv5_property','width'), 'chart[width]', $this->getWidth(), 4),
                                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackersv5_property','height'), 'chart[height]', $this->getHeight(), 4)
                             ),
        );
    }
    
    /**
     * Update the properties of the chart
     *
     * @return boolean true if the update is successful
     */
    public function update($row) {    
        $session = self::getSession($this->renderer->report->id, $this->renderer->id);
        
        //Set in session
        $session->set("$this->id.rank", $row['rank']);
        $session->set("$this->id.title", $row['title']);
        $session->set("$this->id.description", $row['description']);
        if (isset($row['width'])) {
                $session->set("$this->id.width", $row['width']);
        }
        if (isset($row['height'])) {
                $session->set("$this->id.height", $row['height']);
        }
        
        
        $this->setRank($row['rank']);
        $this->setTitle($row['title']);
        $this->setDescription($row['description']);
        if (isset($row['width'])) {
                $this->setWidth($row['width']);
        }
        
        if (isset($row['height'])) {
                $this->setHeight($row['height']);
        }
        
        return $this->updateSpecificProperties($row);
    }
    
    /**
     * @return string The inline help of the chart
     */
    public function getHelp() {
        return '';
    }
    
    public function exportToXml(SimpleXMLElement $root, $formsMapping) {
        $root->addAttribute('type', $this->getChartType());
        $root->addAttribute('width', $this->width);
        $root->addAttribute('height', $this->height);
        $root->addAttribute('rank', $this->rank);
        $root->addChild('title', $this->title);
        if ($this->description != '') {
            $root->addChild('description', $this->description);
        }
    }
    public function delete() {
        $this->getDao()->delete($this->id);
    }
    /**
     * Duplicate the chart
     */
    public function duplicate($from_chart, $field_mapping) {
        return $this->getDao()->duplicate($from_chart->id, $this->id, $field_mapping);
    }
    
    /**
     * Return the specific properties as a row
     * array('prop1' => 'value', 'prop2' => 'value', ...)
     * @return array
     */
    abstract public function getSpecificRow();
    
    /**
     * Return the chart type (gantt, bar, pie, ...)
     */
    abstract public function getChartType();
    
    /**
     * @return GraphOnTracker_Engine The engine associated to the concrete chart
     */
    abstract protected function getEngine();
    
    /**
     * @return ChartDataBuilder The data builder associated to the concrete chart
     */
    abstract protected function getChartDataBuilder($artifacts);
    
    /**
     * Allow update of the specific properties of the concrete chart
     * @return boolean true if the update is successful
     */
    abstract protected function updateSpecificProperties($row);
    
    /**
     * Creates an array of specific properties of chart
     * 
     * @return array containing the properties
     */
    abstract protected function arrayOfSpecificProperties();
    
    /**
     * Sets the specific properties of the concrete chart from XML
     * 
     * @param SimpleXMLElement $xml characterising the chart
     * @param array $formsMapping associating xml IDs to real fields
     */
    abstract public function setSpecificPropertiesFromXML($xml, $formsMapping);
    
     /**
     * User as permission to visualize the chart
     */
    abstract public function userCanVisualize();
    
    /**
     * Create an instance of the chart
     * @return GraphOnTrackersV5_Chart
     */
    abstract public static function create($renderer, $id, $rank, $title, $description, $width, $height);
    
    /**
     * Get the dao of the chart
     */
    protected abstract function getDao();
}
 
?>
