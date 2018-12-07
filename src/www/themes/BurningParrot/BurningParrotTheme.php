<?php
/*
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, If not, see <http://www.gnu.org/licenses/>
 */

namespace Tuleap\Theme\BurningParrot;

use Admin_Homepage_Dao;
use CSRFSynchronizerToken;
use Event;
use EventManager;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbPresenterBuilder;
use Tuleap\layout\HomePage\StatisticsCollectionBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\SidebarPresenter;
use Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder as NavbarPresenterBuilder;
use URLRedirect;
use User_LoginPresenterBuilder;
use UserManager;
use Widget_Static;

require_once __DIR__ . '/vendor/autoload.php';

class BurningParrotTheme extends BaseLayout
{
    /**
     * @var UserManager
     */
    private $user_manager;
    /** @var ProjectManager */
    private $project_manager;

    /** @var \MustacheRenderer */
    private $renderer;

    /** @var PFUser */
    private $user;

    /** @var HTTPRequest */
    private $request;

    private $show_sidebar = false;

    /** @var EventManager */
    private $event_manager;

    public function __construct($root, PFUser $user)
    {
        parent::__construct($root);
        $this->user            = $user;
        $this->project_manager = ProjectManager::instance();
        $this->user_manager    = UserManager::instance();
        $this->event_manager   = EventManager::instance();
        $this->request         = HTTPRequest::instance();
        $this->renderer        = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());

