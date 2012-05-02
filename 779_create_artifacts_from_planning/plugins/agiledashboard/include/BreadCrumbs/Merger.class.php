<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

/**
 * Merge two breadcrumbs into a single one, by appending the second to the first 
 */
class BreadCrumb_Merger implements BreadCrumb_BreadCrumbGenerator {

    /**
     * @var array of BreadCrumb_BreadCrumbGenerator
     */
    private $generators;
    
    /**
     * Takes a variable number of generators 
     * @param BreadCrumb_BreadCrumbGenerator $breadCrumb1
     * @param BreadCrumb_BreadCrumbGenerator $breadCrumb2
     * @param BreadCrumb_BreadCrumbGenerator $andMore 
     */
    function __construct(BreadCrumb_BreadCrumbGenerator $breadCrumb1, BreadCrumb_BreadCrumbGenerator $breadCrumb2, BreadCrumb_BreadCrumbGenerator $andMore = null) {
        $this->generators = func_get_args();
    }

    public function getCrumbs() {
        $crumbs = array();
        foreach ($this->generators as $crumb) {
            $crumbs = array_merge($crumbs, $crumb->getCrumbs());
        }
        return $crumbs;
    }
}

?>
