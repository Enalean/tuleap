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

require_once 'common/layout/DivBasedTabbedLayout.class.php';

class Bootstrap_Theme extends DivBasedTabbedLayout {
    
    function __construct($root) {
        parent::__construct($root);
    }
    
    function header($params) {
        $htmlclassname = '';
        echo '<!DOCTYPE html> 
        <html lang="en" class="'. $htmlclassname .'"> 
          <head> 
            <meta charset="utf-8"> 
            <title>'. ($params['title'] ? $params['title'] . ' - ' : '') . $GLOBALS['sys_name'] .'</title>
            <link rel="SHORTCUT ICON" href="'. $this->imgroot . 'favicon.ico' .'">';
        echo $this->displayJavascriptElements();
        echo $this->displayStylesheetElements($params);
        echo $this->displaySyndicationElements();
        echo '</head>';
        echo '<body>
                <div class="navbar navbar-fixed-top">
                  <div class="navbar-inner"> 
                    <div class="container-fluid"> 
                      <a class="brand" href="index.php"><img src="'. $this->imgroot . 'organization_logo.png" alt="Tuleap" /></a> 
                      <ul class="nav"> 
                        <li><a href="/">Home</a></li> 
                        <li><a href="/my/">My personnal page</a></li> 
                        <li class="dropdown active">
                            <a href="#contact" class="dropdown-toggle" data-toggle="dropdown">Projects<b class="caret"></b></a>
                            <ul class="dropdown-menu">
                                <li><a href="#p1"><img src="stuff/fav1.png" style="vertical-align:top;" /> Some Awesome</a></li>
                                <li><a href="#p2"><img src="stuff/fav2.png" style="vertical-align:top;" /> Projects I Am</a></li>
                                <li><a href="#p3"><img src="stuff/fav3.png" style="vertical-align:top;" /> Member Of</a></li>
                                <li class="divider"></li>
                                <li><a href="/softwaremap/">Browse all projects</a></li>
                                <li><a href="/project/register.php">Register a new project</a></li>
                            </ul>
                        </li> 
                        <li><a href="/search/?words=%%%&type_of_search=people">Users</a></li> 
                        <li><a href="/site/">Help</a></li> 
                      </ul>
                      <form action="" class="navbar-search pull-left">
                        <input type="text" placeholder="Search" class="search-query" />
                      </form>
                      <ul class="nav pull-right">
                            <li class="dropdown"><a href="#" class="dropdown-toggle"><span class="logged-in">Logged in as</span> Nicolas Terray</a>
                                <ul class="dropdown-menu">
                                    <li><a href="#logout">Bookmark this page</a></li>
                                    <li><a href="/account/">Account Settings</a></li>
                                    <li><a href="/account/preferences.php">Preferences</a></li>
                                    <li class="divider"></li>
                                    <li><a href="#logout"><img src="http://p.yusukekamiyamane.com/icons/search/fugue/icons/door-open-out.png" style="vertical-align:top;" /> Logout</a></li>
                                </ul>
                            </li>
                        </ul>
                      </p> 
                    </div> 
                  </div> 
                </div>';
        /*
        echo '
                <ul class="breadcrumb">
                    <li><a href="/my/">Home</a> <span class="divider">/</span></li>
                    <li><a href="/softwaremap/">Projects</a> <span class="divider">/</span></li>
                    <li><a href="#my">The Garden Project</a> <span class="divider">/</span></li>
                    <li><a href="#my">Project Documentation</a> <span class="divider">/</span></li>
                    <li class="active">Installation &amp; Administration/How to install</li>
                </ul>';
                */
        echo $this->getBreadcrumbs();
        echo $this->_getFeedback();
        echo '<div class="container-fluid">';
        echo '<div class="row-fluid">';
        if (isset($params['group']) && $params['group']) {
            $project = ProjectManager::instance()->getProject($params['group']);
            $sidebar = $this->projectSidebar($project, $params['toptab'], Codendi_HTMLPurifier::instance(), UserManager::instance()->getCurrentUser());
            if ($sidebar) {
                echo $sidebar;
                echo '<div class="span9">';
            }
        }
        echo '<div class="content well">';
        echo $this->getToolbar();
    }

    public function footer($params) {
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        $this->generic_footer($params);
    }

    public function displayJavascriptElements() {
        echo ' <!-- Le HTML5 shim, for IE6-8 support of HTML elements --> 
        <!--[if lt IE 9]>
          <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
        <![endif]-->
        <script src="'. $this->root . '/jquery/jquery-1.7.1.min.js"></script> 
        <script src="'. $this->root . '/bootstrap/js/bootstrap.js"></script>
        <script src="'. $this->root . '/google-code-prettify/prettify.js"></script> 
        <script>$(function () { prettyPrint() })</script> ';
        echo parent::displayJavascriptElements();
    }

    public function displayCommonStylesheetElements($params) {
        echo '<link rel="stylesheet" href="'. $this->root . '/bootstrap/css/bootstrap.css">
        <link href="'. $this->root . '/google-code-prettify/professional.css" rel="stylesheet">';
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

    public function projectSidebar(Project $project, $toptab, Codendi_HTMLPurifier $hp, User $user) {
        $html = '';
        $html .= '<div class="span3">';
        $html .= '<div class="well sidebar-nav">';
        $html .= '<div style="text-align:center">';
        $html .= '<img src="http://placehold.it/176x76" />';
        $html .= '<h5>'. $hp->purify($project->getPublicName()) .'</h5>';
        $html .= '<p>'. $hp->purify($project->getDescription()) .'</p>';
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
