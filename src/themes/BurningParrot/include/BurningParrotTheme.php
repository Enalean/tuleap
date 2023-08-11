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
use Project;
use ProjectManager;
use TemplateRendererFactory;
use ThemeVariant;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
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
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\InviteBuddy\InvitationDao;
use Tuleap\InviteBuddy\InvitationInstrumentation;
use Tuleap\InviteBuddy\InvitationLimitChecker;
use Tuleap\InviteBuddy\InviteBuddiesPresenterBuilder;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Layout\AfterStartProjectContainer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\BeforeStartProjectHeader;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbPresenterBuilder;
use Tuleap\Layout\FooterConfiguration;
use Tuleap\Layout\HeaderConfiguration;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\JavascriptViteAsset;
use Tuleap\Layout\Logo\CachedCustomizedLogoDetector;
use Tuleap\Layout\Logo\CustomizedLogoDetector;
use Tuleap\Layout\Logo\FileContentComparator;
use Tuleap\Layout\NewDropdown\NewDropdownPresenterBuilder;
use Tuleap\Layout\SearchFormPresenterBuilder;
use Tuleap\Layout\ThemeVariantColor;
use Tuleap\Layout\ThemeVariation;
use Tuleap\OpenGraph\NoOpenGraphPresenter;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Admin\ProjectMembers\UserCanManageProjectMembersChecker;
use Tuleap\Project\CachedProjectPresentersBuilder;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\Icons\EmojiCodepointConverter;
use Tuleap\Project\ProjectPresentersBuilder;
use Tuleap\Project\Registration\ProjectRegistrationPermissionsChecker;
use Tuleap\Project\Registration\ProjectRegistrationUserPermissionChecker;
use Tuleap\Project\Sidebar\ProjectContextPresenter;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\Theme\BurningParrot\Navbar\PresenterBuilder as NavbarPresenterBuilder;
use Tuleap\User\CurrentUserWithLoggedInInformation;
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

    /**
     * @var DetectedBrowser
     */
    private $detected_browser;

    /** @var HTTPRequest */
    private $request;

    private $show_sidebar = false;
    /** @var EventManager */
    private $event_manager;
    /**
     * @var ProjectFlagsBuilder
     */
    private $project_flags_builder;
    private ThemeVariantColor $theme_variant_color;
    private ThemeVariation $theme_variation;
    private bool $header_has_been_written = false;

    public function __construct($root, private CurrentUserWithLoggedInInformation $current_user)
    {
        parent::__construct($root);
        $this->project_manager  = ProjectManager::instance();
        $this->event_manager    = EventManager::instance();
        $this->request          = HTTPRequest::instance();
        $this->renderer         = TemplateRendererFactory::build()->getRenderer($this->getTemplateDir());
        $this->version          = VersionPresenter::fromFlavorFinder(new FlavorFinderFromFilePresence());
        $this->detected_browser = DetectedBrowser::detectFromTuleapHTTPRequest($this->request);

        $this->project_flags_builder = new ProjectFlagsBuilder(new ProjectFlagsDao());

        $this->theme_variant_color = (new ThemeVariant())->getVariantColorForUser($this->current_user->user);
        $this->theme_variation     = new ThemeVariation($this->theme_variant_color, $this->current_user->user);

        $this->includeFooterJavascriptFile((new JavascriptAsset(new \Tuleap\Layout\IncludeCoreAssets(), 'collect-frontend-errors.js'))->getFileURL());
        $this->includeFooterJavascriptFile(
            (new IncludeAssets(
                __DIR__ . '/../../../scripts/tlp/frontend-assets',
                '/assets/core/tlp'
            ))->getFileURLWithFallback('tlp-' . $this->current_user->user->getLocale() . '.js', 'tlp-en_US.js')
        );
        $this->includeFooterJavascriptFile($this->include_asset->getFileURL('burning-parrot.js'));

        $this->addJavascriptAsset(new JavascriptViteAsset(
            new IncludeViteAssets(
                __DIR__ . '/../../../scripts/switch-to/frontend-assets',
                '/assets/core/switch-to'
            ),
            'src/index-bp.ts'
        ));
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

    public function header(HeaderConfiguration|array $params): void
    {
        $project = null;
        if (is_array($params) && ! empty($params['group'])) {
            $project = $this->project_manager->getProject($params['group']);
            $this->event_manager->dispatch(new BeforeStartProjectHeader($project, $this, $this->current_user->user));
        }

        $this->header_has_been_written = true;
        $project_context               = null;
        $in_project_without_sidebar    = null;

        if ($params instanceof HeaderConfiguration) {
            $in_project_without_sidebar = $params->in_project_without_sidebar;
            $params                     = [
                'title'      => $params->title,
                'body_class' => $params->body_class,
            ];
        }

        if ($project) {
            $project_context = ProjectContextPresenter::build(
                $project,
                $this->project_flags_builder->buildProjectFlags($project),
                $this->getProjectBannerWithScript($project, $this->current_user->user, 'project/project-banner.js'),
                $this->getProjectSidebarData($params, $project, $this->current_user),
            );

            if (! isset($params['without-project-in-breadcrumbs']) || $params['without-project-in-breadcrumbs'] === false) {
                $crumb_link = new BreadCrumbLink($project->getPublicName(), $project->getUrl());
                    $crumb_link->setProjectIcon(
                        EmojiCodepointConverter::convertStoredEmojiFormatToEmojiFormat(
                            $project->getIconUnicodeCodepoint()
                        )
                    );
                $crumb = new BreadCrumb($crumb_link);
                $crumb->setAdditionalClassname("breadcrumb-project");
                $this->breadcrumbs->addFirst($crumb);
            }

            $this->injectProjectBackground($project, $params);
        }


        $url_redirect                 = new URLRedirect(EventManager::instance());
        $header_presenter_builder     = new HeaderPresenterBuilder();
        $main_classes                 = isset($params['main_classes']) ? $params['main_classes'] : [];
        $sidebar                      = $this->getSidebarFromParams($params);
        $body_classes                 = $this->getArrayOfClassnamesForBodyTag($params, $sidebar, $project);
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
            $this->current_user->user,
            $this->version->version_number
        );

        $new_dropdown_presenter_builder = new NewDropdownPresenterBuilder(
            $this->event_manager,
            new ProjectRegistrationPermissionsChecker(
                new ProjectRegistrationUserPermissionChecker(
                    new \ProjectDao()
                ),
            )
        );

        $project_presenters_builder  = new CachedProjectPresentersBuilder(new ProjectPresentersBuilder());
        $switch_to_presenter_builder = new SwitchToPresenterBuilder(
            $project_presenters_builder,
            new SearchFormPresenterBuilder($this->event_manager, $this->request)
        );

        $current_context_section = $this->getNewDropdownCurrentContextSectionFromParams($params);

        $configuration                    = new InviteBuddyConfiguration($this->event_manager);
        $invitation_dao                   = new InvitationDao(
            new SplitTokenVerificationStringHasher(),
            new InvitationInstrumentation(Prometheus::instance()),
        );
        $invite_buddies_presenter_builder = new InviteBuddiesPresenterBuilder(
            new InvitationLimitChecker(
                $invitation_dao,
                $configuration
            ),
            $configuration,
            $project_presenters_builder,
            new UserCanManageProjectMembersChecker(new MembershipDelegationDao()),
        );
        $invite_buddies_presenter         = $invite_buddies_presenter_builder->build($this->current_user->user, $project);

        $header_presenter = $header_presenter_builder->build(
            new NavbarPresenterBuilder(),
            $this->current_user,
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
            $new_dropdown_presenter_builder->getPresenter($this->current_user->user, $project, $current_context_section),
            $this->isInSiteAdmin($params),
            $project_context,
            $switch_to_presenter_builder->build($this->current_user),
            new CachedCustomizedLogoDetector(
                new CustomizedLogoDetector(new \LogoRetriever(), new FileContentComparator()),
                \BackendLogger::getDefaultLogger(),
            ),
            $this->getPlatformBannerWithScript($this->current_user->user, 'platform/platform-banner.js'),
            $this->detected_browser,
            $this->theme_variant_color,
            $this->theme_variation,
            $this->javascript_assets,
            $in_project_without_sidebar,
            $invite_buddies_presenter,
        );

        $this->renderer->renderToPage('header', $header_presenter);

        if ($project) {
            $this->event_manager->dispatch(new AfterStartProjectContainer($project, $this->current_user->user));
        }
    }

    protected function hasHeaderBeenWritten(): bool
    {
        return $this->header_has_been_written;
    }

    public function displayContactPage()
    {
        include($GLOBALS['Language']->getContent('contact/contact'));
    }

    public function displayHelpPage()
    {
        $extra_content = '';

        $this->event_manager->processEvent('site_help', [
            'extra_content' => &$extra_content,
        ]);

        include($GLOBALS['Language']->getContent('help/site'));

        echo $extra_content;
    }

    private function getArrayOfClassnamesForBodyTag(
        $params,
        $sidebar,
        ?Project $project,
    ): array {
        $body_classes = [];

        if (isset($params['body_class'])) {
            $body_classes = $params['body_class'];
        }

        $color          = (new \ThemeVariant())->getVariantColorForUser($this->current_user->user);
        $body_classes[] = 'theme-' . $color->getName();
        $is_condensed   = $this->current_user->user->getPreference(\PFUser::PREFERENCE_DISPLAY_DENSITY) === \PFUser::DISPLAY_DENSITY_CONDENSED;
        if ($is_condensed) {
            $body_classes[] = 'theme-condensed';
        }

        if ($project) {
            $banner = $this->getProjectBanner($project, $this->current_user->user);
            if ($banner && $banner->isVisible()) {
                $body_classes[] = 'has-visible-project-banner';
            }
        }

        $platform_banner = $this->getPlatformBanner($this->current_user->user);
        if ($platform_banner && $platform_banner->isVisible()) {
                $body_classes[] = 'has-visible-platform-banner';
        }

        if (! $sidebar && $project === null) {
            return $body_classes;
        }

        $body_classes[] = 'has-sidebar';

        if ($this->shouldIncludeSitebarStatePreference($params)) {
            $body_classes[] = $this->current_user->user->getPreference('sidebar_state');
        }

        return $body_classes;
    }

    public function footer(FooterConfiguration|array $params): void
    {
        $javascript_files = [];
        EventManager::instance()->processEvent(
            Event::BURNING_PARROT_GET_JAVASCRIPT_FILES,
            [
                'javascript_files' => &$javascript_files,
            ]
        );

        foreach ($javascript_files as $javascript_file) {
            $this->includeFooterJavascriptFile($javascript_file);
        }
        $this->includeFooterJavascriptSnippet($this->getFooterSiteJs());

        $browser_deprecation_message = BrowserDeprecationMessage::fromDetectedBrowser(
            $this->current_user->user,
            $this->detected_browser
        );
        if ($browser_deprecation_message !== null) {
            $this->addJavascriptAsset(new JavascriptAsset(new \Tuleap\Layout\IncludeCoreAssets(), 'browser-deprecation-bp.js'));
        }

        $footer = new FooterPresenter(
            $this->javascript_in_footer,
            $this->javascript_assets,
            $browser_deprecation_message,
            $this->canShowFooter($params),
            $this->version->getFullDescriptiveVersion(),
            $this->getCSPNonce(),
        );
        $this->renderer->renderToPage('footer', $footer);
    }

    /**
     * Only show the footer if the sidebar is not present. The sidebar is used
     * for project navigation.
     * Note: there is an ugly dependency on the page content being rendered first.
     * Although this is the case, it's worth bearing in mind when refactoring.
     *
     * @param FooterConfiguration|array $params
     */
    private function canShowFooter($params): bool
    {
        if (\ForgeConfig::getFeatureFlag(self::FEATURE_FLAG_SHOW_FOOTER) !== 'contact_the_dev_team_if_you_enable_this') {
            return false;
        }
        if ($params instanceof FooterConfiguration) {
            return $params->without_content === false;
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
        }

        if (! empty($params['group'])) {
            $this->show_sidebar = true;
        }

        return false;
    }

    private function shouldIncludeSitebarStatePreference(array $params)
    {
        $is_in_siteadmin     = $this->isInSiteAdmin($params);
        $user_has_preference = $this->current_user->user->getPreference('sidebar_state');

        return ! $is_in_siteadmin && $user_has_preference;
    }

    private function isInSiteAdmin(array $params)
    {
        return isset($params['in_siteadmin']) && $params['in_siteadmin'] === true;
    }
}
