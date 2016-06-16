<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

use Widget_Static;
use Response;

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

    public function __construct($root)
    {
        parent::__construct();
        $this->root     = $root;
        $this->imgroot  = $root . '/images/';
    }

    abstract public function header(array $params);
    abstract public function footer(array $params);
    abstract public function displayStaticWidget(Widget_Static $widget);
    abstract public function isLabFeature();

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

        $return = '<img src="'. $src .'"';
        foreach ($args as $k => $v) {
            $return .= ' '.$k.'="'.$v.'"';
        }

        // insert a border tag if there isn't one
        if (! isset($args['border']) || ! $args['border']) {
            $return .= ' border="0"';
        }

        // insert alt tag if there isn't one
        if (! isset($args['alt']) || ! $args['alt']) {
            $return .= ' alt="'. $src .'"';
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
        $this->javascript_in_footer[] = array('file' => $file);
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

    public function redirect($url)
    {
        $is_anon = session_hash() ? false : true;
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
}
