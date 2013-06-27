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

require_once 'common/layout/DivBasedTabbedLayout.class.php';
require_once 'NavBarBuilder.class.php';
require_once 'Bootstrap_FeedbackFormatter.class.php';

class Experimental_Theme extends DivBasedTabbedLayout {
    
    function __construct($root) {
        parent::__construct($root);
        $this->_feedback->setFormatter(new Bootstrap_FeedbackFormatter());
    }

    public function header($params) {
        $htmlclassname = '';
        if (isset($params['htmlclassname'])) {
            $htmlclassname = $params['htmlclassname'];
        }
        echo '<!DOCTYPE html> 
        <html lang="en" class="'. $htmlclassname .'">';
        echo $this->head($params);
        echo $this->body($params);
    }

    private function head($params) {
        $title = $this->getHtmlTitleFromParams($params);
        $html  = '';
        $html .= '<head> 
            <meta charset="utf-8"> 
            <title>'. $title .'</title>
            <link rel="SHORTCUT ICON" href="'. $this->imgroot . 'favicon.ico' .'">';
        $html .=  $this->displayJavascriptElements();
        $html .=  $this->displayStylesheetElements($params);
        $html .=  $this->displaySyndicationElements();
        $html .=  '</head>';
        return $html;
    }

    private function getHtmlTitleFromParams($params) {
        $title = $GLOBALS['sys_name'];
        if (!empty($params['title'])) {
           $title = $params['title'] .' - '. $title;
        }
        return $title;
    }


    private function body($params) {
        $project_manager = ProjectManager::instance();
        $current_user    = UserManager::instance()->getCurrentUser();
        $html  = '';
        $html .= '<body>';
        $selectedTopTab = isset($params['selected_top_tab']) ? $params['selected_top_tab'] : false;
        $nav = new NavBarBuilder($project_manager, EventManager::instance(), $GLOBALS['Language'], HTTPRequest::instance(), $current_user, $params['title'], $this->imgroot, $_SERVER['REQUEST_URI'], $selectedTopTab);
        $html .= $nav->render();
        $html .= $this->_getFeedback();
        $this->_feedback->display();
        $html .= $this->container($params, $project_manager, $current_user);
        return $html;
    }

    private function container(array $params, ProjectManager $project_manager, PFUser $current_user) {
        $html  = '';
        $html .= '<div class="main container-fluid">';
        $html .= '<div class="row-fluid">';
        if (!empty($params['group'])) {
            $project = ProjectManager::instance()->getProject($params['group']);
            $sidebar = $this->projectSidebar($project, $params['toptab'], Codendi_HTMLPurifier::instance(), $current_user);
            if ($sidebar) {
                $html .= $sidebar;
                $html .= '<div class="span10">';
            }
        }
        $html .= '<div class="content well">';
        $html .= $this->getBreadcrumbs();
        $html .= $this->getToolbar();
        return $html;
    }

    public function footer($params) {
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        $this->generic_footer($params);
    }

    public function displayCommonStylesheetElements($params) {
        //echo '<link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.no-icons.min.css" rel="stylesheet">';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/style.css" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('style.css') .'"  />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('print.css') .'" media="print" />';
    }

    public function getBreadCrumbs() {
        $html = '';
        $nb = count($this->breadcrumbs);
        if ($nb) {
            $html .= '<ul class="breadcrumb">';
            $i = 0;
            foreach ($this->breadcrumbs as $breadcrumb) {
                $active = '';
                if ($i == $nb - 1) {
                    $active = 'class="active"';
                }
                $html .= "<li $active>";
                $html .= $breadcrumb;
                if (!$active) {
                    $html .= ' <span class="divider">/</span>';
                }
                $html .= '</li>';
                $i++;
            }
            $html .= '</ul>';
        }
        return $html;
    }

    function getToolbar() {
        $html = '';
        if (count($this->toolbar)) {
            $html .= '<ul class="nav nav-pills"><li>';
            $html .= implode('</li><li>', $this->toolbar);
            $html .= '</li></ul>';
            $html .= '<div class="clearfix">';
            $html .= '</div>';
        }
        return $html;
    }

    public function projectSidebar(Project $project, $toptab, Codendi_HTMLPurifier $hp, PFUser $user) {
        $html = '';
        $html .= '<div class="span2">';
        $html .= '<div class="well sidebar-nav">';
        $html .= '<div style="text-align:center">';
        $html .= '<img src="http://placehold.it/176x76" />';
        $html .= '<h5>'. $hp->purify($project->getPublicName()) .'</h5>';
        //$html .= '<p>'. $hp->purify($project->getDescription()) .'</p>';
        $html .= '</div>';
        $html .= '<ul class="nav nav-list">';
        foreach ($this->_getProjectTabs($toptab, $project) as $tab) {
            $active = '';
            if ($tab['enabled']) {
                $active = 'class="active"';
            }
            $html .= "<li $active>";
            $html .= '<a href="'. $tab['link'] .'">'. $tab['label'] .'</a>';
            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';
        return $html;
    }
}

?>
