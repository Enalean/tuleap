<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2000 (c) The SourceForge Crew
 * http://sourceforge.net
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

use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Layout\ThemeVariation;

/**
 *
 * Extends the basic Response class to add HTML functions for displaying all site dependent HTML, while allowing extendibility/overriding by themes via the Theme class.
 *
 * Geoffrey Herteg, August 29, 2000
 * @deprecated
 *
 */
abstract class Layout extends Tuleap\Layout\BaseLayout //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    /**
     * Html purifier
     * @var Codendi_HTMLPurifier
     */
    protected $purifier;


    private $javascript;

    //Define all the icons for this theme
    public $icons = ['Summary' => 'ic/anvil24.png',
        'Homepage' => 'ic/home.png',
        'Forums' => 'ic/notes.png',
        'Bugs' => 'ic/bug.png',
        'Support' => 'ic/support.png',
        'Patches' => 'ic/patch.png',
        'Lists' => 'ic/mail.png',
        'Tasks' => 'ic/index.png',
        'Docs' => 'ic/docman.png',
        'News' => 'ic/news.png',
        'Files' => 'ic/save.png',
        'Trackers' => 'ic/tracker20w.png',
    ];

    public const INCLUDE_FAT_COMBINED = 'include_fat_combined';

    /**
     * Background for priorities
     *
     * @var array
     */
    private $bgpri = [];

    /**
     * Store custom css added on the fly
     *
     * @var Array of path to CSS files
     */
    protected $stylesheets = [];

    /**
     * Constuctor
     * @param string $root the root of the theme : '/themes/Tuleap/'
     */
    public function __construct($root)
    {
        // Constructor for parent class...
        parent::__construct($root);

        $this->imgroot = $root . '/images/';

        $this->javascript = [];
        $this->includeJavascriptFile((new JavascriptAsset(new \Tuleap\Layout\IncludeCoreAssets(), 'collect-frontend-errors.js'))->getFileURL());

        /*
            Set up the priority color array one time only
        */
        $this->bgpri[1] = 'priora';
        $this->bgpri[2] = 'priorb';
        $this->bgpri[3] = 'priorc';
        $this->bgpri[4] = 'priord';
        $this->bgpri[5] = 'priore';
        $this->bgpri[6] = 'priorf';
        $this->bgpri[7] = 'priorg';
        $this->bgpri[8] = 'priorh';
        $this->bgpri[9] = 'priori';

        $this->purifier = Codendi_HTMLPurifier::instance();
    }

    public function iframe($url, $html_options = [])
    {
        $url_purified = $this->purifier->purify($this->uri_sanitizer->sanitizeForHTMLAttribute($url));

        $html = '<div class="iframe_showonly"><a href="' . $url_purified . '" title="' . $GLOBALS['Language']->getText('global', 'show_frame') . '">' . $GLOBALS['Language']->getText('global', 'show_frame') . ' ' . $this->getImage('ic/plain-arrow-down.png') . '</a></div>';
        $args = ' src="' . $url_purified . '" ';
        foreach ($html_options as $key => $value) {
            $args .= ' ' . $key . '="' . $value . '" ';
        }
        $html .= '<iframe ' . $args . '></iframe>';
        echo $html;
    }

    public function selectRank($id, $rank, $items, $html_options)
    {
        $html  = '';
        $html .= '<select ';
        foreach ($html_options as $key => $value) {
            $html .= $key . '="' . $value . '"';
        }
        $html                 .= '>';
        $html                 .= '<option value="beginning">' . $GLOBALS['Language']->getText('global', 'at_the_beginning') . '</option>';
        $html                 .= '<option value="end">' . $GLOBALS['Language']->getText('global', 'at_the_end') . '</option>';
        [$options, $optgroups] = $this->selectRank_optgroup($id, $items);
        $html                 .= $options . $optgroups;
        $html                 .= '</select>';
        return $html;
    }

    protected function selectRank_optgroup($id, $items, $prefix = '', $value_prefix = '') // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $html      = '';
        $optgroups = '';
        $purifier  = Codendi_HTMLPurifier::instance();
        foreach ($items as $i => $item) {
            // don't include the item itself
            if ($item['id'] != $id) {
                // need an optgroup ?
                if (isset($item['subitems'])) {
                    $optgroups .= '<optgroup label="' . $purifier->purify($prefix . $item['name']) . '">';

                    $selected = '';
                    if (count($item['subitems'])) {
                        // look if our item is the first subitem
                        // if it is the case then select 'At the beginning of <parent>'
                        reset($item['subitems']);
                        $subitem = current($item['subitems']);
                        if ($subitem['id'] == $id) {
                            $selected = 'selected="selected"';
                        }
                    }
                    $optgroups .= '<option value="' . $purifier->purify(
                        $item['id']
                    ) . ':' . 'beginning' . '" ' . $selected . '>' . 'At the beginning of ' . $purifier->purify(
                        $prefix . $item['name']
                    ) . '</option>';
                    [$o, $g]    = $this->selectRank_optgroup(
                        $id,
                        $item['subitems'],
                        $prefix . $item['name'] . '::',
                        $item['id'] . ':'
                    );
                    $optgroups .= $o;
                    $optgroups .= '</optgroup>';
                    $optgroups .= $g;
                }

                // The rank is the next one.
                // TODO: use the next rank instead?
                $value = $item['rank'] + 1;

                // select the element if the item is just after id
                $selected = '';
                if (isset($items[$i + 1]) && $items[$i + 1]['id'] == $id) {
                    $selected = 'selected="selected"';
                }
                $html .= '<option value="' . $purifier->purify($value_prefix . $value) . '" ' . $selected . '>';
                $html .= $GLOBALS['Language']->getText('global', 'after', $purifier->purify($prefix . $item['name']));
                $html .= '</option>';
            }
        }
        return [$html, $optgroups];
    }

    /**
     * Add a Javascript file path that will be included in the header of the HTML page.
     *
     * The file will be included in the generated page in <head> section
     * Note: the order of call of include*Javascript method is very important.
     * The code will be included and executed in the same order the
     * includes are done. This allows (for instance) to define a var before
     * including a script (eg. Layout::includeCalendarScripts).
     *
     * @see   Layout::includeCalendarScripts
     * @param String $file Path (relative to URL root) the the javascript file
     *
     * @return void
     */
    public function includeJavascriptFile($file)
    {
        $this->javascript[] = ['file' => $file];
        return $this;
    }

    /**
     * Add a Javascript piece of code to execute in the header of the page.
     *
     * Codendi will append and execute the code in <head> section.
     * Note: the order of call of include*Javascript method is very important.
     * see includeJavascriptFile for more details
     *
     * @see Layout::includeJavascriptFile
     * @param String $snippet Javascript code.
     *
     * @return void
     */
    public function includeJavascriptSnippet($snippet)
    {
        $this->javascript[] = ['snippet' => $snippet];
        return $this;
    }

    protected function includeJavascriptPolyfills()
    {
    }

    /**
     * @return PFUser
     */
    protected function getUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    public function includeCalendarScripts()
    {
        $this->includeJavascriptSnippet("var useLanguage = '" . Codendi_HTMLPurifier::instance()->purify(substr($this->getUser()->getLocale(), 0, 2), CODENDI_PURIFIER_JS_QUOTE) . "';");
        $this->includeJavascriptFile("/scripts/datepicker/datepicker.js");
        return $this;
    }

    public function _getFeedback(): string //phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $feedback = '';
        if (trim($GLOBALS['feedback']) !== '') {
            $feedback = '<H3><span class="feedback">' . $GLOBALS['feedback'] . '</span></H3>';
        }
        return $feedback;
    }

    public function displayStaticWidget(Widget_Static $widget)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        echo '<div class="widget">';

        echo '<div class="widget_titlebar">';
        echo '<div class="widget_titlebar_title">' . $purifier->purify($widget->getTitle()) . '</div>';
        echo '</div>';

        echo '<div class="widget_content">';
        echo $widget->getContent();
        echo '</div>';

        echo '</div>';
    }

    /**
     * Box Top, equivalent to html_box1_top()
     *
     * @see Widget_Static
     * @deprecated You should consider using Widget_Static instead
     */
    public function box1_top($title, $echoout = 1, $bgcolor = '', $cols = 2) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
            $return = '<TABLE class="boxtable" cellspacing="1" cellpadding="5" width="100%" border="0">
                        <TR class="boxtitle" align="center">
                                <TD colspan="' . $cols . '"><SPAN class=titlebar>' . $title . '</SPAN></TD>
                        </TR>
                        <TR class="boxitem">
                                <TD colspan="' . $cols . '">';
        if ($echoout) {
                print $return;
        } else {
                return $return;
        }
    }

    /**
     * Box Bottom, equivalent to html_box1_bottom()
     *
     * @see Widget_Static
     * @deprecated You should consider using Widget_Static instead
     */
    public function box1_bottom($echoout = 1) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
            $return = '
                </TD>
                        </TR>
        </TABLE>
