<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

/**
 *
 * Extends the basic Response class to add HTML functions for displaying all site dependent HTML, while allowing extendibility/overriding by themes via the Theme class.
 *
 * Geoffrey Herteg, August 29, 2000
 * @deprecated
 *
 */
abstract class Layout extends Tuleap\Layout\BaseLayout
{
    /**
     * Html purifier
     */
    protected $purifier;


    private $javascript;

    private $version;

    //Define all the icons for this theme
    var $icons = array('Summary' => 'ic/anvil24.png',
        'Homepage' => 'ic/home.png',
        'Forums' => 'ic/notes.png',
        'Bugs' => 'ic/bug.png',
        'Support' => 'ic/support.png',
        'Patches' => 'ic/patch.png',
        'Lists' => 'ic/mail.png',
        'Tasks' => 'ic/index.png',
        'Docs' => 'ic/docman.png',
        'News' => 'ic/news.png',
        'CVS' => 'ic/convert.png',
        'Files' => 'ic/save.png',
        'Trackers' => 'ic/tracker20w.png'
        );

    const INCLUDE_FAT_COMBINED = 'include_fat_combined';

    /**
     * Background for priorities
     */
    private $bgpri = array();

    var $feeds;

    /**
     * Store custom css added on the fly
     *
     * @var Array of path to CSS files
     */
    protected $stylesheets = array();

