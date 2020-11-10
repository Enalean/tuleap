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
use HTTPRequest;
use PFUser;
use Project;
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\BrowserDetection\BrowserDeprecationMessage;
use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\HelpDropdown\HelpDropdownPresenterBuilder;
use Tuleap\HelpDropdown\ReleaseLinkDao;
use Tuleap\HelpDropdown\ReleaseNoteManager;
use Tuleap\HelpDropdown\VersionNumberExtractor;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbPresenterBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\Logo\CachedCustomizedLogoDetector;
use Tuleap\Layout\Logo\CustomizedLogoDetector;
use Tuleap\Layout\Logo\FileContentComparator;
use Tuleap\layout\NewDropdown\NewDropdownPresenterBuilder;
use Tuleap\Layout\SearchFormPresenterBuilder;
use Tuleap\Layout\SidebarPresenter;
use Tuleap\OpenGraph\NoOpenGraphPresenter;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\ProjectContextPresenter;
use Tuleap\Project\ProjectPresentersBuilder;
use Tuleap\Project\ProjectPrivacyPresenter;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder as NavbarPresenterBuilder;
use Tuleap\User\SwitchToPresenterBuilder;
use URLRedirect;
use Valid_HTTPURI;
use Valid_LocalURI;
use Widget_Static;