        $tlp_include_assets = new IncludeAssets(
            ForgeConfig::get('tuleap_dir') . '/src/www/themes/common/tlp/dist',
            '/themes/common/tlp/dist'
        );
        $this->includeFooterJavascriptFile($tlp_include_assets->getFileURL('tlp.' . $user->getLocale() . '.min.js'));
        $this->includeFooterJavascriptFile($this->include_asset->getFileURL('burning-parrot.js'));
    }

    protected function getUser()
    {
        return $this->request->getCurrentUser();
    }

    public function includeCalendarScripts()
    {
    }

    public function getDatePicker()
    {
    }

    public function header(array $params)
    {
        $url_redirect                = new URLRedirect(EventManager::instance());
        $header_presenter_builder    = new HeaderPresenterBuilder();
        $main_classes                = isset($params['main_classes']) ? $params['main_classes'] : array();
        $sidebar                     = $this->getSidebarFromParams($params);
        $body_classes                = $this->getArrayOfClassnamesForBodyTag($params, $sidebar);
        $current_project_navbar_info = $this->getCurrentProjectNavbarInfo($params);

        $breadcrumb_presenter_builder = new BreadCrumbPresenterBuilder();

        $breadcrumbs = $breadcrumb_presenter_builder->build($this->breadcrumbs);

        $header_presenter = $header_presenter_builder->build(
            new NavbarPresenterBuilder(),
            $this->request,
            $this->user,
            $this->imgroot,
            $params['title'],
            $this->_feedback->logs,
            $body_classes,
            $main_classes,
            $sidebar,
            $current_project_navbar_info,
            $this->getListOfIconUnicodes(),
            $url_redirect,
            $this->toolbar,
            $breadcrumbs,
            $this->getMOTD(),
            $this->css_assets
        );

        $this->renderer->renderToPage('header', $header_presenter);
    }

    public function displayContactPage()
    {
        include($GLOBALS['Language']->getContent('contact/contact'));
    }

    public function displayHelpPage()
    {
        $extra_content = '';

        $this->event_manager->processEvent('site_help', array(
            'extra_content' => &$extra_content
        ));

        include($GLOBALS['Language']->getContent('help/site'));

        echo $extra_content;
    }

    private function getArrayOfClassnamesForBodyTag($params, $sidebar)
    {
        $body_classes = array();

        if (isset($params['body_class'])) {
            $body_classes = $params['body_class'];
        }

        if (! $sidebar) {
            return $body_classes;
        }

        $body_classes[] = 'has-sidebar';

        if ($this->shouldIncludeSitebarStatePreference($params)) {
            $body_classes[] = $this->user->getPreference('sidebar_state');
        }

        return $body_classes;
    }

    public function footer(array $params)
    {
        $javascript_files = array();
        EventManager::instance()->processEvent(
            Event::BURNING_PARROT_GET_JAVASCRIPT_FILES,
            array(
                'javascript_files' => &$javascript_files
            )
        );

        foreach ($javascript_files as $javascript_file) {
            $this->includeFooterJavascriptFile($javascript_file);
        }
        $this->includeFooterJavascriptSnippet($this->getFooterSiteJs());

        $footer = new FooterPresenter(
            $this->javascript_in_footer,
            $this->canShowFooter($params),
            $this->getTuleapVersion()
        );
        $this->renderer->renderToPage('footer', $footer);

        if ($this->isInDebugMode()) {
            $this->showDebugInfo();
        }
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
    private function canShowFooter($params)
    {
        if (! empty($params['without_content'])) {
            return false;
        }

        if (empty($params['group']) && ! $this->show_sidebar) {
            return true;
        }

        return false;
    }

    public function displayStaticWidget(Widget_Static $widget)
    {
        $this->renderer->renderToPage('widget', $widget);
    }

    private function getTemplateDir()
    {
        return __DIR__ . '/templates/';
    }

    private function isInDebugMode()
    {
        return (ForgeConfig::get('DEBUG_MODE') && (ForgeConfig::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')));
    }

    public function displayStandardHomepage(
        $display_new_account_button,
        $login_url,
        $is_secure
    ) {
        $homepage_dao = $this->getAdminHomepageDao();
        $current_user = UserManager::instance()->getCurrentUser();

        $headline = $homepage_dao->getHeadlineByLanguage($current_user->getLocale());

        $most_secure_url = '';
        if (ForgeConfig::get('sys_https_host')) {
            $most_secure_url = 'https://'. ForgeConfig::get('sys_https_host');
        }

        $login_presenter_builder = new User_LoginPresenterBuilder();
        $login_csrf              = new CSRFSynchronizerToken('/account/login.php');
        $login_presenter         = $login_presenter_builder->buildForHomepage($is_secure, $login_csrf);

        $display_new_account_button = ($current_user->isAnonymous() && $display_new_account_button);

        $statistics_collection_builder = new StatisticsCollectionBuilder($this->project_manager, $this->user_manager, $this->event_manager);
        $statistics_collection = $statistics_collection_builder->build();

        $templates_dir = ForgeConfig::get('codendi_dir') . '/src/templates/homepage/';
        $renderer      = TemplateRendererFactory::build()->getRenderer($templates_dir);
        $presenter     = new HomePagePresenter(
            $headline,
            $current_user,
            $most_secure_url,
            $login_presenter,
            $display_new_account_button,
            $login_url,
            $statistics_collection
        );
        $renderer->renderToPage('homepage', $presenter);
    }

    private function getAdminHomepageDao()
    {
        return new Admin_Homepage_Dao();
    }

    private function getTuleapVersion()
    {
        return trim(file_get_contents(ForgeConfig::get('tuleap_dir') . '/VERSION'));
    }

    private function getSidebarFromParams(array $params)
    {
        if (isset($params['sidebar'])) {
            $this->show_sidebar = true;
            return $params['sidebar'];
        } else if (! empty($params['group'])) {
            $project = $this->project_manager->getProject($params['group']);
            $this->show_sidebar = true;
            return $this->getSidebarPresenterForProject($project, $params);
        }

        return false;
    }

    private function getSidebarPresenterForProject(Project $project, array $params)
    {
        $project_sidebar_presenter = new ProjectSidebarPresenter(
            $this->getUser(),
            $project,
            $this->getProjectSidebar($params, $project),
            $this->getProjectPrivacy($project)
        );

        return new SidebarPresenter(
            'project-sidebar',
            $this->renderer->renderToString('project-sidebar', $project_sidebar_presenter)
        );
    }

    private function getCurrentProjectNavbarInfo(array $params)
    {
        if (empty($params['group'])) {
            return false;
        }

        $project = $this->project_manager->getProject($params['group']);

        return new CurrentProjectNavbarInfoPresenter(
            $project,
            $this->getProjectPrivacy($project)
        );
    }

    private function shouldIncludeSitebarStatePreference(array $params)
    {
        $is_in_siteadmin     = isset($params['in_siteadmin']) && $params['in_siteadmin'] === true;
        $user_has_preference = $this->user->getPreference('sidebar_state');

        return ! $is_in_siteadmin && $user_has_preference;
    }
}
