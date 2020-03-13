<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Event;
use EventManager;
use ForgeConfig;
use HTTPRequest;
use PFUser;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbPresenterBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\SidebarPresenter;
use Tuleap\OpenGraph\NoOpenGraphPresenter;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder as NavbarPresenterBuilder;
use URLRedirect;
use Widget_Static;

require_once __DIR__ . '/vendor/autoload.php';

class BurningParrotTheme extends BaseLayout
{
    /** @var ProjectManager */
    private $project_manager;

    /** @var \MustacheRenderer */
    private $renderer;

    /**
     * @var VersionPresenter
     */
    private $version;

    /** @var PFUser */
    private $user;

    /** @var HTTPRequest */
    private $request;

    private $show_sidebar = false;

    /** @var EventManager */
    private $event_manager;
    /**
     * @var ProjectFlagsBuilder
     */
    private $project_flags_builder;

    public function __construct($root, PFUser $user)
    {
        parent::__construct($root);
        $this->user            = $user;
        $this->project_manager = ProjectManager::instance();
        $this->event_manager   = EventManager::instance();
        $this->request         = HTTPRequest::instance();
        $this->renderer        = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $this->version         = VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence());

        $this->project_flags_builder    = new ProjectFlagsBuilder(new ProjectFlagsDao());

        $tlp_include_assets = new IncludeAssets(
            __DIR__ . '/../../themes/common/tlp/dist',
            '/themes/common/tlp/dist'
        );
        $this->includeFooterJavascriptFile($tlp_include_assets->getFileURLWithFallback('tlp-' . $user->getLocale() . '.js', 'tlp-en_US.js'));
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
        $current_project_navbar_info = $this->getCurrentProjectNavbarInfo($params);
        $body_classes                = $this->getArrayOfClassnamesForBodyTag($params, $sidebar, $current_project_navbar_info);

        $breadcrumb_presenter_builder = new BreadCrumbPresenterBuilder();

        $breadcrumbs = $breadcrumb_presenter_builder->build($this->breadcrumbs);

        $open_graph = isset($params['open_graph']) ? $params['open_graph'] : new NoOpenGraphPresenter();

        $header_presenter = $header_presenter_builder->build(
            new NavbarPresenterBuilder(),
            $this->user,
            $this->imgroot,
            $params['title'],
            $this->_feedback->logs,
            $body_classes,
            $main_classes,
            $sidebar,
            $current_project_navbar_info,
            $url_redirect,
            $this->toolbar,
            $breadcrumbs,
            $this->getMOTD(),
            $this->css_assets,
            $open_graph,
            new ProjectRegistrationUserPermissionChecker(
                new \ProjectDao()
            )
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

    private function getArrayOfClassnamesForBodyTag(
        $params,
        $sidebar,
        ?CurrentProjectNavbarInfoPresenter $current_project_navbar_info_presenter
    ): array {
        $body_classes = array();

        if (isset($params['body_class'])) {
            $body_classes = $params['body_class'];
        }

        $color = \ThemeVariantColor::buildFromVariant((new \ThemeVariant())->getVariantForUser($this->user));
        $body_classes[] = 'theme-' . $color->getName();
        $is_condensed = $this->user->getPreference(\PFUser::PREFERENCE_DISPLAY_DENSITY) === \PFUser::DISPLAY_DENSITY_CONDENSED;
        if ($is_condensed) {
            $body_classes[] = 'theme-condensed';
        }

        if ($current_project_navbar_info_presenter !== null && $current_project_navbar_info_presenter->project_banner_is_visible) {
            $body_classes[] = 'has-visible-project-banner';
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
            $this->javascript_assets,
            $this->canShowFooter($params),
            $this->version->getFullDescriptiveVersion()
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
     * @return bool
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

    private function getSidebarFromParams(array $params)
    {
        if (isset($params['sidebar'])) {
            $this->show_sidebar = true;
            return $params['sidebar'];
        } elseif (! empty($params['group'])) {
            $project = $this->project_manager->getProject($params['group']);
            $this->show_sidebar = true;
            return $this->getSidebarPresenterForProject($project, $params);
        }

        return false;
    }

    private function getSidebarPresenterForProject(Project $project, array $params): SidebarPresenter
    {
        $project_sidebar_presenter = new ProjectSidebarPresenter(
            $this->getUser(),
            $project,
            $this->getProjectSidebar($params, $project),
            $this->getProjectPrivacy($project),
            $this->version
        );

        return new SidebarPresenter(
            'project-sidebar',
            $this->renderer->renderToString('project-sidebar', $project_sidebar_presenter),
            $this->version
        );
    }

    private function getCurrentProjectNavbarInfo(array $params): ?CurrentProjectNavbarInfoPresenter
    {
        if (empty($params['group'])) {
            return null;
        }

        $project = $this->project_manager->getProject($params['group']);

        return new CurrentProjectNavbarInfoPresenter(
            $project,
            $this->getProjectPrivacy($project),
            $this->project_flags_builder->buildProjectFlags($project),
            $this->getProjectBanner($project, $this->user, 'project-banner-bp.js')
        );
    }

    private function shouldIncludeSitebarStatePreference(array $params)
    {
        $is_in_siteadmin     = isset($params['in_siteadmin']) && $params['in_siteadmin'] === true;
        $user_has_preference = $this->user->getPreference('sidebar_state');

        return ! $is_in_siteadmin && $user_has_preference;
    }
}