';
        if ($echoout) {
                print $return;
        } else {
                return $return;
        }
    }

    private function shouldIncludeFatCombined(array $params)
    {
        return ! isset($params[self::INCLUDE_FAT_COMBINED]) || $params[self::INCLUDE_FAT_COMBINED] == true;
    }

    /**
     * Display the Javascript code to be included in <head>
     *
     * Snippet and files are included one after another in the order of call
     * of includeJavascriptFile & includeJavascriptSnippet methods.
     *
     * @see includeJavascriptFile
     * @see includeJavascriptSnippet
     */
    public function displayJavascriptElements($params)
    {
        if ($this->shouldIncludeFatCombined($params)) {
            $this->includeJavascriptFile($this->include_asset->getFileURL('tuleap-including-prototypejs.js'));
        } else {
            $this->includeSubsetOfCombined();
        }

        $this->includeJavascriptFile($this->include_asset->getFileURL('ckeditor.js'));
        $this->includeJavascriptFile($this->include_asset->getFileURL('rich-text-editor-including-prototypejs.js'));

        //Javascript i18n
        echo '<script type="text/javascript" nonce="' . $this->purifier->purify($this->getCSPNonce()) . '">' . "\n";
        include $GLOBALS['Language']->getContent('scripts/locale');
        echo '
        codendi.imgroot = \'' . $this->imgroot . '\';
        </script>' . "\n";

        $this->includeJavascriptPolyfills();

        $em = EventManager::instance();
        $em->processEvent("javascript_file", ['layout' => $this]);

        foreach ($this->javascript as $js) {
            if (isset($js['file'])) {
                echo '<script type="text/javascript" src="' . $js['file'] . '" nonce="' . $this->purifier->purify($this->getCSPNonce()) . '"></script>' . "\n";
            } else {
                if (isset($js['snippet'])) {
                    echo '<script type="text/javascript" nonce="' . $this->purifier->purify($this->getCSPNonce()) . '">' . "\n";
                    echo '//<!--' . "\n";
                    echo $js['snippet'] . "\n";
                    echo '//-->' . "\n";
                    echo '</script>' . "\n";
                }
            }
        }
        echo '<script type="text/javascript" nonce="' . $this->purifier->purify($this->getCSPNonce()) . '">' . "\n";
        $em->processEvent(Event::JAVASCRIPT, null);
        echo '
        </script>';
    }

    protected function includeSubsetOfCombined()
    {
        $this->includeJavascriptFile($this->include_asset->getFileURL('tuleap_subset.js'));
    }

    /**
     * Display the Javascript code to be included at the end of the page.
     * Snippet and files are included one after another in the order of call
     * of includeFooterJavascriptFile & includeFooterJavascriptSnippet methods.
     *
     * @see includeFooterJavascriptFile
     * @see includeFooterJavascriptSnippet
     */
    public function displayFooterJavascriptElements()
    {
        foreach ($this->javascript_in_footer as $js) {
            if (isset($js['file'])) {
                echo '<script type="text/javascript" src="' . $js['file'] . '" nonce="' . $this->purifier->purify($this->getCSPNonce()) . '"></script>' . "\n";
            } else {
                echo '<script type="text/javascript" nonce="' . $this->purifier->purify($this->getCSPNonce()) . '">' . "\n";
                echo '//<!--' . "\n";
                echo $js['snippet'] . "\n";
                echo '//-->' . "\n";
                echo '</script>' . "\n";
            }
        }
        foreach ($this->javascript_assets as $javascript_asset) {
            echo sprintf(
                '<script type="%s" src="%s" nonce="%s"></script>%s',
                $this->purifier->purify($javascript_asset->getType()),
                $this->purifier->purify($javascript_asset->getFileURL()),
                $this->purifier->purify($this->getCSPNonce()),
                PHP_EOL
            );
        }
        echo '<script type="text/javascript" nonce="' . $this->purifier->purify($this->getCSPNonce()) . '">' . "\n";
        echo $this->getFooterSiteJs();
        echo '
        </script>';
    }

    /**
     * Add a stylesheet to be include in headers
     *
     * @param String $file Path to CSS file
     */
    public function addStylesheet($file)
    {
        $this->stylesheets[] = $file;
    }

    /**
     * Get all stylesheets defined previously
     *
     * @return Array of CSS file path
     */
    public function getAllStyleSheets()
    {
        return $this->stylesheets;
    }

    public function getStylesheetTheme($css)
    {
        return '/themes/' . ForgeConfig::get('sys_user_theme') . '/css/' . $css;
    }

    private function getAssets(): IncludeAssets
    {
        return new \Tuleap\Layout\IncludeCoreAssets();
    }

    private function getCurrentThemeVariation(): ThemeVariation
    {
        $current_user  = UserManager::instance()->getCurrentUser();
        $theme_variant = new ThemeVariant();
        $color         = $theme_variant->getVariantColorForUser($current_user);
        return new ThemeVariation($color, $current_user);
    }

    /**
     * Display all the stylesheets for the current page
     */
    public function displayStylesheetElements(): void
    {
        $this->displayCommonStylesheetElements();

        // Display custom css
        foreach ($this->getAllStylesheets() as $css) {
            echo '<link rel="stylesheet" type="text/css" href="' . $css . '" />';
        }

        $theme_variation = $this->getCurrentThemeVariation();
        foreach ($this->css_assets->getDeduplicatedAssets() as $css_asset) {
            echo '<link rel="stylesheet" type="text/css" href="' . $css_asset->getFileURL($theme_variation) . '" />';
        }

        $purifier = Codendi_HTMLPurifier::instance();
        foreach ($this->javascript_assets as $javascript_asset) {
            foreach ($javascript_asset->getAssociatedCSSAssets()->getDeduplicatedAssets() as $css_asset) {
                echo '<link rel="stylesheet" type="text/css" href="' . $purifier->purify($css_asset->getFileURL($theme_variation)) . '" />';
            }
        }

        // Plugins css
        $em = $this->getEventManager();
        $em->processEvent("cssfile", null);

        // Inline stylesheets
        echo '
        <style type="text/css">
        ';
        $em->processEvent("cssstyle", null);
        echo '
        </style>';
    }

    protected function displayCommonStylesheetElements(): void
    {
        $core_assets = $this->getAssets();
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-tuleap-22d39b3.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-tuleap-responsive-22d39b3.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/animate.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $core_assets->getFileURL('common-theme/style.css') . '" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $core_assets->getFileURL('common-theme/print.css') . '" media="print" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $this->getStylesheetTheme('style.css') . '" />';
        echo '<link rel="stylesheet" type="text/css" href="' . $this->getStylesheetTheme('print.css') . '" media="print" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-select/bootstrap-select.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/select2/select2.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" />';
    }

    /**
     * Display all the syndication feeds (rss for now) for the current page
     */
    public function displaySyndicationElements()
    {
        $hp = Codendi_HTMLPurifier::instance();

        //Basic feeds
        echo $this->getRssFeed(
            $hp->purify(ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) . ' - ' . $GLOBALS['Language']->getText('include_layout', 'latest_news_rss'), CODENDI_PURIFIER_CONVERT_HTML),
            '/export/rss_sfnews.php'
        );
        echo $this->getRssFeed(
            $hp->purify(ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) . ' - ' . $GLOBALS['Language']->getText('include_layout', 'newest_projects_rss'), CODENDI_PURIFIER_CONVERT_HTML),
            '/export/rss_sfprojects.php'
        );
    }

    /**
     * @param string $title the title of the feed
     * @param string $href the href of the feed
     * @return string the <link> tag for the feed
     */
    public function getRssFeed($title, $href)
    {
        return '<link rel="alternate" title="' . $title . '" href="' . $href . '" type="application/rss+xml" />';
    }

    /**
     * Helper for the calendar picker. It returns the html snippet which will
     * enable user to specify a date with the help of little dhtml
     *
     * @deprecated since version 7.0 in favor of getBootstrapDatePicker
     * @param string $id the id of the input element
     * @param string $name the name of the input element
     * @param string $size the optional size of the input element, default is 10
     * @param string $maxlength the optional maxlength the input element, default is 10
     * @return string The calendar picker
     */
    public function getDatePicker($id, $name, $value, $size = 10, $maxlength = 10)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return '<span style="white-space:nowrap;"><input type="text"
                       class="highlight-days-67 format-y-m-d divider-dash no-transparency"
                       id="' .  $hp->purify($id, CODENDI_PURIFIER_CONVERT_HTML)  . '"
                       name="' . $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) . '"
                       size="' . $hp->purify($size, CODENDI_PURIFIER_CONVERT_HTML) . '"
                       maxlength="' . $hp->purify($maxlength, CODENDI_PURIFIER_CONVERT_HTML) . '"
                       value="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '"></span>';
    }

    /**
     * Helper for the calendar picker. It returns the html snippet which will
     * enable user to specify a date with the help of little dhtml
     *
     * @param array   $criteria_selector list of extra criterias to be listed in a prepended select
     * @param array   $classes extra css classes if needed
     *
     */
    public function getBootstrapDatePicker(
        string $id,
        string $name,
        $value,
        array $criteria_selector,
        array $classes,
        bool $is_time_displayed,
        string $data_test,
    ): string {
        $hp = Codendi_HTMLPurifier::instance();

        $format     = "yyyy-MM-dd";
        $date_class = 'tuleap_field_date';

        if ($is_time_displayed) {
            $format     = "yyyy-MM-dd hh:mm";
            $date_class = 'tuleap_field_datetime';
        }

        $classes[] = $date_class;

        $html  = '';
        $html .= '<div class="input-prepend dropdown input-append date ' . implode(' ', $classes) . '">';

        if (count($criteria_selector) > 0) {
            $html .= '<select id="add-on-select" name="' . $criteria_selector['name'] . '" class="add-on-select selectpicker">';
            foreach ($criteria_selector['criterias'] as $criteria_value => $criteria) {
                $html .= '<option value="' . $criteria_value . '" ' . $criteria['selected'] . '>' . $criteria['html_value'] . '</option>';
            }
            $html .= '</select>';
        }

        $html .= '
            <input name="' . $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) . '"
                    id="' . $hp->purify($id, CODENDI_PURIFIER_CONVERT_HTML) . '"
                    data-format="' . $format . '"
                    type="text"
                    value="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '"
                    data-test="' . $hp->purify($data_test, CODENDI_PURIFIER_CONVERT_HTML) . '">
            </input>
            <span class="add-on add-on-calendar">
                <i class="fas fa-calendar-alt" data-time-icon="fa-clock-o" data-date-icon="fa-calendar"></i>
            </span>
        </div>';

        return $html;
    }

    public function warning_for_services_which_configuration_is_not_inherited($group_id, $service_top_tab) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $pm      = ProjectManager::instance();
        $project = $pm->getProject($group_id);
        if ($project->isTemplate()) {
            switch ($service_top_tab) {
                case 'admin':
                case 'forum':
                case 'docman':
                case 'svn':
                case 'file':
                case 'tracker':
                case 'wiki':
                case 'salome':
                    break;
                default:
                    $this->addFeedback('warning', $GLOBALS['Language']->getText('global', 'service_conf_not_inherited'));
                    break;
            }
        }
    }

    public function pv_header(\Tuleap\Layout\HeaderConfiguration|array $params) // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params instanceof \Tuleap\Layout\HeaderConfiguration) {
            $params = $params->flatten();
        }
        if (! $this->is_rendered_through_service && isset($GLOBALS['group_id']) && $GLOBALS['group_id']) {
            if (isset($params['toptab'])) {
                $this->warning_for_services_which_configuration_is_not_inherited($GLOBALS['group_id'], $params['toptab']);
            }
        }
        $hp        = Codendi_HTMLPurifier::instance();
        $pagetitle = ($params['title'] ? $params['title'] . ' - ' : '') . ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME);
        echo '<!DOCTYPE html>' . "\n";
        echo '<html lang="' . $GLOBALS['Language']->getText('conf', 'language_code') . '">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title>' . $hp->purify($pagetitle) . '</title>
                    <link rel="SHORTCUT ICON" href="' . $this->imgroot . 'favicon.ico' . '">';
        $this->displayJavascriptElements($params);
        $this->displayStylesheetElements();
        $this->displaySyndicationElements();
        echo '</head>';

        $current_user = UserManager::instance()->getCurrentUser();
        $user_locale  = $current_user->getLocale();
        echo '
