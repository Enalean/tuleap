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
 * SummonResults Recommendations Module
 *
 * This class provides recommendations by doing a search of Summon.
 */
class SummonDatabases implements RecommendationInterface
{
    private $searchObject;
    
    /* Constructor
     *
     * Establishes base settings for making recommendations.
     *
     * @access  public
     * @param   object  $searchObject   The SearchObject requesting recommendations.
     * @param   string  $requestParam   $_REQUEST field containing search terms
     *                                  (ignored if $searchObject is Summon type).
     */
    public function __construct($searchObject, $requestParam)
    {
        // If we received a Summon search object, we'll use that.  If not, we need
        // to create a new Summon search object using the specified REQUEST 
        // parameter for search terms.
        if (strtolower(get_class($searchObject)) == 'searchobject_summon') {
            $this->searchObject = $searchObject;
        } else {
            $this->searchObject = SearchObjectFactory::initSearchObject('Summon');
            $this->searchObject->disableLogging();
            $this->searchObject->setBasicQuery($_REQUEST[$requestParam]);
            $this->searchObject->processSearch(true);
        }
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
        
        $interface->assign('summonDatabases',
            $this->searchObject->getDatabaseRecommendations());
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
        return 'Search/Recommend/SummonDatabases.tpl';
    }
}

?>