require_once __DIR__ . '/../vendor/autoload.php';

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

        $this->project_flags_builder = new ProjectFlagsBuilder(new ProjectFlagsDao());

        $this->includeFooterJavascriptFile(
            $this->include_asset->getFileURLWithFallback('tlp-' . $user->getLocale() . '.js', 'tlp-en_US.js')
        );
        $this->includeFooterJavascriptFile($this->include_asset->getFileURL('burning-parrot.js'));
        $this->includeFooterJavascriptFile($this->include_asset->getFileURL('keyboard-navigation.js'));
        $this->includeFooterJavascriptFile($this->include_asset->getFileURL('switch-to-bp.js'));
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
        $project         = null;
        $project_context = null;
        if (! empty($params['group'])) {
            $project = $this->project_manager->getProject($params['group']);

            $project_context = ProjectContextPresenter::build(
                $project,
                ProjectPrivacyPresenter::fromProject($project),
                $this->project_flags_builder->buildProjectFlags($project),
                $this->getProjectBannerWithScript($project, $this->user, 'project/project-banner-bp.js'),
            );

            if (! isset($params['without-project-in-breadcrumbs']) || $params['without-project-in-breadcrumbs'] === false) {
                $crumb = new BreadCrumb(new BreadCrumbLink($project->getPublicName(), $project->getUrl()));
                $crumb->setAdditionalClassname("breadcrumb-project");
                $this->breadcrumbs->addFirst($crumb);
            }

            $this->injectProjectBackground($project, $params);
        }


        $url_redirect                = new URLRedirect(EventManager::instance());
        $header_presenter_builder    = new HeaderPresenterBuilder();
        $main_classes                = isset($params['main_classes']) ? $params['main_classes'] : [];
        $sidebar                     = $this->getSidebarFromParams($params);
        $body_classes                = $this->getArrayOfClassnamesForBodyTag($params, $sidebar, $project);
        $breadcrumb_presenter_builder = new BreadCrumbPresenterBuilder();

        $breadcrumbs = $breadcrumb_presenter_builder->build($this->breadcrumbs);

        $open_graph = isset($params['open_graph']) ? $params['open_graph'] : new NoOpenGraphPresenter();

        $dropdown_presenter_builder = new HelpDropdownPresenterBuilder(
            new ReleaseNoteManager(
                new ReleaseLinkDao(),
                new \UserPreferencesDao(),
                new VersionNumberExtractor(),
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
                )
            ),
            $this->event_manager,
            new URISanitizer(new Valid_HTTPURI(), new Valid_LocalURI())
        );

        $help_dropdown_presenter = $dropdown_presenter_builder->build(
            $this->user,
            $this->version->version_number
        );

        $new_dropdown_presenter_builder = new NewDropdownPresenterBuilder(
            $this->event_manager,
            new ProjectRegistrationUserPermissionChecker(
                new \ProjectDao()
            )
        );

        $switch_to_presenter_builder = new SwitchToPresenterBuilder(
            new ProjectPresentersBuilder(),
            new SearchFormPresenterBuilder($this->event_manager, $this->request)
        );

        $current_context_section = $this->getNewDropdownCurrentContextSectionFromParams($params);

        $header_presenter = $header_presenter_builder->build(
            new NavbarPresenterBuilder(),
            $this->user,
            $this->imgroot,
            $params['title'],
            $this->_feedback->logs,
            $body_classes,
            $main_classes,
            $sidebar,
            $url_redirect,
            $this->toolbar,
            $breadcrumbs,
            $this->css_assets,
            $open_graph,
            $help_dropdown_presenter,
            $new_dropdown_presenter_builder->getPresenter($this->user, $project, $current_context_section),
            $this->isInSiteAdmin($params),
            $project_context,
            $switch_to_presenter_builder->build($this->user),
            new CachedCustomizedLogoDetector(
                new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator()),
                \BackendLogger::getDefaultLogger(),
            ),
            $this->getPlatformBannerWithScript($this->user, 'platform/platform-banner-bp.js')
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

        $this->event_manager->processEvent('site_help', [
            'extra_content' => &$extra_content
        ]);

        include($GLOBALS['Language']->getContent('help/site'));

        echo $extra_content;
    }

    private function getArrayOfClassnamesForBodyTag(
        $params,
        $sidebar,
        ?Project $project
    ): array {
        $body_classes = [];

        if (isset($params['body_class'])) {
            $body_classes = $params['body_class'];
        }

        $color = \ThemeVariantColor::buildFromVariant((new \ThemeVariant())->getVariantForUser($this->user));
        $body_classes[] = 'theme-' . $color->getName();
        $is_condensed = $this->user->getPreference(\PFUser::PREFERENCE_DISPLAY_DENSITY) === \PFUser::DISPLAY_DENSITY_CONDENSED;
        if ($is_condensed) {
            $body_classes[] = 'theme-condensed';
        }

        if ($project) {
            $banner = $this->getProjectBanner($project, $this->user);
            if ($banner && $banner->isVisible()) {
                $body_classes[] = 'has-visible-project-banner';
            }
        }

        $platform_banner = $this->getPlatformBanner($this->user);
        if ($platform_banner && $platform_banner->isVisible()) {
                $body_classes[] = 'has-visible-platform-banner';
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
        $javascript_files = [];
        EventManager::instance()->processEvent(
            Event::BURNING_PARROT_GET_JAVASCRIPT_FILES,
            [
                'javascript_files' => &$javascript_files
            ]
        );

        foreach ($javascript_files as $javascript_file) {
            $this->includeFooterJavascriptFile($javascript_file);
        }
        $this->includeFooterJavascriptSnippet($this->getFooterSiteJs());

        $browser_deprecation_message = BrowserDeprecationMessage::fromDetectedBrowser(
            DetectedBrowser::detectFromTuleapHTTPRequest($this->request)
        );
        if ($browser_deprecation_message !== null) {
            $this->addJavascriptAsset(new JavascriptAsset(new IncludeAssets(__DIR__ . '/../../../www/assets/core', '/assets/core'), 'browser-deprecation-bp.js'));
        }

        $footer = new FooterPresenter(
            $this->javascript_in_footer,
            $this->javascript_assets,
            $browser_deprecation_message,
            $this->canShowFooter($params),
            $this->version->getFullDescriptiveVersion()
        );
        $this->renderer->renderToPage('footer', $footer);
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
        return __DIR__ . '/../templates/';
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
            ProjectPrivacyPresenter::fromProject($project),
            $this->version,
            $this->getProjectBanner($project, $this->user),
            $this->project_flags_builder->buildProjectFlags($project),
        );

        return new SidebarPresenter(
            'project-sidebar',
            $this->renderer->renderToString('project-sidebar', $project_sidebar_presenter),
            $this->version
        );
    }

    private function shouldIncludeSitebarStatePreference(array $params)
    {
        $is_in_siteadmin     = $this->isInSiteAdmin($params);
        $user_has_preference = $this->user->getPreference('sidebar_state');

        return ! $is_in_siteadmin && $user_has_preference;
    }

    private function isInSiteAdmin(array $params)
    {
        return isset($params['in_siteadmin']) && $params['in_siteadmin'] === true;
    }
}
