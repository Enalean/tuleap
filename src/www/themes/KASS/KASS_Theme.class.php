<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

require_once 'common/layout/DivBasedTabbedLayout.class.php';

class KASS_Theme extends DivBasedTabbedLayout {

    function __construct($root) {
        parent::__construct($root);
    }

    public function isLabFeature() {
        return true;
    }

    public function header($params) {
        $htmlclassname = '';
        if (isset($params['htmlclassname'])) {
            $htmlclassname = $params['htmlclassname'];
        }
        echo '<!DOCTYPE html>
        <html lang="en" class="'. $htmlclassname .'">';
        echo $this->head($params);
        echo $this->body($params);
    }

    private function head($params) {
        $title = $this->getHtmlTitleFromParams($params);
        $html  = '';
        $html .= '<head>
            <meta charset="utf-8">
            <title>'. $title .'</title>
            <link rel="SHORTCUT ICON" href="'. $this->imgroot . 'favicon.ico' .'">';
        $html .=  $this->displayJavascriptElements();
        $html .=  $this->displayStylesheetElements($params);
        $html .=  $this->displaySyndicationElements();
        $html .=  '</head>';
        return $html;
    }

    private function getHtmlTitleFromParams($params) {
        $title = $GLOBALS['sys_name'];
        if (!empty($params['title'])) {
           $title = $params['title'] .' - '. $title;
        }
        return $title;
    }

    protected function displayCommonStylesheetElements($params) {
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-2.3.2.min.css" />';
        echo '<link rel="stylesheet" type="text/css" href="/themes/common/css/bootstrap-responsive-2.3.2.min.css" />';
        $this->displayFontAwesomeStylesheetElements();
        $css = $GLOBALS['sys_user_theme'] . $this->getFontSizeName($GLOBALS['sys_user_font_size']) .'.css';
        if (file_exists($GLOBALS['codendi_dir'].'/src/www'.$this->getStylesheetTheme($css))) {
            echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme($css) .'" />';
        }
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('style.css') .'" />';
        echo '<link rel="stylesheet" type="text/css" href="'. $this->getStylesheetTheme('print.css') .'" media="print" />';
    }

}

?>
