<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once 'GitViewsRepositoriesTraversalStrategyTest.class.php';
require_once dirname(__FILE__) .'/../include/GitViewsRepositoriesTraversalStrategy_Selectbox.class.php';
Mock::generate('GitViews');
Mock::generate('User');

class GitViewsRepositoriesTraversalStrategy_SelectboxTest extends GitViewsRepositoriesTraversalStrategyTest {
    
    public function __construct() {
        parent::__construct('GitViewsRepositoriesTraversalStrategy_Selectbox');
    }
    
    public function getExpectedPattern($repositories) {
        $li_regexp_for_repository_representation = '<option>(?P<repo>.*)</option>';
        $nb_repositories                         = count($repositories);
        
        return sprintf('<select multiple size="7">(?:%s){%d}</select>', $li_regexp_for_repository_representation, $nb_repositories);
    }
    
}
?>
