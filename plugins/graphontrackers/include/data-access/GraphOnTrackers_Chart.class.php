<?php
/**
 * Copyright (c) Enalean, 2017-2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
abstract class GraphOnTrackers_Chart {

    const MARKER_BEGINNING_OUTPUT_DISPLAY = '💩';

    protected $id;
    protected $rank;
    protected $title;
    protected $description;
    protected $graphic_report;
    protected $width;
    protected $height;
    
    /**
     * @param GraphicReport The graphic_report wich contains the chart
     * @param int The id of the chart
     * @param int The rank of the chart
     * @param string The title of the chart
     * @param string The description of the chart
     * @param int The width of the chart
     * @param int The height of the chart
     */
    public function __construct($graphic_report, $id, $rank, $title, $description, $width, $height) {
        $this->graphic_report = $graphic_report;
        $this->id             = $id;
        $this->rank           = $rank;
        $this->title          = $title;
        $this->description    = $description;
        $this->width          = $width;
        $this->height         = $height;
    }
    
    /* Getters and setters */
    public function getId() { return $this->id; }
    public function getRank() { return $this->rank; }
    public function setRank($rank) { $this->rank = $rank; }
    public function getTitle() { return $this->title; }
    public function setTitle($title) { $this->title = $title; }
    public function getDescription() { return $this->description; }
    public function setDescription($description) { $this->description = $description; }
    public function getGraphicReport() { return $this->graphic_report; }
    public function setGraphicReport($graphic_report) { $this->graphic_report = $graphic_report; }
    public function getHeight() { return $this->height; }
    public function setHeight($height) { return $this->height = $height; }
    public function getWidth() { return $this->width; }
    public function setWidth($width) { return $this->width = $width; }
    public static function getDefaultHeight(){ return 400; }
    public static function getDefaultWidth(){ return 600; }
    /**
     * Display the html <img /> tag to embed the chart in a html page.
     */
    public function displayImgTag() {
        $urlimg = "/plugins/graphontrackers/reportgraphic.php?_jpg_csimd=1&group_id=".(int)$this->graphic_report->getGroupId()."&atid=". $this->graphic_report->getAtid();
        $urlimg .= "&report_graphic_id=".$this->graphic_report->getId()."&id=".$this->getId();
        
        
        echo '<img  src="'. $urlimg .'"  ismap usemap="#map'. $this->getId() .'"  ';
        if ($this->width) {
            echo ' width="'. $this->width .'" ';
        }
        if ($this->height) {
            echo ' height="'. $this->height .'" ';
        }
        echo ' alt="'. $this->title .'" border="0">';
    }
    
    /**
     * Display both <img /> and <map /> tags to embed the chart in a html page
     */
    public function display() {
    	
    	if($this->userCanVisualize()){
    		
    		$e = $this->buildGraph();
    		if($e){
                $this->displayHTMLImageMapWithoutInterruptingExecutionFlow($e, "map".$this->getId());
	        	$this->displayImgTag();
    		}
    	}
    }

    private function displayHTMLImageMapWithoutInterruptingExecutionFlow(GraphOnTrackers_Engine $engine, $image_map)
    {
        ob_start();
        echo self::MARKER_BEGINNING_OUTPUT_DISPLAY;
        try {
            $html = $engine->graph->GetHTMLImageMap($image_map);
        } catch (Exception $ex) {
            ob_clean();
            throw $ex;
        }
        echo mb_substr(ob_get_clean(), mb_strlen(self::MARKER_BEGINNING_OUTPUT_DISPLAY));
        echo $html;
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
        //based on the user pref (last report, last query
        $geup =  new graphicEngineUserPrefs($this->graphic_report->getAtid());
        $geup->fetchPrefs();
        $artifacts = $geup->getArtifactsInOrder();
        
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
        $dao = new GraphOnTrackers_ChartDao(CodendiDataAccess::instance());
        foreach($dao->getSiblings($this->getId()) as $row) {
            $siblings[] = array('id' => $row['id'], 'name' => $row['title'], 'rank' => $row['rank']);
        }
        return array(
            'id'          => new HTML_Element_Input_Hidden($GLOBALS['Language']->getText('plugin_graphontrackers_property','id'), 'chart[id]', $this->getId()),
            'title'       => new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackers_property','title'), 'chart[title]', $this->getTitle()),
            'description' => new HTML_Element_Textarea($GLOBALS['Language']->getText('plugin_graphontrackers_property','description'), 'chart[description]', $this->getDescription()),
            'rank'        => new HTML_Element_Selectbox_Rank($GLOBALS['Language']->getText('plugin_graphontrackers_property','rank'), 'chart[rank]', $this->getRank(), $this->getId(), $siblings),
            'dimensions'  => new HTML_Element_Columns(
                                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackers_property','width'), 'chart[width]', $this->getWidth(), 4),
                                new HTML_Element_Input_Text($GLOBALS['Language']->getText('plugin_graphontrackers_property','height'), 'chart[height]', $this->getHeight(), 4)
                             ),
        );
    }
    
    /**
     * Update the properties of the chart
     *
     * @return boolean true if the update is successful
     */
    public function update($row) {
        $db_update_needed = false;
        foreach(array('rank', 'title', 'description', 'width', 'height') as $prop) {
            if (isset($row[$prop]) && $this->$prop != $row[$prop]) {
                $this->$prop = $row[$prop];
                $db_update_needed = true;
            }
        }
        
        $updated = false;
        if ($db_update_needed) {
            $dao = new GraphOnTrackers_ChartDao(CodendiDataAccess::instance());
            $updated = $dao->updateById($this->id, $this->graphic_report->getId(), $this->rank, $this->title, $this->description, $this->width, $this->height);
        }
        return $this->updateSpecificProperties($row) && $updated;
    }
    
    /**
     * @return string The inline help of the chart
     */
    public function getHelp() {
        return '';
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
     * Delete the chart from its report
     */
    abstract public function delete();
    
    
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
     * User as permission to visualize the chart
     */
    abstract public function userCanVisualize();
    
    /**
     * Create an instance of the chart
     * @return GraphOnTrackers_Chart
     */
    abstract public static function create($graphic_report, $id, $rank, $title, $description, $width, $height);
}
 
?>