    /**
     * Constuctor
     * @param string $root the root of the theme : '/themes/Tuleap/'
     */
    public function __construct($root) {
        // Constructor for parent class...
        parent::__construct($root);

        $this->imgroot = $root . '/images/';

        $this->feeds       = array();
        $this->javascript  = array();

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

    function iframe($url, $html_options = array()) {
        $url_purified = $this->purifier->purify($this->uri_sanitizer->sanitizeForHTMLAttribute($url));

        $html = '<div class="iframe_showonly"><a href="'. $url_purified .'" title="'.$GLOBALS['Language']->getText('global', 'show_frame') .'">'.$GLOBALS['Language']->getText('global', 'show_frame').' '. $this->getImage('ic/plain-arrow-down.png') .'</a></div>';
        $args = ' src="'. $url_purified .'" ';
        foreach($html_options as $key => $value) {
            $args .= ' '. $key .'="'. $value .'" ';
        }
        $html .= '<iframe '. $args .'></iframe>';
        echo $html;
    }

    function selectRank($id, $rank, $items, $html_options) {
        $html = '';
        $html .= '<select ';
        foreach($html_options as $key => $value) {
            $html .= $key .'="'. $value .'"';
        }
        $html .= '>';
        $html .= '<option value="beginning">'. $GLOBALS['Language']->getText('global', 'at_the_beginning') .'</option>';
        $html .= '<option value="end">'. $GLOBALS['Language']->getText('global', 'at_the_end') .'</option>';
        list($options, $optgroups) = $this->selectRank_optgroup($id, $items);
        $html .= $options . $optgroups;
        $html .= '</select>';
        return $html;
    }

    protected function selectRank_optgroup($id, $items, $prefix = '', $value_prefix = '') {
        $html      = '';
        $optgroups = '';
        $purifier  = Codendi_HTMLPurifier::instance();
        foreach($items as $i => $item) {
            // don't include the item itself
            if ($item['id'] != $id) {

                // need an optgroup ?
                if (isset($item['subitems'])) {
                    $optgroups .= '<optgroup label="'. $purifier->purify($prefix . $item['name']) .'">';

                    $selected = '';
                    if ( count($item['subitems']) ) {
                        // look if our item is the first subitem
                        // if it is the case then select 'At the beginning of <parent>'
                        reset($item['subitems']);
                        list(,$subitem) = each($item['subitems']);
                        if ($subitem['id'] == $id) {
                            $selected = 'selected="selected"';
                        }
                    }
                    $optgroups .= '<option value="'. $purifier->purify($item['id']) . ':' . 'beginning' .'" '. $selected .'>'. 'At the beginning of '. $purifier->purify($prefix . $item['name']) .'</option>';
                    list($o, $g) = $this->selectRank_optgroup($id, $item['subitems'], $prefix . $item['name'] .'::', $item['id'] . ':');
                    $optgroups .= $o;
                    $optgroups .= '</optgroup>';
                    $optgroups .= $g;
                }

                // The rank is the next one.
                // TODO: use the next rank instead?
                $value = $item['rank']+1;

                // select the element if the item is just after id
                $selected = '';
                if (isset($items[$i + 1]) && $items[$i + 1]['id'] == $id) {
                    $selected = 'selected="selected"';
                }
                $html .= '<option value="'. $purifier->purify($value_prefix . $value) .'" '. $selected .'>';
                $html .= $GLOBALS['Language']->getText('global', 'after', $purifier->purify($prefix . $item['name']));
                $html .= '</option>';
            }
        }
        return array($html, $optgroups);
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
    function includeJavascriptFile($file) {
        $this->javascript[] = array('file' => $file);
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
    function includeJavascriptSnippet($snippet) {
        $this->javascript[] = array('snippet' => $snippet);
        return $this;
    }

    protected function includeJavascriptPolyfills() {
    }

    /**
     * @return PFUser
     */
    protected function getUser() {
        return UserManager::instance()->getCurrentUser();
    }

    public function addUserAutocompleteOn($element_id, $multiple=false) {
        $jsbool = $multiple ? "true" : "false";
        $js = "new UserAutoCompleter('".$element_id."', '".util_get_dir_image_theme()."', ".$jsbool.");";
        $this->includeFooterJavascriptSnippet($js);
    }

    function includeCalendarScripts() {
        $this->includeJavascriptSnippet("var useLanguage = '". substr($this->getUser()->getLocale(), 0, 2) ."';");
        $this->includeJavascriptFile("/scripts/datepicker/datepicker.js");
        return $this;
    }

    function addFeed($title, $href) {
        $this->feeds[] = array('title' => $title, 'href' => $href);
    }

    function _getFeedback() {
        $feedback = '';
        if (trim($GLOBALS['feedback']) !== '') {
            $feedback = '<H3><span class="feedback">'.$GLOBALS['feedback'].'</span></H3>';
        }
        return $feedback;
    }

    public function displayStaticWidget(Widget_Static $widget)
    {
        $owner_id            = null;
        $owner_type          = null;

        $purifier   = Codendi_HTMLPurifier::instance();
        $element_id = 'widget_'. $widget->id .'-'. $widget->getInstanceId();

        echo '<div class="widget" id="'. $element_id .'">';
        echo '<div class="widget_titlebar">';
        echo '<div class="widget_titlebar_title">'. $purifier->purify($widget->getTitle()) .'</div>';

        if ($widget->hasRss()) {
            echo '<div class="widget_titlebar_rss" title="'. $GLOBALS['Language']->getText('widget', 'rss_title') .'"><a href="'.$widget->getRssUrl($owner_id, $owner_type).'" class="icon-rss"></a></div>';
        }
        echo '</div>';
        echo '<div class="widget_content">';

        if ($widget->isAjax()) {
            echo '<div id="'. $element_id .'-ajax">';
            echo '</div>';
        } else {
            echo $widget->getContent();
        }
        echo '</div>';
        if ($widget->isAjax()) {
            echo '<script type="text/javascript">'."
            document.observe('dom:loaded', function () {
                $('$element_id-ajax').update('<div style=\"text-align:center\">". $this->getImage('ic/spinner.gif') ."</div>');
                new Ajax.Updater('$element_id-ajax',
                                 '". $widget->getAjaxUrl($owner_id, $owner_type, null) ."',
                                 {
                                     onComplete: function() {
                                        codendi.Tooltip.load('$element_id-ajax');
                                        codendi.Toggler.init($('$element_id-ajax'));
                                     }
                                 }
                );
            });
            </script>";
        }
        echo '</div>';
    }

    public function getDropdownPanel($id, $content) {
        $html = '';
        $html .= '<table id="'. $id .'" class="dropdown_panel"><tr><td>';
        $html .= $content;
        $html .= '</td></tr></table>';
        return $html;
    }

    /**
     * Box Top, equivalent to html_box1_top()
     *
     * @see Widget_Static
     * @deprecated You should consider using Widget_Static instead
     */
    function box1_top($title,$echoout=1,$bgcolor='',$cols=2){
            $return = '<TABLE class="boxtable" cellspacing="1" cellpadding="5" width="100%" border="0">
                        <TR class="boxtitle" align="center">
                                <TD colspan="'.$cols.'"><SPAN class=titlebar>'.$title.'</SPAN></TD>
                        </TR>
                        <TR class="boxitem">
                                <TD colspan="'.$cols.'">';
            if ($echoout) {
                    print $return;
            } else {
                    return $return;
            }
    }

    /**
     * Box Middle, equivalent to html_box1_middle()
     *
     * @see Widget_Static
     * @deprecated You should consider using Widget_Static instead
     */
    function box1_middle($title,$bgcolor='',$cols=2) {
            return '
                                </TD>
                        </TR>

                        <TR class="boxtitle">
                                <TD colspan="'.$cols.'"><SPAN class=titlebar>'.$title.'</SPAN></TD>
                        </TR>
                        <TR class="boxitem">
                                <TD colspan="'.$cols.'">';
    }

    /**
     * Box Bottom, equivalent to html_box1_bottom()
     *
     * @see Widget_Static
     * @deprecated You should consider using Widget_Static instead
     */
    function box1_bottom($echoout=1) {
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

    /**
     * This is a generic header method shared by header() and pv_header()
     */
    private function generic_header($params) {
        if (!$this->is_rendered_through_service && isset($GLOBALS['group_id']) && $GLOBALS['group_id']) {
            $pm = ProjectManager::instance();
            $project = $pm->getProject($GLOBALS['group_id']);
            if (isset($params['toptab'])) {
                $this->warning_for_services_which_configuration_is_not_inherited($GLOBALS['group_id'], $params['toptab']);
            }
        }
        echo '<!DOCTYPE html>'."\n";
        echo '<html lang="'. $GLOBALS['Language']->getText('conf', 'language_code') .'">
                <head>
                    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
                    <title>'. ($params['title'] ? $params['title'] . ' - ' : '') . $GLOBALS['sys_name'] .'</title>
                    <link rel="SHORTCUT ICON" href="'. $this->imgroot . 'favicon.ico' .'">';
        echo $this->displayJavascriptElements($params);
        echo $this->displayStylesheetElements($params);
        echo $this->displaySyndicationElements();
        echo '</head>';
    }
    protected $colorpicker_palettes = array(
        'small'    => '[["#EEEEEE", "#FFFFFF", "#F9F7ED", "#FFFF88", "#CDEB8B", "#C3D9FF", "#36393D"],
                        ["#FF1A00", "#CC0000", "#FF7400", "#008C00", "#006E2E", "#4096EE", "#FF0084"],
                        ["#B02B2C", "#D15600", "#73880A", "#6BBA70", "#3F4C6B", "#356AA0", "#D01F3C"],
                        ["#FEF5A8", "#C5F19A", "#FFD8A0", "#F5DDB7", "#B9D0E8", "#D6BFD4", "#F79494"]]',

        'vivid'    => '[["#ffffff", "#0000ff", "#004dff", "#0077ff", "#00a0ff", "#00c4ff", "#00e3ff", "#4dfeff", "#00ffdf", "#00ffb2", "#00ff8c", "#00ff79", "#00ff31", "#00ff00", "#17ff00", "#70ff00", "#a0ff00", "#c8ff00", "#ebff00", "#ffff00", "#ffdc00", "#ffb000", "#ff8400", "#ff5d00", "#ff3600", "#ff0000", "#ff0034", "#ff006b", "#ff0091", "#ff00aa", "#ff00d0", "#ff00ff", "#cc19ff", "#9500f9", "#7a00ff", "#6100ff", "#4700ff", "#0000ff"],
                        ["#e2e2e2", "#0000ec", "#0049f3", "#0070f3", "#0091e9", "#00b8f3", "#00d0ef", "#00f2f6", "#00efd4", "#00eda8", "#00e680", "#00ee73", "#00ee2d", "#00ff00", "#16ee00", "#68eb00", "#94e900", "#b8eb00", "#dced00", "#ffeb01", "#f6c800", "#f09e00", "#ec7700", "#ed5300", "#ed2e00", "#df1b00", "#ef072b", "#e90068", "#e60084", "#df1d96", "#ec00d9", "#e020e4", "#bb16ec", "#8a00e6", "#7000ea", "#6100ff", "#4700ff", "#0000ff"],
                        ["#c6c6c6", "#0000d7", "#0041dd", "#0062d8", "#0081d3", "#009fdb", "#00b4da", "#00cbdc", "#00cdbe", "#00d69a", "#00cc74", "#00d86a", "#00da29", "#00d300", "#15da00", "#5ed200", "#89d500", "#aedc00", "#cee000", "#ffd202", "#edb100", "#e08900", "#d36700", "#d74601", "#d32201", "#d50000", "#d81633", "#c70063", "#cf0077", "#d4008d", "#ce00ae", "#d100d1", "#a913d6", "#7e00d1", "#6300cd", "#5414d9", "#4700ff", "#0000d7"],
                        ["#aaaaaa", "#0000b3", "#0037c6", "#0056c1", "#006fbf", "#0087c3", "#009bc6", "#00aec8", "#00b2ab", "#00bf8d", "#00bb6c", "#00c463", "#00c324", "#00c100", "#14c300", "#54b900", "#7fc300", "#9ac200", "#bbc800", "#ffc000", "#e29500", "#ce7100", "#c85a00", "#c03901", "#bd1502", "#c60404", "#b8182a", "#b80366", "#b8006a", "#be007e", "#b5009c", "#ba00ba", "#9310ba", "#7000ba", "#5812b7", "#4c12c3", "#3e00e7", "#0000b3"],
                        ["#8d8d8d", "#0000ab", "#002eae", "#004bab", "#015bbe", "#0071ae", "#0082b2", "#008eb3", "#009698", "#00a57e", "#00a965", "#00a959", "#00aa1e", "#00af00", "#13aa00", "#4aa000", "#75b100", "#89ad00", "#a4ae00", "#ec9f00", "#dd8900", "#c46200", "#b74e00", "#b53302", "#b31103", "#b00400", "#a11726", "#a80b54", "#a6005f", "#a5006e", "#9d008c", "#a600a6", "#7f0da4", "#6100a0", "#4e00a3", "#420fa8", "#3300c6", "#0000ab"],
                        ["#717171", "#0000a0", "#002593", "#003e93", "#01449e", "#005996", "#006a9e", "#006d9d", "#007d87", "#008b6f", "#009a5e", "#009351", "#008f18", "#009f00", "#139500", "#448e00", "#699b00", "#759300", "#8d9500", "#ca7900", "#b96a00", "#aa5500", "#954000", "#952700", "#9c0d01", "#a50303", "#8a1522", "#98094c", "#980057", "#8d005e", "#88007d", "#920092", "#611681", "#530087", "#4a0099", "#38068e", "#2800a5", "#0000a0"],
                        ["#555555", "#00008e", "#001c7c", "#00327b", "#023a8e", "#004380", "#004f89", "#00538b", "#006577", "#007360", "#008052", "#007847", "#007512", "#008200", "#127b00", "#397300", "#5a8200", "#5e7400", "#757000", "#995100", "#8c4800", "#863a00", "#792e00", "#801f00", "#820a00", "#8f0303", "#76141e", "#81073f", "#810049", "#77004e", "#74006f", "#7c007c", "#52066e", "#43006c", "#3f0082", "#300978", "#240981", "#00008e"],
                        ["#383838", "#00007d", "#001263", "#0f225c", "#00266f", "#002c6b", "#003474", "#003879", "#004b6b", "#005750", "#006642", "#00613e", "#005d0e", "#006800", "#116000", "#306000", "#456a00", "#495900", "#4d4a00", "#663800", "#6e3100", "#652300", "#652100", "#6f1900", "#6e0901", "#700101", "#63141b", "#611032", "#730041", "#670043", "#5d005e", "#600060", "#42045b", "#350054", "#350071", "#280663", "#240563", "#00007d"],
                        ["#1c1c1c", "#0a045b", "#00094c", "#0a194c", "#0a1950", "#0a1950", "#001c61", "#102863", "#002d51", "#003c40", "#004836", "#004c36", "#004d0a", "#004a00", "#0f4c00", "#294c00", "#354d00", "#374200", "#393200", "#482100", "#491300", "#4d1300", "#480f00", "#4d0e00", "#4d0500", "#4e0101", "#4a0a0f", "#4a0222", "#53002e", "#570038", "#4d0045", "#480048", "#34024a", "#31004c", "#2d0556", "#240347", "#1f0346", "#150351"],
                        ["#000000", "#000042", "#000540", "#00103a", "#060d41", "#000544", "#00054f", "#001355", "#00224a", "#00313a", "#00372e", "#003a30", "#003806", "#003200", "#0f3700", "#213700", "#233100", "#2b2d04", "#302302", "#350f00", "#350300", "#350300", "#350300", "#350300", "#350300", "#350300", "#360308", "#370118", "#37001e", "#370024", "#370032", "#370037", "#320040", "#260039", "#200035", "#210233", "#170039", "#000042"]]',

        //See http://www.visibone.com. Copyright (c) 2011 VisiBone
        'visibone-light' => '[["#FFFFFF", "#CCCCCC", "#999999", "#666666", "#333333", "#000000", "#FFCC00", "#FF9900", "#FF6600", "#FF3300", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF"],
                        ["#99CC00", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#CC9900", "#FFCC33", "#FFCC66", "#FF9966", "#FF6633", "#CC3300", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#CC0033"],
                        ["#CCFF00", "#CCFF33", "#333300", "#666600", "#999900", "#CCCC00", "#FFFF00", "#CC9933", "#CC6633", "#330000", "#660000", "#990000", "#CC0000", "#FF0000", "#FF3366", "#FF0033"],
                        ["#99FF00", "#CCFF66", "#99CC33", "#666633", "#999933", "#CCCC33", "#FFFF33", "#996600", "#993300", "#663333", "#993333", "#CC3333", "#FF3333", "#CC3366", "#FF6699", "#FF0066"],
                        ["#66FF00", "#99FF66", "#66CC33", "#669900", "#999966", "#CCCC66", "#FFFF66", "#996633", "#663300", "#996666", "#CC6666", "#FF6666", "#990033", "#CC3399", "#FF66CC", "#FF0099"],
                        ["#33FF00", "#66FF33", "#339900", "#66CC00", "#99FF33", "#CCCC99", "#FFFF99", "#CC9966", "#CC6600", "#CC9999", "#FF9999", "#FF3399", "#CC0066", "#990066", "#FF33CC", "#FF00CC"],
                        ["#00CC00", "#33CC00", "#336600", "#669933", "#99CC66", "#CCFF99", "#FFFFCC", "#FFCC99", "#FF9933", "#FFCCCC", "#FF99CC", "#CC6699", "#993366", "#660033", "#CC0099", "#330033"],
                        ["#33CC33", "#66CC66", "#00FF00", "#33FF33", "#66FF66", "#99FF99", "#CCFFCC", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#CC99CC", "#996699", "#993399", "#990099", "#663366", "#660066"],
                        ["#006600", "#336633", "#009900", "#339933", "#669966", "#99CC99", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFCCFF", "#FF99FF", "#FF66FF", "#FF33FF", "#FF00FF", "#CC66CC", "#CC33CC"],
                        ["#003300", "#00CC33", "#006633", "#339966", "#66CC99", "#99FFCC", "#CCFFFF", "#3399FF", "#99CCFF", "#CCCCFF", "#CC99FF", "#9966CC", "#663399", "#330066", "#9900CC", "#CC00CC"],
                        ["#00FF33", "#33FF66", "#009933", "#00CC66", "#33FF99", "#99FFFF", "#99CCCC", "#0066CC", "#6699CC", "#9999FF", "#9999CC", "#9933FF", "#6600CC", "#660099", "#CC33FF", "#CC00FF"],
                        ["#00FF66", "#66FF99", "#33CC66", "#009966", "#66FFFF", "#66CCCC", "#669999", "#003366", "#336699", "#6666FF", "#6666CC", "#666699", "#330099", "#9933CC", "#CC66FF", "#9900FF"],
                        ["#00FF99", "#66FFCC", "#33CC99", "#33FFFF", "#33CCCC", "#339999", "#336666", "#006699", "#003399", "#3333FF", "#3333CC", "#333399", "#333366", "#6633CC", "#9966FF", "#6600FF"],
                        ["#00FFCC", "#33FFCC", "#00FFFF", "#00CCCC", "#009999", "#006666", "#003333", "#3399CC", "#3366CC", "#0000FF", "#0000CC", "#000099", "#000066", "#000033", "#6633FF", "#3300FF"],
                        ["#00CC99", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#0099CC", "#33CCFF", "#66CCFF", "#6699FF", "#3366FF", "#0033CC", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#3300CC"],
                        ["#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#00CCFF", "#0099FF", "#0066FF", "#0033FF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF", "#FFFFFF"]]',

        'visibone-dark' => '[["#FFFFFF", "#CCCCCC", "#999999", "#666666", "#333333", "#000000", "#FFCC00", "#FF9900", "#FF6600", "#FF3300", "#000000", "#000000", "#000000", "#000000", "#000000", "#000000"],
                        ["#99CC00", "#000000", "#000000", "#000000", "#000000", "#CC9900", "#FFCC33", "#FFCC66", "#FF9966", "#FF6633", "#CC3300", "#000000", "#000000", "#000000", "#000000", "#CC0033"],
                        ["#CCFF00", "#CCFF33", "#333300", "#666600", "#999900", "#CCCC00", "#FFFF00", "#CC9933", "#CC6633", "#330000", "#660000", "#990000", "#CC0000", "#FF0000", "#FF3366", "#FF0033"],
                        ["#99FF00", "#CCFF66", "#99CC33", "#666633", "#999933", "#CCCC33", "#FFFF33", "#996600", "#993300", "#663333", "#993333", "#CC3333", "#FF3333", "#CC3366", "#FF6699", "#FF0066"],
                        ["#66FF00", "#99FF66", "#66CC33", "#669900", "#999966", "#CCCC66", "#FFFF66", "#996633", "#663300", "#996666", "#CC6666", "#FF6666", "#990033", "#CC3399", "#FF66CC", "#FF0099"],
                        ["#33FF00", "#66FF33", "#339900", "#66CC00", "#99FF33", "#CCCC99", "#FFFF99", "#CC9966", "#CC6600", "#CC9999", "#FF9999", "#FF3399", "#CC0066", "#990066", "#FF33CC", "#FF00CC"],
                        ["#00CC00", "#33CC00", "#336600", "#669933", "#99CC66", "#CCFF99", "#FFFFCC", "#FFCC99", "#FF9933", "#FFCCCC", "#FF99CC", "#CC6699", "#993366", "#660033", "#CC0099", "#330033"],
                        ["#33CC33", "#66CC66", "#00FF00", "#33FF33", "#66FF66", "#99FF99", "#CCFFCC", "#000000", "#000000", "#000000", "#CC99CC", "#996699", "#993399", "#990099", "#663366", "#660066"],
                        ["#006600", "#336633", "#009900", "#339933", "#669966", "#99CC99", "#000000", "#000000", "#000000", "#FFCCFF", "#FF99FF", "#FF66FF", "#FF33FF", "#FF00FF", "#CC66CC", "#CC33CC"],
                        ["#003300", "#00CC33", "#006633", "#339966", "#66CC99", "#99FFCC", "#CCFFFF", "#3399FF", "#99CCFF", "#CCCCFF", "#CC99FF", "#9966CC", "#663399", "#330066", "#9900CC", "#CC00CC"],
                        ["#00FF33", "#33FF66", "#009933", "#00CC66", "#33FF99", "#99FFFF", "#99CCCC", "#0066CC", "#6699CC", "#9999FF", "#9999CC", "#9933FF", "#6600CC", "#660099", "#CC33FF", "#CC00FF"],
                        ["#00FF66", "#66FF99", "#33CC66", "#009966", "#66FFFF", "#66CCCC", "#669999", "#003366", "#336699", "#6666FF", "#6666CC", "#666699", "#330099", "#9933CC", "#CC66FF", "#9900FF"],
                        ["#00FF99", "#66FFCC", "#33CC99", "#33FFFF", "#33CCCC", "#339999", "#336666", "#006699", "#003399", "#3333FF", "#3333CC", "#333399", "#333366", "#6633CC", "#9966FF", "#6600FF"],
                        ["#00FFCC", "#33FFCC", "#00FFFF", "#00CCCC", "#009999", "#006666", "#003333", "#3399CC", "#3366CC", "#0000FF", "#0000CC", "#000099", "#000066", "#000033", "#6633FF", "#3300FF"],
                        ["#00CC99", "#000000", "#000000", "#000000", "#000000", "#0099CC", "#33CCFF", "#66CCFF", "#6699FF", "#3366FF", "#0033CC", "#000000", "#000000", "#000000", "#000000", "#3300CC"],
                        ["#000000", "#000000", "#000000", "#000000", "#000000", "#000000", "#00CCFF", "#0099FF", "#0066FF", "#0033FF", "#000000", "#000000", "#000000", "#000000", "#000000", "#000000"]]',

    );

    /**
     * Customize the palette used for the colorpicker.
     *
     * @example return 'codendi.colorpicker.theme = '. $this->colorpicker_palettes['vivid'] .';';
     *
     * @return string javascript
     */
    protected function changeColorpickerPalette() {
        return 'codendi.colorpicker_theme = '. $this->colorpicker_palettes['visibone-dark'] .';';
    }

    private function shouldIncludeFatCombined(array $params) {
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
            echo $this->include_asset->getHTMLSnippet('tuleap.js');
        } else {
            $this->includeSubsetOfCombined();
        }

        $ckeditor_path = '/scripts/ckeditor-4.3.2/';
        echo '<script type="text/javascript">window.CKEDITOR_BASEPATH = "'. $ckeditor_path .'";</script>
              <script type="text/javascript" src="'. $ckeditor_path .'/ckeditor.js"></script>'."\n";

        //Javascript i18n
        echo '<script type="text/javascript">'."\n";
        include $GLOBALS['Language']->getContent('scripts/locale');
        echo '
        codendi.imgroot = \''. $this->imgroot .'\';
        '. $this->changeColorpickerPalette() .'
        </script>'."\n";

        if (ForgeConfig::get('DEBUG_MODE') && (ForgeConfig::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')) ) {
            echo '<script type="text/javascript" src="/scripts/codendi/debug_reserved_names.js"></script>'."\n";
        }
        $this->includeJavascriptPolyfills();

        $em = EventManager::instance();
        $em->processEvent("javascript_file", null);

        foreach ($this->javascript as $js) {
            if (isset($js['file'])) {
                echo '<script type="text/javascript" src="'. $js['file'] .'"></script>'."\n";
            } else {
                if (isset($js['snippet'])) {
                    echo '<script type="text/javascript">'."\n";
                    echo '//<!--'."\n";
                    echo $js['snippet']."\n";
                    echo '//-->'."\n";
                    echo '</script>'."\n";
                }
            }
        }
        echo '<script type="text/javascript">'."\n";
        $em->processEvent(Event::JAVASCRIPT, null);
        echo '
        </script>';
    }

    protected function includeSubsetOfCombined() {
        echo $this->include_asset->getHTMLSnippet('tuleap_subset.js');
    }

    /**
     * Display the Javascript code to be included at the end of the page.
     * Snippet and files are included one after another in the order of call
     * of includeFooterJavascriptFile & includeFooterJavascriptSnippet methods.
     *
     * @see includeFooterJavascriptFile
     * @see includeFooterJavascriptSnippet
     */
    function displayFooterJavascriptElements() {
        foreach ($this->javascript_in_footer as $js) {
            if (isset($js['file'])) {
                echo '<script type="text/javascript" src="'. $js['file'] .'"></script>'."\n";
            } else {
                echo '<script type="text/javascript">'."\n";
                echo '//<!--'."\n";
                echo $js['snippet']."\n";
                echo '//-->'."\n";
                echo '</script>'."\n";
            }
        }
        $em = EventManager::instance();
        echo '<script type="text/javascript">'."\n";
        $em->processEvent(Event::JAVASCRIPT_FOOTER, null);
        echo $this->getFooterSiteJs();
        echo '
        </script>';
    }

    /**
     * Add a stylesheet to be include in headers
     *
     * @param String $file Path to CSS file
     */
    public function addStylesheet($file) {
        $this->stylesheets[] = $file;
    }

    /**
     * Get all stylesheets defined previously
     *
     * @return Array of CSS file path
     */
    public function getAllStyleSheets() {
        return $this->stylesheets;
    }

    function getStylesheetTheme($css) {
        if ($GLOBALS['sys_is_theme_custom']) {
            $path = '/custom/'.$GLOBALS['sys_user_theme'].'/css/'.$css;
        } else {
            $path = '/themes/'.$GLOBALS['sys_user_theme'].'/css/'.$css;
        }
        return $path;
    }

    /**
     * Display all the stylesheets for the current page
     */
    public function displayStylesheetElements($params) {
        $this->displayCommonStylesheetElements($params);

        // Stylesheet external files
        if(isset($params['stylesheet']) && is_array($params['stylesheet'])) {
            foreach($params['stylesheet'] as $css) {
                print '<link rel="stylesheet" type="text/css" href="'.$css.'" />';
            }
        }

        // Display custom css
        foreach ($this->getAllStylesheets() as $css) {
            echo '<link rel="stylesheet" type="text/css" href="'.$css.'" />';
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

    protected function displayCommonStylesheetElements($params) {
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-tuleap-22d39b3.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-tuleap-responsive-22d39b3.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/animate.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/style.css" />';
        $this->displayFontAwesomeStylesheetElements();
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/print.css" media="print" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('style.css') .'" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('print.css') .'" media="print" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-select/bootstrap-select.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/select2/select2.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/vendor/at/css/atwho.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" />';
    }

    protected function displayFontAwesomeStylesheetElements() {
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/font-awesome.css" />';
    }

    /**
     * Display all the syndication feeds (rss for now) for the current page
     */
    public function displaySyndicationElements() {
        $hp =& Codendi_HTMLPurifier::instance();

        //Basic feeds
        echo $this->getRssFeed(
            $hp->purify($GLOBALS['sys_name']. ' - ' .$GLOBALS['Language']->getText('include_layout','latest_news_rss'), CODENDI_PURIFIER_CONVERT_HTML),
            '/export/rss_sfnews.php'
        );
        echo $this->getRssFeed(
            $hp->purify($GLOBALS['sys_name']. ' - ' .$GLOBALS['Language']->getText('include_layout','newest_projects_rss'), CODENDI_PURIFIER_CONVERT_HTML),
            '/export/rss_sfprojects.php?type=rss&option=newest'
        );

        // If in a project page, add a project news feed
        if (isset($GLOBALS['group_id']) && $GLOBALS['group_id']) {
            $pm = ProjectManager::instance();
            $project = $pm->getProject($GLOBALS['group_id']);
            echo $this->getRssFeed(
                $hp->purify($project->getPublicName().' - '.$GLOBALS['Language']->getText('include_layout','latest_news_rss'), CODENDI_PURIFIER_CONVERT_HTML),
                '/export/rss_sfnews.php?group_id='.$GLOBALS['group_id']
            );
        }

        //Add additionnal feeds
        foreach($this->feeds as $feed) {
            echo $this->getRssFeed(
                $hp->purify($feed['title'], CODENDI_PURIFIER_CONVERT_HTML),
                $feed['href']
            );
        }
    }

    /**
     * @param string $title the title of the feed
     * @param string $href the href of the feed
     * @return string the <link> tag for the feed
     */
    function getRssFeed($title, $href) {
        return '<link rel="alternate" title="'. $title .'" href="'. $href .'" type="application/rss+xml" />';
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
    function getDatePicker($id, $name, $value, $size = 10, $maxlength = 10) {
        $hp = Codendi_HTMLPurifier::instance();
        return '<span style="white-space:nowrap;"><input type="text"
                       class="highlight-days-67 format-y-m-d divider-dash no-transparency"
                       id="'.  $hp->purify($id, CODENDI_PURIFIER_CONVERT_HTML)  .'"
                       name="'. $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) .'"
                       size="'. $hp->purify($size, CODENDI_PURIFIER_CONVERT_HTML) .'"
                       maxlength="'. $hp->purify($maxlength, CODENDI_PURIFIER_CONVERT_HTML) .'"
                       value="'. $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) .'"></span>';
    }

    /**
     * Helper for the calendar picker. It returns the html snippet which will
     * enable user to specify a date with the help of little dhtml
     *
     * @param string  $id the id of the input element
     * @param string  $name the name of the input element
     * @param array   $critria_selector list of extra criterias to be listed in a prepended select
     * @param array   $classes extra css classes if needed
     * @param boolean $is_time_displayed to know if the time need to be displayed
     *
     * @return string The calendar picker
     */
    public function getBootstrapDatePicker(
        $id,
        $name,
        $value,
        array $criteria_selector,
        array $classes,
        $is_time_displayed
    ) {
        $hp = Codendi_HTMLPurifier::instance();
        $html  = '';
        $html .= '<div class="input-prepend dropdown input-append date ' . implode(' ', $classes) . '">';

        if(count($criteria_selector) > 0) {
            $html .= '<select id="add-on-select" name="' . $criteria_selector['name'] . '" class="add-on add-on-select selectpicker">';
            foreach($criteria_selector['criterias'] as $criteria_value => $criteria) {
                $html .= '<option value="' . $criteria_value . '" ' . $criteria['selected'] . '>' . $criteria['html_value'] . '</option>';
            }

            $html .= '</select>';
        }

        $format = "yyyy-MM-dd";
        $span_class = 'tuleap_field_date';

        if ($is_time_displayed) {
            $format = "yyyy-MM-dd hh:mm";
            $span_class = 'tuleap_field_datetime';
        }

        $html .= '
            <span class="'.$span_class.'">
                <input name="'. $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) .'"
                       id="'. $hp->purify($id, CODENDI_PURIFIER_CONVERT_HTML) .'"
                       data-format="'.$format.'"
                       type="text"
                       value="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '">
                </input>
                <span class="add-on add-on-calendar">
                  <i class="icon-calendar" data-time-icon="icon-time" data-date-icon="icon-calendar"></i>
                </span>
            </span>
        </div>';

        return $html;
    }

    function warning_for_services_which_configuration_is_not_inherited($group_id, $service_top_tab) {
        $pm = ProjectManager::instance();
        $project=$pm->getProject($group_id);
        if ($project->isTemplate()) {
            switch($service_top_tab) {
            case 'admin':
            case 'forum':
            case 'docman':
            case 'cvs':
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

    function generic_footer($params) {

        global $Language;

        $version = $this->getVersion();

        echo '<footer class="footer">';
        include($Language->getContent('layout/footer'));
        echo '</footer>';

        if ( ForgeConfig::get('DEBUG_MODE') && (ForgeConfig::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')) ) {
            $this->showDebugInfo();
        }

        echo $this->displayFooterJavascriptElements();
        echo '</body>';
        echo '</html>';
    }

    function pv_header($params) {
        $this->generic_header($params);
        echo '
<body class="bg_help">
';
        if(isset($params['pv']) && $params['pv'] < 2) {
            if (isset($params['title']) && $params['title']) {
                echo '<h2>'.$params['title'].' - '.format_date($GLOBALS['Language']->getText('system', 'datefmt'),time()).'</h2>
                <hr />';
            }
        }
    }

    function pv_footer($params) {
        echo $this->displayFooterJavascriptElements();
        echo "\n</body></html>";
    }

    /**
     * @return string
     */
    protected function getClassnamesForBodyTag($params = array()) {
        $body_class = isset($params['body_class']) ? $params['body_class'] : array();

        if ($this->getUser()->useLabFeatures()) {
            $body_class[] = 'lab-mode';
        }

        return implode(' ', $body_class);
    }

    /**
     * This method generates header for pages embbeded in overlay like LiteWindow
     */
    public function overlay_header() {
        $this->includeCalendarScripts();
        echo '<!DOCTYPE html>
              <html>
              <head>
                 <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
        echo $this->displayJavascriptElements(array());
        echo $this->displayStylesheetElements(array());
        echo $this->displaySyndicationElements();
        echo '    </head>
                     <body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" marginwidth="0" marginheight="0">
                       <div class="main_body_row">
                           <div class="contenttable">';
        echo $this->getNotificationPlaceholder();
    }

    public function getNotificationPlaceholder() {
        return '<div id="notification-placeholder"></div>';
    }

    function feedback($feedback) {
        return '';
    }

    /**
     * This method generates footer for pages embbeded in overlay like LiteWindow
     */
    public function overlay_footer() {
        echo '         </div>
                     </div>
                 '.$this->displayFooterJavascriptElements().'
                 </body>
             </html>';
    }

    function footer(array $params) {
        if (!isset($params['showfeedback']) || $params['showfeedback']) {
            echo $this->_getFeedback();
        }
        ?>
        </div>
        <!-- end content -->
        </tr>
<!-- New row added for the thin black line at the bottom of the array -->
<tr><td background="<? echo util_get_image_theme("black.png"); ?>" colspan="4" align="center"><img src="<? echo util_get_image_theme("clear.png"); ?>" width="2" height="2" alt=" "></td> </tr>
        </table>

                </td>

                <td background="<? echo util_get_image_theme("right_border.png"); ?>" valign="bottom"><img src="<? echo util_get_image_theme("bottom_right_corner.png"); ?>" width="16" height="16" alt=" "></td>
        </tr>

</table>
</div>
<!-- themed page footer -->
<?php
    $this->generic_footer($params);
    }

    function menu_entry($link, $title) {
            print "\t".'<A class="menus" href="'.$link.'">'.$title.'</A> &nbsp;<img src="'.util_get_image_theme("point1.png").'" alt=" " width="7" height="7"><br>';
    }

    protected function getSearchEntries() {
        $em      = EventManager::instance();
        $request = HTTPRequest::instance();

        $type_of_search = $request->get('type_of_search');
        $group_id       = $request->get('group_id');

        $search_entries = array();
        $hidden = array();

        if ($group_id) {
            $hidden[] = array(
                'name'  => 'group_id',
                'value' => $group_id
            );

            if ($request->exist('forum_id')) {
                $search_entries[] = array(
                    'value'    => 'forums',
                    'label'    => $GLOBALS['Language']->getText('include_menu', 'this_forum'),
                    'selected' => true,
                );
                $hidden[] = array(
                    'name'  => 'forum_id',
                    'value' => $this->purifier->purify($request->get('forum_id'))
                );
            }
            if ($request->exist('atid')) {
                $search_entries[] = array(
                    'value'    => 'tracker',
                    'label'    => $GLOBALS['Language']->getText('include_menu', 'this_tracker'),
                    'selected' => true,
                );
                $hidden[] = array(
                    'name'  => 'atid',
                    'value' => $this->purifier->purify($request->get('atid'))
                );
            }
            if (strpos($_SERVER['REQUEST_URI'], '/wiki/') === 0) {
                $search_entries[] = array(
                    'value'    => 'wiki',
                    'label'    => $GLOBALS['Language']->getText('include_menu', 'this_wiki'),
                    'selected' => true,
                );
            }
        }

        if (ForgeConfig::get('sys_use_trove')) {
            $search_entries[] = array(
                'value' => 'soft',
                'label' => $GLOBALS['Language']->getText('include_menu', 'software_proj')
            );
        }

        $search_entries[] = array(
            'value' => 'people',
            'label' => $GLOBALS['Language']->getText('include_menu', 'people')
        );

        $em->processEvent(
            Event::LAYOUT_SEARCH_ENTRY,
            array(
                'type_of_search' => $type_of_search,
                'search_entries' => &$search_entries,
                'hidden_fields'  => &$hidden,
            )
        );

        $search_entries = $this->forceSelectedOption($search_entries);
        $selected_entry = $this->getSelectedOption($search_entries);

        return array($search_entries, $selected_entry, $hidden);
    }

    private function forceSelectedOption(array $search_entries) {
        foreach ($search_entries as $key => $search_entry) {
            if (! isset($search_entry['selected'])) {
                $search_entries[$key]['selected'] = false;
            }
        }

        return $search_entries;
    }

    private function getSelectedOption(array $search_entries) {
        $selected_option = $search_entries[0];

        foreach ($search_entries as $key => $search_entry) {
            if ($search_entry['selected']) {
                return $search_entries[$key];
            }
        }

        return $selected_option;
    }

    public function getSearchBox() {
        $request = HTTPRequest::instance();

        $type_of_search = $request->get('type_of_search');
        $words          = $request->get('words');

        // if there is no search currently, set the default
        $exact = 1;
        if (isset($type_of_search)) {
            $exact = 0;
        }

        list($search_entries, $selected_entry, $hidden_fields) = $this->getSearchEntries();

        $output = '
                <form action="/search/" method="post"><table style="text-align:left;float:right"><tr style="vertical-align:top;"><td>
        ';
        $output .= '<input type="hidden" name="number_of_page_results" value="'.Search_SearchPlugin::RESULTS_PER_QUERY.'">';
        $output .= '<select style="font-size: x-small" name="type_of_search">';
        foreach ($search_entries as $entry) {
            $selected = '';
            if (isset($entry['selected']) && $entry['selected'] == true) {
                $selected = ' selected="selected"';
            }
            $output .= '<option value="'.$entry['value'].'"'.$selected.'>'.$entry['label'].'</option>';
        }
        $output .= '</select>';

        foreach ($hidden_fields as $hidden) {
            $output .= '<input type="hidden" name="'.$hidden['name'].'" value="'.$hidden['value'].'" />';
        }

        $output .= '<input style="font-size:0.8em" type="text" class="input-medium" size="22" name="words" value="' . $this->purifier->purify($words, CODENDI_PURIFIER_CONVERT_HTML) . '" /><br />';
        $output .= '<input type="CHECKBOX" name="exact" value="1"' . ( $exact ? ' CHECKED' : ' UNCHECKED' ) . '><span style="font-size:0.8em">' . $GLOBALS['Language']->getText('include_menu', 'require_all_words') . '</span>';

        $output .= '</td><td>';
        $output .= '<input class="btn" style="font-size:0.8em" type="submit" name="Search" value="' . $GLOBALS['Language']->getText('searchbox', 'search') . '" />';
        $output .= '</td></tr></table></form>';
        return $output;
    }

    /**
     * Echo the search box
     */
    function searchBox() {
        echo "\t<CENTER>\n".$this->getSearchBox()."\t</CENTER>\n";
    }

    /**
     * Return the background color (classname) for priority
     *
     * @param $index the index (id) of the priority : 1
     * @return string 'priora'
     */
    function getPriorityColor($index) {
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
    protected function getEventManager() {
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
    public function appendJsonEncodedVariable($js_variable_name, $object) {
        $this->includeFooterJavascriptSnippet(
            $js_variable_name.' = '.json_encode($object).';'
        );
    }

    protected function getVersion() {
        if ($this->version === null) {
            $this->version = trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));
        }
        return $this->version;
    }
}
