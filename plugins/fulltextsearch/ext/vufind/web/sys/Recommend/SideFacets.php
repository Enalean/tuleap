<?php
/**
 *
 * Copyright (C) Villanova University 2010.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'sys/Recommend/Interface.php';

/**
 * SideFacets Recommendations Module
 *
 * This class provides recommendations displaying facets beside search results
 */
class SideFacets implements RecommendationInterface
{
    private $searchObject;
    private $mainFacets;
    private $checkboxFacets;

    /* Constructor
     *
     * Establishes base settings for making recommendations.
     *
     * @access  public
     * @param   object  $searchObject   The SearchObject requesting recommendations.
     * @param   string  $params         Additional settings from the searches.ini.
     */
    public function __construct($searchObject, $params)
    {
        // Save the passed-in SearchObject:
        $this->searchObject = $searchObject;
        
        // Parse the additional settings:
        $params = explode(':', $params);
        $mainSection = empty($params[0]) ? 'Results' : $params[0];
        $checkboxSection = isset($params[1]) ? $params[1] : false;
        $iniName = isset($params[2]) ? $params[2] : 'facets';
        
        // Load the desired facet information:
        $config = getExtraConfigArray($iniName);
        $this->mainFacets = isset($config[$mainSection]) ? 
            $config[$mainSection] : array();
        $this->checkboxFacets =
            ($checkboxSection && isset($config[$checkboxSection])) ?
            $config[$checkboxSection] : array();
    }

    /* init
     *
     * Called before the SearchObject performs its main search.  This may be used
     * to set SearchObject parameters in order to generate recommendations as part
     * of the search.
     *
     * @access  public
     */
    public function init()
    {
        // Turn on side facets in the search results:
        foreach($this->mainFacets as $name => $desc) {
            $this->searchObject->addFacet($name, $desc);
        }
        foreach($this->checkboxFacets as $name => $desc) {
            $this->searchObject->addCheckboxFacet($name, $desc);
        }
    }

    /* process
     *
     * Called after the SearchObject has performed its main search.  This may be 
     * used to extract necessary information from the SearchObject or to perform
     * completely unrelated processing.
     *
     * @access  public
     */
    public function process()
    {
        global $interface;
        $interface->assign('checkboxFilters',
            $this->searchObject->getCheckboxFacets());
        $interface->assign('filterList', $this->searchObject->getFilterList(true));
        $interface->assign('sideFacetSet',
            $this->searchObject->getFacetList($this->mainFacets));
    }

    /* getTemplate
     *
     * This method provides a template name so that recommendations can be displayed
     * to the end user.  It is the responsibility of the process() method to
     * populate all necessary template variables.
     *
     * @access  public
     * @return  string      The template to use to display the recommendations.
     */
    public function getTemplate()
    {
        return 'Search/Recommend/SideFacets.tpl';
    }
}

?>