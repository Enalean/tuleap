<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
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
 * 
 */
require_once 'DocmanWatermark_Controller.class.php';
require_once 'DocmanWatermark_Actions.class.php';

class DocmanWatermark_HTTPController extends DocmanWatermark_Controller {

    public function __construct($plugin, $docmanPath,$pluginPath, $themePath) {
        parent::__construct($plugin, $docmanPath,$pluginPath, $themePath, HTTPRequest::instance());
    }

    function _includeView() {
        $className = 'DocmanWatermark_View_'. $this->view;
        if(file_exists(dirname(__FILE__).'/view/'. $className .'.class.php')) {
            require_once('view/'. $className .'.class.php');
            return $className;
        }
        return false;
    }
        
}

?>
