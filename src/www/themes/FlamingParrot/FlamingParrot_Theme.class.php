<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\BrowserDetection\BrowserDeprecationMessage;
use Tuleap\BrowserDetection\DetectedBrowser;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\BuildVersion\VersionPresenter;
use Tuleap\Dashboard\User\UserDashboardDao;
use Tuleap\Dashboard\User\UserDashboardRetriever;
use Tuleap\Dashboard\Widget\DashboardWidgetDao;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\HelpDropdown\HelpDropdownPresenterBuilder;
use Tuleap\HelpDropdown\ReleaseLinkDao;
use Tuleap\HelpDropdown\ReleaseNoteManager;
use Tuleap\HelpDropdown\VersionNumberExtractor;
use Tuleap\InviteBuddy\InviteBuddiesPresenter;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbPresenterBuilder;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\Logo\CustomizedLogoDetector;
use Tuleap\Layout\Logo\FileContentComparator;
use Tuleap\layout\NewDropdown\NewDropdownPresenterBuilder;
use Tuleap\OpenGraph\NoOpenGraphPresenter;
use Tuleap\Project\Banner\BannerDisplay;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\ProjectPresentersBuilder;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\User\Account\RegistrationGuardEvent;
use Tuleap\User\SwitchToPresenterBuilder;
use Tuleap\Widget\WidgetFactory;

require_once __DIR__ . '/../../../themes/FlamingParrot/vendor/autoload.php';

