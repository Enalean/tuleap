<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
require_once dirname(__FILE__) .'/../../include/autoload.php';

class FullTextSearch_TypeOfSearchCalculatorTest extends TuleapTestCase {

    public function itReturnsTypeOfSearchFromRequestIfNotInLabMode() {
        $user                  = mock('PFUser');
        $plugin_manager        = mock('PluginManager');
        $fulltextsearch_plugin = mock('fulltextsearchPlugin');
        $calculator            = new FullTextSearch_TypeOfSearchCalculator($plugin_manager, $fulltextsearch_plugin);

        $current_type = 'wild guess';
        $service_name = 'tea_set';
        $project_id   = 125886;

        stub($user)->useLabFeatures()->returns(false);

        $type = $calculator->calculate($user, $current_type, $service_name, $project_id);

        $this->assertEqual($type, 'wild guess');
    }

    /*
     * All other tests in lab mode
     */
    public function itReturnsWikiTypeIfUserWasInWikiServiceAndProjectNotIndexed() {
        $user                  = mock('PFUser');
        $plugin_manager        = mock('PluginManager');
        $fulltextsearch_plugin = mock('fulltextsearchPlugin');
        $calculator            = new FullTextSearch_TypeOfSearchCalculator($plugin_manager, $fulltextsearch_plugin);

        $current_type = 'wild guess';
        $service_name = Search_SearchWiki::NAME;
        $project_id   = 125886;

        stub($user)->useLabFeatures()->returns(true);
        stub($plugin_manager)->isPluginAllowedForProject($fulltextsearch_plugin, $project_id)->returns(false);

        $type = $calculator->calculate($user, $current_type, $service_name, $project_id);

        $this->assertEqual($type, Search_SearchWiki::NAME);
    }

    public function itReturnsFullTextTypeIfUserWasInWikiServiceAndProjectIsIndexed() {
        $user                  = mock('PFUser');
        $plugin_manager        = mock('PluginManager');
        $fulltextsearch_plugin = mock('fulltextsearchPlugin');
        $calculator            = new FullTextSearch_TypeOfSearchCalculator($plugin_manager, $fulltextsearch_plugin);

        $current_type = 'wild guess';
        $service_name = Search_SearchWiki::NAME;
        $project_id   = 125886;

        stub($user)->useLabFeatures()->returns(true);
        stub($plugin_manager)->isPluginAllowedForProject($fulltextsearch_plugin, $project_id)->returns(true);

        $type = $calculator->calculate($user, $current_type, $service_name, $project_id);

        $this->assertEqual($type, fulltextsearchPlugin::SEARCH_TYPE);
    }

    public function itReturnsFullTextTypeIfUserWasInDocmanService() {
        $user                  = mock('PFUser');
        $plugin_manager        = mock('PluginManager');
        $fulltextsearch_plugin = mock('fulltextsearchPlugin');
        $calculator            = new FullTextSearch_TypeOfSearchCalculator($plugin_manager, $fulltextsearch_plugin);

        $current_type = 'wild guess';
        $service_name = 'docman';
        $project_id   = 125886;

        stub($user)->useLabFeatures()->returns(true);

        $type = $calculator->calculate($user, $current_type, $service_name, $project_id);

        $this->assertEqual($type, fulltextsearchPlugin::SEARCH_TYPE);
    }

    public function itReturnsFullTextTypeIfUserWasInTrackerService() {
        $user                  = mock('PFUser');
        $plugin_manager        = mock('PluginManager');
        $fulltextsearch_plugin = mock('fulltextsearchPlugin');
        $calculator            = new FullTextSearch_TypeOfSearchCalculator($plugin_manager, $fulltextsearch_plugin);

        $current_type = 'wild guess';
        $service_name = 'tracker';
        $project_id   = 125886;

        stub($user)->useLabFeatures()->returns(true);

        $type = $calculator->calculate($user, $current_type, $service_name, $project_id);

        $this->assertEqual($type, fulltextsearchPlugin::SEARCH_TYPE);
    }

    public function itReturnsGenericTypeIfUserWasInGenericService() {
        $user                  = mock('PFUser');
        $plugin_manager        = mock('PluginManager');
        $fulltextsearch_plugin = mock('fulltextsearchPlugin');
        $calculator            = new FullTextSearch_TypeOfSearchCalculator($plugin_manager, $fulltextsearch_plugin);

        $current_type = 'wild guess';
        $service_name = 'my service';
        $project_id   = 125886;

        stub($user)->useLabFeatures()->returns(true);

        $type = $calculator->calculate($user, $current_type, $service_name, $project_id);

        $this->assertEqual($type, 'wild guess');
    }
}