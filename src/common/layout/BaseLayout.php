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

use Codendi_HTMLPurifier;
use EventManager;
use PermissionsOverrider_PermissionsOverriderManager;
use PFUser;
use Project;
use ProjectManager;
use Response;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\BannerDisplay;
use Tuleap\Project\Banner\BannerRetriever;
use Tuleap\Project\ProjectBackground\ProjectBackgroundConfiguration;
use Tuleap\Project\ProjectBackground\ProjectBackgroundDao;
use Tuleap\Sanitizer\URISanitizer;
use UserManager;
use Valid_FTPURI;
use Valid_LocalURI;
use Widget_Static;

abstract class BaseLayout extends Response
{
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
     * @var JavascriptAsset[]
     */
    protected $javascript_assets = [];

    public function __construct($root)
    {
        parent::__construct();
        $this->root    = $root;
        $this->imgroot = $root . '/images/';

        $this->breadcrumbs = new BreadCrumbCollection();
        $this->toolbar     = [];

        $this->include_asset  = new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core');
        $this->uri_sanitizer  = new URISanitizer(new Valid_LocalURI(), new Valid_FTPURI());
        $this->url_verification = new \URLVerification();

        $this->css_assets = new CssAssetCollection([]);
    }

    abstract public function header(array $params);
    abstract public function footer(array $params);
    abstract public function displayStaticWidget(Widget_Static $widget);
    abstract public function includeCalendarScripts();
    abstract protected function getUser();

    public function addCssAsset(CssAsset $asset)
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

    public function addJavascriptAsset(JavascriptAsset $asset): void
    {
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
         * @psalm-taint-escape text
         */
        $url = $this->url_verification->isInternal($url) ? $url : '/';

        $is_anon = UserManager::instance()->getCurrentUser()->isAnonymous();
        $has_feedback = $GLOBALS['feedback'] || count($this->_feedback->logs);
        if (($is_anon && (headers_sent() || $has_feedback)) || (! $is_anon && headers_sent())) {
            $this->header(['title' => 'Redirection']);
            echo '<p>' . $GLOBALS['Language']->getText('global', 'return_to', [$url]) . '</p>';
            echo '<script type="text/javascript">';
            if ($has_feedback) {
                echo 'setTimeout(function() {';
            }
            echo " location.href = '" . Codendi_HTMLPurifier::instance()->purify($url, Codendi_HTMLPurifier::CONFIG_JS_QUOTE) . "';";
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

    protected function getProjectSidebar($params, $project)
    {
        $builder = new ProjectSidebarBuilder(
            EventManager::instance(),
            ProjectManager::instance(),
            PermissionsOverrider_PermissionsOverriderManager::instance(),
            Codendi_HTMLPurifier::instance(),
            $this->uri_sanitizer,
            new MembershipDelegationDao()
        );

        return $builder->getSidebar($this->getUser(), $params['toptab'], $project);
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
        $banner = $banner_retriever->getBannerForDisplayPurpose($current_user);
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
                new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core'),
                "project-background/$background"
            )
        );
    }
}