<body class="bg_help" data-user-locale="' . $hp->purify($user_locale) . '">
';
        if (isset($params['pv']) && $params['pv'] < 2) {
            if (isset($params['title']) && $params['title']) {
                $title = $params['title'] . ' - ' . format_date(
                    $GLOBALS['Language']->getText('system', 'datefmt'),
                    time()
                );
                echo '<h2>' . $hp->purify($title) . '</h2>
                <hr />';
            }
        }
    }

    public function pv_footer(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        echo $this->displayFooterJavascriptElements();
        echo "\n</body></html>";
    }

    /**
     * This method generates header for pages embbeded in overlay like LiteWindow
     */
    public function overlay_header() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $current_user        = UserManager::instance()->getCurrentUser();
        $theme_variant       = new ThemeVariant();
        $color               = $theme_variant->getVariantColorForUser($current_user);
        $theme_color_variant = ThemeVariant::convertToFlamingParrotVariant($color);
        $user_locale         = $current_user->getLocale();

        $this->includeCalendarScripts();
        echo '<!DOCTYPE html>
              <html>
              <head>
                 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
        echo $this->displayJavascriptElements([]);
        $this->displayStylesheetElements();
        echo $this->displaySyndicationElements();
        echo '</head>';
        echo '<body class="' . Codendi_HTMLPurifier::instance()->purify($theme_color_variant) . '" leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" marginwidth="0" marginheight="0" data-user-locale="' . Codendi_HTMLPurifier::instance()->purify($user_locale) . '">';
        echo '<div class="main_body_row"><div class="contenttable">';
        echo $this->getNotificationPlaceholder();
    }

    public function getNotificationPlaceholder()
    {
        return '<div id="notification-placeholder"></div>';
    }

    public function feedback($feedback)
    {
        return '';
    }

    /**
     * This method generates footer for pages embbeded in overlay like LiteWindow
     */
    public function overlay_footer() // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        echo '         </div>
                     </div>
                 ' . $this->displayFooterJavascriptElements() . '
                 </body>
             </html>';
    }

    /**
     * Return the background color (classname) for priority
     *
     * @param $index the index (id) of the priority : 1
     * @return string 'priora'
     */
    public function getPriorityColor($index)
    {
        if (isset($this->bgpri[$index])) {
            return $this->bgpri[$index];
        } else {
            return "";
        }
    }

    /**
     * Wrapper for event manager
     *
     * @return EventManager
     */
    protected function getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * Create a new Javascript variable in page flow (footer) with given object
     *
     * object is json encoded beforehand
     *
     * @param String $js_variable_name
     * @param Mixed $object
     */
    public function appendJsonEncodedVariable($js_variable_name, $object)
    {
        $this->includeFooterJavascriptSnippet(
            $js_variable_name . ' = ' . json_encode($object) . ';'
        );
    }
}
