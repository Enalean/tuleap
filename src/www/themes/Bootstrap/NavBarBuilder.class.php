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

class NavBarBuilder {

    public function __construct(ProjectManager $projectManager, EventManager $eventManager, BaseLanguage $language, HTTPRequest $request, PFUser $user, $title, $imgroot, $requestUri, $selectedTopTab) {
        $this->user           = $user;
        $this->title          = $title;
        $this->request        = $request;
        $this->imgroot        = $imgroot;
        $this->language       = $language;
        $this->requestUri     = $requestUri;
        $this->eventManager   = $eventManager;
        $this->projectManager = $projectManager;
        $this->selectedTopTab = $selectedTopTab;
    }

    public function render() {
        $html  = '';
        $html .= '
                <div class="navbar navbar-fixed-top">
                  <div class="navbar-inner"> 
                    <div class="container-fluid"> 
                      <a class="brand" href="/"><img src="'. $this->imgroot . 'organization_logo.png" alt="Tuleap" /></a>'.
                      $this->getMainNavigation().
                      $this->getSearchForm().
                      $this->getUserNavigation()
                      .'
                    </div> 
                  </div> 
                </div>';
        return $html;
    }

    private function getSearchForm() {
        $html  = '';
        $html .= '<form action="" class="navbar-search pull-left">';
        $html .= '<input type="text" placeholder="Search" class="search-query" />';
        $html .= '</form>';
        return $html;
    }

    private function getMainNavigation() {
        $html  = '';
        $html .= '<ul class="nav">';
        $html .= $this->getNavHome();
        $html .= $this->getNavMyPage();
        $html .= $this->getNavProjects();
        $html .= $this->getNavUsers();
        $html .= $this->getNavAdmin();
        $html .= $this->getNavHelp();
        $html .= '</ul>';
        return $html;
    }

    private function getNavUsers() {
        return '';
        //Not yet implemented
        return '<li><a href="/search/?words=%%%&type_of_search=people">Users</a></li>';
    }

    private function getNavAdmin() {
        $html  = '';
        if ($this->user->isSuperUser()) {
            $html .= '<li class="'. $this->getClassnameNavItemActive('/admin/', 'admin') .'">';
            $html .= '<a href="/admin/">Admin</a></li>';
        }
        return $html;
    }

    private function getNavHelp() {
        $html  = '';
        $html .= '<li class="'. $this->getClassnameNavItemActive('/site/', 'site') .'">';
        $html .= '<a href="/site/">Help</a></li>';
        return $html;
    }

    private function getClassnameNavItemActive($pathsToDetect, $toptab = null) {
        if ($toptab === $this->selectedTopTab) {
            $class = 'active';
        } else {
            if (!is_array($pathsToDetect)) {
                $pathsToDetect = array($pathsToDetect);
            }
            $class = '';
            while (!$class && (list(,$path) = each($pathsToDetect))) {
                if (strpos($this->requestUri, $path) === 0) {
                    $class = 'active';
                }
            }
        }
        return $class;
    }

    private function getNavHome() {
        $html  = '';
        $class = '';
        if ($this->requestUri == '/') {
            $class = 'class="active"';
        }
        $html .= '<li '. $class .'><a href="/">';
        $html .= $this->language->getText('menu', 'home');
        $html .= '</a></li>';
        return $html;
    }

    private function getNavProjects() {
        $html_project_register = '';
        if ((isset($GLOBALS['sys_use_project_registration']) && $GLOBALS['sys_use_project_registration'] == 1) || !isset($GLOBALS['sys_use_project_registration'])) {
            $html_project_register .= '<li><a href="/project/register.php">'. $this->language->getText('include_menu','register_new_proj') .'</a></li>';
        } 

        $class = $this->getClassnameNavItemActive(array('/softwaremap/', '/projects/', '/project/'));
        $html  = '';
        $html .= '<li class="dropdown '. $class .'">';
        if ($this->user->isLoggedIn()) {
            $projectIds = $this->user->getAllProjects();
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
            foreach ($projectIds as $projectId) {
                if ($project = $this->projectManager->getProject($projectId)) {
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

    private function getNavMyPage() {
        $html = '';
        if ($this->user->isLoggedIn()) {
            $class = $this->getClassnameNavItemActive(array('/my/', '/widgets/'));
            $html .= '<li class="'. $class .'"><a href="/my/">';
            $html .= $this->language->getText('menu', 'my_personal_page');
            $html .= '</a></li>';
        }
        return $html;
    }

    private function getUserNavigation() {
        $html = '';
        $html .= '<ul class="nav pull-right">';
        if ($this->user->isLoggedIn()) {
            $html .= $this->getNavUserLoggedIn();
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
        $html .= $this->language->getText('include_menu','login');
        $html .= '</a>';
        $html .= '</li>';
        $display_new_user = true;
        $this->eventManager->processEvent('display_newaccount', array('allow' => &$display_new_user));
        if ($display_new_user) {
            $html .= '<li><a href="/account/register.php">'.$this->language->getText('include_menu','new_user').'</a></li>';
        }
        return $html;
    }

    private function getNavUserLoggedIn() {
        $html  = '';
        $class = $this->getClassnameNavItemActive('/account/');
        $html .= '<li class="dropdown '. $class .'">';
        $html .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown">';
        $html .= '<span class="logged-in">Logged in as </span>';
        $html .= $this->user->getRealName();
        $html .= '<b class="caret"></b>';
        $html .= '</a>';
        $html .= $this->getSubNavLoggedinUser();
        $html .= '</li>';
        return $html;
    }

    private function getSubNavLoggedinUser() {
        $html  = '';
        $html .= '<ul class="dropdown-menu">';
        if (!$this->request->isPost()) {
            $bookmark_title = urlencode(str_replace($GLOBALS['sys_name'].': ', '', $this->title));
            $href = '/my/bookmark_add.php?bookmark_url='. urlencode($_SERVER['REQUEST_URI']) .'&bookmark_title='. $bookmark_title;
            $html .= '<li class="bookmarkpage"><a href="'. $href .'">';
            $html .= $this->language->getText('include_menu','bookmark_this_page');
            $html .= '</a></li>';
        }
        $html .= '<li><a href="/account/">'. $this->language->getText('my_index','account_maintenance') .'</a></li>';
        $html .= '<li><a href="/account/preferences.php">'. $this->language->getText('account_options','preferences') .'</a></li>';
        $html .= '<li class="divider"></li>';
        $html .= '<li><a href="/account/logout.php">'.$this->language->getText('include_menu','logout').'</a></li>';
        $html .= '</ul>';
        return $html;
    }

}
?>
