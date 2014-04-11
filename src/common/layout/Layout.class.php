<?php
/*
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2000 (c) The SourceForge Crew
 * http://sourceforge.net
 *
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *
 * Extends the basic Response class to add HTML functions for displaying all site dependent HTML, while allowing extendibility/overriding by themes via the Theme class.
 *
 * Geoffrey Herteg, August 29, 2000
 *
 */
class Layout extends Response {


    /**
     * The root location for the current theme : '/themes/Tuleap/'
     */
    public $root;

    /**
     * The root location for images : '/themes/Tuleap/images/'
     */
    public $imgroot;

    /**
     * Html purifier
     */
    protected $purifier;

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
        'Surveys' => 'ic/survey.png',
        'News' => 'ic/news.png',
        'CVS' => 'ic/convert.png',
        'Files' => 'ic/save.png',
        'Trackers' => 'ic/tracker20w.png'
        );

    const DEFAULT_SERVICE_ICON = 'tuleap-services-angle-double-right';

    /**
     * Background for priorities
     */
    private $bgpri = array();

    var $feeds;
    protected $javascriptFooter;

    protected $breadcrumbs;
    protected $toolbar;

    /**
     * Set to true if HTML object is displayed through a Service
     *
     * @var Boolean
     */
    protected $renderedThroughService = false;

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
        parent::Response();

        $this->feeds       = array();
        $this->javascript  = array();
        $this->breadcrumbs = array();
        $this->toolbar     = array();
        $this->javascriptFooter = array();

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

        $this->root     = $root;
        $this->imgroot  = $root . '/images/';
        $this->purifier = Codendi_HTMLPurifier::instance();
    }

    function getChartColors() {
        return array(
            'lightsalmon',
            'palegreen',
            'paleturquoise',
            'lightyellow',
            'thistle',
            'steelblue1',
            'palevioletred1',
            'palegoldenrod',
            'wheat1',
            'gold',
            'olivedrab1',
            'lightcyan',
            'lightcyan3',
            'lightgoldenrod1',
            'rosybrown',
            'mistyrose',
            'silver',
            'aquamarine',
            'pink1',
            'lemonchiffon3',
            'skyblue',
            'mintcream',
            'lavender',
            'linen',
            'yellowgreen',
            'burlywood',
            'coral',
            'mistyrose3',
            'slategray1',
            'yellow1',
            'darkgreen',
            'darkseagreen',
            'cornflowerblue',
            'royalblue',
            'darkslategray',
            'darkkhaki',
            'gainsboro',
            'lavender',
            'darkturquoise',
            'sandybrown',
            'forestgreen',
            'saddlebrown',
            'peru',
            'darkolivegreen',
            'darksalmon',
            'purple4'
        );
    }

    function getChartBackgroundColor() {
        return "white";
    }

    function getChartMainColor() {
        return "#444444";
    }

    public function getGanttLateBarColor() {
        return 'salmon';
    }

    public function getGanttErrorBarColor() {
        return 'yellow';
    }

    public function getGanttGreenBarColor() {
        return 'darkgreen';
    }

    public function getGanttTodayLineColor() {
        return 'red';
    }

    public function getGanttHeaderColor() {
        return 'gray9';
    }

    public function getGanttBarColor() {
        return 'steelblue1';
    }

    public function getGanttMilestoneColor() {
        return 'orange';
    }

    public function getTextColors() {
        return array(
            'lightsalmon',
            'palegreen',
            'thistle',
            'steelblue1',
            'palevioletred1',
            'gold',
            'lightcyan3',
            'rosybrown',
            'silver',
            'pink1',
            'lemonchiffon3',
            'skyblue',
            'yellowgreen',
            'burlywood',
            'coral',
            'mistyrose3'
        );
    }

    public function getColorCodeFromColorName($color_name, $type='chart') {
        if ($type == 'text') {
            $available_colors = $this->getTextColors();
        } else {
            $available_colors = $this->getChartColors();
        }
        if (in_array($color_name, $available_colors)) {
            $rgb_table = array(
                "aqua"=> array(0,255,255),
                "lime"=> array(0,255,0),
                "teal"=> array(0,128,128),
                "whitesmoke"=>array(245,245,245),
                "gainsboro"=>array(220,220,220),
                "oldlace"=>array(253,245,230),
                "linen"=>array(250,240,230),
                "antiquewhite"=>array(250,235,215),
                "papayawhip"=>array(255,239,213),
                "blanchedalmond"=>array(255,235,205),
                "bisque"=>array(255,228,196),
                "peachpuff"=>array(255,218,185),
                "navajowhite"=>array(255,222,173),
                "moccasin"=>array(255,228,181),
                "cornsilk"=>array(255,248,220),
                "ivory"=>array(255,255,240),
                "lemonchiffon"=>array(255,250,205),
                "seashell"=>array(255,245,238),
                "mintcream"=>array(245,255,250),
                "azure"=>array(240,255,255),
                "aliceblue"=>array(240,248,255),
                "lavender"=>array(230,230,250),
                "lavenderblush"=>array(255,240,245),
                "mistyrose"=>array(255,228,225),
                "white"=>array(255,255,255),
                "black"=>array(0,0,0),
                "darkslategray"=>array(47,79,79),
                "dimgray"=>array(105,105,105),
                "slategray"=>array(112,128,144),
                "lightslategray"=>array(119,136,153),
                "gray"=>array(190,190,190),
                "lightgray"=>array(211,211,211),
                "midnightblue"=>array(25,25,112),
                "navy"=>array(0,0,128),
                "cornflowerblue"=>array(100,149,237),
                "darkslateblue"=>array(72,61,139),
                "slateblue"=>array(106,90,205),
                "mediumslateblue"=>array(123,104,238),
                "lightslateblue"=>array(132,112,255),
                "mediumblue"=>array(0,0,205),
                "royalblue"=>array(65,105,225),
                "blue"=>array(0,0,255),
                "dodgerblue"=>array(30,144,255),
                "deepskyblue"=>array(0,191,255),
                "skyblue"=>array(135,206,235),
                "lightskyblue"=>array(135,206,250),
                "steelblue"=>array(70,130,180),
                "lightred"=>array(211,167,168),
                "lightsteelblue"=>array(176,196,222),
                "lightblue"=>array(173,216,230),
                "powderblue"=>array(176,224,230),
                "paleturquoise"=>array(175,238,238),
                "darkturquoise"=>array(0,206,209),
                "mediumturquoise"=>array(72,209,204),
                "turquoise"=>array(64,224,208),
                "cyan"=>array(0,255,255),
                "lightcyan"=>array(224,255,255),
                "cadetblue"=>array(95,158,160),
                "mediumaquamarine"=>array(102,205,170),
                "aquamarine"=>array(127,255,212),
                "darkgreen"=>array(0,100,0),
                "darkolivegreen"=>array(85,107,47),
                "darkseagreen"=>array(143,188,143),
                "seagreen"=>array(46,139,87),
                "mediumseagreen"=>array(60,179,113),
                "lightseagreen"=>array(32,178,170),
                "palegreen"=>array(152,251,152),
                "springgreen"=>array(0,255,127),
                "lawngreen"=>array(124,252,0),
                "green"=>array(0,255,0),
                "chartreuse"=>array(127,255,0),
                "mediumspringgreen"=>array(0,250,154),
                "greenyellow"=>array(173,255,47),
                "limegreen"=>array(50,205,50),
                "yellowgreen"=>array(154,205,50),
                "forestgreen"=>array(34,139,34),
                "olivedrab"=>array(107,142,35),
                "darkkhaki"=>array(189,183,107),
                "khaki"=>array(240,230,140),
                "palegoldenrod"=>array(238,232,170),
                "lightgoldenrodyellow"=>array(250,250,210),
                "lightyellow"=>array(255,255,200),
                "yellow"=>array(255,255,0),
                "gold"=>array(255,215,0),
                "lightgoldenrod"=>array(238,221,130),
                "goldenrod"=>array(218,165,32),
                "darkgoldenrod"=>array(184,134,11),
                "rosybrown"=>array(188,143,143),
                "indianred"=>array(205,92,92),
                "saddlebrown"=>array(139,69,19),
                "sienna"=>array(160,82,45),
                "peru"=>array(205,133,63),
                "burlywood"=>array(222,184,135),
                "beige"=>array(245,245,220),
                "wheat"=>array(245,222,179),
                "sandybrown"=>array(244,164,96),
                "tan"=>array(210,180,140),
                "chocolate"=>array(210,105,30),
                "firebrick"=>array(178,34,34),
                "brown"=>array(165,42,42),
                "darksalmon"=>array(233,150,122),
                "salmon"=>array(250,128,114),
                "lightsalmon"=>array(255,160,122),
                "orange"=>array(255,165,0),
                "darkorange"=>array(255,140,0),
                "coral"=>array(255,127,80),
                "lightcoral"=>array(240,128,128),
                "tomato"=>array(255,99,71),
                "orangered"=>array(255,69,0),
                "red"=>array(255,0,0),
                "hotpink"=>array(255,105,180),
                "deeppink"=>array(255,20,147),
                "pink"=>array(255,192,203),
                "lightpink"=>array(255,182,193),
                "palevioletred"=>array(219,112,147),
                "maroon"=>array(176,48,96),
                "mediumvioletred"=>array(199,21,133),
                "violetred"=>array(208,32,144),
                "magenta"=>array(255,0,255),
                "violet"=>array(238,130,238),
                "plum"=>array(221,160,221),
                "orchid"=>array(218,112,214),
                "mediumorchid"=>array(186,85,211),
                "darkorchid"=>array(153,50,204),
                "darkviolet"=>array(148,0,211),
                "blueviolet"=>array(138,43,226),
                "purple"=>array(160,32,240),
                "mediumpurple"=>array(147,112,219),
                "thistle"=>array(216,191,216),
                "snow1"=>array(255,250,250),
                "snow2"=>array(238,233,233),
                "snow3"=>array(205,201,201),
                "snow4"=>array(139,137,137),
                "seashell1"=>array(255,245,238),
                "seashell2"=>array(238,229,222),
                "seashell3"=>array(205,197,191),
                "seashell4"=>array(139,134,130),
                "AntiqueWhite1"=>array(255,239,219),
                "AntiqueWhite2"=>array(238,223,204),
                "AntiqueWhite3"=>array(205,192,176),
                "AntiqueWhite4"=>array(139,131,120),
                "bisque1"=>array(255,228,196),
                "bisque2"=>array(238,213,183),
                "bisque3"=>array(205,183,158),
                "bisque4"=>array(139,125,107),
                "peachPuff1"=>array(255,218,185),
                "peachpuff2"=>array(238,203,173),
                "peachpuff3"=>array(205,175,149),
                "peachpuff4"=>array(139,119,101),
                "navajowhite1"=>array(255,222,173),
                "navajowhite2"=>array(238,207,161),
                "navajowhite3"=>array(205,179,139),
                "navajowhite4"=>array(139,121,94),
                "lemonchiffon1"=>array(255,250,205),
                "lemonchiffon2"=>array(238,233,191),
                "lemonchiffon3"=>array(205,201,165),
                "lemonchiffon4"=>array(139,137,112),
                "ivory1"=>array(255,255,240),
                "ivory2"=>array(238,238,224),
                "ivory3"=>array(205,205,193),
                "ivory4"=>array(139,139,131),
                "honeydew"=>array(193,205,193),
                "lavenderblush1"=>array(255,240,245),
                "lavenderblush2"=>array(238,224,229),
                "lavenderblush3"=>array(205,193,197),
                "lavenderblush4"=>array(139,131,134),
                "mistyrose1"=>array(255,228,225),
                "mistyrose2"=>array(238,213,210),
                "mistyrose3"=>array(205,183,181),
                "mistyrose4"=>array(139,125,123),
                "azure1"=>array(240,255,255),
                "azure2"=>array(224,238,238),
                "azure3"=>array(193,205,205),
                "azure4"=>array(131,139,139),
                "slateblue1"=>array(131,111,255),
                "slateblue2"=>array(122,103,238),
                "slateblue3"=>array(105,89,205),
                "slateblue4"=>array(71,60,139),
                "royalblue1"=>array(72,118,255),
                "royalblue2"=>array(67,110,238),
                "royalblue3"=>array(58,95,205),
                "royalblue4"=>array(39,64,139),
                "dodgerblue1"=>array(30,144,255),
                "dodgerblue2"=>array(28,134,238),
                "dodgerblue3"=>array(24,116,205),
                "dodgerblue4"=>array(16,78,139),
                "steelblue1"=>array(99,184,255),
                "steelblue2"=>array(92,172,238),
                "steelblue3"=>array(79,148,205),
                "steelblue4"=>array(54,100,139),
                "deepskyblue1"=>array(0,191,255),
                "deepskyblue2"=>array(0,178,238),
                "deepskyblue3"=>array(0,154,205),
                "deepskyblue4"=>array(0,104,139),
                "skyblue1"=>array(135,206,255),
                "skyblue2"=>array(126,192,238),
                "skyblue3"=>array(108,166,205),
                "skyblue4"=>array(74,112,139),
                "lightskyblue1"=>array(176,226,255),
                "lightskyblue2"=>array(164,211,238),
                "lightskyblue3"=>array(141,182,205),
                "lightskyblue4"=>array(96,123,139),
                "slategray1"=>array(198,226,255),
                "slategray2"=>array(185,211,238),
                "slategray3"=>array(159,182,205),
                "slategray4"=>array(108,123,139),
                "lightsteelblue1"=>array(202,225,255),
                "lightsteelblue2"=>array(188,210,238),
                "lightsteelblue3"=>array(162,181,205),
                "lightsteelblue4"=>array(110,123,139),
                "lightblue1"=>array(191,239,255),
                "lightblue2"=>array(178,223,238),
                "lightblue3"=>array(154,192,205),
                "lightblue4"=>array(104,131,139),
                "lightcyan1"=>array(224,255,255),
                "lightcyan2"=>array(209,238,238),
                "lightcyan3"=>array(180,205,205),
                "lightcyan4"=>array(122,139,139),
                "paleturquoise1"=>array(187,255,255),
                "paleturquoise2"=>array(174,238,238),
                "paleturquoise3"=>array(150,205,205),
                "paleturquoise4"=>array(102,139,139),
                "cadetblue1"=>array(152,245,255),
                "cadetblue2"=>array(142,229,238),
                "cadetblue3"=>array(122,197,205),
                "cadetblue4"=>array(83,134,139),
                "turquoise1"=>array(0,245,255),
                "turquoise2"=>array(0,229,238),
                "turquoise3"=>array(0,197,205),
                "turquoise4"=>array(0,134,139),
                "cyan1"=>array(0,255,255),
                "cyan2"=>array(0,238,238),
                "cyan3"=>array(0,205,205),
                "cyan4"=>array(0,139,139),
                "darkslategray1"=>array(151,255,255),
                "darkslategray2"=>array(141,238,238),
                "darkslategray3"=>array(121,205,205),
                "darkslategray4"=>array(82,139,139),
                "aquamarine1"=>array(127,255,212),
                "aquamarine2"=>array(118,238,198),
                "aquamarine3"=>array(102,205,170),
                "aquamarine4"=>array(69,139,116),
                "darkseagreen1"=>array(193,255,193),
                "darkseagreen2"=>array(180,238,180),
                "darkseagreen3"=>array(155,205,155),
                "darkseagreen4"=>array(105,139,105),
                "seagreen1"=>array(84,255,159),
                "seagreen2"=>array(78,238,148),
                "seagreen3"=>array(67,205,128),
                "seagreen4"=>array(46,139,87),
                "palegreen1"=>array(154,255,154),
                "palegreen2"=>array(144,238,144),
                "palegreen3"=>array(124,205,124),
                "palegreen4"=>array(84,139,84),
                "springgreen1"=>array(0,255,127),
                "springgreen2"=>array(0,238,118),
                "springgreen3"=>array(0,205,102),
                "springgreen4"=>array(0,139,69),
                "chartreuse1"=>array(127,255,0),
                "chartreuse2"=>array(118,238,0),
                "chartreuse3"=>array(102,205,0),
                "chartreuse4"=>array(69,139,0),
                "olivedrab1"=>array(192,255,62),
                "olivedrab2"=>array(179,238,58),
                "olivedrab3"=>array(154,205,50),
                "olivedrab4"=>array(105,139,34),
                "darkolivegreen1"=>array(202,255,112),
                "darkolivegreen2"=>array(188,238,104),
                "darkolivegreen3"=>array(162,205,90),
                "darkolivegreen4"=>array(110,139,61),
                "khaki1"=>array(255,246,143),
                "khaki2"=>array(238,230,133),
                "khaki3"=>array(205,198,115),
                "khaki4"=>array(139,134,78),
                "lightgoldenrod1"=>array(255,236,139),
                "lightgoldenrod2"=>array(238,220,130),
                "lightgoldenrod3"=>array(205,190,112),
                "lightgoldenrod4"=>array(139,129,76),
                "yellow1"=>array(255,255,0),
                "yellow2"=>array(238,238,0),
                "yellow3"=>array(205,205,0),
                "yellow4"=>array(139,139,0),
                "gold1"=>array(255,215,0),
                "gold2"=>array(238,201,0),
                "gold3"=>array(205,173,0),
                "gold4"=>array(139,117,0),
                "goldenrod1"=>array(255,193,37),
                "goldenrod2"=>array(238,180,34),
                "goldenrod3"=>array(205,155,29),
                "goldenrod4"=>array(139,105,20),
                "darkgoldenrod1"=>array(255,185,15),
                "darkgoldenrod2"=>array(238,173,14),
                "darkgoldenrod3"=>array(205,149,12),
                "darkgoldenrod4"=>array(139,101,8),
                "rosybrown1"=>array(255,193,193),
                "rosybrown2"=>array(238,180,180),
                "rosybrown3"=>array(205,155,155),
                "rosybrown4"=>array(139,105,105),
                "indianred1"=>array(255,106,106),
                "indianred2"=>array(238,99,99),
                "indianred3"=>array(205,85,85),
                "indianred4"=>array(139,58,58),
                "sienna1"=>array(255,130,71),
                "sienna2"=>array(238,121,66),
                "sienna3"=>array(205,104,57),
                "sienna4"=>array(139,71,38),
                "burlywood1"=>array(255,211,155),
                "burlywood2"=>array(238,197,145),
                "burlywood3"=>array(205,170,125),
                "burlywood4"=>array(139,115,85),
                "wheat1"=>array(255,231,186),
                "wheat2"=>array(238,216,174),
                "wheat3"=>array(205,186,150),
                "wheat4"=>array(139,126,102),
                "tan1"=>array(255,165,79),
                "tan2"=>array(238,154,73),
                "tan3"=>array(205,133,63),
                "tan4"=>array(139,90,43),
                "chocolate1"=>array(255,127,36),
                "chocolate2"=>array(238,118,33),
                "chocolate3"=>array(205,102,29),
                "chocolate4"=>array(139,69,19),
                "firebrick1"=>array(255,48,48),
                "firebrick2"=>array(238,44,44),
                "firebrick3"=>array(205,38,38),
                "firebrick4"=>array(139,26,26),
                "brown1"=>array(255,64,64),
                "brown2"=>array(238,59,59),
                "brown3"=>array(205,51,51),
                "brown4"=>array(139,35,35),
                "salmon1"=>array(255,140,105),
                "salmon2"=>array(238,130,98),
                "salmon3"=>array(205,112,84),
                "salmon4"=>array(139,76,57),
                "lightsalmon1"=>array(255,160,122),
                "lightsalmon2"=>array(238,149,114),
                "lightsalmon3"=>array(205,129,98),
                "lightsalmon4"=>array(139,87,66),
                "orange1"=>array(255,165,0),
                "orange2"=>array(238,154,0),
                "orange3"=>array(205,133,0),
                "orange4"=>array(139,90,0),
                "darkorange1"=>array(255,127,0),
                "darkorange2"=>array(238,118,0),
                "darkorange3"=>array(205,102,0),
                "darkorange4"=>array(139,69,0),
                "coral1"=>array(255,114,86),
                "coral2"=>array(238,106,80),
                "coral3"=>array(205,91,69),
                "coral4"=>array(139,62,47),
                "tomato1"=>array(255,99,71),
                "tomato2"=>array(238,92,66),
                "tomato3"=>array(205,79,57),
                "tomato4"=>array(139,54,38),
                "orangered1"=>array(255,69,0),
                "orangered2"=>array(238,64,0),
                "orangered3"=>array(205,55,0),
                "orangered4"=>array(139,37,0),
                "deeppink1"=>array(255,20,147),
                "deeppink2"=>array(238,18,137),
                "deeppink3"=>array(205,16,118),
                "deeppink4"=>array(139,10,80),
                "hotpink1"=>array(255,110,180),
                "hotpink2"=>array(238,106,167),
                "hotpink3"=>array(205,96,144),
                "hotpink4"=>array(139,58,98),
                "pink1"=>array(255,181,197),
                "pink2"=>array(238,169,184),
                "pink3"=>array(205,145,158),
                "pink4"=>array(139,99,108),
                "lightpink1"=>array(255,174,185),
                "lightpink2"=>array(238,162,173),
                "lightpink3"=>array(205,140,149),
                "lightpink4"=>array(139,95,101),
                "palevioletred1"=>array(255,130,171),
                "palevioletred2"=>array(238,121,159),
                "palevioletred3"=>array(205,104,137),
                "palevioletred4"=>array(139,71,93),
                "maroon1"=>array(255,52,179),
                "maroon2"=>array(238,48,167),
                "maroon3"=>array(205,41,144),
                "maroon4"=>array(139,28,98),
                "violetred1"=>array(255,62,150),
                "violetred2"=>array(238,58,140),
                "violetred3"=>array(205,50,120),
                "violetred4"=>array(139,34,82),
                "magenta1"=>array(255,0,255),
                "magenta2"=>array(238,0,238),
                "magenta3"=>array(205,0,205),
                "magenta4"=>array(139,0,139),
                "mediumred"=>array(140,34,34),
                "orchid1"=>array(255,131,250),
                "orchid2"=>array(238,122,233),
                "orchid3"=>array(205,105,201),
                "orchid4"=>array(139,71,137),
                "plum1"=>array(255,187,255),
                "plum2"=>array(238,174,238),
                "plum3"=>array(205,150,205),
                "plum4"=>array(139,102,139),
                "mediumorchid1"=>array(224,102,255),
                "mediumorchid2"=>array(209,95,238),
                "mediumorchid3"=>array(180,82,205),
                "mediumorchid4"=>array(122,55,139),
                "darkorchid1"=>array(191,62,255),
                "darkorchid2"=>array(178,58,238),
                "darkorchid3"=>array(154,50,205),
                "darkorchid4"=>array(104,34,139),
                "purple1"=>array(155,48,255),
                "purple2"=>array(145,44,238),
                "purple3"=>array(125,38,205),
                "purple4"=>array(85,26,139),
                "mediumpurple1"=>array(171,130,255),
                "mediumpurple2"=>array(159,121,238),
                "mediumpurple3"=>array(137,104,205),
                "mediumpurple4"=>array(93,71,139),
                "thistle1"=>array(255,225,255),
                "thistle2"=>array(238,210,238),
                "thistle3"=>array(205,181,205),
                "thistle4"=>array(139,123,139),
                "gray1"=>array(10,10,10),
                "gray2"=>array(40,40,30),
                "gray3"=>array(70,70,70),
                "gray4"=>array(100,100,100),
                "gray5"=>array(130,130,130),
                "gray6"=>array(160,160,160),
                "gray7"=>array(190,190,190),
                "gray8"=>array(210,210,210),
                "gray9"=>array(240,240,240),
                "darkgray"=>array(100,100,100),
                "darkblue"=>array(0,0,139),
                "darkcyan"=>array(0,139,139),
                "darkmagenta"=>array(139,0,139),
                "darkred"=>array(139,0,0),
                "silver"=>array(192, 192, 192),
                "eggplant"=>array(144,176,168),
                "lightgreen"=>array(144,238,144));

            $rgb_arr = $rgb_table[$color_name];
            $r = dechex($rgb_arr[0]);
            if (strlen($r) < 2) {
                $r = "0".$r;
            }
            $g = dechex($rgb_arr[1]);
            if (strlen($g) < 2) {
                $g = "0".$g;
            }
            $b = dechex($rgb_arr[2]);
            if (strlen($b) < 2) {
                $b = "0".$b;
            }
            return "#".$r.$g.$b;
        }
    }

    public function isLabFeature() {
        return false;
    }

    function redirect($url) {
       $is_anon = session_hash() ? false : true;
       $fb = $GLOBALS['feedback'] || count($this->_feedback->logs);
       if (($is_anon && (headers_sent() || $fb)) || (!$is_anon && headers_sent())) {
            $this->header(array('title' => 'Redirection'));
            echo '<p>'. $GLOBALS['Language']->getText('global', 'return_to', array($url)) .'</p>';
            echo '<script type="text/javascript">';
            if ($fb) {
                echo 'setTimeout(function() {';
            }
            echo " location.href = '". $url ."';";
            if ($fb) {
                echo '}, 5000);';
            }
            echo '</script>';
            $this->footer(array());
        } else {
            if (!$is_anon && !headers_sent() && $fb) {
                $this->_serializeFeedback();
            }
            // Protect against CRLF injections,
            // This seems to be fixed in php 4.4.2 and 5.1.2 according to
            // http://php.net/header
            if(strpos($url, "\n")) {
                trigger_error('HTTP header injection detected. Abort.', E_USER_ERROR);
            } else {
                header('Location: '. $url);
            }
        }
        exit();
    }

    function iframe($url, $html_options = array()) {
        $html = '';
        $html .= '<div class="iframe_showonly"><a id="link_show_only" href="'. $url .'" title="'.$GLOBALS['Language']->getText('global', 'show_frame') .'">'.$GLOBALS['Language']->getText('global', 'show_frame').' '. $this->getImage('ic/plain-arrow-down.png') .'</a></div>';
        $args = ' src="'. $url .'" ';
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
        $html = '';
        $optgroups = '';
        foreach($items as $i => $item) {
            // don't include the item itself
            if ($item['id'] != $id) {

                // need an optgroup ?
                if (isset($item['subitems'])) {
                    $optgroups .= '<optgroup label="'. $prefix . $item['name'] .'">';

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
                    $optgroups .= '<option value="'. $item['id'] . ':' . 'beginning' .'" '. $selected .'>'. 'At the beginning of '. $prefix . $item['name'] .'</option>';
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
                $html .= '<option value="'. $value_prefix . $value .'" '. $selected .'>';
                $html .= $GLOBALS['Language']->getText('global', 'after', $prefix . $item['name']);
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

    /**
     * Add a Javascript file path that will be included at the end of the HTML page.
     *
     * The file will be included in the generated page just before the </body>
     * markup.
     * Note: the order of call of include*Javascript method is very important.
     * see includeJavascriptFile for more details
     *
     * @see Layout::includeJavascriptFile
     * @param String $file Path (relative to URL root) the the javascript file
     *
     * @return void
     */
    function includeFooterJavascriptFile($file) {
        $this->javascriptFooter[] = array('file' => $file);
    }

    /**
     * Add a Javascript piece of code to execute in the footer of the page.
     *
     * Codendi will append and execute the code just before </body> markup.
     * Note: the order of call of include*Javascript method is very important.
     * see includeJavascriptFile for more details
     *
     * @see Layout::includeJavascriptFile
     * @param String $snippet Javascript code.
     *
     * @return void
     */
    function includeFooterJavascriptSnippet($snippet) {
        $this->javascriptFooter[] = array('snippet' => $snippet);
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
    function addBreadcrumb($step) {
        $this->breadcrumbs[] = $step;
        return $this;
    }

    public function addBreadcrumbs($breadcrumbs) {
        foreach($breadcrumbs as $b) {
            $classname = '';
            if (isset($b['classname'])) {
                $classname = 'class="breadcrumb-step-'. $b['classname'] .'"';
            }
            $this->addBreadcrumb('<a href="'. $b['url'] .'" '. $classname .'>'. $b['title'] .'</a>');
        }
    }

    function getBreadCrumbs() {
        $html = '';
        if (count($this->breadcrumbs)) {
            $html .= '<ul class="breadcrumb"><li>';
            $html .= implode('</li><li><span class="breadcrumb-sep">&raquo;</span>', $this->breadcrumbs);
            $html .= '</ul>';
        }
        return $html;
    }
    function addToolbarItem($item) {
        $this->toolbar[] = $item;
        return $this;
    }
    function getToolbar() {
        $html = '';
        if (count($this->toolbar)) {
            $html .= '<ul class="toolbar"><li>';
            $html .= implode('</li><li><span class="toolbar-sep">|</span>', $this->toolbar);
            $html .= '</li></ul>';
        }
        return $html;
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

    function widget(&$widget, $layout_id, $readonly, $column_id, $is_minimized, $display_preferences, $owner_id, $owner_type) {
        $element_id = 'widget_'. $widget->id .'-'. $widget->getInstanceId();

        if ($is_minimized) {
            echo '<div class="widget minimized" id="'. $element_id .'">';
        } else {
            echo '<div class="widget" id="'. $element_id .'">';
        }

        echo '<div class="widget_titlebar '. ($readonly?'':'widget_titlebar_handle') .'">';
        echo '<div class="widget_titlebar_title">'. $widget->getTitle() .'</div>';

        if (!$readonly) {
            echo '<div class="widget_titlebar_close"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=widget&amp;name['. $widget->id .'][remove]='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'" class="icon-remove" title="'. $GLOBALS['Language']->getText('widget', 'close_title') .'"></a></div>';
            if ($is_minimized) {
                echo '<div class="widget_titlebar_maximize"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=maximize&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'" class="icon-caret-up" title="'. $GLOBALS['Language']->getText('widget', 'maximize_title') .'"></a></div>';
            } else {
                echo '<div class="widget_titlebar_minimize"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=minimize&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;column_id='. $column_id .'&amp;layout_id='. $layout_id .'" class="icon-caret-down" title="'. $GLOBALS['Language']->getText('widget', 'minimize_title') .'"></a></div>';
            }
            if (strlen($widget->getPreferences($owner_id))) {
                echo '<div class="widget_titlebar_prefs"><a href="/widgets/updatelayout.php?owner='. $owner_type.$owner_id .'&amp;action=preferences&amp;name['. $widget->id .']='. $widget->getInstanceId() .'&amp;layout_id='. $layout_id .'" title="'. $GLOBALS['Language']->getText('widget', 'preferences_title') .'" class="icon-cog"></a></div>';
            }
        }
        if ($widget->hasRss()) {
            echo '<div class="widget_titlebar_rss" title="'. $GLOBALS['Language']->getText('widget', 'rss_title') .'"><a href="'.$widget->getRssUrl($owner_id, $owner_type).'" class="icon-rss"></a></div>';
        }
        echo '</div>';
        $style = '';
        if ($is_minimized) {
            $style = 'display:none;';
        }
        echo '<div class="widget_content" style="'. $style .'">';
        if (!$readonly && $display_preferences) {
            echo '<div class="widget_preferences">'. $widget->getPreferencesForm($layout_id, $owner_id, $owner_type) .'</div>';
        }
        if ($widget->isAjax()) {
            echo '<div id="'. $element_id .'-ajax">';
            echo '<noscript><iframe width="99%" frameborder="0" src="'. $widget->getIframeUrl($owner_id, $owner_type) .'"></iframe></noscript>';
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
                                 '". $widget->getAjaxUrl($owner_id, $owner_type) ."',
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
    function _getTogglePlusForWidgets() {
        return 'ic/toggle_plus.png';
    }
    function _getToggleMinusForWidgets() {
        return 'ic/toggle_minus.png';
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
    public function generic_header($params) {
        if (!$this->renderedThroughService && isset($GLOBALS['group_id']) && $GLOBALS['group_id']) {
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
        echo $this->displayJavascriptElements();
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

    /**
     * Display the Javascript code to be included in <head>
     *
     * Snippet and files are included one after another in the order of call
     * of includeJavascriptFile & includeJavascriptSnippet methods.
     *
     * @see includeJavascriptFile
     * @see includeJavascriptSnippet
     */
    public function displayJavascriptElements() {
        $c = new Combined();
        echo $c->getScripts(array('/scripts/codendi/common.js'));

        $ckeditor_path         = '/scripts/ckeditor-4.3.2/';
        $ckeditor_path_for_ie7 = '/scripts/ckeditor-for-ie7/';
        echo '<!--[if IE 7]>
            <script type="text/javascript">window.CKEDITOR_BASEPATH = "'. $ckeditor_path_for_ie7 .'";</script>
            <script type="text/javascript" src="'. $ckeditor_path_for_ie7 .'/ckeditor.js"></script>
            <![endif]-->
            <!--[if ! IE 7]><!-->
            <script type="text/javascript">window.CKEDITOR_BASEPATH = "'. $ckeditor_path .'";</script>
            <script type="text/javascript" src="'. $ckeditor_path .'/ckeditor.js"></script>
            <!--<![endif]-->
        ';

        //Javascript i18n
        echo '<script type="text/javascript">'."\n";
        include $GLOBALS['Language']->getContent('scripts/locale');
        echo '
        codendi.imgroot = \''. $this->imgroot .'\';
        '. $this->changeColorpickerPalette() .'
        </script>';

        if (Config::get('DEBUG_MODE') && (Config::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')) ) {
            echo '<script type="text/javascript" src="/scripts/codendi/debug_reserved_names.js"></script>';
        }

        $em =& EventManager::instance();
        $em->processEvent("javascript_file", null);

        foreach ($this->javascript as $js) {
            if (isset($js['file'])) {
                if (!$c->isCombined($js['file'])) {
                    echo '<script type="text/javascript" src="'. $js['file'] .'"></script>'."\n";
                }
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

    /**
     * Display the Javascript code to be included at the end of the page.
     * Snippet and files are included one after another in the order of call
     * of includeFooterJavascriptFile & includeFooterJavascriptSnippet methods.
     *
     * @see includeFooterJavascriptFile
     * @see includeFooterJavascriptSnippet
     */
    function displayFooterJavascriptElements() {
        foreach ($this->javascriptFooter as $js) {
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

    protected function getFooterSiteJs() {
        ob_start();
        include($GLOBALS['Language']->getContent('layout/footer', null, null, '.js'));
        return ob_get_clean();
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
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-2.3.2.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-responsive-2.3.2.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/style.css" />';
        $this->displayFontAwesomeStylesheetElements();
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/print.css" media="print" />';
        $css = $GLOBALS['sys_user_theme'] . $this->getFontSizeName($GLOBALS['sys_user_font_size']) .'.css';
        if (file_exists($GLOBALS['codendi_dir'].'/src/www'.$this->getStylesheetTheme($css))) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme($css) .'" />';
        }
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('style.css') .'" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('print.css') .'" media="print" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-select/bootstrap-select.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/select2/select2.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/scripts/bootstrap/bootstrap-datetimepicker/css/bootstrap-datetimepicker.min.css" />';
    }

    protected function displayFontAwesomeStylesheetElements() {
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/font-awesome.css" />';
        echo '<!--[if IE 7]><link rel="stylesheet" href="/themes/common/css/font-awesome-ie7.css"><![endif]-->';
    }

    protected function getFontSizeName($p) {
        return getFontsizeName($GLOBALS['sys_user_font_size']);
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
            $hp->purify($GLOBALS['sys_name']. ' - ' .$GLOBALS['Language']->getText('include_layout','newest_releases_rss'), CODENDI_PURIFIER_CONVERT_HTML),
            '/export/rss_sfnewreleases.php'
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
     * @param string $id the id of the input element
     * @param string $name the name of the input element
     * @param array $critria_selector list of extra criterias to be listed in a prepended select
     * @params array $classes extra css classes if needed
     *
     * @return string The calendar picker
     */
    public function getBootstrapDatePicker($id, $name, $value, array $criteria_selector, array $classes) {
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

        $html .= '
            <span class="tuleap_field_date">
                <input name="'. $hp->purify($name, CODENDI_PURIFIER_CONVERT_HTML) .'"
                       id="'. $hp->purify($id, CODENDI_PURIFIER_CONVERT_HTML) .'"
                       data-format="yyyy-MM-dd"
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

        // Codendi version number
        $version = trim(file_get_contents($GLOBALS['codendi_dir'].'/VERSION'));

        echo '<footer class="footer">';
        include($Language->getContent('layout/footer'));
        echo '</footer>';

        if ( Config::get('DEBUG_MODE') && (Config::get('DEBUG_DISPLAY_FOR_ALL') || user_ismember(1, 'A')) ) {
            $this->showDebugInfo();
        }

        echo $this->displayFooterJavascriptElements();
        echo '</body>';
        echo '</html>';
    }

    /**
     * Display debug info gathered along the execution
     *
     * @return void
     */
    public static function showDebugInfo() {
        global $Language;

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
            foreach($GLOBALS['DBSTORE'] as $d) {
                foreach($d['trace'] as $trace) {
                    $query_time += $trace[2] - $trace[1];
                }
            }

            echo '<span class="debug">'.$Language->getText('include_layout','query_count').": ";
            echo $GLOBALS['DEBUG_DAO_QUERY_COUNT'] ."</span>";
            $percent     = (int) ($GLOBALS['DEBUG_TIME_IN_PRE'] * 100 / $debug_compute_tile);
            $sql_percent = (int) ($query_time * 100 / $debug_compute_tile);
            echo '<table border=1><thead><tr><th></th><th>Page generated in</th></tr></thead><tbody>';
            echo '<tr><td>pre.php</td><td>'. number_format(1000 * $GLOBALS['DEBUG_TIME_IN_PRE'], 0, '.', "'") .' ms ('. $percent .'%)</td>';
            echo '<tr><td>remaining</td><td>'. number_format(1000 * ($debug_compute_tile - $GLOBALS['DEBUG_TIME_IN_PRE']), 0, '.', "'") .' ms</td>';
            echo '<tr><td><b>total</td><td><b>'. number_format(1000 * $debug_compute_tile, 0, '.', "'") .' ms</td>';
            if (function_exists('xdebug_time_index')) {
                echo '<tr><td>xdebug</td><td>'. number_format(1000 * $xdebug_time_index, 0, '.', "'") .' ms</tr>';
            }
            echo '<tr><td>sql</td><td>'. number_format(1000 * $query_time, 0, '.', "'") .' ms ('. $sql_percent .'%)</tr>';
            echo '</tbody></table>';
            if (function_exists('xdebug_get_profiler_filename')) {
                if ($file = xdebug_get_profiler_filename()) {
                    echo '<div>Profiler info has been written in: '. $file .'</div>';
                }
            }

            $hook_params = array();
            EventManager::instance()->processEvent('layout_footer_debug', $hook_params);

            //Display the config
            // Uncomment this only if you know what you are doing. This may lead to sensitive information leakage /!\
            //echo '<fieldset><legend id="footer_debug_config" class="'. Toggler::getClassname('footer_debug_config') .'">Config:</legend>';
            //echo '<pre>';
            //Config::dump();
            //echo '</pre>';
            //echo '</fieldset>';

            // Display all queries used to generate the page
            echo '<fieldset><legend id="footer_debug_allqueries" class="'. Toggler::getClassname('footer_debug_allqueries') .'">All queries:</legend>';
            echo '<pre>';
            $queries               = array();
            $queries_by_time_taken = array();
            $i                     = 0;
            foreach($GLOBALS['QUERIES'] as $sql) {
                $t = 0;
                foreach($GLOBALS['DBSTORE'][md5($sql)]['trace'] as $trace) {
                    $t += $trace[2] - $trace[1];
                }
                $q = array(
                    'sql' => $sql,
                    'total time' => number_format(1000 * $t, 0, '.', "'") .' ms',
                );
                $queries[] = $q;
                $queries_by_time_taken[] = array('n°' => $i++, 't' => $t) + $q;

            }
            print_r($queries);
            echo '</pre>';
            echo '</fieldset>';

            // Display all queries used to generate the page ordered by time taken
            usort($queries_by_time_taken, array(__CLASS__, 'sort_queries_by_time_taken'));
            echo '<fieldset><legend id="footer_debug_allqueries_time_taken" class="'. Toggler::getClassname('footer_debug_allqueries_time_taken') .'">All queries by time taken:</legend>';
            echo '<table border="1" style="border-collapse:collapse" cellpadding="2" cellspacing="0">';
            echo '<thead><tr><th>n°</th><th style="white-space:nowrap;">time taken</th><th>sum</th><th>sql</th></tr></thead>';
            $i   = 0;
            $sum = 0;
            foreach($queries_by_time_taken as $q) {
                echo '<tr valign="top" class="'. html_get_alt_row_color($i++) .'">';
                echo '<td>'. $q['n°'] .'</td>';
                echo '<td style="white-space:nowrap;">'. $q['total time'] .'</td>';
                echo '<td style="white-space:nowrap;">'. number_format(1000 * ($sum += $q['t']), 0, '.', "'") .' ms' .'</td>';
                echo '<td><pre>'. $q['sql'] .'</pre></td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</fieldset>';

            echo '<fieldset><legend id="footer_debug_queriespaths" class="'. Toggler::getClassname('footer_dubug_queriespaths') .'">Path of all queries:</legend>';
            $max = 0;
            foreach($GLOBALS['DBSTORE'] as $d) {
                foreach($d['trace'] as $trace) {
                    $time_taken = 1000 * round($trace[2] - $trace[1], 3);
                    if ($max < $time_taken) {
                        $max = $time_taken;

                    }
                }
            }

            $paths = array();
            $time = $GLOBALS['debug_time_start'];
            foreach($GLOBALS['DBSTORE'] as $d) {
                foreach($d['trace'] as $trace) {
                    $time_taken = 1000 * round($trace[2] - $trace[1], 3);
                    self::_debug_backtrace_rec($paths, array_reverse($trace[0]),
                        '['. (1000*round($trace[1] - $GLOBALS['debug_time_start'], 3))
                        .'/'. $time_taken .'] '.
                        ($time_taken >= $max ? ' <span style="background:yellow; padding-left:4px; padding-right:4px; color:red;">top!</span> ' : '') . $d['sql']);
                }
            }
            echo '<table>';
            self::_debug_display_paths($paths, false);
            echo '</table>';
            echo '</fieldset>';
            //Print the backtrace of specific queries
            /*
            echo '<pre>';
            $specific_queries = array(48,49);
            $i = 0;
            foreach($GLOBALS['DBSTORE'] as $d) {
                //echo $i ."\t". $d['sql'] ."\n";
                if (in_array($i++, $specific_queries)) {
                    $traces = $d['trace'][0];
                    foreach($traces as $trace) {
                        echo '<code>'. $trace['file']. ' #'. $trace['line'] .' ('. (isset($trace['class']) ? $trace['class'] .'::' : '') . $trace['function'] ."</code>\n";
                    }
                    echo "\n";
                }
            }
            echo '</pre>';
            /**/

            // Display queries executed more than once
            $title_displayed = false;
            foreach ($GLOBALS['DBSTORE'] as $key => $value) {
                if ($GLOBALS['DBSTORE'][$key]['nb'] > 1) {
                    if (!$title_displayed) {
                        echo '<fieldset><legend>Queries executed more than once :</legend>';
                        $title_displayed = true;
                    }
                    echo "<fieldset>";
                    echo '<legend id="footer_debug_doublequery_'. $key .'" class="'. Toggler::getClassname('footer_debug_doublequery_'. $key) .'">';
                    echo '<b>Run '.$GLOBALS['DBSTORE'][$key]['nb']." times: </b>";
                    echo $GLOBALS['DBSTORE'][$key]['sql']."\n";
                    echo '</legend>';
                    self::_debug_backtraces($GLOBALS['DBSTORE'][$key]['trace']);
                    echo "</fieldset>";
                }
            }
            if ($title_displayed) {
                echo '</fieldset>';
            }
            echo '<fieldset>';
            echo '<legend id="footer_debug_session" class="'. Toggler::getClassname('footer_debug_session') .'">Session</legend>';
            echo "<div>";
            echo '<a href="#" onclick="new Ajax.Updater(\'footer_debug_session_data\', \'/include/debug_session.php?reload\');return false;">reload</a>';
            echo '  |  ';
            echo '<a href="#" onclick="new Ajax.Updater(\'footer_debug_session_data\', \'/include/debug_session.php?reset\');return false;">reset</a>';
            echo '<pre id="footer_debug_session_data">'.print_r($_SESSION, 1).'</pre>';
            echo "</div>";
            echo '</fieldset>';
            echo "</pre>\n";
            echo '</div>';
        echo '</div>';
    }

    private static function sort_queries_by_time_taken($a, $b) {
        return strnatcasecmp($b['total time'], $a['total time']);
    }

    public static function _debug_backtraces($backtraces) {
        $paths = array();
        $i = 1;
        foreach($backtraces as $b) {
            self::_debug_backtrace_rec($paths, array_reverse($b[0]), ('#' . $i++));
        }
        echo '<table>';
        self::_debug_display_paths($paths);
        echo '</table>';
    }

    public static function _debug_backtrace_rec(&$paths, $trace, $leaf = '') {
        if (count($trace)) {
            $file = '';
            if (isset($trace[0]['file'])) {
                $file = substr($trace[0]['file'], strlen($GLOBALS['codendi_dir'])) .' #'. $trace[0]['line'];
            }
            $file .= ' ('. (isset($trace[0]['class']) ? $trace[0]['class'] .'::' : '') . $trace[0]['function'] .')';
            if (strpos($file, '/src/common/dao/include/DataAccessObject.class.php') === 0) {
                self::_debug_backtrace_rec($paths, array_slice($trace, 1), $leaf);
            } else {
                self::_debug_backtrace_rec($paths[$file], array_slice($trace, 1), $leaf);
            }
        } else if ($leaf) {
            $paths[] = $leaf;
        }
    }

    public static function _debug_display_paths($paths, $red = true, $padding = 0) {
        if (is_array($paths)) {
            $color = "black";
            if ($red && count($paths) > 1) {
                $color = "red";
            }
            foreach($paths as $p => $next) {
                if (is_numeric($p)) {
                    echo '<tr style="color:green">';
                    echo '<td></td>';
                    echo '<td>'. $next .'</td>';
                    echo '</tr>';
                } else {
                    echo '<tr style="color:'. $color .'">';
                    echo '<td style="padding-left:'. $padding .'px;">';
                    echo substr($p, 0, strpos($p, ' '));
                    echo '</td>';
                    echo '<td>';
                    echo substr($p, strpos($p, ' '));
                    echo '</td>';
                    echo '</tr>';
                }
                self::_debug_display_paths($next, $red, $padding+20);
            }
        }
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
        echo $this->displayJavascriptElements();
        echo $this->displayStylesheetElements(array());
        echo $this->displaySyndicationElements();
        echo '    </head>
                     <body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" marginwidth="0" marginheight="0">
                       <div class="main_body_row">
                           <div class="contenttable">';
        echo $this->getNotificationPlaceholder();
    }

    function header($params) {
        global $Language;
        $this->generic_header($params);

        //themable someday?
        $site_fonts='verdana,arial,helvetica,sans-serif';

        echo '<body leftmargin="0" rightmargin="0" topmargin="0" bottommargin="0" marginwidth="0" marginheight="0" class="'. $this->getClassnamesForBodyTag($params) .'">';

        echo $this->getOsdnNavBar();

        echo html_blankimage(5,100);
?>
<br>
<!-- start page body -->
<div align="center">
<table cellpadding="0" cellspacing="0" border="0" width="97%">

<!-- First line with borders and corners -->
           <tr>
               <td background="<? echo util_get_image_theme("upper_left_corner.png"); ?>" width="1%" height="26"><img src="<? echo util_get_image_theme("upper_left_corner.png"); ?>" width="16" height="26" alt=" "></td>
                <td background="<? echo util_get_image_theme("top_border.png"); ?>" align="left" colspan="3" width="99%"><a href="/"><img src="<? echo util_get_image_theme("codex_banner_lc.png"); ?>"  height="26" border="0" alt="<?php echo $GLOBALS['sys_name'].' '.$Language->getText('include_layout','banner'); ?>"></a></td>
                <td><img src="<? echo util_get_image_theme("upper_right_corner.png"); ?>" width="16" height="26" alt=" "></td>
        </tr>


<!-- Second line with menus and content -->
        <tr>

                <td background="<? echo util_get_image_theme("left_border.png"); ?>" align="left" valign="bottom" alt=""><img src="<? echo util_get_image_theme("bottom_left_corner.png"); ?>" width="16" height="16" alt=""></td>

                <td colspan="3" >
<!-- start main body cell -->


        <div align="left">
        <table style=menus cellpadding="0" cellspacing="0" border="0" width="100%">

                <tr valign="top">
                    <td class="menuframe">

        <!-- VA Linux Stats Counter -->
        <?php
        if (!session_issecure()) {
                print '<IMG src="'.util_get_image_theme("clear.png").'" width=140 height=1 alt="'.$Language->getText('include_layout','counter').'"><BR>';
        } else {
                print html_blankimage(1,140) . '<br>';
        }

        $main_body_class = '';
        if (isset($params['toptab']) && is_string($params['toptab'])) {
            $main_body_class = 'service-' . $params['toptab'];
        }
        ?>


        <!-- Company Logo here -->
        <P>
    <?php
    print '<center><IMG src="'.util_get_image_theme("organization_logo.png").'" alt="'.$GLOBALS['sys_org_name'].' '.$Language->getText('include_layout','logo').'"></center><BR>';
    ?>

        <!-- menus -->
        <?php
        // html_blankimage(1,140);
        menu_print_sidebar($params);
        ?>
        <P>
        </TD>

        <td width="15" background="<? echo util_get_image_theme("fade.png"); ?>" nowrap>&nbsp;</td>

        <td class="contenttable <?=$main_body_class?>">
        <BR>
<?php
        if (isset($params['group']) && $params['group']) {
            echo $this->project_tabs($params['toptab'],$params['group']);
        } else if (strstr(getStringFromServer('REQUEST_URI'),'/my/') ||
                   strstr(getStringFromServer('REQUEST_URI'),'/account/')) {
            $tabs = array(
                array(
                    'link'  => '/my/',
                    'label' => $Language->getText('my_index','my_dashboard')
                ),
                array(
                    'link'  => '/account/',
                    'label' => $Language->getText('my_index','account_maintenance'),
                ),
                array(
                    'link'  => '/account/preferences.php',
                    'label' => $Language->getText('account_options','preferences')
                ),
            );
            echo '<hr SIZE="1" NoShade>';
            foreach($tabs as $tab) {
                $this->tab_entry($tab['link'],'',$tab['label'],strstr(getStringFromServer('REQUEST_URI'),'/my/') ? 0 :
                      (strstr(getStringFromServer('REQUEST_URI'),'/account/preferences.php') ? 2 : 1),'');
            }
            echo '<hr SIZE="1" NoShade>';
        }
        echo $this->getBreadCrumbs();
        echo $this->getToolbar();
        echo $this->_getFeedback();
        $this->_feedback->display();
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

    function footer($params) {
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



    function menuhtml_top($title) {
            /*
                    Use only for the top most menu
            */
        ?>
<table class="menutable">
        <tr>
                <td class="menutitle"><?php echo $title; ?><br></td>
        </tr>
        <tr>
                <td class="menuitem">
        <?php
    }


    function menuhtml_bottom() {
            /*
                    End the table
            */
            print '
                        <BR>
                        </td>
                </tr>
        </table>
';
    }

    function menu_entry($link, $title) {
            print "\t".'<A class="menus" href="'.$link.'">'.$title.'</A> &nbsp;<img src="'.util_get_image_theme("point1.png").'" alt=" " width="7" height="7"><br>';
    }

        /*!     @function tab_entry
                @abstract Prints out the a themed tab, used by project_tabs
                @param  $url is the URL to link to
            $icon is the image to use (if the theme uses it) NOT USED
            $title is the title to use in the link tags
            $selected is a boolean to test if the tab is 'selected'
                @result text - echos HTML to the screen directly
        */
    function tab_entry($url, $icon='', $title='Home', $selected=0, $description=null) {
            print '
                <A ';
            if ($selected){
                    print 'class=tabselect ';
            } else {
                    print 'class=tabs ';
            }
                if (substr($url, 0, 1)!="/") {
                    // Absolute link -> open new window on click
                    print "target=_blank ";
                }
                if ($description) {
                    print "title=\"$description\" ";
                }
            print 'href="'. $url .'">' . $title . '</A>&nbsp;|&nbsp;';
    }

    /*!     @function project_tabs
            @abstract Prints out the project tabs, contained here in case
            we want to allow it to be overriden
            @param     $toptab is the tab currently selected ('short_name' of the service)
            $group is the group we should look up get title info
            @result text - echos HTML to the screen directly
    */
    function project_tabs($toptab,$group_id) {

      global $sys_default_domain,$Language;

            // get group info using the common result set
            $pm = ProjectManager::instance();
            $project=$pm->getProject($group_id);
            if ($project->isError()) {
                //wasn't found or some other problem
                return;
            }

            print '<H2>'. $project->getPublicName() .' - ';

            if (isset($project->service_data_array[$toptab])) {
                echo $project->service_data_array[$toptab]['label'];
            }
            print '</H2>';

        print '
        <P>
    <HR SIZE="1" NoShade>';
            $tabs = $this->_getProjectTabs($toptab, $project);
            foreach($tabs as $tab) {
                $this->tab_entry($tab['link'],$tab['icon'],$tab['label'],$tab['enabled'],$tab['description']);
            }

            print '<HR SIZE="1" NoShade><P>';
    }

    function _getProjectTabs($toptab,&$project) {
        global $sys_default_domain;
        $pm = ProjectManager::instance();
        $tabs = array();
        $group_id = $project->getGroupId();

        reset($project->service_data_array);
         while (list($short_name,$service_data) = each($project->service_data_array)) {
               if ((string)$short_name == "admin") {
                // for the admin service, we will check if the user is allowed to use the service
                // it means : 1) to be a super user, or
                //            2) to be project admin
                if (!user_is_super_user()) {
                    if (!user_isloggedin()) {
                        continue;   // we don't include the service in the $tabs
                    } else {
                        if (!user_ismember($group_id, 'A')) {
                            continue;   // we don't include the service in the $tabs
                        }
                    }
                }
            }

            if (!$service_data['is_used']) continue;
            if (!$service_data['is_active']) continue;
            // Get URL, and eval variables
            //$project->services[$short_name]->getUrl(); <- to use when service will be fully served by satellite
            if ($service_data['is_in_iframe']) {
                $link = '/service/?group_id='. $group_id .'&amp;id='. $service_data['service_id'];
            } else {
                $link = $service_data['link'];
            }
            if ($group_id==100) {
                if (strstr($link,'$projectname')) {
                    // NOTE: if you change link variables here, change them also in src/common/project/RegisterProjectStep_Confirmation.class.php and src/www/project/admin/servicebar.php
                    // Don't check project name if not needed.
                    // When it is done here, the service bar will not appear updated on the current page
                    $link=str_replace('$projectname',$pm->getProject($group_id)->getUnixName(),$link);
                }
                $link=str_replace('$sys_default_domain',$GLOBALS['sys_default_domain'],$link);
                if ($GLOBALS['sys_force_ssl']) {
                    $sys_default_protocol='https';
                } else { $sys_default_protocol='http'; }
                $link=str_replace('$sys_default_protocol',$sys_default_protocol,$link);
                $link=str_replace('$group_id',$group_id,$link);
            }
            $enabled = (is_numeric($toptab) && $toptab == $service_data['service_id']) || ($short_name && ($toptab == $short_name));
            $hp =& Codendi_HTMLPurifier::instance();
            if ($short_name == 'summary') {

                $label = '<span>';
                if (Config::get('sys_display_project_privacy_in_service_bar')) {
                    // Add a default tab to explain project privacy
                    if ($project->isPublic()) {
                        $privacy = 'public';
                    } else {
                        $privacy = 'private';
                    }
                    $label .= '<span class="project_privacy_'.$privacy.'">[';
                    $label .= $GLOBALS['Language']->getText('project_privacy', $privacy);
                    $label .= ']</span>';

                    // Javascript for project privacy tooltip
                    $js = "
                    document.observe('dom:loaded', function() {
                        $$('span[class=project_privacy_private], span[class=project_privacy_public]').each(function (span) {
                            var type = span.className.substring('project_privacy_'.length, span.className.length);
                            codendi.Tooltips.push(new codendi.Tooltip(span, '/project/privacy.php?project_type='+type));
                        });
                    });
                    ";
                    $this->includeFooterJavascriptSnippet($js);

                    $label .= '&nbsp;';
                }
                $label .= $hp->purify(util_unconvert_htmlspecialchars($project->getPublicName()), CODENDI_PURIFIER_CONVERT_HTML).'&nbsp;&raquo;</span>';
            } else {
                $label  = '<span title="'.$hp->purify($service_data['description']).'">';
                $label .= $hp->purify($service_data['label']).'</span>';
            }

            $name = $hp->purify($service_data['label']);

            $icon = $this->getServiceIcon($short_name);
            if (isset($service_data['icon'])) {
                $icon = $service_data['icon'];
            }
            $tabs[] = array('link'        => $link,
                            'icon'        => $icon,
                            'name'        => $name,
                            'label'       => $label,
                            'enabled'     => $enabled,
                            'description' => $hp->purify($service_data['description']));
        }
        return $tabs;
    }

    private function getServiceIcon($service_name) {
        return self::DEFAULT_SERVICE_ICON . ' tuleap-services-' . $service_name;
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

        if (Config::get('sys_use_trove')) {
            $search_entries[] = array(
                'value' => 'soft',
                'label' => $GLOBALS['Language']->getText('include_menu', 'software_proj')
            );
        }

        if (Config::get('sys_use_snippet')) {
            $search_entries[] = array(
                'value'    => 'snippets',
                'label'    => $GLOBALS['Language']->getText('include_menu', 'code_snippets'),
                'selected' => strpos($_SERVER['REQUEST_URI'], '/snippet/') === 0
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

        return array($search_entries, $hidden);
    }

    private function forceSelectedOption(array $search_entries) {
        foreach ($search_entries as $key => $search_entry) {
            if (! isset($search_entry['selected'])) {
                $search_entries[$key]['selected'] = false;
            }
        }

        return $search_entries;
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

        list($search_entries, $hidden_fields) = $this->getSearchEntries();

        $output = '
                <form action="/search/" method="post"><table style="text-align:left;float:right"><tr style="vertical-align:top;"><td>
        ';
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

    //diplaying search box in body
    function bodySearchBox() {
        $this->searchBox();
    }


    /**
     * @return string the message of the day
     */
    protected function getMOTD() {
        $motd = $GLOBALS['Language']->getContent('others/motd');
        if (! strpos($motd, "empty.txt")) { # empty.txt returned when no motd file found
            ob_start();
            include($motd);
            return ob_get_clean();
        }
    }

    protected function getBrowserDeprecatedMessage() {
        return HTTPRequest::instance()->getBrowser()->getDeprecatedMessage();
    }

    function getOsdnNavBar() {
        $output = '
        <!-- OSDN navbar -->
        <div class="osdnnavbar">
        ';

        echo $this->getBrowserDeprecatedMessage();
        $motd = $this->getMOTD();
        if ($motd) {
            echo $motd;
        } else {
            // MN : Before displaying the osdn nav drop down, we verify that the osdn_sites array exists
            include($GLOBALS['Language']->getContent('layout/osdn_sites'));
            if (isset($osdn_sites)) {
                $output .= '<span class="osdn">'.$GLOBALS['Language']->getText('include_layout','network_gallery').'&nbsp;:&nbsp;';
                // if less than 5 sites are defined, we only display the min number
                $output .= $this->_getOsdnRandpick($osdn_sites, min(5, count($osdn_sites)));
                $output .= '</span>';
            }
        }

        $output .= '</div>
        <!-- End OSDN NavBar -->
        ';
        return $output;
    }

    function _getOsdnRandpick($sitear, $num_sites = 1) {
        $output = '';
        shuffle($sitear);
        reset($sitear);
        $i = 0;
        while ( ( $i < $num_sites ) && (list($key,$val) = each($sitear)) ) {
            list($key,$val) = each($val);
            $output .= "&nbsp;&middot;&nbsp;<a href='$val' class='osdntext'>$key</a>\n";
            $i++;
        }
        $output .= '&nbsp;&middot;&nbsp;';
        return $output;
    }

    function getOsdnNavDropdown() {
        $output = '
        <!-- OSDN navdropdown -->
        <script type="text/javascript">
        function handle_navbar(index,form) {
            if ( index > 1 ) {
                window.location=form.options[index].value;
            }
        }
        </script>';
        $output .= '<a href="'.get_server_url().'" class="osdn_codendi_logo">';
        $output .= $this->getImage("codendi_logo.png", array("alt"=>$GLOBALS['sys_default_domain'], "border"=>"0"));
        $output .= '<br /></a>';
        // MN : Before displaying the osdn nav drop down, we verify that the osdn_sites array exists
        include($GLOBALS['Language']->getContent('layout/osdn_sites'));
        if (isset($osdn_sites)) {
            $output .= '<form name="form1"><div>';
            $output .= '<select name="navbar" onChange="handle_navbar(selectedIndex,this)">';
            $output .= '   <option>------------</option>';
            reset ($osdn_sites);
            while (list ($key, $val) = each ($osdn_sites)) {
                list ($key, $val) = each ($val);
                $output .= '   <option value="'.$val.'">'.$key.'</option>';
            }
            $output .= '</select>';
            $output .= '</div></form>';
        }
        $output .= '<!-- end OSDN navdropdown -->';

        return $output;
    }

    public function getImagePath($src) {
        return $this->imgroot . $src;
    }

    /**
     * Build an img tag
     *
     * @param string $src The src of the image "trash.png"
     * @param array $args The optionnal arguments for the tag ['alt' => 'Beautiful image']
     * @return string <img src="/themes/Tuleap/images/trash.png" alt="Beautiful image" />
     */
    function getImage($src, $args = array()) {
        return $this->getAbsoluteImage($this->getImagePath($src), $args);
    }

    /**
     * Same as getImage but with absolute path to the image.
     * Usefull for plugin related image for example
     *
     * @see getImage
     */
    function getAbsoluteImage($src, $args = array()) {
        $return = '<img src="'. $src .'"';
        foreach($args as $k => $v) {
            $return .= ' '.$k.'="'.$v.'"';
        }

        // insert a border tag if there isn't one
        if (!isset($args['border']) || !$args['border']) $return .= ' border="0"';

        // insert alt tag if there isn't one
        if (!isset($args['alt']) || !$args['alt']) $return .= ' alt="'. $src .'"';

        $return .= ' />';
        return $return;
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
     * Set to true if HTML object is displayed through a Service
     *
     * @see Service
     *
     * @param Boolean $value
     */
    function setRenderedThroughService($value) {
        $this->renderedThroughService = $value;
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
}
?>
