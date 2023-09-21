<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

namespace Tuleap\Layout;

use BackendLogger;
use Codendi_HTMLPurifier;
use EventManager;
use LogoRetriever;
use PFUser;
use Project;
use ProjectManager;
use Response;
use Tuleap\BuildVersion\FlavorFinderFromFilePresence;
use Tuleap\Config\FeatureFlagConfigKey;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\Layout\Logo\CustomizedLogoDetector;
use Tuleap\Layout\Logo\FileContentComparator;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Layout\ProjectSidebar\ProjectSidebarConfigRepresentation;
use Tuleap\Project\Admin\Access\UserCanAccessProjectAdministrationVerifier;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\BannerDisplay;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\ProjectBackground\ProjectBackgroundDao;
use Tuleap\Project\REST\v1\ProjectSidebarDataRepresentation;
use Tuleap\Sanitizer\URISanitizer;
use Tuleap\User\CurrentUserWithLoggedInInformation;
use UserManager;
use Valid_FTPURI;
use Valid_LocalURI;
use Widget_Static;

abstract class BaseLayout extends Response
{
    #[FeatureFlagConfigKey("Feature flag to show a footer on some pages. ⚠️ The footer will soon be removed definitively.")]
    public const FEATURE_FLAG_SHOW_FOOTER = 'show_footer';

    /**
     * The root location for the current theme : '/themes/Tuleap/'
     */
    public $root;

    /**
     * The root location for images : '/themes/Tuleap/images/'
     */
    public $imgroot;

    /** @var array */
    protected $javascript_in_footer = [];

    /** @var IncludeAssets */
    protected $include_asset;

    /**
     * Set to true if HTML object is displayed through a Service
     *
     * @var bool
     */
    protected $is_rendered_through_service = false;

    /**
     * @var BreadCrumbCollection
     */
    protected $breadcrumbs;

    /**
     * @var string[] HTML
     */
    protected $toolbar;

    /**
     * @var URISanitizer
     */
    protected $uri_sanitizer;

    /**
     * @var \URLVerification
     */
    private $url_verification;
    /**
     * @var CssAssetCollection
     */
    protected $css_assets;
    /**
     * @var JavascriptAssetGeneric[]
     */
    protected array $javascript_assets = [];

    /**
     * @var string
     * @psalm-readonly
     */
    private $csp_nonce = '';

