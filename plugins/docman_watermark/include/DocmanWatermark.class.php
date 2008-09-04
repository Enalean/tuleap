<?php
/**
 * Copyright (c) STMicroelectronics, 2008. All Rights Reserved.
 *
 * Originally written by Mahmoud MAALEJ, 2008
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * 
 */
require_once('DocmanWatermarkController.class.php');
require_once('DocmanWatermarkActions.class.php');
class DocmanWatermark extends DocmanWatermarkController {

    public function __construct($plugin, $docmanPath,$pluginPath, $themePath) {
        parent::__construct($plugin, $docmanPath,$pluginPath, $themePath, HTTPRequest::instance());
    }


    function _checkBrowserCompliance() {
        if($this->request->browserIsNetscape4()) {
            $this->feedback->log('warning', $GLOBALS['Language']->getText('plugin_docmanwatermark', 'docmanwatermark_browserns4'));
        }
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
