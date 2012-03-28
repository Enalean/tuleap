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

require_once 'common/project/Service.class.php';
require_once 'SearchContentView.class.php';
require_once dirname(__FILE__).'/../Report/Tracker_Report.class.php';
require_once dirname(__FILE__).'/../Hierarchy/Hierarchy.class.php';
require_once 'common/TreeNode/InjectPaddingInTreeNodeVisitor.class.php';

require_once 'html.php';

class Tracker_CrossSearch_SearchView {
    
    /**
     * @var Project
     */
    private $project;
    
    /**
     * @var Service
     */
    private $service;
    
    /**
     * @var Array of Tracker_Report_Criteria
     */
    private $criteria;
    
    /**
     * @var Array of Tracker
     */
    private $trackers;

    public function __construct(Project                          $project,
                                Service                          $service,
                                array                            $criteria, 
                                                                 $trackers) {
        $this->project           = $project;
        $this->service           = $service;
        $this->criteria          = $criteria;
        $this->trackers          = $trackers;
    }
    
    public function render(Tracker_CrossSearch_SearchContentView $content_view) {
        $title = $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'title');
        
        $breadcrumbs = array(
            array(
                'url' => null,
                'title' => $title,
            )
        );
        
        $this->service->displayHeader($title, $breadcrumbs, array());
        
        $html  = '';
        $html .= $this->fetchTrackerHomeNav();
        $html .= '<div class="tracker_homenav_cross_search">';
        $html .= '<h1>'. $title .'</h1>';
        $html .= '<p class="lab_features" title="'. $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'creation_lab_feature') .'">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'creation_lab_feature');
        $html .= '</p>';
        
        if ($this->criteria) {
            $html .= $content_view->fetch();
            $html .= $this->fetchTrackerList();
        } else {
            $html .= '<em>'. 'There is no shared field to query across your trackers' .'</em>';
        }
        $html .= '</div>';
        echo $html;
        
        $this->service->displayFooter();
    }
    
    private function fetchTrackerHomeNav() {
        $presenter = new Tracker_HomeNavPresenter($this->project, 'cross-search');
        $renderer  = new MustacheRenderer(dirname(__FILE__).'/../../../templates');
        return $renderer->render('tracker-home-nav', $presenter);
    }

    
    private function fetchTrackerList() {
        $html  = '';
        $html .= '<div class="tracker_homenav_list">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'included_trackers_title');
        if (count($this->trackers) > 0) {
            $html .= '<ul>';
            foreach($this->trackers as $tracker) {
                $html .= '<li>';
                $html .= $tracker->getName().' ('.$tracker->getProject()->getPublicName().')';
                $html .= '</li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<p><em>'.$GLOBALS['Language']->getText('plugin_tracker_crosssearch', 'included_trackers_not_found').'</em></p>';
        }
        $html .= '</div>';
        return $html;
    }

}
?>