    public function __construct($root)
    {
        parent::__construct();
        $this->root    = $root;
        $this->imgroot = $root . '/images/';

        $this->breadcrumbs = new BreadCrumbCollection();
        $this->toolbar     = [];

        $this->include_asset    = new \Tuleap\Layout\IncludeCoreAssets();
        $this->uri_sanitizer    = new URISanitizer(new Valid_LocalURI(), new Valid_FTPURI());
        $this->url_verification = new \URLVerification();

        $this->css_assets = new CssAssetCollection([]);

        $this->csp_nonce = sodium_bin2base64(random_bytes(32), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    abstract public function header(HeaderConfiguration|array $params): void;

    abstract protected function hasHeaderBeenWritten(): bool;

    abstract public function footer(FooterConfiguration|array $params): void;

    abstract public function displayStaticWidget(Widget_Static $widget);

    abstract public function includeCalendarScripts();

    abstract protected function getUser();

    public function addCssAsset(CssAssetGeneric $asset): void
    {
        $this->css_assets = $this->css_assets->merge(new CssAssetCollection([$asset]));
    }

    public function addCssAssetCollection(CssAssetCollection $collection)
    {
        $this->css_assets = $this->css_assets->merge($collection);
    }

    /**
     * Build an img tag
     *
     * @param string $src The src of the image "trash.png"
     * @param array $args The optionnal arguments for the tag ['alt' => 'Beautiful image']
     * @return string <img src="/themes/Tuleap/images/trash.png" alt="Beautiful image" />
     */
    public function getImage($src, $args = [])
    {
        $src = $this->getImagePath($src);

        $return = '<img src="' . $src . '"';
        foreach ($args as $k => $v) {
            $return .= ' ' . $k . '="' . $v . '"';
        }

        // insert a border tag if there isn't one
        if (! isset($args['border']) || ! $args['border']) {
            $return .= ' border="0"';
        }

        // insert alt tag if there isn't one
        if (! isset($args['alt']) || ! $args['alt']) {
            $return .= ' alt="' . $src . '"';
        }

        $return .= ' />';

        return $return;
    }

    public function getImagePath($src)
    {
        return $this->imgroot . $src;
    }

    /**
     * Add a Javascript file path that will be included at the end of the HTML page.
     *
     * The file will be included in the generated page just before the </body>
     * markup.
     *
     * @param String $file Path (relative to URL root) to the javascript file
     */
    public function includeFooterJavascriptFile($file)
    {
        $this->javascript_in_footer[] = ['file' => $file];
    }

    public function addJavascriptAsset(JavascriptAssetGeneric $asset): void
    {
        if ($this->hasHeaderBeenWritten() && ($asset->getType() === 'module' || $asset->getAssociatedCSSAssets()->getDeduplicatedAssets() !== [])) {
            throw new \RuntimeException('JavaScript module asset or with associated CSS assets must be added before the page header is written');
        }
        $this->javascript_assets[] = $asset;
    }

    /**
     * Add a Javascript piece of code to execute in the footer of the page.
     *
     * The code will appear just before </body> markup.
     *
     * @param String $snippet Javascript code.
     */
    public function includeFooterJavascriptSnippet($snippet)
    {
        $this->javascript_in_footer[] = ['snippet' => $snippet];
    }

    /** @deprecated */
    public function feedback($feedback)
    {
        return '';
    }

    /**
     * @psalm-return never-return
     */
    public function redirect(string $url): void
    {
        /**
         * @psalm-taint-escape header
         */
        $url = $this->url_verification->isInternal($url) ? $url : '/';

        $is_anon      = UserManager::instance()->getCurrentUser()->isAnonymous();
        $has_feedback = $GLOBALS['feedback'] || count($this->_feedback->logs);
        if (($is_anon && (headers_sent() || $has_feedback)) || (! $is_anon && headers_sent())) {
            $html_purifier = Codendi_HTMLPurifier::instance();
            $this->header(['title' => 'Redirection']);
            echo '<p>' . $GLOBALS['Language']->getText('global', 'return_to', [$url]) . '</p>';
            echo '<script type="text/javascript" nonce="' . $html_purifier->purify($this->getCSPNonce()) . '">';
            if ($has_feedback) {
                echo 'setTimeout(function() {';
            }
            echo " location.href = '" . $html_purifier->purify($url, Codendi_HTMLPurifier::CONFIG_JS_QUOTE) . "';";
            if ($has_feedback) {
                echo '}, 5000);';
            }
            echo '</script>';
            $this->footer([]);
        } else {
            if (! $is_anon && ! headers_sent()) {
                $this->_serializeFeedback();
            }

            header('Location: ' . $url);
        }
        exit();
    }

    public function addBreadcrumbs($breadcrumbs)
    {
        if ($breadcrumbs instanceof BreadCrumbCollection) {
            $this->breadcrumbs = $breadcrumbs;
            return;
        }

        foreach ($breadcrumbs as $breadcrumb) {
            if ($breadcrumb instanceof BreadCrumb) {
                $this->breadcrumbs->addBreadCrumb($breadcrumb);
            } else {
                $this->breadcrumbs->addBreadCrumb($this->getBreadCrumbItem($breadcrumb));
            }
        }
    }

    /**
     * @param array $breadcrumb
     *
     * @return BreadCrumb
     */
    private function getBreadCrumbItem(array $breadcrumb)
    {
        $link = $this->getLink($breadcrumb);

        $item = new BreadCrumb($link);
        if (isset($breadcrumb['sub_items'])) {
            $item->setSubItems($this->getSubItems($breadcrumb['sub_items']));
        }

        return $item;
    }

    /**
     * @param array $sub_items
     *
     * @return BreadCrumbSubItems
     */
    private function getSubItems(array $sub_items)
    {
        $links = [];
        foreach ($sub_items as $sub_item) {
            $links[] = $this->getLink($sub_item);
        }
        $collection = new BreadCrumbSubItems();
        $collection->addSection(
            new SubItemsUnlabelledSection(
                new BreadCrumbLinkCollection($links)
            )
        );

        return $collection;
    }

    /**
     * @param array $breadcrumb
     *
     * @return BreadCrumbLink
     */
    private function getLink(array $breadcrumb)
    {
        $link = new BreadCrumbLink($breadcrumb['title'], $breadcrumb['url']);
        if (isset($breadcrumb['icon_name'])) {
            $link->setIconName($breadcrumb['icon_name']);
        }

        return $link;
    }

    /**
     * @param string $item HTML
     * @return $this
     */
    public function addToolbarItem($item)
    {
        $this->toolbar[] = $item;

        return $this;
    }

    /**
     * Set to true if HTML object is displayed through a Service
     *
     * @see Service
     *
     * @param bool $value
     */
    public function setRenderedThroughservice($value)
    {
        $this->is_rendered_through_service = $value;
    }

    final protected function getProjectSidebarData(array $params, Project $project, CurrentUserWithLoggedInInformation $current_user): ProjectSidebarDataRepresentation
    {
        $event_manager = EventManager::instance();
        return ProjectSidebarDataRepresentation::fromConfigRepresentationAndUser(
            ProjectSidebarConfigRepresentation::build(
                $project,
                $current_user,
                new \Tuleap\Project\Banner\BannerRetriever(new \Tuleap\Project\Banner\BannerDao()),
                new ProjectFlagsBuilder(new ProjectFlagsDao()),
                EventManager::instance(),
                new UserCanAccessProjectAdministrationVerifier(new MembershipDelegationDao()),
                new FlavorFinderFromFilePresence(),
                new \Tuleap\Layout\Logo\CachedCustomizedLogoDetector(
                    new CustomizedLogoDetector(new LogoRetriever(), new FileContentComparator()),
                    BackendLogger::getDefaultLogger(),
                ),
                new GlyphFinder($event_manager),
                new ProjectSidebarToolsBuilder($event_manager, ProjectManager::instance(), $this->uri_sanitizer),
                $params['toptab'],
                $params['active-promoted-item-id'] ?? '',
            ),
            $current_user->user,
        );
    }

    final protected function getProjectBannerWithScript(Project $project, PFUser $current_user, string $script_name): ?BannerDisplay
    {
        $project_banner = $this->getProjectBanner($project, $current_user);
        if ($project_banner === null) {
            return null;
        }

        $this->includeFooterJavascriptFile($this->include_asset->getFileURL($script_name));

        return $project_banner;
    }

    final protected function getPlatformBannerWithScript(PFUser $current_user, string $script_name): ?\Tuleap\Platform\Banner\BannerDisplay
    {
        $banner = $this->getPlatformBanner($current_user);
        if ($banner) {
            $this->includeFooterJavascriptFile($this->include_asset->getFileURL($script_name));
        }

        return $banner;
    }

    final protected function getPlatformBanner(PFUser $current_user): ?\Tuleap\Platform\Banner\BannerDisplay
    {
        $banner_retriever = new \Tuleap\Platform\Banner\BannerRetriever(new \Tuleap\Platform\Banner\BannerDao());
        $banner           = $banner_retriever->getBannerForDisplayPurpose($current_user, new \DateTimeImmutable());
        if ($banner === null) {
            return null;
        }

        return $banner;
    }

    protected function getFooterSiteJs()
    {
        ob_start();
        include($GLOBALS['Language']->getContent('layout/footer', null, null, '.js'));

        return ob_get_clean();
    }

    final protected function getProjectBanner(Project $project, PFUser $current_user): ?BannerDisplay
    {
        return (new BannerRetriever(new BannerDao()))->getBannerForDisplayPurpose($project, $current_user);
    }

    protected function getNewDropdownCurrentContextSectionFromParams(array $params): ?NewDropdownLinkSectionPresenter
    {
        if (! isset($params['new_dropdown_current_context_section'])) {
            return null;
        }

        $section = $params['new_dropdown_current_context_section'];

        return $section instanceof NewDropdownLinkSectionPresenter ? $section : null;
    }

    protected function injectProjectBackground(Project $project, array &$params): void
    {
        $background_configuration = new ProjectBackgroundConfiguration(new ProjectBackgroundDao());
        $background               = $background_configuration->getBackground($project);
        if (! $background) {
            return;
        }

        if (! isset($params['main_classes'])) {
            $params['main_classes'] = [];
        }

        $params['main_classes'][] = 'project-with-background';
        $this->addCSSAsset(
            new CssAssetWithoutVariantDeclinaisons(
                new \Tuleap\Layout\IncludeCoreAssets(),
                "project-background/" . $background->getIdentifier()
            )
        );
    }

    final public function getCSPNonce(): string
    {
        return $this->csp_nonce;
    }
}
