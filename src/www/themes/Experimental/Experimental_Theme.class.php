<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/templating/TemplateRenderer.class.php';
require_once 'common/templating/TemplateRendererFactory.class.php';
require_once 'common/layout/DivBasedTabbedLayout.class.php';
require_once 'HeaderPresenter.class.php';
require_once 'BodyPresenter.class.php';
require_once 'ContainerPresenter.class.php';
require_once 'FooterPresenter.class.php';
require_once 'NavBarPresenter.class.php';
require_once 'SearchFormPresenter.class.php';

class Experimental_Theme extends DivBasedTabbedLayout {

    /**
     * @var TemplateRenderer
     */
    protected $renderer;

    private $show_sidebar = false;

    function __construct($root) {
        parent::__construct($root);
        $this->renderer = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $this->includeJavascriptFile('/themes/Experimental/js/navbar.js');
        $this->includeJavascriptFile('/themes/Experimental/js/sidebar.js');
        $this->includeJavascriptFile('/themes/Experimental/js/motd.js');
    }

    private function render($template_name, $presenter) {
        $this->renderer->renderToPage($template_name, $presenter);
    }

    private function getTemplateDir() {
        return dirname(__FILE__) . '/templates/';
    }

    public function isLabFeature() {
        return false;
    }

    public function header($params) {
        $title = $GLOBALS['sys_name'];
        if (!empty($params['title'])) {
           $title = $params['title'] .' - '. $title;
        }

        $this->render('header', new Experimental_HeaderPresenter(
            $title,
            $this->imgroot
        ));

        $this->displayJavascriptElements();
        $this->displayStylesheetElements($params);
        $this->displaySyndicationElements();

        $this->body($params);
    }

    protected function displayCommonStylesheetElements($params) {
        $this->displayFontAwesomeStylesheetElements();

        $css = $GLOBALS['sys_user_theme'] . $this->getFontSizeName($GLOBALS['sys_user_font_size']) .'.css';
        if (file_exists($GLOBALS['codendi_dir'].'/src/www'.$this->getStylesheetTheme($css))) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme($css) .'" />';
        }

        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-select/bootstrap-select.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/jscrollpane/jquery.jscrollpane.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/jscrollpane/jquery.jscrollpane-tuleap.css" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('style.css') .'" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('print.css') .'" media="print" />';

        $custom_dir = $GLOBALS['codendi_dir'].'/src/www'.$this->getStylesheetTheme('').'custom';
        foreach(glob($custom_dir.'/*.css') as $custom_css_file) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('custom/'.basename($custom_css_file)) .'" />';
        }
    }

    private function body($params) {
        $selected_top_tab = isset($params['selected_top_tab']) ? $params['selected_top_tab'] : false;
        $body_class       = isset($params['body_class']) ? $params['body_class'] : array();
        $has_sidebar      = isset($params['group']) ? 'has_sidebar' : '';
        $body_class[]     = $has_sidebar;

        $this->render('body', new Experimental_BodyPresenter(
            $_SERVER['REQUEST_URI'],
            $params['title'],
            $this->imgroot,
            $selected_top_tab,
            $this->getNotificationPlaceholder(),
            $body_class
        ));

        $current_user = UserManager::instance()->getCurrentUser();

        $this->navbar($params, $current_user, $selected_top_tab);

    }

    private function navbar($params, PFUser $current_user, $selected_top_tab) {
        list($search_options, $hidden_fields) = $this->getSearchEntries();
        $search_form_presenter                = new Experimental_SearchFormPresenter($search_options, $hidden_fields);
        $project_manager                      = ProjectManager::instance();

        $this->render('navbar', new Experimental_NavBarPresenter(
                $this->imgroot,
                $current_user,
                $_SERVER['REQUEST_URI'],
                $selected_top_tab,
                HTTPRequest::instance(),
                $params['title'],
                $search_form_presenter,
                $project_manager->getActiveProjectsForUser($current_user),
                $this->displayNewAccount(),
                $this->getMOTD()
            )
        );

        $this->container($params, $project_manager, $current_user);
    }

    protected function getMOTD() {
        $motd       = parent::getMOTD();
        $deprecated = $this->getBrowserDeprecatedMessage();
        if ($motd && $deprecated) {
            return $deprecated.'<br />'.$motd;
        } else {
            return $motd.$deprecated;
        }
    }

    private function displayNewAccount() {
        $display_new_user = true;
        EventManager::instance()->processEvent('display_newaccount', array('allow' => &$display_new_user));
        return $display_new_user;
    }

    private function container(array $params, ProjectManager $project_manager, PFUser $current_user) {
        $project_tabs      = null;
        $project_name      = null;
        $project_link      = null;
        $project_is_public = null;
        $project_privacy   = null;

        if (! empty($params['group'])) {
            $this->show_sidebar = true;

            $project = ProjectManager::instance()->getProject($params['group']);

            $project_tabs      = $this->getProjectTabs($params, $project);
            $project_name      = $project->getPublicName();
            $project_link      = $this->getProjectLink($project);
            $project_is_public = $project->isPublic();
            $project_privacy   = $this->getProjectPrivacy($project);
        }

        $this->render('container', new Experimental_ContainerPresenter(
            $this->breadcrumbs,
            $this->toolbar,
            $project_name,
            $project_link,
            $project_is_public,
            $project_privacy,
            $project_tabs,
            $this->_feedback,
            $this->_getFeedback(),
            $this->getForgeVersion()
        ));
    }

    private function getProjectPrivacy(Project $project) {
        if ($project->isPublic()) {
            $privacy = 'public';

            if ($GLOBALS['sys_allow_anon']) {
                $privacy .= '_w_anon';
            } else {
                $privacy .= '_wo_anon';
            }

        } else {
            $privacy = 'private';
        }

        return $privacy;
    }

    private function getForgeVersion() {
        return trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));
    }

    /**
     * A "project tab" is a link towards a project service.
     * The parent method getProjectTabs() generates an array of these links.
     * However, the first element is a link to the forge homepage and we don't
     * want it in this theme.
     *
     */
    private function getProjectTabs($params, $project) {
        $tabs = parent::_getProjectTabs($params['toptab'], $project);
        array_shift($tabs);

        return $tabs;
    }

    private function getProjectLink(Project $project) {
        return '/projects/' . $project->getUnixName() . '/';
    }

    public function footer($params) {
        if ($this->canShowFooter($params)) {
            $this->render('footer', new Experimental_FooterPresenter($this));
        }

        $this->endOfPage();
    }

    /**
     * Only show the footer if the sidebar is not present. The sidebar is used
     * for project navigation.
     * Note: there is an ugly dependency on the page content being rendered first.
     * Although this is the case, it's worth bearing in mind when refactoring.
     *
     * @param array $params
     * @return boolean
     */
    private function canShowFooter($params) {
        if (empty($params['group']) && ! $this->show_sidebar) {
            return true;
        }

        return false;
    }

    private function endOfPage() {
        $this->displayFooterJavascriptElements();
        if ($this->isInDebugMode()) {
            $this->showDebugInfo();
        }

        $this->render('end-of-page', null);
    }

    private function isInDebugMode() {
        return (Config::get('DEBUG_MODE') && (Config::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')));
    }
}

?>
