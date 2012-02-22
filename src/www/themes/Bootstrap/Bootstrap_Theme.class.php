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
        $current_user  = UserManager::instance()->getCurrentUser();
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
                        <li><a href="/">Home</a></li>'.
                        $this->getNavMyPage($current_user)
                        .
                        $this->getNavProjects($current_user)
                        .'
                        <li><a href="/search/?words=%%%&type_of_search=people">Users</a></li>
                        <li><a href="/site/">Help</a></li>
                      </ul>
                      <form action="" class="navbar-search pull-left">
                        <input type="text" placeholder="Search" class="search-query" />
                      </form>
                      '.
                      $this->getNavUser($current_user, $params['title'])
                      .'
                    </div> 
                  </div> 
                </div>';
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

    private function getNavProjects(User $user) {
        $html_project_register = '';
        if ((isset($GLOBALS['sys_use_project_registration']) && $GLOBALS['sys_use_project_registration'] == 1) || !isset($GLOBALS['sys_use_project_registration'])) {
            $html_project_register .= '<li><a href="/project/register.php">'. $GLOBALS['Language']->getText('include_menu','register_new_proj') .'</a></li>';
        } 

        $html  = '';
        $html .= '<li class="dropdown active">';
        if ($user->isLoggedIn()) {
            $projectIds = $user->getAllProjects();
            $html_my_projects = $this->getNavMyProjects($projectIds);
            if ($html_my_projects || $html_project_register) {
            $html .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">Projects<b class="caret"></b></a>';
            $html .= '<ul class="dropdown-menu">';
            $html .= $html_my_projects;
            $html .= '<li><a href="/softwaremap/">Browse all projects</a></li>';
            $html .= $html_project_register;
            $html .= '</ul>';
            } else {
                $html .= $this->getNavProjectsAnonymous();
            }
        } else {
            $html .= $this->getNavProjectsAnonymous();
        }
        $html .= '</li>';
        return $html;
    }

    private function getNavProjectsAnonymous() {
        return '<a href="/softwaremap/">Projects</a>';
    }

    private function getNavMyProjects($projectIds) {
        $html  = '';
        if ($projectIds) {
            $pm = ProjectManager::instance();
            foreach ($projectIds as $projectId) {
                if ($project = $pm->getProject($projectId)) {
                    $html .= '<li>';
                    $html .= '<a href="/projects/'. $project->getUnixName() .'/">';
                    $html .= $project->getPublicName();
                    $html .= '</a>';
                    $html .= '</li>';
                }
            }
            $html .= '<li class="divider"></li>';
        }
        return $html;
    }

    private function getNavMyPage(User $user) {
        $html = '';
        if ($user->isLoggedIn()) {
            $html .= '<li><a href="/my/">My personnal page</a></li>';
        }
        return $html;
    }

    private function getNavUser(User $user, $title) {
        $html = '';
        $html .= '<ul class="nav pull-right">';
        if ($user->isLoggedIn()) {
            $html .= $this->getNavUserLoggedIn($user, $title);
        } else {
            $html .= $this->getNavUserAnonymous();
        }
        $html .= '</ul>';
        return $html;
    }

    private function getNavUserAnonymous() {
        $html  = '';
        $html .= '<li>';
        $html .= '<a href="/account/login.php">';
        $html .= $GLOBALS['Language']->getText('include_menu','login');
        $html .= '</a>';
        $html .= '</li>';
        $em =& EventManager::instance();
        $display_new_user = true;
        $em->processEvent('display_newaccount', array('allow' => &$display_new_user));
        if ($display_new_user) {
            $html .= '<li><a href="/account/register.php">'.$GLOBALS['Language']->getText('include_menu','new_user').'</a></li>';
        }
        return $html;
    }

    private function getNavUserLoggedIn(User $user, $title) {
        $html  = '';
        $html .= '<li class="dropdown">';
        $html .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">';
        $html .= '<span class="logged-in">Logged in as </span>';
        $html .= $user->getRealName();
        $html .= '<b class="caret"></b>';
        $html .= '</a>';
        $html .= $this->getSubNavLoggedinUser($user, $title);
        $html .= '</li>';
        return $html;
    }

    private function getSubNavLoggedinUser(User $user, $title) {
        $html  = '';
        $html .= '<ul class="dropdown-menu">';
        if (!HTTPRequest::instance()->isPost()) {
            $bookmark_title = urlencode(str_replace($GLOBALS['sys_name'].': ', '', $title));
            $href = '/my/bookmark_add.php?bookmark_url='. urlencode($_SERVER['REQUEST_URI']) .'&bookmark_title='. $bookmark_title;
            $html .= '<li class="bookmarkpage"><a href="'. $href .'">';
            $html .= $GLOBALS['Language']->getText('include_menu','bookmark_this_page');
            $html .= '</a></li>';
        }
        $html .= '<li><a href="/account/">'. $GLOBALS['Language']->getText('my_index','account_maintenance') .'</a></li>';
        $html .= '<li><a href="/account/preferences.php">'. $GLOBALS['Language']->getText('account_options','preferences') .'</a></li>';
        $html .= '<li class="divider"></li>';
        $html .= '<li><a href="/account/logout.php">'.$GLOBALS['Language']->getText('include_menu','logout').'</a></li>';
        $html .= '</ul>';
        return $html;
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
