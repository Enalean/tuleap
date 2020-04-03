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
use ForgeConfig;
use HTTPRequest;
use PermissionsOverrider_PermissionsOverriderManager;
use PFUser;
use Project;
use ProjectManager;
use Response;
use Toggler;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLink;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbSubItems;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsUnlabelledSection;
use Tuleap\Project\Admin\MembershipDelegationDao;
use Tuleap\Project\Banner\BannerDao;
use Tuleap\Project\Banner\BannerDisplay;
use Tuleap\Project\Banner\BannerRetriever;
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
    protected $javascript_in_footer = array();

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
        $this->toolbar     = array();

        $this->include_asset = new IncludeAssets(__DIR__ . '/../../www/assets/core', '/assets/core');
        $this->uri_sanitizer = new URISanitizer(new Valid_LocalURI(), new Valid_FTPURI());
        $this->css_assets    = new CssAssetCollection([]);
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
    public function getImage($src, $args = array())
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

    protected function getMOTD()
    {
        $motd      = '';
        $motd_file = $GLOBALS['Language']->getContent('others/motd');
        if (! strpos($motd_file, "empty.txt")) {
            // empty.txt returned when no motd file found
            ob_start();
            include($motd_file);
            $motd = ob_get_clean();
        }

        $deprecated = $this->getBrowserDeprecatedMessage();
        if ($motd && $deprecated) {
            return $deprecated . '<br />' . $motd;
        } else {
            return $motd . $deprecated;
        }
    }

    private function getBrowserDeprecatedMessage()
    {
        return HTTPRequest::instance()->getBrowser()->getDeprecatedMessage();
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
        $this->javascript_in_footer[] = array('file' => $file);
    }

    public function addJavascriptAsset(JavascriptAsset $asset)
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
        $this->javascript_in_footer[] = array('snippet' => $snippet);
    }

    /** @deprecated */
    public function feedback($feedback)
    {
        return '';
    }

    /**
     * @psalm-return never-return
     */
    public function redirect($url)
    {
        $is_anon = UserManager::instance()->getCurrentUser()->isAnonymous();
        $has_feedback = $GLOBALS['feedback'] || count($this->_feedback->logs);
        if (($is_anon && (headers_sent() || $has_feedback)) || (!$is_anon && headers_sent())) {
            $this->header(array('title' => 'Redirection'));
            echo '<p>' . $GLOBALS['Language']->getText('global', 'return_to', array($url)) . '</p>';
            echo '<script type="text/javascript">';
            if ($has_feedback) {
                echo 'setTimeout(function() {';
            }
            echo " location.href = '" . $url . "';";
            if ($has_feedback) {
                echo '}, 5000);';
            }
            echo '</script>';
            $this->footer(array());
        } else {
            if (!$is_anon && !headers_sent() && $has_feedback) {
                $this->_serializeFeedback();
            }

            header('Location: ' . $url);
        }
        exit();
    }

    /**
     * Display debug info gathered along the execution
     *
     * @return void
     */
    public static function showDebugInfo()
    {
        echo '<div id="footer_debug_separator"/>';
        echo '<div id="footer_debug">';

        echo '<div class="alert alert-info">
                   <h4> Development useful information! </h4>
                   The section above will show you some useful information about Tuleap for development purpose.
              </div>';

        echo '<div id="footer_debug_content">';
            $debug_compute_tile = microtime(true) - $GLOBALS['debug_time_start'];

        if (function_exists('xdebug_time_index')) {
            $xdebug_time_index  = xdebug_time_index();
        }

        $query_time = 0;
        foreach ($GLOBALS['DBSTORE'] as $d) {
            foreach ($d['trace'] as $trace) {
                $query_time += $trace[2] - $trace[1];
            }
        }

        $purifier = Codendi_HTMLPurifier::instance();

        echo '<span class="debug">' . $GLOBALS['Language']->getText('include_layout', 'query_count') . ": ";
        echo $GLOBALS['DEBUG_DAO_QUERY_COUNT'] . "</span>";
        $percent     = (int) ($GLOBALS['DEBUG_TIME_IN_PRE'] * 100 / $debug_compute_tile);
        $sql_percent = (int) ($query_time * 100 / $debug_compute_tile);
        echo '<table border=1><thead><tr><th></th><th>Page generated in</th></tr></thead><tbody>';
        echo '<tr><td>pre.php</td><td>' . number_format(1000 * $GLOBALS['DEBUG_TIME_IN_PRE'], 0, '.', "'") . ' ms (' . $percent . '%)</td>';
        echo '<tr><td>remaining</td><td>' . number_format(1000 * ($debug_compute_tile - $GLOBALS['DEBUG_TIME_IN_PRE']), 0, '.', "'") . ' ms</td>';
        echo '<tr><td><b>total</td><td><b>' . number_format(1000 * $debug_compute_tile, 0, '.', "'") . ' ms</td>';
        if (function_exists('xdebug_time_index')) {
            echo '<tr><td>xdebug</td><td>' . number_format(1000 * $xdebug_time_index, 0, '.', "'") . ' ms</tr>';
        }
        echo '<tr><td>sql</td><td>' . number_format(1000 * $query_time, 0, '.', "'") . ' ms (' . $sql_percent . '%)</tr>';
        echo '</tbody></table>';
        if (function_exists('xdebug_get_profiler_filename')) {
            if ($file = xdebug_get_profiler_filename()) {
                echo '<div>Profiler info has been written in: ' . $file . '</div>';
            }
        }

        $hook_params = array();
        EventManager::instance()->processEvent('layout_footer_debug', $hook_params);

        // Display all queries used to generate the page
        echo '<fieldset><legend id="footer_debug_allqueries" class="' . Toggler::getClassname('footer_debug_allqueries') . '">All queries:</legend>';
        echo '<pre>';
        $queries               = array();
        $queries_by_time_taken = array();
        $i                     = 0;
        foreach ($GLOBALS['QUERIES'] as $sql) {
            $t = 0;
            foreach ($GLOBALS['DBSTORE'][md5($sql)]['trace'] as $trace) {
                $t += $trace[2] - $trace[1];
            }
            $q = array(
                'sql' => $purifier->purify($sql),
                'total time' => number_format(1000 * $t, 0, '.', "'") . ' ms',
            );
            $queries[] = $q;
            $queries_by_time_taken[] = array('n°' => $i++, 't' => $t) + $q;
        }
        print_r($queries);
        echo '</pre>';
        echo '</fieldset>';

        // Display all queries used to generate the page ordered by time taken
        usort(
            $queries_by_time_taken,
            static function ($a, $b) {
                return strnatcasecmp($b['total time'], $a['total time']);
            }
        );
        echo '<fieldset><legend id="footer_debug_allqueries_time_taken" class="' . Toggler::getClassname('footer_debug_allqueries_time_taken') . '">All queries by time taken:</legend>';
        echo '<table border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">';
        echo '<thead><tr><th>n°</th><th style="white-space:nowrap;">time taken</th><th>sum</th><th>sql</th></tr></thead>';
        $i   = 0;
        $sum = 0;
        foreach ($queries_by_time_taken as $q) {
            echo '<tr valign="top" class="' . html_get_alt_row_color($i++) . '">';
            echo '<td>' . $q['n°'] . '</td>';
            echo '<td style="white-space:nowrap;">' . $q['total time'] . '</td>';
            echo '<td style="white-space:nowrap;">' . number_format(1000 * ($sum += $q['t']), 0, '.', "'") . ' ms' . '</td>';
            echo '<td><pre>' . $q['sql'] . '</pre></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</fieldset>';

        echo '<fieldset><legend id="footer_debug_queriespaths" class="' . Toggler::getClassname('footer_dubug_queriespaths') . '">Path of all queries:</legend>';
        $max = 0;
        foreach ($GLOBALS['DBSTORE'] as $d) {
            foreach ($d['trace'] as $trace) {
                $time_taken = 1000 * round($trace[2] - $trace[1], 3);
                if ($max < $time_taken) {
                    $max = $time_taken;
                }
            }
        }

        $paths       = array();
        $time        = $GLOBALS['debug_time_start'];
        foreach ($GLOBALS['DBSTORE'] as $d) {
            foreach ($d['trace'] as $trace) {
                $time_taken = 1000 * round($trace[2] - $trace[1], 3);
                self::debugBacktraceRec(
                    $paths,
                    array_reverse($trace[0]),
                    '[' . (1000 * round($trace[1] - $GLOBALS['debug_time_start'], 3)) . '/' . $time_taken . '] ' .
                    ($time_taken >= $max ? ' <span style="background:yellow; padding-left:4px; padding-right:4px; color:red;">top!</span> ' : '') . $purifier->purify($d['sql'])
                );
            }
        }
        echo '<table>';
        self::debugDisplayPaths($paths, false);
        echo '</table>';
        echo '</fieldset>';

        // Display queries executed more than once
        $title_displayed = false;
        foreach ($GLOBALS['DBSTORE'] as $key => $value) {
            if ($GLOBALS['DBSTORE'][$key]['nb'] > 1) {
                if (!$title_displayed) {
                    echo '<fieldset><legend>Queries executed more than once :</legend>';
                    $title_displayed = true;
                }
                echo "<fieldset>";
                echo '<legend id="footer_debug_doublequery_' . $key . '" class="' . Toggler::getClassname('footer_debug_doublequery_' . $key) . '">';
                echo '<b>Run ' . $GLOBALS['DBSTORE'][$key]['nb'] . " times: </b>";
                echo $purifier->purify($GLOBALS['DBSTORE'][$key]['sql']) . "\n";
                echo '</legend>';
                self::debugBacktraces($GLOBALS['DBSTORE'][$key]['trace']);
                echo "</fieldset>";
            }
        }
        if ($title_displayed) {
            echo '</fieldset>';
        }
        echo "</pre>\n";
        echo '</div>';
        echo '</div>';
    }

    public static function debugBacktraceRec(&$paths, $trace, $leaf = '')
    {
        if (count($trace)) {
            $file = '';
            if (isset($trace[0]['file'])) {
                $file = substr($trace[0]['file'], strlen($GLOBALS['codendi_dir'])) . ' #' . $trace[0]['line'];
            }
            $file .= ' (' . (isset($trace[0]['class']) ? $trace[0]['class'] . '::' : '') . $trace[0]['function'] . ')';
            if (strpos($file, '/src/common/dao/include/DataAccessObject.class.php') === 0) {
                self::debugBacktraceRec($paths, array_slice($trace, 1), $leaf);
            } else {
                self::debugBacktraceRec($paths[$file], array_slice($trace, 1), $leaf);
            }
        } elseif ($leaf) {
            $paths[] = $leaf;
        }
    }

    public static function debugBacktraces($backtraces)
    {
        $paths = array();
        $i = 1;
        foreach ($backtraces as $b) {
            self::debugBacktraceRec($paths, array_reverse($b[0]), ('#' . $i++));
        }
        echo '<table>';
        self::debugDisplayPaths($paths);
        echo '</table>';
    }

    public static function debugDisplayPaths($paths, $red = true, $padding = 0)
    {
        if (is_array($paths)) {
            $color = "black";
            if ($red && count($paths) > 1) {
                $color = "red";
            }
            $purifier = Codendi_HTMLPurifier::instance();
            foreach ($paths as $p => $next) {
                if (is_numeric($p)) {
                    echo '<tr style="color:green">';
                    echo '<td></td>';
                    echo '<td>' . $purifier->purify($next) . '</td>';
                    echo '</tr>';
                } else {
                    echo '<tr style="color:' . $color . '">';
                    echo '<td style="padding-left:' . $padding . 'px;">';
                    echo substr($p, 0, strpos($p, ' '));
                    echo '</td>';
                    echo '<td>';
                    echo substr($p, strpos($p, ' '));
                    echo '</td>';
                    echo '</tr>';
                }
                self::debugDisplayPaths($next, $red, $padding + 20);
            }
        }
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

    protected function getProjectPrivacy(Project $project)
    {
        if (ForgeConfig::areRestrictedUsersAllowed()) {
            switch ($project->getAccess()) {
                case Project::ACCESS_PUBLIC:
                    return _('Project privacy set to public.') . ' ' .
                        _('By default, its content is available to all authenticated, but not restricted, users.') . ' ' .
                        _('Please note that more restrictive permissions might exist on some items.');
                    break;
                case Project::ACCESS_PUBLIC_UNRESTRICTED:
                    return _('Project privacy set to public including restricted.') . ' ' .
                        _('By default, its content is available to all authenticated users.') . ' ' .
                        _('Please note that more restrictive permissions might exist on some items.');
                    break;
                case Project::ACCESS_PRIVATE_WO_RESTRICTED:
                    return _('Project privacy set to private.') . ' ' .
                        _('Only project members can access its content.') . ' ' .
                        _('Restricted users are not allowed in this project.');
                    break;
                default:
                    return _('Project privacy set to private including restricted.') . ' ' .
                        _('Only project members can access its content.') . ' ' .
                        _('Restricted users are allowed in this project.');
            }
        } elseif (ForgeConfig::areAnonymousAllowed()) {
            if ($project->isPublic()) {
                return _('Project privacy set to public.') . ' ' .
                    _('By default, its content is available to everyone (authenticated or not).') . ' ' .
                    _('Please note that more restrictive permissions might exist on some items.');
            }

            return _('Project privacy set to private.') . ' ' .
                _('Only project members can access its content.');
        } else {
            if ($project->isPublic()) {
                return _('Project privacy set to public.') . ' ' .
                    _('By default, its content is available to all authenticated, but not restricted, users.') . ' ' .
                    _('Please note that more restrictive permissions might exist on some items.');
            }

            return _('Project privacy set to private.') . ' ' .
                _('Only project members can access its content.');
        }
    }

    final protected function getProjectBanner(Project $project, PFUser $current_user, string $script_name): ?BannerDisplay
    {
        $project_banner = (new BannerRetriever(new BannerDao()))->getBannerForDisplayPurpose($project, $current_user);
        if ($project_banner === null) {
            return null;
        }

        $this->includeFooterJavascriptFile($this->include_asset->getFileURL($script_name));

        return $project_banner;
    }

    protected function getFooterSiteJs()
    {
        ob_start();
        include($GLOBALS['Language']->getContent('layout/footer', null, null, '.js'));

        return ob_get_clean();
    }
}
