<?php
/**
 *
 * Copyright (C) Andrew Nagy 2009.
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

require_once 'Base.php';


class Home extends Base {
    
    function launch()
    {
        global $interface;
        global $configArray;

        // Cache homepage
        $interface->caching = 1; 
        $cacheId = 'summon-homepage|' . $interface->lang;
        if (!$interface->is_cached('layout.tpl', $cacheId)) {
            $interface->setPageTitle('Search Home');
            $interface->setTemplate('home.tpl');

            // Search Summon
            $summon = new Summon($configArray['Summon']['apiId'], $configArray['Summon']['apiKey']);
            $results = $summon->query('', null, null, 0, null, array('ContentType,or,1,20', 'Language,or,1,20'));

            $interface->assign('formatList', $results['facetFields'][0]);
            $interface->assign('languageList', $results['facetFields'][1]);
        }
        $interface->display('layout.tpl', $cacheId);

    }

}

?>
