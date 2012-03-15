<?php
/**
 *
 * Copyright (C) Villanova University 2009.
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
 * TopFacets Recommendations Module
 *
 * This class provides recommendations displaying facets above search results
 */
class TopFacets implements RecommendationInterface
{
    private $searchObject;
    private $facets;
    private $baseSettings;

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
        // Save the basic parameters:
        $this->searchObject = $searchObject;
        
        // Parse the additional parameters:
        $params = explode(':', $params);
        $section = empty($params[0]) ? 'ResultsTop' : $params[0];
        $iniFile = isset($params[1]) ? $params[1] : 'facets';

        // Load the desired facet information:
        $config = getExtraConfigArray($iniFile);
        $this->facets = isset($config[$section]) ? $config[$section] : array();
        
        // Load other relevant settings:
        $this->baseSettings = array(
            'rows' => $config['Results_Settings']['top_rows'],
            'cols' => $config['Results_Settings']['top_cols']
        );
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
        // Turn on top facets in the search results:
        foreach($this->facets as $name => $desc) {
            $this->searchObject->addFacet($name, $desc);
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
        
        // Grab the facet set -- note that we need to take advantage of the third
        // parameter to getFacetList in order to pass down row and column 
        // information for inclusion in the final list.
        $interface->assign('topFacetSet',
            $this->searchObject->getFacetList($this->facets, false));
        $interface->assign('topFacetSettings', $this->baseSettings);
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
        return 'Search/Recommend/TopFacets.tpl';
    }
}

?>