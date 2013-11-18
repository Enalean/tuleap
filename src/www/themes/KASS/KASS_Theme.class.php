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
require_once 'KASS_HeaderPresenter.class.php';
require_once 'KASS_BodyPresenter.class.php';
require_once 'KASS_ContainerPresenter.class.php';
require_once 'KASS_FooterPresenter.class.php';
require_once 'KASS_NavBarPresenter.class.php';

class KASS_Theme extends DivBasedTabbedLayout {

    /**
     * @var TemplateRenderer
     */
    protected $renderer;

    function __construct($root) {
        parent::__construct($root);
        $this->renderer = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $this->includeJavascriptFile('/themes/KASS/js/sidebar.js');
    }

    private function render($template_name, $presenter) {
        $this->renderer->renderToPage($template_name, $presenter);
    }

    private function getTemplateDir() {
        return dirname(__FILE__) . '/templates/';
    }

    public function isLabFeature() {
        return true;
    }

    public function header($params) {
        $title = $GLOBALS['sys_name'];
        if (!empty($params['title'])) {
           $title = $params['title'] .' - '. $title;
        }

        $javascript_elements  = $this->displayJavascriptElements();
        $stylesheet_elements  = $this->displayStylesheetElements($params);
        $syndication_elements = $this->displaySyndicationElements();

        $this->render('header', new KASS_HeaderPresenter(
            $title,
            $this->imgroot,
            $javascript_elements,
            $stylesheet_elements,
            $syndication_elements
        ));

        $this->body($params);
    }

    protected function displayCommonStylesheetElements($params) {
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-2.3.2.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-responsive-2.3.2.min.css" />';

        $this->displayFontAwesomeStylesheetElements();

        $css = $GLOBALS['sys_user_theme'] . $this->getFontSizeName($GLOBALS['sys_user_font_size']) .'.css';
        if (file_exists($GLOBALS['codendi_dir'].'/src/www'.$this->getStylesheetTheme($css))) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme($css) .'" />';
        }

        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('style.css') .'" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('print.css') .'" media="print" />';
    }

    private function body($params) {
        $selected_top_tab = isset($params['selected_top_tab']) ? $params['selected_top_tab'] : false;

        $this->render('body', new KASS_BodyPresenter(
            $_SERVER['REQUEST_URI'],
            $params['title'],
            $this->imgroot,
            $selected_top_tab,
            $this->getNotificationPlaceholder()
        ));

        $current_user = UserManager::instance()->getCurrentUser();

        $this->navbar($params, $current_user, $selected_top_tab);

    }

    private function navbar($params, PFUser $current_user, $selected_top_tab) {
        $this->render('navbar', new KASS_NavBarPresenter(
                $this->imgroot,
                $current_user,
                $_SERVER['REQUEST_URI'],
                $selected_top_tab,
                HTTPRequest::instance(),
                $params['title']
            )
        );

        $this->container($params, ProjectManager::instance(), $current_user);
    }

    private function container(array $params, ProjectManager $project_manager, PFUser $current_user) {
        $project_tabs = null;
        $project_name = null;

        if (! empty($params['group'])) {
            $project_tabs = $this->_getProjectTabs($params['toptab'], ProjectManager::instance()->getProject($params['group']));
            $project_name = ProjectManager::instance()->getProject($params['group'])->getPublicName();
        }

        $this->render('container', new KASS_ContainerPresenter(
            $this->breadcrumbs,
            $this->toolbar,
            $project_name,
            $project_tabs,
            $this->_feedback,
            $this->_getFeedback()
        ));
    }

    public function footer($params) {
        $this->render('footer', new KASS_FooterPresenter($this));
    }
}

?>