class FlamingParrot_Theme extends Layout // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{

    /**
     * @var TemplateRenderer
     */
    protected $renderer;

    private $show_sidebar = false;
    /**
     * @var ProjectFlagsBuilder
     */
    private $project_flags_builder;
    /**
     * @var VersionPresenter
     */
    private $tuleap_version;

    public function __construct($root)
    {
        parent::__construct($root);

        $this->renderer       = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $this->tuleap_version = VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence());

        $this->project_flags_builder = new ProjectFlagsBuilder(new ProjectFlagsDao());
    }

    private function render($template_name, $presenter)
    {
        $this->renderer->renderToPage($template_name, $presenter);
    }

    private function getTemplateDir()
    {
        return __DIR__ . '/../../../themes/FlamingParrot/templates/';
    }

    public static function getVariants()
    {
        return [
            "FlamingParrot_Orange",
            "FlamingParrot_Blue",
            "FlamingParrot_Green",
            "FlamingParrot_BlueGrey",
            "FlamingParrot_Purple",
            "FlamingParrot_Red",
        ];
    }

    public static function getColorOfCurrentTheme($theme)
    {
        $array = [
            "FlamingParrot_Orange"          => "#F79514",
            "FlamingParrot_Blue"            => "#1593C4",
            "FlamingParrot_Green"           => "#67AF45",
            "FlamingParrot_BlueGrey"        => "#5B6C79",
            "FlamingParrot_Purple"          => "#79558A",
            "FlamingParrot_Red"             => "#BD2626",
        ];

        return $array[$theme];
    }

    public function header(array $params)
    {
        $title = ForgeConfig::get('sys_name');
        if (! empty($params['title'])) {
            $title = $params['title'] . ' - ' . $title;
        }

        $current_user    = UserManager::instance()->getCurrentUser();
        $theme_variant   = new ThemeVariant();
        $current_variant = $theme_variant->getVariantForUser($current_user);

        $open_graph = isset($params['open_graph']) ? $params['open_graph'] : new NoOpenGraphPresenter();

        $this->render('header', new FlamingParrot_HeaderPresenter(
            $title,
            $this->imgroot,
            $open_graph,
            $current_variant,
            $this->getColorOfCurrentTheme($current_variant)
        ));

        if (! empty($params['group'])) {
            $project = ProjectManager::instance()->getProject($params['group']);
            $this->injectProjectBackground($project, $params);
        }

        $this->displayJavascriptElements($params);
        $this->displayStylesheetElements($params);
        $this->displaySyndicationElements();

        $this->body($params);
    }

    protected function includeSubsetOfCombined()
    {
        echo $this->include_asset->getHTMLSnippet('tuleap_subset_flamingparrot.js');
    }

    protected function displayCommonStylesheetElements($params)
    {
        $core_assets = new IncludeAssets(__DIR__ . '/../../assets/core', '/assets/core');

        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/animate.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-select/bootstrap-select.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/select2/select2.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/vendor/at/css/atwho.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" />';

        $style_css_url = $this->getCSSThemeFileURL($core_assets);
        echo '<link rel="stylesheet" type="text/css" href="' . $style_css_url . '" />';
        $print_css_url = $core_assets->getFileURL('FlamingParrot/print.css');
        echo '<link rel="stylesheet" type="text/css" href="' . $print_css_url . '" media="print" />';

        $custom_dir = ForgeConfig::get('codendi_dir') . '/src/www' . $this->getStylesheetTheme('') . 'custom';
        foreach (glob($custom_dir . '/*.css') as $custom_css_file) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getStylesheetTheme('custom/' . basename($custom_css_file)) . '" />';
        }
    }

    private function getCSSThemeFileURL(IncludeAssets $include_assets)
    {
        $current_user = UserManager::instance()->getCurrentUser();

        $theme_variant = new ThemeVariant();
        $variant_used  = $theme_variant->getVariantForUser($current_user);

        return $include_assets->getFileURL('FlamingParrot/' . $variant_used . '.css');
    }

    private function body($params)
    {
        $current_user = UserManager::instance()->getCurrentUser();
        $project_manager = ProjectManager::instance();

        $body_class       = isset($params['body_class']) ? $params['body_class'] : [];
        $has_sidebar      = isset($params['group']) ? 'has-sidebar' : '';
        $sidebar_state    = 'sidebar-expanded';

        $this->addBodyClassDependingThemeVariant($current_user, $body_class);
        $this->addBodyClassDependingUserPreference($current_user, $body_class);

        if ($current_user->getPreference('sidebar_state')) {
            $sidebar_state = $current_user->getPreference('sidebar_state');
        }

        $banner = null;
        $project = null;
        if (! empty($params['group'])) {
            $project = $project_manager->getProject($params['group']);
            $banner  = $this->getProjectBannerWithScript($project, $current_user, 'project/project-banner-fp.js');

            if ($banner && $banner->isVisible()) {
                $body_class[] = 'has-visible-project-banner';
            }
        }

        $body_class[] = $has_sidebar;
        $body_class[] = $sidebar_state;

        $dropdown_presenter_builder = new HelpDropdownPresenterBuilder(
            new ReleaseNoteManager(
                new ReleaseLinkDao(),
                new UserPreferencesDao(),
                new VersionNumberExtractor(),
                new DBTransactionExecutorWithConnection(
                    DBFactory::getMainTuleapDBConnection()
                )
            ),
            $this->getEventManager(),
            new URISanitizer(new Valid_HTTPURI(), new Valid_LocalURI()),
        );

        $help_dropdown_presenter = $dropdown_presenter_builder->build(
            $current_user,
            $this->tuleap_version->version_number
        );

        $platform_banner = $this->getPlatformBannerWithScript($current_user, 'platform/platform-banner-fp.js');
        $this->render('body', new FlamingParrot_BodyPresenter(
            $current_user,
            $this->getNotificationPlaceholder(),
            $help_dropdown_presenter,
            $body_class,
            InviteBuddiesPresenter::build($current_user),
            $platform_banner,
        ));

        $this->navbar($params, $current_user, $project, $banner, $platform_banner);
    }

    private function addBodyClassDependingThemeVariant(PFUser $user, array &$body_class)
    {
        $theme_variant   = new ThemeVariant();
        $current_variant = $theme_variant->getVariantForUser($user);
        $body_class[]    = $current_variant;
    }

    private function addBodyClassDependingUserPreference(PFUser $user, array &$body_class)
    {
        $edition_default_format = $user->getPreference(PFUser::EDITION_DEFAULT_FORMAT);
        if ($edition_default_format && $edition_default_format === 'html') {
            $body_class[] = 'default_format_' . $edition_default_format;
        }
    }

    private function navbar(
        $params,
        PFUser $current_user,
        ?Project $project,
        ?BannerDisplay $project_banner,
        ?\Tuleap\Platform\Banner\BannerDisplay $platform_banner
    ) {
        $csrf_logout_token = new CSRFSynchronizerToken('logout_action');
        $event_manager     = EventManager::instance();
        $url_redirect      = new URLRedirect($event_manager);
        $main_classes      = $params['main_classes'] ?? [];

        $current_context_section = $this->getNewDropdownCurrentContextSectionFromParams($params);

        $widget_factory = new WidgetFactory(
            UserManager::instance(),
            new User_ForgeUserGroupPermissionsManager(new User_ForgeUserGroupPermissionsDao()),
            $event_manager
        );
        $user_dashboard_retriever = new UserDashboardRetriever(new UserDashboardDao(new DashboardWidgetDao($widget_factory)));

        $registration_user_permission_checker = new ProjectRegistrationUserPermissionChecker(
            new ProjectDao()
        );
        $new_dropdown_presenter_builder = new NewDropdownPresenterBuilder(
            $event_manager,
            $registration_user_permission_checker
        );

        $switch_to_presenter_builder = new SwitchToPresenterBuilder(
            new ProjectPresentersBuilder(),
            new \Tuleap\Layout\SearchFormPresenterBuilder($event_manager, HTTPRequest::instance())
        );

        $switch_to = $switch_to_presenter_builder->build($current_user);

        $customized_logo_detector = new \Tuleap\Layout\Logo\CachedCustomizedLogoDetector(
            new CustomizedLogoDetector(new LogoRetriever(), new FileContentComparator()),
            BackendLogger::getDefaultLogger(),
        );

        $is_legacy_logo_customized = $customized_logo_detector->isLegacyOrganizationLogoCustomized();
        $is_svg_logo_customized    = $customized_logo_detector->isSvgOrganizationLogoCustomized();

        $this->render('navbar', new FlamingParrot_NavBarPresenter(
            $this->imgroot,
            $current_user,
            $this->displayNewAccount(),
            $csrf_logout_token,
            $url_redirect,
            $user_dashboard_retriever->getAllUserDashboards($current_user),
            $new_dropdown_presenter_builder->getPresenter($current_user, $project, $current_context_section),
            $this->shouldLogoBeDisplayed($params, $project),
            $switch_to,
            $is_legacy_logo_customized,
            $is_svg_logo_customized,
            InviteBuddiesPresenter::build($current_user),
            $platform_banner
        ));

        $this->container(
            $params,
            $current_user,
            $project_banner,
            $switch_to,
            $is_legacy_logo_customized,
            $is_svg_logo_customized,
            $main_classes,
        );
    }

    private function shouldLogoBeDisplayed(array $params, ?Project $project): bool
    {
        return ! $this->isInSiteAdmin($params) && ! isset($project);
    }

    private function isInSiteAdmin(array $params): bool
    {
        return isset($params['in_siteadmin']) && $params['in_siteadmin'] === true;
    }

    private function displayNewAccount(): bool
    {
        $registration_guard = EventManager::instance()->dispatch(new RegistrationGuardEvent());
        assert($registration_guard instanceof RegistrationGuardEvent);
        return $registration_guard->isRegistrationPossible();
    }

    private function container(
        array $params,
        PFUser $current_user,
        ?BannerDisplay $banner,
        ?\Tuleap\User\SwitchToPresenter $switch_to,
        bool $is_legacy_logo_customized,
        bool $is_svg_logo_customized,
        array $main_classes
    ): void {
        $project_tabs        = null;
        $project_name        = null;
        $project_link        = null;
        $project             = null;
        $privacy             = null;
        $project_context     = null;
        $sidebar_collapsable = false;

        if (! empty($params['group'])) {
            $this->show_sidebar = true;

            $project = ProjectManager::instance()->getProject($params['group']);

            $project_tabs        = $this->getProjectSidebar($params, $project);
            $project_name        = $project->getPublicName();
            $project_link        = $this->getProjectLink($project);
            $sidebar_collapsable = (! $current_user->isAnonymous() && $current_user->isLoggedIn()) ? true : false;

            $crumb = new BreadCrumb(new BreadCrumbLink($project->getPublicName(), $project->getUrl()));
            $crumb->setAdditionalClassname("breadcrumb-project");
            $this->breadcrumbs->addFirst($crumb);

            $project_context = \Tuleap\Project\ProjectContextPresenter::build(
                $project,
                \Tuleap\Project\ProjectPrivacyPresenter::fromProject($project),
                $this->project_flags_builder->buildProjectFlags($project),
                $banner
            );
        }

        $breadcrumb_presenter_builder = new BreadCrumbPresenterBuilder();

        $breadcrumbs = $breadcrumb_presenter_builder->build($this->breadcrumbs);

        $this->render('container', new FlamingParrot_ContainerPresenter(
            $breadcrumbs,
            $this->toolbar,
            $project_name,
            $project_link,
            $project_tabs,
            $this->_feedback,
            $this->_getFeedback(),
            $this->tuleap_version,
            $sidebar_collapsable,
            $current_user,
            $project_context,
            $switch_to,
            $is_legacy_logo_customized,
            $is_svg_logo_customized,
            $main_classes,
        ));
    }

    private function getProjectLink(Project $project)
    {
        return '/projects/' . $project->getUnixName() . '/';
    }

    public function footer(array $params)
    {
        $this->displayBrowserDeprecationMessage();
        if ($this->canShowFooter($params)) {
            $this->render('footer', []);
        }

        $this->endOfPage();
    }

    private function displayBrowserDeprecationMessage(): void
    {
        $browser_deprecation_message = BrowserDeprecationMessage::fromDetectedBrowser(
            DetectedBrowser::detectFromTuleapHTTPRequest(HTTPRequest::instance())
        );
        if ($browser_deprecation_message === null) {
            return;
        }
        $this->addJavascriptAsset(new JavascriptAsset(new IncludeAssets(__DIR__ . '/../../../www/assets/core', '/assets/core'), 'browser-deprecation-fp.js'));
        $this->render('browser-deprecation', $browser_deprecation_message);
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

    private function endOfPage()
    {
        $this->displayFooterJavascriptElements();

        $this->render('end-of-page', null);
    }

    protected function includeJavascriptPolyfills()
    {
        echo $this->include_asset->getHTMLSnippet('flamingparrot-with-polyfills.js');
    }
}
