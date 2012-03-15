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

require_once 'sys/WorldCatUtils.php';
require_once 'sys/Recommend/Interface.php';

/**
 * WorldCatIdentities Recommendations Module
 *
 * This class provides recommendations by using the WorldCat Terminologies API.
 */
class WorldCatIdentities implements RecommendationInterface
{
    private $searchObject;
    private $params;
    
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
        $this->params = $params;
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
        // No action needed here.
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
        
        // Extract the first search term from the search object:
        $search = $this->searchObject->getSearchTerms();
        $lookfor = isset($search[0]['lookfor']) ? $search[0]['lookfor'] : '';
        
        // Get terminology information:
        $wc = new WorldCatUtils();
        $identities = $wc->getRelatedIdentities($lookfor);
        
        $interface->assign('WorldCatIdentities', $identities);
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
        return 'Search/Recommend/WorldCatIdentities.tpl';
    }
}

?>