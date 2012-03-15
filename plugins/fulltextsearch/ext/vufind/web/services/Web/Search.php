<?php
/**
 *
 * Copyright (C) Villanova University 2007.
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
 
require_once 'Action.php';

class Search extends Action
{
    function launch()
    {
        global $configArray;
        global $interface;

        // Assign basic values
        $interface->assign('googleKey', $configArray['GoogleSearch']['key']);
        $interface->assign('domain', $configArray['GoogleSearch']['domain']);
        $interface->assign('lookfor', $_GET['lookfor']);

        // Optional advanced value -- by using the queryAddition GET parameter,
        // you can append a string to the user's queries.  This can be useful
        // for filtering to a particular type of content or subsection of the
        // site using various advanced Google operators (i.e. inurl:example).
        $interface->assign('queryAddition', isset($_GET['queryAddition']) ?
            $_GET['queryAddition'] : false);

        // Load recommendation module settings -- default to CatalogResults if
        // no settings were found.
        $recSettings = isset($configArray['GoogleSearch']['side_recommend']) ?
            $configArray['GoogleSearch']['side_recommend'] : 
            array('CatalogResults:lookfor');
        $recSettings = is_array($recSettings) ? $recSettings : array($recSettings);

        // Build recommendations modules based on the loaded settings:
        $recommendations = array();
        foreach($recSettings as $current) {
            if (!empty($current)) {
                @list($name, $params) = explode(':', $current, 2);
                $rec = RecommendationFactory::initRecommendation($name, null, $params);
                $rec->init();
                $rec->process();
                $recommendations[] = $rec->getTemplate();
            }
        }
        $interface->assign('recommendations', $recommendations);

        // Set up the page
        $interface->setPageTitle('Library Web Search');
        $interface->setTemplate('home.tpl');
        $interface->display('layout.tpl');
    }
}

?>
